<?php
if(!defined('ABSPATH'))exit;

/* Endpoint: obtener datos de producto para modal */
add_action('wp_ajax_wrm_get_item',        'wrm_ajax_get_item');
add_action('wp_ajax_nopriv_wrm_get_item', 'wrm_ajax_get_item');

function wrm_ajax_get_item(){
    check_ajax_referer('wrm_cart','nonce');
    $id   = absint($_POST['id'] ?? 0);
    $post = get_post($id);
    if(!$post||$post->post_type!=='wrm_item'){ wp_send_json_error('not_found'); }

    $opts = get_option('wrm_settings',[]);
    $cur  = $opts['currency'] ?? '$';

    wp_send_json_success([
        'id'       => $id,
        'title'    => get_the_title($id),
        'desc'     => get_the_excerpt($id) ?: wp_trim_words(get_post_field('post_content',$id),'',20,'…'),
        'price'    => (float)(get_post_meta($id,'_wrm_price',true) ?: 0),
        'price_old'=> (float)(get_post_meta($id,'_wrm_price_old',true) ?: 0),
        'badge'    => get_post_meta($id,'_wrm_badge',true),
        'image'    => get_the_post_thumbnail_url($id,'medium') ?: '',
        'variants' => get_post_meta($id,'_wrm_variants',true) ?: [],
        'extras'   => get_post_meta($id,'_wrm_extras',true)   ?: [],
        'currency' => $cur,
    ]);
}
