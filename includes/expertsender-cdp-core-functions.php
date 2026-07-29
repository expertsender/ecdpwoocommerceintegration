<?php

defined ( 'ABSPATH' ) || exit;

/**
 * @return \ExpertSender_CDP_Logger
 */
function expertsender_cdp_get_logger() {
    static $logger = null;

    if (null === $logger) {
        $logger = new ExpertSender_CDP_Logger();
    }

    return $logger;
}

/**
 * Zwraca strefę czasową sklepu w formacie akceptowanym przez API ECDP
 * (parametr 'timeZone' w requeście orders).
 *
 * Kolejność źródeł:
 * 1. wp_timezone_string() - tylko jeśli zwraca nazwę strefy (np. "Europe/Warsaw"),
 *    a nie ręczny offset UTC (np. "+02:00"), bo offset nie uwzględnia zmiany czasu (DST).
 * 2. Kraj z adresu siedziby WooCommerce (Ustawienia > Ogólne > Adres siedziby) -
 *    zwykle wiarygodny, bo wpływa na podatki/wysyłkę.
 * 3. Twardy fallback: Europe/Warsaw.
 *
 * @return string
 */
function es_get_mapped_timezone() {
    $wp_timezone = wp_timezone_string();

    if ( ! empty( $wp_timezone ) && ! preg_match( '/^[+\-]/', $wp_timezone ) ) {
        return $wp_timezone;
    }

    $country_timezone = es_get_timezone_by_country_code(
        es_get_store_base_country_code()
    );

    if ( ! empty( $country_timezone ) ) {
        return $country_timezone;
    }

    return 'Europe/Warsaw';
}

/**
 * @return string
 */
function es_get_store_base_country_code() {
    $base_country = get_option( 'woocommerce_default_country', '' );

    // Opcja może mieć format "PL" albo "PL:14" (kraj:stan/województwo).
    list( $country_code ) = explode( ':', $base_country );

    return $country_code;
}

/**
 * Mapowanie kodu kraju (ISO 3166-1 alpha-2) na strefę czasową akceptowaną przez ECDP.
 *
 * @param string $country_code
 *
 * @return string|null
 */
function es_get_timezone_by_country_code( $country_code ) {
    $map = array(
        'AD' => 'Europe/Andorra',
        'AL' => 'Europe/Tirane',
        'AT' => 'Europe/Vienna',
        'BA' => 'Europe/Sarajevo',
        'BE' => 'Europe/Brussels',
        'BG' => 'Europe/Sofia',
        'BY' => 'Europe/Minsk',
        'CH' => 'Europe/Zurich',
        'CY' => 'Asia/Nicosia',
        'CZ' => 'Europe/Prague',
        'DE' => 'Europe/Berlin',
        'DK' => 'Europe/Copenhagen',
        'EE' => 'Europe/Tallinn',
        'ES' => 'Europe/Madrid',
        'FI' => 'Europe/Helsinki',
        'FR' => 'Europe/Paris',
        'GB' => 'Europe/London',
        'GR' => 'Europe/Athens',
        'HR' => 'Europe/Zagreb',
        'HU' => 'Europe/Budapest',
        'IE' => 'Europe/Dublin',
        'IS' => 'Atlantic/Reykjavik',
        'IT' => 'Europe/Rome',
        'LI' => 'Europe/Vaduz',
        'LT' => 'Europe/Vilnius',
        'LU' => 'Europe/Luxembourg',
        'LV' => 'Europe/Riga',
        'MC' => 'Europe/Monaco',
        'MD' => 'Europe/Chisinau',
        'ME' => 'Europe/Podgorica',
        'MK' => 'Europe/Skopje',
        'MT' => 'Europe/Malta',
        'NL' => 'Europe/Amsterdam',
        'NO' => 'Europe/Oslo',
        'PL' => 'Europe/Warsaw',
        'PT' => 'Europe/Lisbon',
        'RO' => 'Europe/Bucharest',
        'RS' => 'Europe/Belgrade',
        'SE' => 'Europe/Stockholm',
        'SI' => 'Europe/Ljubljana',
        'SK' => 'Europe/Bratislava',
        'SM' => 'Europe/Rome',
        'UA' => 'Europe/Kyiv',
        'VA' => 'Europe/Rome',
        'XK' => 'Europe/Belgrade',
    );

    return $map[ $country_code ] ?? null;
}
