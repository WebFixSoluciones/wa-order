<?php
if(!defined('ABSPATH')) exit;

function wrm_locations_table_name(){
    global $wpdb;
    return $wpdb->prefix . 'wrm_locations';
}

function wrm_create_locations_table(){
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = wrm_locations_table_name();
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        location_type varchar(20) NOT NULL DEFAULT 'branch',
        name varchar(190) NOT NULL DEFAULT '',
        address text NULL,
        phone varchar(50) NOT NULL DEFAULT '',
        whatsapp varchar(50) NOT NULL DEFAULT '',
        coverage text NULL,
        city varchar(100) NOT NULL DEFAULT '',
        maps_enabled tinyint(1) NOT NULL DEFAULT 0,
        maps_url text NULL,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        sort_order int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY location_type (location_type),
        KEY is_active (is_active),
        KEY sort_order (sort_order)
    ) {$charset};";
    dbDelta($sql);
    update_option('wrm_locations_db_version', '1.0.0');
}

function wrm_default_location_row($type='branch'){
    return [
        'location_type' => $type,
        'name' => '',
        'address' => '',
        'phone' => '',
        'whatsapp' => '',
        'coverage' => '',
        'city' => '',
        'maps_enabled' => 0,
        'maps_url' => '',
        'is_active' => 1,
        'sort_order' => 0,
    ];
}

function wrm_normalize_location_db_row($row, $defaults = []){
    $row = wp_parse_args((array)$row, $defaults);
    return [
        'location_type' => in_array(($row['location_type'] ?? 'branch'), ['matrix','branch'], true) ? $row['location_type'] : 'branch',
        'name' => sanitize_text_field($row['name'] ?? ''),
        'address' => sanitize_text_field($row['address'] ?? ''),
        'phone' => sanitize_text_field($row['phone'] ?? ''),
        'whatsapp' => preg_replace('/[^0-9]/','', (string)($row['whatsapp'] ?? '')),
        'coverage' => sanitize_text_field($row['coverage'] ?? ''),
        'city' => sanitize_text_field($row['city'] ?? ''),
        'maps_enabled' => !empty($row['maps_enabled']) ? 1 : 0,
        'maps_url' => esc_url_raw($row['maps_url'] ?? ''),
        'is_active' => !empty($row['is_active']) ? 1 : 0,
        'sort_order' => isset($row['sort_order']) ? intval($row['sort_order']) : 0,
    ];
}

function wrm_get_all_locations($type = null){
    global $wpdb;
    $table = wrm_locations_table_name();
    $sql = "SELECT * FROM {$table}";
    if($type){
        $sql .= $wpdb->prepare(" WHERE location_type = %s", $type);
    }
    $sql .= " ORDER BY sort_order ASC, id ASC";
    return $wpdb->get_results($sql, ARRAY_A);
}

function wrm_replace_locations_by_type($type, $rows){
    global $wpdb;
    $table = wrm_locations_table_name();
    $wpdb->delete($table, ['location_type' => $type], ['%s']);
    $i = 0;
    foreach((array)$rows as $row){
        $defaults = wrm_default_location_row($type);
        $clean = wrm_normalize_location_db_row($row, $defaults);
        if($clean['name'] === '' && $clean['address'] === '') continue;
        $clean['location_type'] = $type;
        $clean['sort_order'] = $i;
        $clean['created_at'] = current_time('mysql');
        $clean['updated_at'] = current_time('mysql');
        $wpdb->insert($table, $clean, ['%s','%s','%s','%s','%s','%s','%s','%d','%s','%d','%d','%s','%s']);
        $i += 1;
    }
}

