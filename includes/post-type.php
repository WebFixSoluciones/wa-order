<?php
if(!defined('ABSPATH'))exit;

add_action('init','wrm_register_post_type');
function wrm_register_post_type(){
    /* ── Categorías del menú ─────────────────────────── */
    register_taxonomy('wrm_category',['wrm_item'],[
        'labels'=>[
            'name'              =>'Categorías del menú',
            'singular_name'     =>'Categoría',
            'add_new_item'      =>'Nueva categoría',
            'edit_item'         =>'Editar categoría',
            'search_items'      =>'Buscar categorías',
        ],
        'hierarchical'      =>true,
        'show_ui'           =>true,
        'show_in_rest'      =>true,
        'show_admin_column' =>true,
        'rewrite'           =>['slug'=>'menu-categoria'],
    ]);

    /* ── Etiquetas del menú ──────────────────────────── */
    register_taxonomy('wrm_tag',['wrm_item'],[
        'labels'=>[
            'name'              =>'Etiquetas del menú',
            'singular_name'     =>'Etiqueta',
            'add_new_item'      =>'Nueva etiqueta',
            'edit_item'         =>'Editar etiqueta',
            'search_items'      =>'Buscar etiquetas',
        ],
        'hierarchical'      =>true,
        'show_ui'           =>true,
        'show_in_rest'      =>true,
        'show_admin_column' =>true,
        'meta_box_cb'       =>'post_categories_meta_box',
        'rewrite'           =>['slug'=>'menu-etiqueta'],
    ]);

    /* ── Post type: item del menú ───────────────────── */
    register_post_type('wrm_item',[
        'labels'=>[
            'name'          =>'Menú',
            'singular_name' =>'Ítem del menú',
            'add_new'       =>'Agregar ítem',
            'add_new_item'  =>'Nuevo ítem del menú',
            'edit_item'     =>'Editar ítem',
            'view_item'     =>'Ver ítem',
            'search_items'  =>'Buscar en el menú',
            'not_found'     =>'No se encontraron ítems',
        ],
        'public'        =>true,
        'has_archive'   =>false,
        'show_in_rest'  =>true,
        'supports'      =>['title','editor','thumbnail','excerpt'],
        'menu_position' =>5,
        'menu_icon'     =>'dashicons-food',
        'rewrite'       =>['slug'=>'menu-item'],
        'taxonomies'    =>['wrm_category', 'wrm_tag'],
    ]);
}
