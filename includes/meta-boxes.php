<?php
if(!defined('ABSPATH'))exit;

add_action('add_meta_boxes','wrm_add_meta_boxes');
function wrm_add_meta_boxes(){
    add_meta_box('wrm_item_data','💰 Datos del producto','wrm_render_item_meta','wrm_item','normal','high');
}

function wrm_render_item_meta($post){
    wp_nonce_field('wrm_item_meta','wrm_item_nonce');
    $price    = get_post_meta($post->ID,'_wrm_price',true);
    $old      = get_post_meta($post->ID,'_wrm_price_old',true);
    $badge    = get_post_meta($post->ID,'_wrm_badge',true);
    $avail    = get_post_meta($post->ID,'_wrm_available',true);
    $featured = get_post_meta($post->ID,'_wrm_featured',true);
    $variants = get_post_meta($post->ID,'_wrm_variants',true) ?: [];
    $extras   = get_post_meta($post->ID,'_wrm_extras',true)   ?: [];
    $avail    = $avail==='' ? '1' : $avail;
    ?>
<style>
.wrm-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem}
.wrm-meta-f{display:flex;flex-direction:column;gap:.3rem}
.wrm-meta-f label{font-weight:700;font-size:.8rem;color:#374151}
.wrm-meta-f input,.wrm-meta-f select{padding:.45rem .7rem;border:1.5px solid #d1d5db;border-radius:6px;font-size:.875rem}
.wrm-meta-f input:focus{outline:none;border-color:#25d366}
.wrm-var-row,.wrm-ext-row{display:grid;grid-template-columns:1fr 1fr auto;gap:.5rem;align-items:center;margin-bottom:.4rem}
.wrm-add-btn{background:#25d366;color:#fff;border:none;padding:.35rem .9rem;border-radius:6px;cursor:pointer;font-size:.8rem;font-weight:700}
.wrm-del-btn{background:#ef4444;color:#fff;border:none;width:28px;height:28px;border-radius:6px;cursor:pointer;font-weight:700}
.wrm-section-title{font-weight:700;color:#111827;margin:1rem 0 .5rem;font-size:.875rem;border-bottom:1px solid #e5e7eb;padding-bottom:.3rem}
</style>

<div class="wrm-meta-grid">
  <div class="wrm-meta-f"><label>💲 Precio *</label><input type="number" name="wrm_price" value="<?php echo esc_attr($price)?>" step="0.01" min="0" placeholder="0.00"></div>
  <div class="wrm-meta-f"><label>🏷️ Precio anterior (tachado)</label><input type="number" name="wrm_price_old" value="<?php echo esc_attr($old)?>" step="0.01" min="0" placeholder="0.00"></div>
  <div class="wrm-meta-f"><label>⭐ Badge / Etiqueta</label>
    <select name="wrm_badge">
      <option value="" <?php selected($badge,'')?>>Sin etiqueta</option>
      <?php 
      $tags = get_terms(['taxonomy' => 'wrm_tag', 'hide_empty' => false]);
      if(!is_wp_error($tags) && !empty($tags)){
          foreach($tags as $t):
      ?>
      <option value="<?php echo esc_attr($t->name)?>" <?php selected($badge,$t->name)?>><?php echo esc_html($t->name)?></option>
      <?php 
          endforeach; 
      }
      ?>
    </select>
  </div>
  <div class="wrm-meta-f">
    <label>✅ Disponibilidad</label>
    <select name="wrm_available">
      <option value="1" <?php selected($avail,'1')?>>Disponible</option>
      <option value="0" <?php selected($avail,'0')?>>No disponible</option>
    </select>
  </div>
  <div class="wrm-meta-f" style="grid-column:1/-1">
    <label><input type="checkbox" name="wrm_featured" value="1" <?php checked($featured,'1')?>> 🔥 Destacado (aparece primero)</label>
  </div>
</div>

<!-- Variantes -->
<div class="wrm-section-title">🔀 Variantes (ej: Pequeño $3, Grande $5)</div>
<div id="wrm-variants-wrap">
<?php foreach($variants as $i=>$v): ?>
<div class="wrm-var-row">
  <input type="text"   name="wrm_variant_name[]"  value="<?php echo esc_attr($v['name']??'')?>"  placeholder="Nombre (ej: Grande)">
  <input type="number" name="wrm_variant_price[]" value="<?php echo esc_attr($v['price']??'')?>" placeholder="Precio" step="0.01" min="0">
  <button type="button" class="wrm-del-btn" onclick="this.parentNode.remove()">×</button>
</div>
<?php endforeach;?>
</div>
<button type="button" class="wrm-add-btn" onclick="wrmAddRow('variants')">+ Variante</button>

<!-- Extras -->
<div class="wrm-section-title">➕ Extras / Complementos (ej: Queso extra $0.50)</div>
<div id="wrm-extras-wrap">
<?php foreach($extras as $i=>$e): ?>
<div class="wrm-ext-row">
  <input type="text"   name="wrm_extra_name[]"  value="<?php echo esc_attr($e['name']??'')?>"  placeholder="Nombre (ej: Queso extra)">
  <input type="number" name="wrm_extra_price[]" value="<?php echo esc_attr($e['price']??'')?>" placeholder="Precio" step="0.01" min="0">
  <button type="button" class="wrm-del-btn" onclick="this.parentNode.remove()">×</button>
</div>
<?php endforeach;?>
</div>
<button type="button" class="wrm-add-btn" onclick="wrmAddRow('extras')">+ Extra</button>

<script>
function wrmAddRow(type){
  var w=document.getElementById('wrm-'+type+'-wrap');
  var p=type==='variants'?'Nombre':'Nombre';
  var row=document.createElement('div');
  row.className='wrm-'+(type==='variants'?'var':'ext')+'-row';
  row.innerHTML='<input type="text" name="wrm_'+type.slice(0,-1)+'_name[]" placeholder="'+p+'">'
    +'<input type="number" name="wrm_'+type.slice(0,-1)+'_price[]" placeholder="Precio" step="0.01" min="0">'
    +'<button type="button" class="wrm-del-btn" onclick="this.parentNode.remove()">×</button>';
  w.appendChild(row);
}
</script>
<?php
}

add_action('save_post_wrm_item','wrm_save_item_meta');
function wrm_save_item_meta($pid){
    if(!isset($_POST['wrm_item_nonce'])||!wp_verify_nonce($_POST['wrm_item_nonce'],'wrm_item_meta'))return;
    if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return;
    if(!current_user_can('edit_post',$pid))return;

    update_post_meta($pid,'_wrm_price',     sanitize_text_field($_POST['wrm_price']??''));
    update_post_meta($pid,'_wrm_price_old', sanitize_text_field($_POST['wrm_price_old']??''));
    update_post_meta($pid,'_wrm_badge',     sanitize_text_field($_POST['wrm_badge']??''));
    update_post_meta($pid,'_wrm_available', sanitize_text_field($_POST['wrm_available']??'1'));
    update_post_meta($pid,'_wrm_featured',  isset($_POST['wrm_featured'])?'1':'0');

    $vnames  = array_map('sanitize_text_field', $_POST['wrm_variant_name']  ?? []);
    $vprices = array_map('sanitize_text_field', $_POST['wrm_variant_price'] ?? []);
    $variants = [];
    foreach($vnames as $i=>$n){
        if(trim($n)==='')continue;
        $variants[] = ['name'=>$n,'price'=>$vprices[$i]??'0'];
    }
    update_post_meta($pid,'_wrm_variants',$variants);

    $enames  = array_map('sanitize_text_field', $_POST['wrm_extra_name']  ?? []);
    $eprices = array_map('sanitize_text_field', $_POST['wrm_extra_price'] ?? []);
    $extras = [];
    foreach($enames as $i=>$n){
        if(trim($n)==='')continue;
        $extras[] = ['name'=>$n,'price'=>$eprices[$i]??'0'];
    }
    update_post_meta($pid,'_wrm_extras',$extras);
}
