<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DataLayer integration class — collects ecommerce data server-side
 * and passes it to the frontend JS via wp_localize_script.
 *
 * @package    ExpertSender_CDP
 * @subpackage ExpertSender_CDP/includes
 */
class ExpertSender_CDP_DataLayer {

    /** @var array Accumulated data passed to ecdpDataLayer JS object. */
    private $datalayer_data = array();

    public function __construct() {
        if ( ! get_option( 'expertsender_cdp_datalayer_enabled', '1' ) ) {
            return;
        }
        add_action( 'wp_enqueue_scripts', array( $this, 'localize_datalayer_data' ), 15 );
        add_action( 'woocommerce_thankyou', array( $this, 'localize_purchase_data' ), 10 );
        add_action( 'wp_footer', array( $this, 'output_localized_data' ), 5 );
        add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'add_product_data_to_remove_link' ), 10, 2 );
        add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'embed_product_data_in_listing' ), 10 );
        add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'embed_product_data_in_add_to_cart_form' ), 10 );
        // Capture add_to_cart server-side; only for non-AJAX (form submit = page reload).
        add_action( 'woocommerce_add_to_cart', array( $this, 'capture_add_to_cart_event' ), 10, 6 );
        // AJAX endpoint: returns current cart state for JS diff after wc_fragments_refreshed.
        add_action( 'wp_ajax_ecdp_get_cart',        array( $this, 'ajax_get_cart' ) );
        add_action( 'wp_ajax_nopriv_ecdp_get_cart', array( $this, 'ajax_get_cart' ) );
    }

    /**
     * Collects page-level ecommerce data and stores it for later output.
     * Runs at wp_enqueue_scripts priority 15 (after script registration at 10).
     */
    public function localize_datalayer_data() {
        if ( ! function_exists( 'WC' ) ) {
            return;
        }

        $currency = get_woocommerce_currency();

        $cart_state_data = null;
        if ( WC()->cart ) {
            $cart_state_data = $this->build_cart_state_data();
        }

        $view_item_data = null;
        if ( is_product() ) {
            $product = wc_get_product( get_the_ID() );
            if ( $product ) {
                $view_item_data = $this->build_product_data( $product );
            }
        }

        $view_cart_data = null;
        if ( is_cart() && WC()->cart ) {
            $view_cart_data = $this->build_view_cart_data();
        }

        $user_data = null;
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $email        = $current_user->user_email;
            $user_data    = array(
                'email'     => $email,
                'email_md5' => md5( strtolower( trim( $email ) ) ),
                'user_id'   => (string) get_current_user_id(),
            );
        }

        // Read pending add_to_cart stored by capture_add_to_cart_event on previous request.
        $pending_add_to_cart = null;
        if ( WC()->session ) {
            $pending_add_to_cart = WC()->session->get( 'ecdp_pending_add_to_cart' );
            if ( $pending_add_to_cart ) {
                WC()->session->set( 'ecdp_pending_add_to_cart', null );
                $this->log( 'localize_datalayer_data: found pendingAddToCart in session: ' . wp_json_encode( $pending_add_to_cart ) );
            } else {
                $this->log( 'localize_datalayer_data: no pendingAddToCart in session (url=' . ( $_SERVER['REQUEST_URI'] ?? '' ) . ')' );
            }
        } else {
            $this->log( 'localize_datalayer_data: WC()->session not available' );
        }

        $event_prefix = get_option( 'expertsender_cdp_datalayer_event_prefix', '1' );
        $this->log( 'localize_datalayer_data: eventPrefix option raw=' . var_export( $event_prefix, true ) );

        $this->datalayer_data = array(
            'currency'        => $currency,
            'eventPrefix'     => $event_prefix ? '_ecdp_' : '',
            'ajaxUrl'         => esc_url( admin_url( 'admin-ajax.php' ) ),
            'ajaxNonce'       => wp_create_nonce( 'ecdp_get_cart' ),
            'debug'           => (bool) get_option( 'expertsender_cdp_datalayer_debug_js' ),
            'viewItem'        => $view_item_data,
            'viewCart'        => $view_cart_data,
            'cartState'       => $cart_state_data,
            'userData'        => $user_data,
            'pendingAddToCart' => $pending_add_to_cart,
            'purchaseData'    => null,
        );
    }

    /**
     * Collects purchase data on the thank-you page and marks order as tracked.
     *
     * @param int $order_id
     */
    public function localize_purchase_data( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Anti-duplicate: skip if already tracked on a previous page load.
        if ( '1' === get_post_meta( $order_id, '_ecdp_purchase_tracked', true ) ) {
            return;
        }

        $items = array();
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }
            $items[] = $this->build_product_data( $product, $item->get_quantity() );
        }

        $this->datalayer_data['purchaseData'] = array(
            'order_id' => (string) $order_id,
            'total'    => round( (float) $order->get_total(), 2 ),
            'items'    => $items,
        );

        update_post_meta( $order_id, '_ecdp_purchase_tracked', '1' );
    }

    /**
     * Outputs all accumulated data via a single wp_localize_script call.
     * Runs at wp_footer priority 5, before scripts are printed at priority 20.
     */
    public function output_localized_data() {
        if ( empty( $this->datalayer_data ) ) {
            return;
        }
        wp_localize_script( 'expertsender-cdp-datalayer', 'ecdpDataLayer', $this->datalayer_data );
    }

    /**
     * Injects product data as a data attribute on the cart remove link,
     * so the JS remove_from_cart handler can read it without an extra request.
     *
     * @param string $link          HTML anchor tag.
     * @param string $cart_item_key Cart item key.
     * @return string Modified HTML.
     */
    public function add_product_data_to_remove_link( $link, $cart_item_key ) {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return $link;
        }

        $cart_contents = WC()->cart->get_cart_contents();
        $cart_item     = $cart_contents[ $cart_item_key ] ?? null;
        if ( ! $cart_item ) {
            return $link;
        }

        $product = $cart_item['data'] ?? null;
        if ( ! $product instanceof WC_Product ) {
            return $link;
        }

        $data      = $this->build_product_data( $product, $cart_item['quantity'] );
        $data_attr = 'data-ecdp_product_data="' . esc_attr( wp_json_encode( $data ) ) . '"';

        return str_replace( 'href=', $data_attr . ' href=', $link );
    }

    /**
     * Embeds product data as a hidden input inside the add-to-cart form
     * on single product pages, so the JS add_to_cart handler can read it.
     */
    public function embed_product_data_in_add_to_cart_form() {
        $product = wc_get_product( get_the_ID() );
        if ( ! $product ) {
            return;
        }

        $data = $this->build_product_data( $product );
        echo '<input type="hidden" name="ecdp_product_data" value="' . esc_attr( wp_json_encode( $data ) ) . '" />';
    }

    /**
     * Embeds product data as a hidden span on product cards in listing pages,
     * so the JS add_to_cart handler can read it for AJAX add-to-cart.
     */
    public function embed_product_data_in_listing() {
        global $product;
        if ( ! $product instanceof WC_Product ) {
            return;
        }

        $data = $this->build_product_data( $product );
        echo '<span class="ecdp-product-data" style="display:none;visibility:hidden;" data-ecdp_product_data="'
            . esc_attr( wp_json_encode( $data ) ) . '"></span>';
    }

    /**
     * Fires on woocommerce_add_to_cart. Stores product data in WC session so it can
     * be injected into ecdpDataLayer on the next page load (after form-submit reload).
     * Skipped for AJAX requests — those are handled client-side via the added_to_cart event.
     *
     * @param string $cart_item_key
     * @param int    $product_id
     * @param int    $quantity
     * @param int    $variation_id
     * @param array  $variation
     * @param array  $cart_item_data
     */
    public function capture_add_to_cart_event( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        $is_ajax = wp_doing_ajax();
        $this->log( sprintf(
            'capture_add_to_cart_event: product_id=%d variation_id=%d qty=%d is_ajax=%s session=%s',
            $product_id, $variation_id, $quantity,
            $is_ajax ? 'yes' : 'no',
            WC()->session ? 'yes' : 'no'
        ) );

        if ( $is_ajax ) {
            return;
        }

        $product = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
        if ( ! $product ) {
            $this->log( 'capture_add_to_cart_event: product not found, skipping' );
            return;
        }

        $data = $this->build_product_data( $product, $quantity );

        if ( WC()->session ) {
            WC()->session->set( 'ecdp_pending_add_to_cart', $data );
            $this->log( 'capture_add_to_cart_event: saved to session: ' . wp_json_encode( $data ) );
        } else {
            $this->log( 'capture_add_to_cart_event: WC session not available, cannot save' );
        }
    }

    /**
     * Writes a debug entry to the WooCommerce logger when logs are enabled.
     *
     * @param string $message
     */
    private function log( $message ) {
        if ( ! get_option( 'expertsender_cdp_enable_logs' ) ) {
            return;
        }
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->debug( $message, array( 'source' => 'expertsender-cdp-datalayer' ) );
        }
    }

    /**
     * AJAX handler — returns current cart state as JSON.
     * Used by JS after wc_fragments_refreshed to diff cart changes.
     */
    public function ajax_get_cart() {
        check_ajax_referer( 'ecdp_get_cart' );
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_success( null );
            return;
        }
        wp_send_json_success( $this->build_cart_state_data() );
    }

    /**
     * Builds a normalised product data array in the format expected by ECDP / GA4.
     *
     * @param WC_Product $product
     * @param int        $quantity
     * @return array
     */
    private function build_product_data( $product, $quantity = 1 ) {
        $parent_id  = $product->get_parent_id();
        $product_id = $product->get_id();
        $ref_id     = $parent_id ?: $product_id;

        $terms      = get_the_terms( $ref_id, 'product_cat' );
        $categories = ( $terms && ! is_wp_error( $terms ) )
            ? wp_list_pluck( $terms, 'name' )
            : array();

        $price         = (float) wc_get_price_including_tax( $product );
        $regular_price = (float) wc_get_price_including_tax(
            $product,
            array( 'price' => $product->get_regular_price() )
        );

        $image_id  = get_post_thumbnail_id( $ref_id );
        $image_url = $image_id
            ? wp_get_attachment_image_url( $image_id, 'woocommerce_single' )
            : wc_placeholder_img_src();

        $data = array(
            'item_id'        => (string) $ref_id,
            'item_group_id'  => (string) $ref_id,
            'item_name'      => $product->get_name(),
            'price'          => round( $price, 2 ),
            'original_price' => round( $regular_price ?: $price, 2 ),
            'currency'       => get_woocommerce_currency(),
            'item_category'  => $categories[0] ?? '',
            'item_category2' => $categories[1] ?? '',
            'item_category3' => $categories[2] ?? '',
            'image_url'      => $image_url ?: '',
            'availability'   => $product->is_in_stock(),
            'quantity'       => (int) $quantity,
        );

        if ( $parent_id ) {
            $data['item_variant'] = (string) $product_id;
        }

        return $data;
    }

    /**
     * Builds cartState data for window._ecdp.cartState.
     *
     * @return array|null
     */
    private function build_cart_state_data() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return null;
        }

        $cart  = WC()->cart;
        $items = array();

        foreach ( $cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'] ?? null;
            if ( ! $product instanceof WC_Product ) {
                continue;
            }
            $items[] = $this->build_product_data( $product, $cart_item['quantity'] );
        }

        return array(
            'currency'   => get_woocommerce_currency(),
            'total'      => round( (float) $cart->get_total( 'edit' ), 2 ),
            'item_count' => $cart->get_cart_contents_count(),
            'items'      => $items,
        );
    }

    /**
     * Builds view_cart ecommerce data (total + items list).
     *
     * @return array|null
     */
    private function build_view_cart_data() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return null;
        }

        $cart  = WC()->cart;
        $items = array();

        foreach ( $cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'] ?? null;
            if ( ! $product instanceof WC_Product ) {
                continue;
            }
            $items[] = $this->build_product_data( $product, $cart_item['quantity'] );
        }

        return array(
            'total' => round( (float) $cart->get_total( 'edit' ), 2 ),
            'items' => $items,
        );
    }
}
