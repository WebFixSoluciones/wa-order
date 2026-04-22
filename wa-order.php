<?php
/**
 * Plugin Name:       WA Order
 * Plugin URI:        https://webfix.ec
 * Description:       Menú visual para restaurantes con carrito popup y pedidos directos por WhatsApp. Soporte para delivery/recogida, variantes, extras y shortcode [wrm_menu].
 * Version:           0.9.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            WebFix
 * Author URI:        https://webfix.ec
 * License:           GPL-2.0-or-later
 * Text Domain:       wrm-menu
 * Domain Path:       /languages
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WRM_VERSION',  '0.9.0' );
define( 'WRM_DIR',      plugin_dir_path( __FILE__ ) );
define( 'WRM_URI',      plugin_dir_url( __FILE__ ) );
define( 'WRM_PREFIX',   'wrm_menu' );

/* ── Cargar módulos ───────────────────────────────────────── */
require_once WRM_DIR . 'includes/post-type.php';
require_once WRM_DIR . 'includes/meta-boxes.php';
require_once WRM_DIR . 'includes/shortcode.php';
require_once WRM_DIR . 'includes/ajax.php';
require_once WRM_DIR . 'includes/admin-page.php';
require_once WRM_DIR . 'includes/admin-columns.php';
require_once WRM_DIR . 'includes/locations-db.php';

/* ── Assets frontend ──────────────────────────────────────── */
add_action( 'wp_enqueue_scripts', function () {
    $v = WRM_VERSION;
    wp_enqueue_style(  'wrm-menu-front', WRM_URI . 'assets/css/menu-front.css', [], $v );
    wp_enqueue_style(  'wrm-cart-front', WRM_URI . 'assets/css/cart-front.css', [], $v );
    wp_enqueue_script( 'wrm-cart-js',    WRM_URI . 'assets/js/cart.js', ['jquery'], $v, true );

    $opts = get_option( 'wrm_settings', [] );
    $wa   = preg_replace( '/[^0-9]/', '', $opts['whatsapp'] ?? get_option('wrm_theme_settings',[])['whatsapp'] ?? '' );

    
$locations_data = wrm_get_locations_for_frontend();
$matrix_payload = $locations_data['matrix'];
$active_branches = $locations_data['branches'];

wp_localize_script( 'wrm-cart-js', 'WRMCart', [

        'whatsapp'      => $wa,
        'business_name' => $opts['business_name'] ?? get_option('wrm_theme_settings',[])['business_name'] ?? get_bloginfo('name'),
        'currency'      => $opts['currency']      ?? '$',
        'delivery_fee'  => $opts['delivery_fee']  ?? get_option('wrm_theme_settings',[])['delivery_fee'] ?? '',
        'address'       => $opts['address']       ?? get_option('wrm_theme_settings',[])['address'] ?? '',
        'matrix'        => $matrix_payload,
        'branches'      => $active_branches,
        'nonce'         => wp_create_nonce( 'wrm_cart' ),
        'ajaxurl'       => admin_url( 'admin-ajax.php' ),
        'i18n'          => [
            'order_btn'   => 'Ordenar por WhatsApp',
            'cart_empty'  => 'Tu carrito está vacío',
            'add_item'    => 'Agregar al carrito',
            'remove'      => 'Eliminar',
            'total'       => 'Total',
            'new_order'   => '🛒 Nuevo Pedido',
        ],
    ]);
} );

/* ── Assets admin ─────────────────────────────────────────── */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( ! in_array( $hook, ['post.php','post-new.php','toplevel_page_wrm-menu-admin'] ) ) return;
    wp_enqueue_style( 'wrm-admin', WRM_URI . 'assets/css/admin.css', [], WRM_VERSION );
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_enqueue_media();
} );

/* ── Activación / Desactivación ───────────────────────────── */
register_activation_hook( __FILE__, function () {
    wrm_register_post_type();
    wrm_create_locations_table();
    wrm_maybe_migrate_options_to_locations_table();
    flush_rewrite_rules();
    if ( ! get_option('wrm_settings') ) {
        update_option( 'wrm_settings', [
            'whatsapp'      => '',
            'business_name' => get_bloginfo('name'),
            'currency'      => '$',
            'delivery_fee'  => '',
            'address'       => '',
            'primary_color' => '#25D366',
        ]);
    }
} );

register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );
