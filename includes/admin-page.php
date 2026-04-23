<?php
if(!defined('ABSPATH'))exit;

add_action('admin_menu','wrm_plugin_admin_menu');
function wrm_plugin_admin_menu(){
    add_menu_page(
        'WA Order Configuración',
        '🟢 WA Order',
        'manage_options',
        'wrm-menu-admin',
        'wrm_plugin_settings_page',
        'dashicons-store',
        4
    );
    add_submenu_page('wrm-menu-admin','Ítems del menú','📋 Ítems del menú','manage_options','edit.php?post_type=wrm_item');
    add_submenu_page('wrm-menu-admin','Categorías','🗂️ Categorías','manage_options','edit-tags.php?taxonomy=wrm_category&post_type=wrm_item');
    add_submenu_page('wrm-menu-admin','Etiquetas','🏷️ Etiquetas','manage_options','edit-tags.php?taxonomy=wrm_tag&post_type=wrm_item');
}

function wrm_get_branch_defaults(){
    return [
        'name' => '',
        'address' => '',
        'phone' => '',
        'whatsapp' => '',
        'maps_enabled' => '0',
        'maps_url' => '',
        'coverage' => '',
        'city' => '',
        'is_active' => '1'
    ];
}

function wrm_plugin_settings_page(){
    if(isset($_POST['wrm_plugin_save']) && check_admin_referer('wrm_plugin_nonce')){
        $fields=['whatsapp','business_name','currency','delivery_fee','address','primary_color','send_confirmation'];
        $data=[];
        foreach($fields as $f) $data[$f]=sanitize_text_field($_POST[$f]??'');
        update_option('wrm_settings',$data);

        $matrix = wrm_get_branch_defaults();
        if(isset($_POST['wrm_matrix']) && is_array($_POST['wrm_matrix'])){
            $m = wp_unslash($_POST['wrm_matrix']);
            $matrix['name'] = sanitize_text_field($m['name'] ?? 'Matriz');
            $matrix['address'] = sanitize_text_field($m['address'] ?? '');
            $matrix['phone'] = sanitize_text_field($m['phone'] ?? '');
            $matrix['whatsapp'] = sanitize_text_field($m['whatsapp'] ?? '');
            $matrix['maps_enabled'] = !empty($m['maps_enabled']) ? '1' : '0';
            $matrix['maps_url'] = esc_url_raw($m['maps_url'] ?? '');
            $matrix['coverage'] = sanitize_text_field($m['coverage'] ?? '');
            $matrix['city'] = sanitize_text_field($m['city'] ?? '');
            $matrix['is_active'] = !empty($m['is_active']) ? '1' : '0';
        }
        update_option('wrm_matrix_settings', $matrix);
        wrm_replace_locations_by_type('matrix', [$matrix]);

        $branches=[];
        if(isset($_POST['wrm_branches']) && is_array($_POST['wrm_branches'])){
            foreach($_POST['wrm_branches'] as $branch){
                $b = wrm_get_branch_defaults();
                $branch = wp_unslash($branch);
                $b['name'] = sanitize_text_field($branch['name'] ?? '');
                $b['address'] = sanitize_text_field($branch['address'] ?? '');
                $b['phone'] = sanitize_text_field($branch['phone'] ?? '');
                $b['whatsapp'] = sanitize_text_field($branch['whatsapp'] ?? '');
                $b['maps_enabled'] = !empty($branch['maps_enabled']) ? '1' : '0';
                $b['maps_url'] = esc_url_raw($branch['maps_url'] ?? '');
                $b['coverage'] = sanitize_text_field($branch['coverage'] ?? '');
                $b['city'] = sanitize_text_field($branch['city'] ?? '');
                $b['is_active'] = !empty($branch['is_active']) ? '1' : '0';
                if($b['name'] !== '' || $b['address'] !== '') $branches[] = $b;
            }
        }
        update_option('wrm_branch_locations', $branches);
        wrm_replace_locations_by_type('branch', $branches);

        $theme_opts = get_option('wrm_theme_settings',[]);
        foreach(['whatsapp','business_name','delivery_fee','address'] as $k){
            if(!empty($data[$k])) $theme_opts[$k]=$data[$k];
        }
        update_option('wrm_theme_settings',$theme_opts);
        echo '<div class="notice notice-success is-dismissible"><p>✅ Configuración guardada.</p></div>';
    }

    $s = get_option('wrm_settings',[]);
    wrm_create_locations_table();
    wrm_maybe_migrate_options_to_locations_table();
    $matrix_rows = wrm_get_all_locations('matrix');
    $matrix = !empty($matrix_rows) ? $matrix_rows[0] : wrm_get_branch_defaults();
    $matrix = wp_parse_args($matrix, wrm_get_branch_defaults());
    $branches = wrm_get_all_locations('branch');
    if(!is_array($branches)) $branches = [];
    ?>
<style>
.wrm-p{max-width:1000px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
.wrm-p h1{font-size:1.55rem;margin-bottom:1.25rem}
.wrm-box{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1.25rem;margin-bottom:1.25rem}
.wrm-box h2{font-size:1rem;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #f3f4f6;color:#111827}
.wrm-pg{display:grid;grid-template-columns:1fr 1fr;gap:.85rem}
@media(max-width:700px){.wrm-pg{grid-template-columns:1fr}}
.wrm-pf{display:flex;flex-direction:column;gap:.3rem}
.wrm-pf label{font-size:.78rem;font-weight:700;color:#374151}
.wrm-pf input,.wrm-pf textarea,.wrm-pf select{padding:.55rem .7rem;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.875rem;width:100%}
.wrm-pf input:focus,.wrm-pf textarea:focus,.wrm-pf select:focus{outline:none;border-color:#25d366}
.wrm-pf .note{font-size:.7rem;color:#9ca3af}
.wrm-ps{background:linear-gradient(135deg,#25d366,#128c7e);color:#fff;border:none;padding:.75rem 1.55rem;border-radius:999px;font-weight:700;cursor:pointer;margin-top:.5rem;box-shadow:0 4px 14px rgba(37,211,102,.3)}
.wrm-shortcode-box{background:#f0fdf4;border:1.5px solid #86efac;border-radius:8px;padding:.75rem 1rem;font-family:monospace;font-size:.9rem;color:#166534;margin-top:.5rem;cursor:pointer}
.wrm-badge{display:inline-block;background:#dcfce7;color:#166534;font-size:.7rem;font-weight:700;padding:.15rem .5rem;border-radius:999px;margin-left:.4rem}
.wrm-tabs{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem}
.wrm-tab-btn{background:#fff;border:1px solid #d1d5db;border-radius:999px;padding:.55rem 1rem;font-weight:700;cursor:pointer;color:#374151}
.wrm-tab-btn.active{background:#25d366;color:#fff;border-color:#25d366}
.wrm-tab-panel{display:none}
.wrm-tab-panel.active{display:block}
.wrm-branch-card{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:1rem;margin-bottom:1rem}
.wrm-branch-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.8rem}
.wrm-branch-title{font-weight:800;color:#111827}
.wrm-del-branch{background:#ef4444;color:#fff;border:none;padding:.5rem .8rem;border-radius:8px;cursor:pointer;font-weight:700}
.wrm-add-branch{background:#111827;color:#fff;border:none;padding:.7rem 1rem;border-radius:10px;cursor:pointer;font-weight:700}
.wrm-switch{display:flex;align-items:center;gap:.5rem;font-size:.85rem;font-weight:700;color:#374151}
.wrm-inline-note{font-size:.74rem;color:#6b7280;margin-top:.2rem}
</style>
<div class="wrm-p">
<h1>🟢 WA Order <span class="wrm-badge">v0.9.0</span></h1>
<form method="post"><?php wp_nonce_field('wrm_plugin_nonce')?>
<div class="wrm-tabs">
  <button type="button" class="wrm-tab-btn active" data-tab="general">General</button>
  <button type="button" class="wrm-tab-btn" data-tab="matrix">Matriz</button>
  <button type="button" class="wrm-tab-btn" data-tab="branches">Sucursales</button>
</div>

<div class="wrm-tab-panel active" data-panel="general">
  <div class="wrm-box">
    <h2>📱 WhatsApp & Moneda</h2>
    <div class="wrm-pg">
      <div class="wrm-pf"><label>Número WhatsApp *</label><input type="text" name="whatsapp" value="<?php echo esc_attr($s['whatsapp']??'')?>" placeholder="593991234567"><span class="note">Solo números con código de país. Sin +, sin espacios.</span></div>
      <div class="wrm-pf"><label>Nombre del negocio</label><input type="text" name="business_name" value="<?php echo esc_attr($s['business_name']??get_bloginfo('name'))?>"></div>
      <div class="wrm-pf"><label>Símbolo de moneda</label><input type="text" name="currency" value="<?php echo esc_attr($s['currency']??'$')?>" style="max-width:80px"></div>
      <div class="wrm-pf"><label>Costo de envío (delivery)</label><input type="text" name="delivery_fee" value="<?php echo esc_attr($s['delivery_fee']??'')?>" placeholder="$1.50 o Gratis"></div>
      <div class="wrm-pf" style="grid-column:1/-1"><label>Dirección del local (para recogida)</label><input type="text" name="address" value="<?php echo esc_attr($s['address']??'')?>" placeholder="Av. Principal 123, Quito"></div>
      <div class="wrm-pf"><label>Color primario</label><input type="text" name="primary_color" value="<?php echo esc_attr($s['primary_color']??'#25D366')?>" class="wrm-color-pick"></div>
    </div>
  </div>
  <div class="wrm-box">
    <h2>📋 Shortcode del menú</h2>
    <p style="font-size:.875rem;color:#6b7280;margin-bottom:.5rem">Copia y pega este shortcode en cualquier página o en Elementor.</p>
    <div class="wrm-shortcode-box" onclick="navigator.clipboard.writeText('[wrm_menu]');this.textContent='✅ Copiado!';">[wrm_menu]</div>
    <p style="font-size:.75rem;color:#9ca3af;margin-top:.4rem">Click para copiar</p>
  </div>
</div>

<div class="wrm-tab-panel" data-panel="matrix">
  <div class="wrm-box">
    <h2>🏢 Matriz</h2>
    <div class="wrm-pg">
      <div class="wrm-pf"><label>Nombre de la matriz</label><input type="text" name="wrm_matrix[name]" value="<?php echo esc_attr($matrix['name'])?>" placeholder="Matriz Centro"></div>
      <div class="wrm-pf"><label>Teléfono</label><input type="text" name="wrm_matrix[phone]" value="<?php echo esc_attr($matrix['phone'])?>" placeholder="0999999999"></div>
      <div class="wrm-pf"><label>WhatsApp del local</label><input type="text" name="wrm_matrix[whatsapp]" value="<?php echo esc_attr($matrix['whatsapp'])?>" placeholder="593991234567"></div>
      <div class="wrm-pf"><label>Cobertura / zona</label><input type="text" name="wrm_matrix[coverage]" value="<?php echo esc_attr($matrix['coverage'])?>" placeholder="Centro, Norte, Cumbayá"></div>
      <div class="wrm-pf"><label>Ciudad</label><input type="text" name="wrm_matrix[city]" value="<?php echo esc_attr($matrix['city'] ?? '')?>" placeholder="Quito"></div>
      <div class="wrm-pf" style="grid-column:1/-1"><label>Dirección</label><input type="text" name="wrm_matrix[address]" value="<?php echo esc_attr($matrix['address'])?>" placeholder="Av. Principal 123, Quito"></div>
      <div class="wrm-pf">
        <label class="wrm-switch"><input type="checkbox" name="wrm_matrix[maps_enabled]" value="1" <?php checked($matrix['maps_enabled'],'1')?>> Habilitar link de Google Maps</label>
        <div class="wrm-inline-note">Actívalo solo si quieres mostrar o enviar la ubicación del local.</div>
      </div>
      <div class="wrm-pf"><label>Link Google Maps</label><input type="url" name="wrm_matrix[maps_url]" value="<?php echo esc_attr($matrix['maps_url'])?>" placeholder="https://maps.google.com/... "></div>
      <div class="wrm-pf"><label class="wrm-switch"><input type="checkbox" name="wrm_matrix[is_active]" value="1" <?php checked($matrix['is_active'],'1')?>> Local activo</label></div>
    </div>
  </div>
</div>

<div class="wrm-tab-panel" data-panel="branches">
  <div class="wrm-box">
    <h2>🏪 Sucursales</h2>
    <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">Agrega las sucursales que usarás para pickup y delivery. Cada una puede tener Google Maps habilitado o deshabilitado.</p>
    <div id="wrm-branches-wrap">
      <?php foreach($branches as $i => $branch): $branch = wp_parse_args($branch, wrm_get_branch_defaults()); ?>
      <div class="wrm-branch-card" data-index="<?php echo esc_attr($i)?>">
        <div class="wrm-branch-head">
          <div class="wrm-branch-title">Sucursal <?php echo intval($i+1)?></div>
          <button type="button" class="wrm-del-branch" onclick="this.closest('.wrm-branch-card').remove()">Eliminar</button>
        </div>
        <div class="wrm-pg">
          <div class="wrm-pf"><label>Nombre</label><input type="text" name="wrm_branches[<?php echo esc_attr($i)?>][name]" value="<?php echo esc_attr($branch['name'])?>" placeholder="Sucursal Norte"></div>
          <div class="wrm-pf"><label>Teléfono</label><input type="text" name="wrm_branches[<?php echo esc_attr($i)?>][phone]" value="<?php echo esc_attr($branch['phone'])?>" placeholder="0999999999"></div>
          <div class="wrm-pf"><label>WhatsApp del local</label><input type="text" name="wrm_branches[<?php echo esc_attr($i)?>][whatsapp]" value="<?php echo esc_attr($branch['whatsapp'])?>" placeholder="593991234567"></div>
          <div class="wrm-pf"><label>Cobertura / zona</label><input type="text" name="wrm_branches[<?php echo esc_attr($i)?>][coverage]" value="<?php echo esc_attr($branch['coverage'])?>" placeholder="Norte, Tumbaco, Valle"></div>
          <div class="wrm-pf"><label>Ciudad</label><input type="text" name="wrm_branches[<?php echo esc_attr($i)?>][city]" value="<?php echo esc_attr($branch['city'] ?? '')?>" placeholder="Quito"></div>
          <div class="wrm-pf" style="grid-column:1/-1"><label>Dirección</label><input type="text" name="wrm_branches[<?php echo esc_attr($i)?>][address]" value="<?php echo esc_attr($branch['address'])?>" placeholder="Av. Siempre Viva 123"></div>
          <div class="wrm-pf">
            <label class="wrm-switch"><input type="checkbox" name="wrm_branches[<?php echo esc_attr($i)?>][maps_enabled]" value="1" <?php checked($branch['maps_enabled'],'1')?>> Habilitar Google Maps</label>
            <div class="wrm-inline-note">Si está desactivado, no se usará el link del mapa.</div>
          </div>
          <div class="wrm-pf"><label>Link Google Maps</label><input type="url" name="wrm_branches[<?php echo esc_attr($i)?>][maps_url]" value="<?php echo esc_attr($branch['maps_url'])?>" placeholder="https://maps.google.com/... "></div>
          <div class="wrm-pf"><label class="wrm-switch"><input type="checkbox" name="wrm_branches[<?php echo esc_attr($i)?>][is_active]" value="1" <?php checked($branch['is_active'],'1')?>> Local activo</label></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="wrm-add-branch" id="wrm-add-branch">+ Agregar sucursal</button>
  </div>
</div>

<button type="submit" name="wrm_plugin_save" class="wrm-ps">💾 Guardar</button>
</form>
</div>
<script>
jQuery(function($){
  if($.fn.wpColorPicker){ $('.wrm-color-pick').wpColorPicker(); }
  $('.wrm-tab-btn').on('click', function(){
    var tab = $(this).data('tab');
    $('.wrm-tab-btn').removeClass('active');
    $(this).addClass('active');
    $('.wrm-tab-panel').removeClass('active');
    $('.wrm-tab-panel[data-panel="'+tab+'"]').addClass('active');
  });
  var branchIndex = $('#wrm-branches-wrap .wrm-branch-card').length;
  $('#wrm-add-branch').on('click', function(){
    var i = branchIndex++;
    var html = ''+
    '<div class="wrm-branch-card" data-index="'+i+'">'+
      '<div class="wrm-branch-head">'+
        '<div class="wrm-branch-title">Sucursal '+(i+1)+'</div>'+
        '<button type="button" class="wrm-del-branch" onclick="this.closest(\'.wrm-branch-card\').remove()">Eliminar</button>'+
      '</div>'+
      '<div class="wrm-pg">'+
        '<div class="wrm-pf"><label>Nombre</label><input type="text" name="wrm_branches['+i+'][name]" placeholder="Sucursal Norte"></div>'+
        '<div class="wrm-pf"><label>Teléfono</label><input type="text" name="wrm_branches['+i+'][phone]" placeholder="0999999999"></div>'+
        '<div class="wrm-pf"><label>WhatsApp del local</label><input type="text" name="wrm_branches['+i+'][whatsapp]" placeholder="593991234567"></div>'+
        '<div class="wrm-pf"><label>Cobertura / zona</label><input type="text" name="wrm_branches['+i+'][coverage]" placeholder="Norte, Tumbaco, Valle"></div>'+
        '<div class="wrm-pf"><label>Ciudad</label><input type="text" name="wrm_branches['+i+'][city]" placeholder="Quito"></div>'+
        '<div class="wrm-pf" style="grid-column:1/-1"><label>Dirección</label><input type="text" name="wrm_branches['+i+'][address]" placeholder="Av. Siempre Viva 123"></div>'+
        '<div class="wrm-pf">'+
          '<label class="wrm-switch"><input type="checkbox" name="wrm_branches['+i+'][maps_enabled]" value="1"> Habilitar Google Maps</label>'+
          '<div class="wrm-inline-note">Si está desactivado, no se usará el link del mapa.</div>'+
        '</div>'+
        '<div class="wrm-pf"><label>Link Google Maps</label><input type="url" name="wrm_branches['+i+'][maps_url]" placeholder="https://maps.google.com/... "></div>'+
        '<div class="wrm-pf"><label class="wrm-switch"><input type="checkbox" name="wrm_branches['+i+'][is_active]" value="1" checked> Local activo</label></div>'+
      '</div>'+
    '</div>';
    $('#wrm-branches-wrap').append(html);
  });
});
</script>
<?php
}

add_action('wrm_category_add_form_fields','wrm_cat_icon_field');
add_action('wrm_category_edit_form_fields','wrm_cat_icon_field_edit',10,2);
function wrm_cat_icon_field(){?>
<div class="form-field">
  <label>Emoji / Ícono</label>
  <input type="text" name="wrm_cat_icon" value="" placeholder="🍔">
  <p class="description">Ícono que aparece en la pestaña de esta categoría.</p>
</div>
<?php }
function wrm_cat_icon_field_edit($term){
    $ico=get_term_meta($term->term_id,'wrm_cat_icon',true);?>
<tr class="form-field">
  <th><label>Emoji / Ícono</label></th>
  <td><input type="text" name="wrm_cat_icon" value="<?php echo esc_attr($ico)?>" placeholder="🍔">
  <p class="description">Ícono que aparece en la pestaña.</p></td>
</tr>
<?php }

add_action('created_wrm_category','wrm_save_cat_icon');
add_action('edited_wrm_category', 'wrm_save_cat_icon');
function wrm_save_cat_icon($tid){
    if(isset($_POST['wrm_cat_icon']))
        update_term_meta($tid,'wrm_cat_icon',sanitize_text_field($_POST['wrm_cat_icon']));
}
