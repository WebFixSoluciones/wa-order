<?php
if(!defined('ABSPATH')) exit;

add_filter('manage_edit-wrm_item_columns', 'wa_order_add_price_column', 20);
function wa_order_add_price_column($columns){
    $new = array();
    foreach($columns as $key => $label){
        $new[$key] = $label;
        if($key === 'taxonomy-wrm_category'){
            $new['wrm_price'] = 'Precio';
        }
    }
    if(!isset($new['wrm_price'])){
        $new['wrm_price'] = 'Precio';
    }
    return $new;
}

add_action('manage_wrm_item_posts_custom_column', 'wa_order_render_price_column', 10, 2);
function wa_order_render_price_column($column, $post_id){
    if($column !== 'wrm_price') return;
    $price = get_post_meta($post_id, '_wrm_price', true);
    if($price === '' || $price === null){
        echo '&mdash;';
        return;
    }
    echo esc_html('$' . $price);
}

add_filter('manage_edit-wrm_item_sortable_columns', 'wa_order_sortable_price_column');
function wa_order_sortable_price_column($columns){
    $columns['wrm_price'] = 'wrm_price';
    return $columns;
}

add_action('pre_get_posts', 'wa_order_admin_price_orderby');
function wa_order_admin_price_orderby($query){
    if(!is_admin() || !$query->is_main_query()) return;
    if($query->get('post_type') !== 'wrm_item') return;
    if($query->get('orderby') !== 'wrm_price') return;
    $query->set('meta_key', '_wrm_price');
    $query->set('orderby', 'meta_value_num');
}