function wrm_maybe_migrate_options_to_locations_table($force = false){
    global $wpdb;
    $table = wrm_locations_table_name();
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if($exists !== $table){
        wrm_create_locations_table();
    }
    $already = get_option('wrm_locations_migrated', '0');
    $count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$table}"));
    if(!$force && $already === '1' && $count > 0) return;

    $matrix = get_option('wrm_matrix_settings', []);
    $branches = get_option('wrm_branch_locations', []);
    if(!is_array($branches)) $branches = [];

    $matrix_row = [[
        'location_type' => 'matrix',
        'name' => sanitize_text_field($matrix['name'] ?? ''),
        'address' => sanitize_text_field($matrix['address'] ?? ''),
        'phone' => sanitize_text_field($matrix['phone'] ?? ''),
        'whatsapp' => preg_replace('/[^0-9]/','', $matrix['whatsapp'] ?? ''),
        'coverage' => sanitize_text_field($matrix['coverage'] ?? ''),
        'city' => sanitize_text_field($matrix['city'] ?? ''),
        'maps_enabled' => !empty($matrix['maps_enabled']) ? 1 : 0,
        'maps_url' => esc_url_raw($matrix['maps_url'] ?? ''),
        'is_active' => !empty($matrix['is_active']) ? 1 : 0,
    ]];
    wrm_replace_locations_by_type('matrix', $matrix_row);

    $branch_rows = [];
    foreach($branches as $idx => $b){
        $branch_rows[] = [
            'location_type' => 'branch',
            'name' => sanitize_text_field($b['name'] ?? ''),
            'address' => sanitize_text_field($b['address'] ?? ''),
            'phone' => sanitize_text_field($b['phone'] ?? ''),
            'whatsapp' => preg_replace('/[^0-9]/','', $b['whatsapp'] ?? ''),
            'coverage' => sanitize_text_field($b['coverage'] ?? ''),
            'city' => sanitize_text_field($b['city'] ?? ''),
            'maps_enabled' => !empty($b['maps_enabled']) ? 1 : 0,
            'maps_url' => esc_url_raw($b['maps_url'] ?? ''),
            'is_active' => !empty($b['is_active']) ? 1 : 0,
            'sort_order' => $idx,
        ];
    }
    wrm_replace_locations_by_type('branch', $branch_rows);
    update_option('wrm_locations_migrated', '1');
}

function wrm_get_locations_for_frontend(){
    $opts = get_option('wrm_settings', []);
    $wa   = preg_replace('/[^0-9]/', '', $opts['whatsapp'] ?? get_option('wrm_theme_settings',[])['whatsapp'] ?? '');
    $matrix_rows = wrm_get_all_locations('matrix');
    $branch_rows = wrm_get_all_locations('branch');

    $matrix = !empty($matrix_rows) ? $matrix_rows[0] : [];
    $matrix_payload = [
        'id' => intval($matrix['id'] ?? 0),
        'name' => sanitize_text_field($matrix['name'] ?? ($opts['business_name'] ?? get_bloginfo('name'))),
        'address' => sanitize_text_field($matrix['address'] ?? ($opts['address'] ?? '')),
        'phone' => sanitize_text_field($matrix['phone'] ?? ''),
        'whatsapp' => preg_replace('/[^0-9]/','', $matrix['whatsapp'] ?? $wa),
        'maps_enabled' => !empty($matrix['maps_enabled']) ? '1' : '0',
        'maps_url' => esc_url_raw($matrix['maps_url'] ?? ''),
        'coverage' => sanitize_text_field($matrix['coverage'] ?? ''),
        'city' => sanitize_text_field($matrix['city'] ?? ''),
        'is_active' => !empty($matrix['is_active']) ? '1' : '0',
        'type' => 'Matriz',
    ];

    $active_branches = [];
    foreach($branch_rows as $b){
        if(empty($b['is_active'])) continue;
        $active_branches[] = [
            'id' => intval($b['id'] ?? 0),
            'name' => sanitize_text_field($b['name'] ?? ''),
            'address' => sanitize_text_field($b['address'] ?? ''),
            'phone' => sanitize_text_field($b['phone'] ?? ''),
            'whatsapp' => preg_replace('/[^0-9]/','', $b['whatsapp'] ?? ''),
            'maps_enabled' => !empty($b['maps_enabled']) ? '1' : '0',
            'maps_url' => esc_url_raw($b['maps_url'] ?? ''),
            'coverage' => sanitize_text_field($b['coverage'] ?? ''),
            'city' => sanitize_text_field($b['city'] ?? ''),
            'is_active' => !empty($b['is_active']) ? '1' : '0',
            'type' => 'Sucursal',
        ];
    }
    return ['matrix' => $matrix_payload, 'branches' => $active_branches];
}
