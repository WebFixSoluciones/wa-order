<?php
if(!defined('ABSPATH'))exit;

add_shortcode('wrm_menu','wrm_menu_shortcode');
function wrm_menu_shortcode($atts=[]){
    $atts = shortcode_atts(['category'=>'','cols'=>'auto','show_search'=>'1'],$atts);

    /* ── Obtener categorías ─────────────────────── */
    $cats = get_terms(['taxonomy'=>'wrm_category','hide_empty'=>true,'orderby'=>'menu_order','order'=>'ASC']);
    if(is_wp_error($cats)||empty($cats)){
        /* Sin categorías: mostrar todos */
        $cats = [];
    }

    ob_start();
    ?>
<div class="wrm-menu-wrap" id="wrm-menu-wrap">

  <?php if(!empty($cats)): ?>
  <!-- Filtros de categoría -->
  <div class="wrm-cat-tabs" id="wrm-cat-tabs" role="tablist">
    <button class="wrm-cat-tab active" data-cat="all" role="tab" aria-selected="true">
      <span class="wrm-cat-icon">🍽️</span> Todo
    </button>
    <?php foreach($cats as $c):
        $ico = get_term_meta($c->term_id,'wrm_cat_icon',true) ?: '🍴';
    ?>
    <button class="wrm-cat-tab" data-cat="<?php echo esc_attr($c->slug)?>" role="tab" aria-selected="false">
      <span class="wrm-cat-icon"><?php echo esc_html($ico)?></span>
      <?php echo esc_html($c->name)?>
    </button>
    <?php endforeach;?>
  </div>
  <?php endif;?>

  <!-- Buscador -->
  <?php if($atts['show_search']==='1'):?>
  <div class="wrm-search-wrap">
    <svg class="wrm-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="search" class="wrm-search" id="wrm-search" placeholder="Buscar en el menú…" aria-label="Buscar">
    <button class="wrm-search-clear" id="wrm-search-clear" aria-label="Limpiar">×</button>
  </div>
  <?php endif;?>

  <!-- Grid de productos -->
  <div class="wrm-items-grid" id="wrm-items-grid">
  <?php
    /* ── Query de ítems ─────────────────────────── */
    $q_args = [
        'post_type'      =>'wrm_item',
        'posts_per_page' =>-1,
        'post_status'    =>'publish',
        'meta_query'     =>[ ['key'=>'_wrm_featured','compare'=>'EXISTS'] ],
        'orderby'        =>['meta_value'=>'DESC','menu_order'=>'ASC','date'=>'DESC'],
    ];
    if($atts['category']) $q_args['tax_query']=[['taxonomy'=>'wrm_category','field'=>'slug','terms'=>explode(',',$atts['category'])]];

    $items = new WP_Query($q_args);
    $opts = get_option('wrm_settings',[]);
    $cur  = $opts['currency'] ?? '$';
    
    $html_promos = '';
    $html_grid   = '';

    while($items->have_posts()): $items->the_post();
        $id       = get_the_ID();
        $price    = (float)(get_post_meta($id,'_wrm_price',true)   ?: 0);
        $old      = (float)(get_post_meta($id,'_wrm_price_old',true) ?: 0);
        $badge    = get_post_meta($id,'_wrm_badge',true);
        $avail    = get_post_meta($id,'_wrm_available',true);
        $avail    = $avail==='' ? true : (bool)(int)$avail;
        $featured = get_post_meta($id,'_wrm_featured',true)==='1';
        $variants = get_post_meta($id,'_wrm_variants',true) ?: [];
        $extras   = get_post_meta($id,'_wrm_extras',true)   ?: [];
        $item_cats= wp_get_post_terms($id,'wrm_category',['fields'=>'slugs']);
        $cats_str = implode(' ',$item_cats);
        $img      = get_the_post_thumbnail_url($id,'medium') ?: '';
        $desc     = get_the_excerpt() ?: wp_trim_words(get_the_content(), 15, '…');

        /* Es promocional si es destacado (estrella) o el badge dice oferta/promo/nuevo */
        $b_lower = strtolower((string)$badge);
        $is_promo = $featured || (strpos($b_lower, 'oferta') !== false) || (strpos($b_lower, 'promo') !== false) || (strpos($b_lower, 'nuevo') !== false);

        /* ── Grid normal ────────────────────────────── */
        ob_start();
  ?>
  <div class="wrm-item-card <?php echo $avail?'':'wrm-unavailable'?> <?php echo $featured?'wrm-featured':''?>"
       data-id="<?php echo $id?>"
       data-cats="all <?php echo esc_attr($cats_str)?>"
       data-title="<?php echo esc_attr(get_the_title())?>"
       data-price="<?php echo esc_attr($price)?>"
       data-has-variants="<?php echo count($variants)?'1':'0'?>"
       data-has-extras="<?php echo count($extras)?'1':'0'?>">

    <div class="wrm-item-img-wrap" <?php if(!$img):?>style="display:none"<?php endif;?>>
      <?php if($img):?><img src="<?php echo esc_url($img)?>" alt="<?php echo esc_attr(get_the_title())?>" loading="lazy" width="300" height="200"><?php endif;?>
      <?php if($badge):?><span class="wrm-item-badge"><?php echo esc_html($badge)?></span><?php endif;?>
      <?php if($featured):?><span class="wrm-item-featured-badge">🔥</span><?php endif;?>
      <?php if(!$avail):?><div class="wrm-item-unavailable-overlay"><span>No disponible</span></div><?php endif;?>
    </div>

    <div class="wrm-item-body">
      <h3 class="wrm-item-title"><?php the_title()?></h3>
      <?php if($desc):?><p class="wrm-item-desc"><?php echo esc_html($desc)?></p><?php endif;?>

      <div class="wrm-item-footer">
        <div class="wrm-item-price-wrap">
          <span class="wrm-item-price"><?php echo esc_html($cur).number_format($price,2)?></span>
          <?php if($old>0):?>
          <span class="wrm-item-price-old"><?php echo esc_html($cur).number_format($old,2)?></span>
          <?php endif;?>
        </div>
        <?php if($avail):?>
        <button class="wrm-add-btn"
                data-id="<?php echo $id?>"
                data-title="<?php echo esc_attr(get_the_title())?>"
                data-price="<?php echo esc_attr($price)?>"
                data-img="<?php echo esc_attr($img)?>"
                data-variants='<?php echo esc_attr(wp_json_encode($variants))?>'
                data-extras='<?php echo esc_attr(wp_json_encode($extras))?>'
                aria-label="Agregar <?php the_title()?>">
          <?php echo (count($variants)||count($extras)) ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Agregar' : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Agregar'?>
        </button>
        <?php else:?>
        <span class="wrm-item-out">No disponible</span>
        <?php endif;?>
      </div>
    </div>
  </div>
  <?php 
        $html_grid .= ob_get_clean();

        /* ── Tarjeta Promocional Horizontal ─────────── */
        if($is_promo && $avail){
            ob_start();
            ?>
            <div class="wrm-promo-card">
              <div class="wrm-promo-img-wrap" <?php if(!$img):?>style="display:none"<?php endif;?>>
                <?php if($img):?><img src="<?php echo esc_url($img)?>" alt="<?php echo esc_attr(get_the_title())?>" loading="lazy"><?php endif;?>
                <?php if($badge):?><span class="wrm-item-badge"><?php echo esc_html($badge)?></span><?php endif;?>
                <?php if($featured):?><span class="wrm-item-featured-badge">🔥</span><?php endif;?>
              </div>
              <div class="wrm-promo-body">
                <h3 class="wrm-promo-title"><?php the_title()?></h3>
                <div class="wrm-promo-bottom">
                  <div class="wrm-item-price-wrap">
                    <span class="wrm-item-price"><?php echo esc_html($cur).number_format($price,2)?></span>
                    <?php if($old>0):?><span class="wrm-item-price-old"><?php echo esc_html($cur).number_format($old,2)?></span><?php endif;?>
                  </div>
                  <button class="wrm-add-btn wrm-promo-add-btn" 
                          data-id="<?php echo $id?>"
                          data-title="<?php echo esc_attr(get_the_title())?>"
                          data-price="<?php echo esc_attr($price)?>"
                          data-img="<?php echo esc_attr($img)?>"
                          data-variants='<?php echo esc_attr(wp_json_encode($variants))?>'
                          data-extras='<?php echo esc_attr(wp_json_encode($extras))?>'>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                  </button>
                </div>
              </div>
            </div>
            <?php
            $html_promos .= ob_get_clean();
        }

    endwhile; wp_reset_postdata();
  ?>

  <!-- Sección: Promociones / Destacados -->
  <?php if($html_promos): ?>
  <div class="wrm-promos-wrap" id="wrm-promos-wrap">
    <h2 class="wrm-promos-heading">Artículos destacados</h2>
    <div class="wrm-promos-scroll">
      <?php echo $html_promos; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Grid de productos -->
  <div class="wrm-items-grid" id="wrm-items-grid">
    <?php echo $html_grid; ?>
  </div><!-- /.wrm-items-grid -->

  <div class="wrm-no-results" id="wrm-no-results" style="display:none">
    <p>😕 No encontramos nada con esa búsqueda.</p>
  </div>
</div><!-- /.wrm-menu-wrap -->

<!-- ──────────────────────────────────────────── -->
<!--  MODAL: Agregar producto               -->
<!-- ──────────────────────────────────────────── -->
<div class="wrm-modal-overlay" id="wrm-product-modal" role="dialog" aria-modal="true" aria-label="Agregar producto" style="display:none">
  <div class="wrm-modal-box">
    <button class="wrm-modal-close" id="wrm-modal-close" aria-label="Cerrar">×</button>
    <div id="wrm-modal-content"></div>
  </div>
</div>

<!-- ──────────────────────────────────────────── -->
<!--  CARRITO POPUP                              -->
<!-- ──────────────────────────────────────────── -->
<div class="wrm-cart-overlay" id="wrm-cart-overlay" aria-hidden="true"></div>
<div class="wrm-cart-popup" id="wrm-cart-popup" role="dialog" aria-modal="true" aria-label="Tu pedido" aria-hidden="true">
  <div class="wrm-cart-header">
    <div class="wrm-cart-header-left">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
      <span>Tu pedido</span>
    </div>
    <button class="wrm-cart-close" id="wrm-cart-close" aria-label="Cerrar carrito">×</button>
  </div>

  <div class="wrm-cart-body" id="wrm-cart-body">
    <div class="wrm-cart-empty" id="wrm-cart-empty">
      <div class="wrm-cart-empty-icon">🛒</div>
      <p>Tu carrito está vacío</p>
      <span>Agrega productos del menú para comenzar</span>
    </div>
    <div class="wrm-cart-items" id="wrm-cart-items"></div>
  </div>

  <div class="wrm-cart-footer" id="wrm-cart-footer" style="display:none">
    <div class="wrm-cart-total-row">
      <span>Total</span>
      <strong class="wrm-cart-total" id="wrm-cart-total">$0.00</strong>
    </div>

    <!-- SELECTOR ENTREGA / RECOGIDA -->
    <div class="wrm-delivery-selector" id="wrm-delivery-block">
      <h4>¿Cómo quieres tu pedido?</h4>
      <div class="wrm-delivery-opts">
        <div class="wrm-delivery-opt" id="wrm-opt-pickup" onclick="WRMDelivery.select('pickup')">
          <span class="icon">🏪</span>
          <div class="label">Recoger en local</div>
          <div class="sublabel">Sin costo extra</div>
        </div>
        <div class="wrm-delivery-opt" id="wrm-opt-delivery" onclick="WRMDelivery.select('delivery')">
          <span class="icon">🛵</span>
          <div class="label">Delivery</div>
          <div class="sublabel" id="wrm-fee-sublabel">A domicilio</div>
        </div>
      </div>

      <div class="wrm-branch-select-wrap" id="wrm-pickup-branch-wrap" style="display:none;margin:.55rem 0 .45rem;">
        <div class="wrm-branch-mini-title" style="font-size:.78rem;font-weight:800;letter-spacing:.03em;text-transform:uppercase;color:#6b7280;margin-bottom:.45rem">Selecciona el local</div>
        <div class="wrm-branch-cards" id="wrm-pickup-branch-cards" style="display:flex;gap:.55rem;overflow:auto;padding-bottom:.2rem"></div>
      </div>

      <!-- Confirmación recogida -->
      <div class="wrm-pickup-confirm" id="wrm-pickup-confirm">
        <span>✅</span>
        <span>Recoge en: <strong id="wrm-pickup-addr">—</strong></span>
      </div>

      <div class="wrm-branch-select-wrap" id="wrm-delivery-branch-wrap" style="display:none;margin:.55rem 0 .45rem;">
        <div class="wrm-branch-mini-title" style="font-size:.78rem;font-weight:800;letter-spacing:.03em;text-transform:uppercase;color:#6b7280;margin-bottom:.45rem">Selecciona el local que despacha</div>
        <div class="wrm-branch-cards" id="wrm-delivery-branch-cards" style="display:flex;gap:.55rem;overflow:auto;padding-bottom:.2rem"></div>
      </div>

      <!-- Formulario delivery (oculto hasta elegir) -->
      <div class="wrm-delivery-form-wrap" id="wrm-delivery-form-wrap">
        <div class="wrm-delivery-form">
          <h4>📍 Datos de envío <span class="wrm-req-note">* Obligatorios</span></h4>
          <div class="wrm-form-row">
            <label>Nombre completo <span class="req">*</span></label>
            <input type="text" id="wrm-d-name" placeholder="Ana García" autocomplete="name">
          </div>
          <div class="wrm-form-row">
            <label>Teléfono / WhatsApp <span class="req">*</span></label>
            <input type="tel" id="wrm-d-phone" placeholder="+593 99 999 9999" autocomplete="tel">
          </div>
          <div class="wrm-form-row">
            <label>Dirección <span class="req">*</span></label>
            <input type="text" id="wrm-d-address" placeholder="Av. Principal 123" autocomplete="street-address">
          </div>
          <div class="wrm-form-row-2">
            <div>
              <label>Sector / Barrio</label>
              <input type="text" id="wrm-d-sector" placeholder="La Mariscal">
            </div>
            <div>
              <label>Referencias</label>
              <input type="text" id="wrm-d-ref" placeholder="Casa azul, piso 3…">
            </div>
          </div>
          <input type="hidden" id="wrm-d-city" value="">
          <div class="wrm-delivery-fee-line" id="wrm-fee-line" style="display:none">
            <span>🛵 Costo de envío</span>
            <strong id="wrm-fee-amount">—</strong>
          </div>
        </div>
      </div>
    </div>
    <!-- /SELECTOR -->

    <button class="wrm-wa-order-btn" id="wrm-wa-send-btn" disabled>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M11.997 4C7.58 4 4 7.582 4 12a7.97 7.97 0 001.224 4.274l-.788 2.869 2.943-.758A7.97 7.97 0 0012 20c4.418 0 8-3.582 8-8s-3.582-8-8.003-8z"/></svg>
      <span>Elige cómo recibirás tu pedido</span>
    </button>
  </div>
</div>

<!-- FAB Carrito -->
<button class="wrm-cart-fab" id="wrm-cart-fab" aria-label="Ver carrito">
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
  <span class="wrm-fab-badge" id="wrm-fab-badge" style="display:none">0</span>
</button>

<?php
    return ob_get_clean();
}
