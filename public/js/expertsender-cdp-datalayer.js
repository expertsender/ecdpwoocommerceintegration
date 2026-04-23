(function() {
    'use strict';

    window.dataLayer = window.dataLayer || [];
    window._ecdp = window._ecdp || {};
    window._ecdp.events = window._ecdp.events || [];

    var cfg = window.ecdpDataLayer;
    if ( ! cfg ) return;

    var currency = cfg.currency || '';
    var prefix   = cfg.eventPrefix || '';

    // =========================================================
    // DEBUG LOGGER — active when cfg.debug is true (WC logs on)
    // =========================================================
    function ecdpLog() {
        if ( cfg.debug && window.console && console.log ) {
            var args = Array.prototype.slice.call( arguments );
            args.unshift( '[ECDP]' );
            console.log.apply( console, args );
        }
    }

    ecdpLog( 'init cfg:', cfg );
    ecdpLog( 'eventPrefix:', JSON.stringify( prefix ) );

    // =========================================================
    // HELPER — push to dataLayer + _ecdp.events
    // =========================================================
    function ecdpPush( eventName, ecommerceData ) {
        var fullName = prefix + eventName;
        ecdpLog( 'push event:', fullName, ecommerceData );
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            event: fullName,
            ecommerce: ecommerceData
        });
        window._ecdp.events.push({
            event: fullName,
            timestamp: Date.now(),
            ecommerce: ecommerceData
        });
    }

    // =========================================================
    // USER DATA — logged-in user (every page, no ecommerce: null prefix)
    // =========================================================
    if ( cfg.userData ) {
        ecdpLog( 'pushing user_data', cfg.userData );
        window.dataLayer.push({
            event:     prefix + 'user_data',
            user_data: cfg.userData
        });
        window._ecdp.userData = cfg.userData;
    }

    // =========================================================
    // CART STATE (every page)
    // =========================================================
    if ( cfg.cartState ) {
        window._ecdp.cartState = cfg.cartState;
        ecdpLog( 'cartState set', cfg.cartState );
    }

    // =========================================================
    // CART STATE — AJAX updates (quantity change, mini-cart)
    // Fires after WooCommerce refreshes cart fragments without page reload.
    // Diffs previous vs new cart state to fire add_to_cart / remove_from_cart.
    // =========================================================
    if ( typeof jQuery !== 'undefined' && cfg.ajaxUrl ) {
        // Tracks item_id+variant keys removed via click so we don't double-fire.
        var recentRemovals = {};

        document.addEventListener( 'click', function( e ) {
            var removeLink = e.target.closest( '.product-remove a.remove, .mini_cart_item a.remove' );
            if ( ! removeLink ) return;
            var raw = removeLink.dataset.ecdp_product_data;
            if ( ! raw ) return;
            try {
                var p = JSON.parse( raw );
                var key = ( p.item_id || '' ) + '|' + ( p.item_variant || '' );
                recentRemovals[ key ] = Date.now();
                ecdpLog( 'click remove recorded', key );
            } catch ( err ) {}
        }, true );

        jQuery( document.body ).on( 'wc_fragments_refreshed', function() {
            var prevState = window._ecdp.cartState;
            ecdpLog( 'wc_fragments_refreshed, prevState:', prevState );

            jQuery.post( cfg.ajaxUrl, { action: 'ecdp_get_cart', nonce: cfg.ajaxNonce }, function( response ) {
                if ( ! response || ! response.success ) {
                    ecdpLog( 'ecdp_get_cart failed', response );
                    return;
                }

                var newState = response.data;
                ecdpLog( 'ecdp_get_cart response:', newState );
                window._ecdp.cartState = newState || { currency: currency, total: 0, item_count: 0, items: [] };

                if ( ! prevState || ! newState ) return;

                function itemKey( item ) {
                    return ( item.item_id || '' ) + '|' + ( item.item_variant || '' );
                }

                var prevMap = {};
                ( prevState.items || [] ).forEach( function( item ) { prevMap[ itemKey( item ) ] = item; });
                var newMap = {};
                ( newState.items || [] ).forEach( function( item ) { newMap[ itemKey( item ) ] = item; });

                var now = Date.now();
                var dedupeWindow = 5000;

                Object.keys( newMap ).forEach( function( key ) {
                    var newItem  = newMap[ key ];
                    var prevItem = prevMap[ key ];
                    var prevQty  = prevItem ? prevItem.quantity : 0;
                    var delta    = newItem.quantity - prevQty;
                    if ( delta > 0 ) {
                        var eventItem = Object.assign( {}, newItem, { quantity: delta } );
                        ecdpLog( 'fragments: add_to_cart delta', delta, key );
                        ecdpPush( 'add_to_cart', {
                            currency: currency,
                            value:    newItem.price * delta,
                            items:    [ eventItem ]
                        });
                    }
                });

                Object.keys( prevMap ).forEach( function( key ) {
                    var prevItem = prevMap[ key ];
                    var newItem  = newMap[ key ];
                    var newQty   = newItem ? newItem.quantity : 0;
                    var delta    = prevItem.quantity - newQty;
                    if ( delta > 0 ) {
                        if ( recentRemovals[ key ] && ( now - recentRemovals[ key ] ) < dedupeWindow ) {
                            delete recentRemovals[ key ];
                            ecdpLog( 'fragments: remove_from_cart deduped', key );
                            return;
                        }
                        var eventItem = Object.assign( {}, prevItem, { quantity: delta } );
                        ecdpLog( 'fragments: remove_from_cart delta', delta, key );
                        ecdpPush( 'remove_from_cart', {
                            currency: currency,
                            value:    prevItem.price * delta,
                            items:    [ eventItem ]
                        });
                    }
                });
            }, 'json' );
        });
    }

    // =========================================================
    // VIEW_ITEM — product page on load
    // =========================================================
    if ( cfg.viewItem ) {
        ecdpLog( 'view_item (pageload)', cfg.viewItem );
        ecdpPush( 'view_item', {
            currency: currency,
            value: cfg.viewItem.price,
            items: [ cfg.viewItem ]
        });
    }

    // =========================================================
    // VIEW_ITEM — variant change (requires jQuery / WooCommerce)
    // currentViewItem keeps the latest variant data so other handlers
    // (added_to_cart) can use it even when there is no hidden input.
    // =========================================================
    var currentViewItem = cfg.viewItem ? Object.assign( {}, cfg.viewItem ) : null;

    if ( cfg.viewItem && typeof jQuery !== 'undefined' ) {
        jQuery( document ).on( 'found_variation', function( event, variation ) {
            if ( ! variation ) return;

            currentViewItem = Object.assign( {}, cfg.viewItem, {
                item_variant:   String( variation.variation_id ),
                price:          parseFloat( variation.display_price || cfg.viewItem.price ),
                original_price: parseFloat( variation.display_regular_price || cfg.viewItem.original_price ),
                availability:   variation.is_in_stock
            });

            ecdpLog( 'found_variation, updated currentViewItem', currentViewItem );

            ecdpPush( 'view_item', {
                currency: currency,
                value:    currentViewItem.price,
                items:    [ currentViewItem ]
            });
        });
    }

    // =========================================================
    // ADD_TO_CART — form submit / page reload (data from PHP session)
    // =========================================================
    if ( cfg.pendingAddToCart ) {
        ecdpLog( 'pendingAddToCart (from session)', cfg.pendingAddToCart );
        ecdpPush( 'add_to_cart', {
            currency: currency,
            value:    cfg.pendingAddToCart.price * cfg.pendingAddToCart.quantity,
            items:    [ cfg.pendingAddToCart ]
        });
    } else {
        ecdpLog( 'pendingAddToCart: null (no session data on this page load)' );
    }

    // =========================================================
    // ADD_TO_CART — AJAX single product page
    // Priority: (1) hidden input in form, (2) currentViewItem with
    // up-to-date variant data from found_variation, (3) cfg.viewItem.
    // Handles themes that convert the form to AJAX (e.g. Nasa theme).
    // =========================================================
    if ( typeof jQuery !== 'undefined' ) {
        jQuery( document.body ).on( 'added_to_cart', function( event, fragments, cart_hash, $button ) {
            ecdpLog( 'added_to_cart event fired, $button:', $button && $button.length ? $button[0] : null );

            var productData = null;
            var form = null;

            if ( $button && $button.length ) {
                form = $button.closest( 'form.cart' )[ 0 ] || null;
            }

            // 1) Try hidden input added by embed_product_data_in_add_to_cart_form hook.
            if ( form ) {
                var hiddenInput = form.querySelector( '[name="ecdp_product_data"]' );
                if ( hiddenInput ) {
                    try {
                        productData = JSON.parse( hiddenInput.value );
                        ecdpLog( 'added_to_cart: got data from hidden input', productData );
                    } catch ( err ) {
                        ecdpLog( 'added_to_cart: hidden input parse error', err );
                    }
                } else {
                    ecdpLog( 'added_to_cart: no hidden input in form' );
                }
            } else {
                ecdpLog( 'added_to_cart: no form.cart found near button' );
            }

            // 2) Fallback: use currentViewItem (updated by found_variation for variants).
            if ( ! productData && currentViewItem ) {
                productData = Object.assign( {}, currentViewItem );
                ecdpLog( 'added_to_cart: using currentViewItem fallback', productData );
            }

            // 3) Last resort: plain cfg.viewItem without variant.
            if ( ! productData && cfg.viewItem ) {
                productData = Object.assign( {}, cfg.viewItem );
                ecdpLog( 'added_to_cart: using cfg.viewItem fallback', productData );
            }

            if ( ! productData ) {
                ecdpLog( 'added_to_cart: no product data found, skipping event' );
                return;
            }

            // Read variation_id and quantity from form if not already in productData.
            if ( form ) {
                var variationInput = form.querySelector( '[name="variation_id"]' );
                if ( variationInput && variationInput.value && variationInput.value !== '0' ) {
                    if ( ! productData.item_variant ) {
                        productData.item_variant = String( variationInput.value );
                        ecdpLog( 'added_to_cart: item_variant from form', productData.item_variant );
                    }
                }
                var qtyInput = form.querySelector( '[name="quantity"]' );
                productData.quantity = qtyInput ? ( parseInt( qtyInput.value ) || 1 ) : 1;
            } else {
                productData.quantity = 1;
            }

            ecdpLog( 'added_to_cart: firing event', productData );
            ecdpPush( 'add_to_cart', {
                currency: currency,
                value:    productData.price * productData.quantity,
                items:    [ productData ]
            });
        });
    }

    // =========================================================
    // ADD_TO_CART — listing / category AJAX buttons
    // =========================================================
    document.addEventListener( 'click', function( e ) {
        var btn = e.target.closest(
            '.add_to_cart_button:not(.product_type_variable):not(.product_type_grouped):not(.single_add_to_cart_button)'
        );
        if ( ! btn ) return;

        var productId = btn.dataset.product_id || btn.dataset.productId;
        if ( ! productId ) return;

        var productEl = btn.closest( '.product, li[class*="product"], .wc-block-grid__product' );
        var dataEl    = productEl && productEl.querySelector( '[data-ecdp_product_data]' );
        if ( ! dataEl ) {
            ecdpLog( 'listing click: no [data-ecdp_product_data] near button', btn );
            return;
        }

        var productData;
        try { productData = JSON.parse( dataEl.dataset.ecdp_product_data ); }
        catch ( err ) { return; }

        productData.quantity = 1;

        ecdpLog( 'listing click: add_to_cart', productData );
        ecdpPush( 'add_to_cart', {
            currency: currency,
            value:    productData.price,
            items:    [ productData ]
        });
    }, true );

    // =========================================================
    // REMOVE_FROM_CART — cart page & mini-cart remove links
    // =========================================================
    document.addEventListener( 'click', function( e ) {
        var removeLink = e.target.closest(
            '.product-remove a.remove, .mini_cart_item a.remove'
        );
        if ( ! removeLink ) return;

        var productDataRaw = removeLink.dataset.ecdp_product_data;
        if ( ! productDataRaw ) {
            ecdpLog( 'remove click: no ecdp_product_data on link' );
            return;
        }

        var product;
        try { product = JSON.parse( productDataRaw ); }
        catch ( err ) { return; }

        var cartItem  = removeLink.closest( '.cart_item, .mini_cart_item' );
        var qtyEl     = cartItem && cartItem.querySelector( '.qty, .quantity' );
        product.quantity = qtyEl ? ( parseInt( qtyEl.value || qtyEl.textContent ) || 1 ) : 1;

        ecdpLog( 'remove_from_cart click', product );
        ecdpPush( 'remove_from_cart', {
            currency: currency,
            value:    product.price * product.quantity,
            items:    [ product ]
        });
    }, true );

    // =========================================================
    // VIEW_CART — cart page on load
    // =========================================================
    if ( cfg.viewCart ) {
        ecdpLog( 'view_cart (pageload)', cfg.viewCart );
        ecdpPush( 'view_cart', {
            currency: currency,
            value:    cfg.viewCart.total,
            items:    cfg.viewCart.items
        });
    }

    // =========================================================
    // PURCHASE — thank-you page on load
    // =========================================================
    if ( cfg.purchaseData ) {
        ecdpLog( 'purchase (pageload)', cfg.purchaseData );
        ecdpPush( 'purchase', {
            transaction_id: cfg.purchaseData.order_id,
            currency:       currency,
            value:          cfg.purchaseData.total,
            items:          cfg.purchaseData.items
        });
    }

})();
