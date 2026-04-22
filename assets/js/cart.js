(function($){
'use strict';
var cart = [];
var deliveryMode = null;
var selectedPickupBranch = null;
var selectedDeliveryBranch = null;
var selectedPickupBranchId = null;
var selectedDeliveryBranchId = null;
function cur(){ return (window.WRMCart && WRMCart.currency) ? WRMCart.currency : '$'; }
function fmt(n){ return cur() + parseFloat(n).toFixed(2); }
function getDeliveryFeeValue(){
  if(!(window.WRMCart && WRMCart.delivery_fee)) return 0;
  var fee = parseFloat(String(WRMCart.delivery_fee).replace(/[^0-9.]/g,''));
  return isNaN(fee) ? 0 : fee;
}
function getTotal(){
  var t = cart.reduce(function(s,i){ return s + (i.price * i.qty); }, 0);
  if(deliveryMode==='delivery') t += getDeliveryFeeValue();
  return t;
}
function getMatrix(){ return (window.WRMCart && WRMCart.matrix) ? WRMCart.matrix : null; }
function getBranches(){ return (window.WRMCart && Array.isArray(WRMCart.branches)) ? WRMCart.branches : []; }
function getAvailableLocations(){
  var arr = [];
  var matrix = getMatrix();
  if(matrix && (matrix.is_active === '1' || matrix.is_active === 1 || matrix.is_active === true)){
    arr.push(matrix);
  }
  return arr.concat(getBranches());
}
function locationLabel(loc){
  if(!loc) return 'Consultar';
  var base = (loc.type ? loc.type + ': ' : '') + (loc.name || loc.address || 'Local');
  if(loc.coverage) base += ' · Cobertura: ' + loc.coverage;
  return base;
}
function locationAddressLine(loc){
  if(!loc) return 'Consultar';
  return loc.address || 'Consultar';
}
function currentWA(){
  if(deliveryMode==='pickup' && selectedPickupBranch && selectedPickupBranch.whatsapp) return selectedPickupBranch.whatsapp;
  if(deliveryMode==='delivery' && selectedDeliveryBranch && selectedDeliveryBranch.whatsapp) return selectedDeliveryBranch.whatsapp;
  var matrix = getMatrix();
  if(matrix && matrix.whatsapp) return matrix.whatsapp;
  return (window.WRMCart && WRMCart.whatsapp) ? WRMCart.whatsapp : '';
}
function buildLocationCards(containerId, mode){
  var locations = getAvailableLocations();
  var $wrap = $(containerId);
  $wrap.empty();
  locations.forEach(function(loc, idx){
    var title = (loc.name || 'Local');
    var chip = (loc.type || (idx === 0 ? 'Matriz' : 'Sucursal'));
    var sub = loc.coverage ? String(loc.coverage) : '';
    var card = $('<button type="button" class="wrm-branch-card-mini" data-mode="'+mode+'" data-idx="'+idx+'">'+
      '<span class="wrm-branch-name is-bubble">'+title+'</span>'+
      (sub ? '<span class="wrm-branch-sub">'+sub+'</span>' : '')+
      '</button>');
    $wrap.append(card);
  });
}
function markActiveBranchCard(mode, idx){
  $('.wrm-branch-card-mini').removeClass('active');
  $('.wrm-branch-card-mini').filter(function(){
    return String($(this).data('mode')) === String(mode) && String($(this).data('idx')) === String(idx);
  }).addClass('active');
}
function refreshBranchOverflowHints(){
  ['pickup','delivery'].forEach(function(mode){
    var $cards = mode === 'pickup' ? $('#wrm-pickup-branch-cards') : $('#wrm-delivery-branch-cards');
    var $hint = mode === 'pickup' ? $('#wrm-pickup-branch-hint') : $('#wrm-delivery-branch-hint');
    if(!$cards.length || !$hint.length) return;
    var hasOverflow = $cards.get(0).scrollWidth > $cards.innerWidth() + 8;
    $hint.toggleClass('has-more', !!hasOverflow);
  });
}


function getSelectedLocationCity(mode){
  var loc = mode === 'pickup' ? selectedPickupBranch : selectedDeliveryBranch;
  return loc && loc.city ? String(loc.city).trim() : '';
}

function syncSelectedLocation(mode, forcedIdx){
  var locations = getAvailableLocations();
  if(!locations.length) return;
  if(mode === 'pickup'){
    var idx = typeof forcedIdx !== 'undefined' ? forcedIdx : locations.findIndex(function(loc){ return selectedPickupBranchId !== null && String(loc.id) === String(selectedPickupBranchId); });
    if(idx < 0) idx = 0;
    selectedPickupBranch = locations[idx] || locations[0];
    selectedPickupBranchId = selectedPickupBranch && typeof selectedPickupBranch.id !== 'undefined' ? selectedPickupBranch.id : idx;
    markActiveBranchCard('pickup', locations.indexOf(selectedPickupBranch));
    $('#wrm-pickup-addr').text(locationAddressLine(selectedPickupBranch) || locationLabel(selectedPickupBranch));
  } else {
    var didx = typeof forcedIdx !== 'undefined' ? forcedIdx : locations.findIndex(function(loc){ return selectedDeliveryBranchId !== null && String(loc.id) === String(selectedDeliveryBranchId); });
    if(didx < 0) didx = 0;
    selectedDeliveryBranch = locations[didx] || locations[0];
    selectedDeliveryBranchId = selectedDeliveryBranch && typeof selectedDeliveryBranch.id !== 'undefined' ? selectedDeliveryBranch.id : didx;
    markActiveBranchCard('delivery', locations.indexOf(selectedDeliveryBranch));
    $('#wrm-d-city').val(getSelectedLocationCity('delivery'));
  }
}

function ensureCleanBranchStyles(){
  if(document.getElementById('wrm-branch-clean-styles')) return;
  var css = ''+
    '.wrm-branch-select-wrap{margin:.7rem 0 .5rem;}'+
    '.wrm-branch-mini-title{font-size:.76rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;margin:0 0 .42rem;}'+
    '.wrm-branch-cards{display:flex;gap:.55rem;overflow-x:auto;overflow-y:hidden;padding:.05rem 0 .3rem;scrollbar-width:thin;scroll-snap-type:x proximity;-webkit-overflow-scrolling:touch;}'+
    '.wrm-branch-cards::after{content:"";flex:0 0 .15rem;}'+
    '.wrm-branch-cards::-webkit-scrollbar{height:6px;}'+
    '.wrm-branch-cards::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:999px;}'+
    '.wrm-branch-cards::-webkit-scrollbar-track{background:transparent;}'+
    '.wrm-branch-card-mini{flex:0 0 166px;min-width:166px;max-width:188px;text-align:left;background:#fff;border:1px solid #d7dce2;border-radius:16px;padding:.78rem .8rem;display:flex;flex-direction:column;gap:.22rem;transition:border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;box-shadow:0 1px 2px rgba(17,24,39,.04);scroll-snap-align:start;}'+
    '.wrm-branch-card-mini .wrm-branch-name{font-size:.78rem;font-weight:700;line-height:1.05;color:#166534;margin:0;text-transform:uppercase;letter-spacing:.03em;}'+
    '.wrm-branch-card-mini .wrm-branch-name.is-bubble{display:inline-flex;align-self:flex-start;background:#dcfce7;border:1px solid #bbf7d0;border-radius:999px;padding:.34rem .56rem;}'+
    '.wrm-branch-card-mini .wrm-branch-sub{font-size:.74rem;line-height:1.25;color:#6b7280;margin:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}'+
        '.wrm-branch-card-mini:hover{border-color:#c8d0d8;transform:translateY(-1px);}'+
    '.wrm-branch-card-mini.active{border-color:#22c55e;background:#f0fdf4;box-shadow:0 0 0 2px rgba(34,197,94,.10);}'+
    '.wrm-branch-card-mini.active .wrm-branch-name{color:#166534;}'+
    '.wrm-branch-scroll-hint{display:none;font-size:.7rem;line-height:1.2;color:#9ca3af;margin:.15rem 0 0;text-align:right;}'+
    '.wrm-delivery-modal{position:fixed;inset:0;z-index:100001;display:none;}'+
    '.wrm-delivery-modal.open{display:block;}'+
    '.wrm-delivery-modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.52);backdrop-filter:blur(4px);}'+
    '.wrm-delivery-modal-panel{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:min(94vw,440px);max-height:min(90vh,680px);overflow-y:auto;overflow-x:hidden;background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(15,23,42,.22);padding:.75rem .75rem .8rem;border:1px solid rgba(0,0,0,.06);}'+
    '.wrm-delivery-modal-head{display:flex;align-items:center;justify-content:space-between;gap:.6rem;margin-bottom:0;padding-bottom:.5rem;border-bottom:1px solid #f0f2f5;}'+
    '.wrm-delivery-modal-head-left{display:flex;align-items:center;gap:.55rem;}'+
    '.wrm-delivery-modal-icon{font-size:1.5rem;flex-shrink:0;width:38px;height:38px;display:flex;align-items:center;justify-content:center;background:#f0fdf4;border-radius:10px;}'+
    '.wrm-delivery-modal-title{font-size:.92rem;font-weight:800;line-height:1.15;color:#111827;margin:0;}'+
    '.wrm-delivery-modal-sub{font-size:.7rem;line-height:1.3;color:#9ca3af;margin:.1rem 0 0;}'+
    '.wrm-delivery-modal-summary{display:flex;align-items:center;justify-content:space-between;padding:.55rem .7rem;margin:.5rem 0 .6rem;background:#f8fafc;border:1px solid #e9ecf0;border-radius:10px;font-size:.78rem;color:#374151;}'+
    '.wrm-dms-left{display:flex;align-items:center;gap:.35rem;font-weight:600;}'+
    '.wrm-dms-icon{font-size:.95rem;}'+
    '.wrm-dms-right{font-weight:600;}'+
    '.wrm-dms-label{color:#6b7280;font-weight:500;}'+
    '.wrm-dms-right strong{color:#111827;font-size:.88rem;font-weight:800;margin-left:.2rem;}'+
    '.wrm-delivery-modal-close{width:32px;height:32px;border-radius:50%;background:#f3f4f6;border:none;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#6b7280;flex:0 0 32px;cursor:pointer;transition:background .15s;}'+
    '.wrm-delivery-modal-close:hover{background:#e5e7eb;color:#111827;}'+
    '.wrm-delivery-modal-body-inner{background:#fafbfc;border:1px solid #eef0f3;border-radius:14px;padding:.6rem .6rem .55rem;}'+
    '.wrm-delivery-modal .wrm-delivery-form{padding-top:0;}'+
    '.wrm-delivery-modal .wrm-delivery-form h4{font-size:.76rem;font-weight:700;color:#166534;margin-bottom:.55rem;padding:.44rem .6rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;display:flex;align-items:center;gap:.35rem;}'+
    '.wrm-delivery-modal .wrm-req-note{font-size:.68rem;color:#ef4444;font-weight:600;}'+
    '.wrm-delivery-modal .wrm-form-row,.wrm-delivery-modal .wrm-form-row-2{margin-bottom:.45rem;}'+
    '.wrm-delivery-modal .wrm-form-row label,.wrm-delivery-modal .wrm-form-row-2>div>label{font-size:.7rem;font-weight:700;letter-spacing:.01em;color:#4b5563;margin-bottom:.2rem;display:block;}'+
    '.wrm-delivery-modal .wrm-form-row label .req{color:#ef4444;margin-left:.15rem;}'+
    '.wrm-delivery-modal .wrm-form-row input,.wrm-delivery-modal .wrm-form-row textarea,.wrm-delivery-modal .wrm-form-row-2 input{width:100%;padding:.48rem .62rem;border:1.5px solid #e2e6ea;border-radius:9px;font-size:.82rem;background:#fff;color:#111827;box-sizing:border-box;transition:border-color .15s,box-shadow .15s;}'+
    '.wrm-delivery-modal .wrm-form-row input::placeholder,.wrm-delivery-modal .wrm-form-row textarea::placeholder,.wrm-delivery-modal .wrm-form-row-2 input::placeholder{color:#c4cad4;font-size:.8rem;}'+
    '.wrm-delivery-modal .wrm-form-row input:focus,.wrm-delivery-modal .wrm-form-row textarea:focus{outline:none;border-color:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.1);background:#fff;}'+
    '.wrm-delivery-modal .wrm-form-row input.error{border-color:#f87171;box-shadow:0 0 0 3px rgba(248,113,113,.1);background:#fff9f9;}'+
    '.wrm-delivery-modal .wrm-form-row textarea{resize:none;min-height:52px;max-height:80px;}'+
    '.wrm-delivery-modal .wrm-form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:.45rem;}'+
    '.wrm-delivery-modal .wrm-delivery-fee-line{margin-top:.15rem;padding:.44rem .6rem;border-radius:9px;background:#fffbeb;border:1px solid #fde68a;font-size:.76rem;display:flex;justify-content:space-between;align-items:center;}'+
    '.wrm-delivery-modal .wrm-wa-send-inline{margin-top:.65rem;width:100%;border:none;border-radius:12px;padding:.78rem .95rem;font-size:.88rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;background:linear-gradient(160deg,#22c55e 0%,#16a34a 100%);color:#fff;box-shadow:0 8px 20px rgba(34,197,94,.28);transition:opacity .15s,transform .1s;}'+
    '.wrm-delivery-modal .wrm-wa-send-inline:disabled{background:#d1d5db;box-shadow:none;cursor:not-allowed;opacity:.9;}'+
    '.wrm-delivery-modal .wrm-wa-send-inline:not(:disabled):hover{opacity:.93;transform:translateY(-1px);}'+
    '.wrm-delivery-modal .wrm-wa-send-inline:disabled{background:#d1d5db;color:#9ca3af;box-shadow:none;cursor:not-allowed;}'+
    '.wrm-delivery-modal .wrm-wa-send-inline:not(:disabled):hover{transform:translateY(-1px);box-shadow:0 16px 32px rgba(34,197,94,.32);}'+
    '@media (max-width:480px){.wrm-delivery-modal-panel{width:min(97vw,440px);padding:.65rem .65rem .7rem;border-radius:18px;max-height:min(93vh,640px)}.wrm-delivery-modal .wrm-form-row-2{grid-template-columns:1fr}.wrm-delivery-modal .wrm-form-row input,.wrm-delivery-modal .wrm-form-row textarea{font-size:.85rem}}'+
    '.wrm-delivery-open-btn{width:100%;margin-top:.55rem;border-radius:14px;background:#fff;border:1px solid #d7dce2;padding:.9rem .95rem;font-weight:700;color:#111827;box-shadow:0 1px 2px rgba(17,24,39,.04);opacity:1!important;pointer-events:auto!important;}'+
    '.wrm-delivery-open-btn small{display:block;font-size:.78rem;font-weight:500;color:#6b7280;margin-top:.15rem;}'+
    '@media (min-width:768px){.wrm-branch-cards{flex-wrap:wrap;overflow:visible;padding-bottom:.05rem;scroll-snap-type:none}.wrm-branch-card-mini{flex:1 1 calc(33.333% - .4rem);min-width:0;max-width:none}.wrm-branch-scroll-hint{display:none}}'+
    '@media (max-width:767px){.wrm-branch-scroll-hint.has-more{display:block}}'+
    '@media (max-width:480px){.wrm-branch-card-mini{flex:0 0 154px;min-width:154px;max-width:170px;padding:.72rem .72rem}.wrm-branch-card-mini .wrm-branch-name{font-size:.74rem}.wrm-branch-card-mini .wrm-branch-sub{font-size:.72rem}}';
  var style = document.createElement('style');
  style.id = 'wrm-branch-clean-styles';
  style.textContent = css;
  document.head.appendChild(style);
}

function ensureDeliveryModal(){
  if($('#wrm-delivery-modal').length) return;
  var html = ''+
    '<div class="wrm-delivery-modal" id="wrm-delivery-modal" aria-hidden="true">'+
      '<div class="wrm-delivery-modal-backdrop" data-close-delivery></div>'+
      '<div class="wrm-delivery-modal-panel" role="dialog" aria-modal="true" aria-labelledby="wrm-delivery-modal-title">'+
        '<div class="wrm-delivery-modal-head">'+
          '<div class="wrm-delivery-modal-head-left">'+
            '<span class="wrm-delivery-modal-icon">🛵</span>'+
            '<div>'+
              '<h3 class="wrm-delivery-modal-title" id="wrm-delivery-modal-title">Datos de envío</h3>'+
              '<p class="wrm-delivery-modal-sub">Completa la información para enviar tu pedido por WhatsApp.</p>'+
            '</div>'+
          '</div>'+
          '<button type="button" class="wrm-delivery-modal-close" data-close-delivery aria-label="Cerrar">×</button>'+
        '</div>'+
        '<div class="wrm-delivery-modal-summary" id="wrm-delivery-modal-summary">'+
          '<div class="wrm-dms-left"><span class="wrm-dms-icon">🛒</span> <span id="wrm-dms-items">0 productos</span></div>'+
          '<div class="wrm-dms-right"><span class="wrm-dms-label">Total:</span> <strong id="wrm-dms-total">$0.00</strong></div>'+
        '</div>'+
        '<div class="wrm-delivery-modal-body-inner"><div id="wrm-delivery-modal-body"></div></div>'+
        '<button type="button" class="wrm-wa-send-btn wrm-wa-send-inline" id="wrm-wa-send-btn-delivery"><span>Ordenar por WhatsApp</span></button>'+
      '</div>'+
    '</div>';
  $('body').append(html);
}
function updateDeliveryModalSummary(){
  var totalItems = cart.reduce(function(s,i){ return s + i.qty; }, 0);
  var label = totalItems === 1 ? '1 producto' : totalItems + ' productos';
  $('#wrm-dms-items').text(label);
  $('#wrm-dms-total').text(fmt(getTotal()));
}
function openDeliveryModal(){
  ensureDeliveryModal();
  updateDeliveryModalSummary();
  var formHtml = $('#wrm-delivery-form-wrap .wrm-delivery-form').length ? $('#wrm-delivery-form-wrap .wrm-delivery-form').prop('outerHTML') : '';
  $('#wrm-delivery-modal-body').html(formHtml);
  // Inyectar selector de sucursal dentro del modal (encima del formulario)
  var $mb = $('#wrm-delivery-modal-body');
  var $branchWrap = $('#wrm-delivery-branch-wrap');
  if($branchWrap.length){
    var mini = $branchWrap.clone(true,true);
    mini.attr('id','wrm-delivery-branch-wrap-modal');
    mini.find('.wrm-branch-mini-title').text('Selecciona sucursal de despacho');
    mini.prependTo($mb);
  }
  // Renombrar IDs en el formulario del modal para evitar duplicados
  var fieldMap = {'wrm-d-name':'wrm-m-name','wrm-d-phone':'wrm-m-phone','wrm-d-address':'wrm-m-address','wrm-d-sector':'wrm-m-sector','wrm-d-city':'wrm-m-city','wrm-d-ref':'wrm-m-ref'};
  $.each(fieldMap, function(oldId, newId){
    $mb.find('#'+oldId).attr('id', newId).attr('name', newId);
    $mb.find('label[for="'+oldId+'"]').attr('for', newId);
  });
  // Copiar valores del base al modal
  $mb.find('#wrm-m-name').val($('#wrm-d-name').val()||'');
  $mb.find('#wrm-m-phone').val($('#wrm-d-phone').val()||'');
  $mb.find('#wrm-m-address').val($('#wrm-d-address').val()||'');
  $mb.find('#wrm-m-sector').val($('#wrm-d-sector').val()||'');
  $mb.find('#wrm-m-city').val(getSelectedLocationCity('delivery')||$('#wrm-d-city').val()||'');
  $mb.find('#wrm-m-ref').val($('#wrm-d-ref').val()||'');
  $('#wrm-delivery-modal').addClass('open').attr('aria-hidden','false');
  $('body').css('overflow','hidden');
  var feeHtml = $('#wrm-fee-line').length ? $('#wrm-fee-line').prop('outerHTML') : '';
  if(feeHtml && !$mb.find('#wrm-fee-line').length){ $mb.find('.wrm-delivery-form').append(feeHtml); }
  validateDeliveryModal();
}

function closeDeliveryModal(){
  var $form = $('#wrm-delivery-form-wrap');
  if($('#wrm-cart-footer').length){
    $form.insertBefore('#wrm-cart-footer').removeClass('open').hide();
  }
  $('#wrm-delivery-modal').removeClass('open').attr('aria-hidden','true');
  $('body').css('overflow','');
}
function ensureBranchSelectors(){
  if(!$('#wrm-pickup-branch-wrap').length && $('#wrm-pickup-confirm').length){
    $('<div class="wrm-branch-select-wrap" id="wrm-pickup-branch-wrap" style="display:none;margin:.55rem 0 .45rem;">'+
      '<div class="wrm-branch-mini-title" style="font-size:.8rem;font-weight:800;letter-spacing:.02em;text-transform:uppercase;color:#6b7280;margin-bottom:.5rem">Selecciona local</div>'+
      '<div class="wrm-branch-cards" id="wrm-pickup-branch-cards"></div>'+
      '<div class="wrm-branch-scroll-hint" id="wrm-pickup-branch-hint">Desliza para ver más locales →</div>'+
      '</div>').insertBefore('#wrm-pickup-confirm');
  }
  if(!$('#wrm-delivery-branch-wrap').length && $('#wrm-delivery-form-wrap').length){
    $('<div class="wrm-branch-select-wrap" id="wrm-delivery-branch-wrap" style="display:none;margin:.55rem 0 .45rem;">'+
      '<div class="wrm-branch-mini-title" style="font-size:.8rem;font-weight:800;letter-spacing:.02em;text-transform:uppercase;color:#6b7280;margin-bottom:.5rem">Selecciona local de despacho</div>'+
      '<div class="wrm-branch-cards" id="wrm-delivery-branch-cards"></div>'+
      '<div class="wrm-branch-scroll-hint" id="wrm-delivery-branch-hint">Desliza para ver más locales →</div>'+
      '<button type="button" class="wrm-delivery-open-btn" id="wrm-open-delivery-modal">Completar datos de delivery<small>Abre un formulario más cómodo con el botón de WhatsApp</small></button>'+
      '</div>').insertBefore('#wrm-delivery-form-wrap');
  }
}
function initBranchSelectors(){
  ensureCleanBranchStyles();
  ensureBranchSelectors();
  var locations = getAvailableLocations();
  if(!locations.length){ $('#wrm-pickup-branch-wrap').hide(); $('#wrm-delivery-branch-wrap').hide(); return; }
  buildLocationCards('#wrm-pickup-branch-cards', 'pickup');
  buildLocationCards('#wrm-delivery-branch-cards', 'delivery');
  $('#wrm-pickup-branch-wrap').hide();
  $('#wrm-delivery-branch-wrap').hide();
  if(selectedPickupBranchId === null){
    selectedPickupBranch = locations[0];
    selectedPickupBranchId = selectedPickupBranch && typeof selectedPickupBranch.id !== 'undefined' ? selectedPickupBranch.id : 0;
  }
  if(selectedDeliveryBranchId === null){
    selectedDeliveryBranch = locations[0];
    selectedDeliveryBranchId = selectedDeliveryBranch && typeof selectedDeliveryBranch.id !== 'undefined' ? selectedDeliveryBranch.id : 0;
  }
  syncSelectedLocation('pickup');
  syncSelectedLocation('delivery');
  refreshBranchOverflowHints();
}
function openCart(){ $('#wrm-cart-popup').addClass('open').attr('aria-hidden','false'); $('#wrm-cart-overlay').addClass('open'); $('body').css('overflow','hidden'); renderCart(); ensureCleanBranchStyles(); ensureBranchSelectors(); ensureDeliveryModal(); setTimeout(initBranchSelectors, 0); }
function closeCart(){ $('#wrm-cart-popup').removeClass('open').attr('aria-hidden','true'); $('#wrm-cart-overlay').removeClass('open'); $('body').css('overflow',''); }
function updateFAB(){ var total = cart.reduce(function(s,i){ return s+i.qty; },0); var $b = $('#wrm-fab-badge'); if(total > 0){ $b.text(total).show(); } else { $b.hide(); } }
function renderCart(){
  var $items = $('#wrm-cart-items'), $empty = $('#wrm-cart-empty'), $footer= $('#wrm-cart-footer');
  if(!cart.length){ $items.hide(); $empty.show(); $footer.hide(); updateFAB(); return; }
  $empty.hide(); $items.show(); $footer.show(); $items.empty();
  cart.forEach(function(item, idx){
    var imgHtml = item.img ? '<img class="wrm-cart-item-img" src="'+item.img+'" alt="'+item.title+'" loading="lazy">' : '<div class="wrm-cart-item-noimg">🍽️</div>';
    var sub = []; if(item.variant) sub.push(item.variant); if(item.extras && item.extras.length) sub.push(item.extras.join(', ')); if(item.notes) sub.push('📝 '+item.notes);
    var row = $('<div class="wrm-cart-item">'+imgHtml+'<div class="wrm-cart-item-info"><div class="wrm-cart-item-name">'+item.title+'</div>'+(sub.length ? '<div class="wrm-cart-item-sub">'+sub.join(' · ')+'</div>' : '')+'<div class="wrm-cart-item-controls"><button class="wrm-qty-dec" data-idx="'+idx+'">−</button><span class="wrm-cart-item-qty">'+item.qty+'</span><button class="wrm-qty-inc" data-idx="'+idx+'">+</button></div></div><div class="wrm-cart-item-price-col"><span class="wrm-cart-item-price">'+fmt(item.price*item.qty)+'</span><button class="wrm-cart-item-del" data-idx="'+idx+'" aria-label="Eliminar">🗑</button></div></div>');
    $items.append(row);
  });
  $('#wrm-cart-total').text(fmt(getTotal())); updateFAB(); updateDeliveryFee();
}
function flashFAB(){ var $fab = $('#wrm-cart-fab'); $fab.css('transform','scale(1.25)'); setTimeout(function(){ $fab.css('transform',''); }, 300); }
function openProductModal(data){
  var variants = data.variants || [], extras = data.extras || [], html = '', basePrice = parseFloat(data.price)||0;
  if(data.img) html += '<img class="wrm-modal-img" src="'+data.img+'" alt="'+data.title+'">';
  html += '<h2 class="wrm-modal-title">'+data.title+'</h2>';
  if(data.desc) html += '<p class="wrm-modal-desc">'+data.desc+'</p>';
  html += '<div class="wrm-modal-price" id="wrm-modal-price">'+fmt(basePrice)+'</div>';
  if(variants.length){ html += '<div class="wrm-modal-section">Elige una opción</div><div class="wrm-variant-list">'; variants.forEach(function(v,i){ html += '<div class="wrm-variant-item'+(i===0?' selected':'')+'" data-price="'+v.price+'" data-name="'+v.name+'" onclick="WRMModal.selectVariant(this)"><span class="v-name">'+v.name+'</span><span class="v-price">'+fmt(v.price)+'</span></div>'; }); html += '</div>'; basePrice = parseFloat(variants[0].price)||basePrice; }
  if(extras.length){ html += '<div class="wrm-modal-section">Extras (opcional)</div><div class="wrm-extra-list">'; extras.forEach(function(e){ html += '<div class="wrm-extra-item"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;width:100%"><input type="checkbox" class="wrm-extra-check" data-price="'+e.price+'" data-name="'+e.name+'" onchange="WRMModal.updatePrice()"><span class="e-name">'+e.name+'</span></label><span class="e-price">+'+fmt(e.price)+'</span></div>'; }); html += '</div>'; }
  html += '<div class="wrm-modal-section">Cantidad</div><div class="wrm-modal-qty"><button class="wrm-qty-btn" onclick="WRMModal.changeQty(-1)">−</button><span class="wrm-qty-val" id="wrm-modal-qty">1</span><button class="wrm-qty-btn" onclick="WRMModal.changeQty(1)">+</button></div>';
  html += '<div class="wrm-modal-notes"><div class="wrm-modal-section">Notas (opcional)</div><textarea id="wrm-modal-notes" placeholder="Sin cebolla, poco picante…"></textarea></div>';
  html += '<button class="wrm-modal-add-btn" id="wrm-modal-add-btn" data-id="'+data.id+'" data-title="'+data.title+'" data-base="'+data.price+'" data-img="'+(data.img||'')+'">Agregar al carrito</button>';
  $('#wrm-modal-content').html(html); $('#wrm-product-modal').show(); $('body').css('overflow','hidden'); window.WRMModal._basePrice = basePrice; window.WRMModal._qty = 1; window.WRMModal.updatePrice();
}
window.WRMModal = {
  _basePrice: 0, _qty: 1,
  selectVariant: function(el){ $('.wrm-variant-item').removeClass('selected'); $(el).addClass('selected'); this._basePrice = parseFloat($(el).data('price'))||0; this.updatePrice(); },
  changeQty: function(d){ this._qty = Math.max(1, this._qty + d); $('#wrm-modal-qty').text(this._qty); this.updatePrice(); },
  updatePrice: function(){ var extra = 0; $('.wrm-extra-check:checked').each(function(){ extra += parseFloat($(this).data('price'))||0; }); var total = (this._basePrice + extra) * this._qty; $('#wrm-modal-price').text(fmt(total)); },
  addToCart: function(){ var $btn=$('#wrm-modal-add-btn'), id=$btn.data('id'), title=$btn.data('title'), img=$btn.data('img'), variant=$('.wrm-variant-item.selected').data('name')||'', extrasArr=[], extraPrice=0; $('.wrm-extra-check:checked').each(function(){ extrasArr.push($(this).data('name')); extraPrice += parseFloat($(this).data('price'))||0; }); var notes=$('#wrm-modal-notes').val().trim(); var price=(this._basePrice + extraPrice); cart.push({ id:id, title:title, price:price, img:img, variant:variant, extras:extrasArr, notes:notes, qty:this._qty }); $('#wrm-product-modal').hide(); $('body').css('overflow',''); flashFAB(); renderCart(); openCart(); }
};
window.WRMDelivery = { select: function(mode){ deliveryMode = mode; $('#wrm-opt-pickup, #wrm-opt-delivery').removeClass('selected'); $('#wrm-opt-'+mode).addClass('selected'); var locations=getAvailableLocations(); if(mode==='pickup'){ if(locations.length>1){ $('#wrm-pickup-branch-wrap').show(); } else { $('#wrm-pickup-branch-wrap').hide(); } $('#wrm-delivery-branch-wrap').hide(); if(selectedPickupBranchId===null){ syncSelectedLocation('pickup', 0); } else { syncSelectedLocation('pickup'); } $('#wrm-pickup-confirm').addClass('visible'); $('#wrm-delivery-form-wrap').removeClass('open'); enableOrderBtn(); } else { $('#wrm-pickup-branch-wrap').hide(); $('#wrm-pickup-confirm').removeClass('visible'); if(locations.length>1){ $('#wrm-delivery-branch-wrap').show(); } else { $('#wrm-delivery-branch-wrap').hide(); } if(selectedDeliveryBranchId===null){ syncSelectedLocation('delivery', 0); } else { syncSelectedLocation('delivery'); } $('#wrm-d-city').val(getSelectedLocationCity('delivery')); $('#wrm-delivery-form-wrap').removeClass('open').hide(); validateDelivery(); setTimeout(function(){ openDeliveryModal(); }, 120); } updateDeliveryFee(); $('#wrm-cart-total').text(fmt(getTotal())); }, validate: function(){ validateDelivery(); } };
function updateDeliveryFee(){ var fee=(window.WRMCart&&WRMCart.delivery_fee)?WRMCart.delivery_fee:''; if(fee){ $('#wrm-fee-sublabel').text('Envío: '+fee); $('#wrm-fee-amount').text(fee); if(deliveryMode==='delivery') $('#wrm-fee-line').show(); else $('#wrm-fee-line').hide(); } }
function validateDelivery(){ var name=$('#wrm-d-name').val().trim(), phone=$('#wrm-d-phone').val().trim(), address=$('#wrm-d-address').val().trim(), city=$('#wrm-d-city').val().trim() || getSelectedLocationCity('delivery'); $('#wrm-d-city').val(city); $('#wrm-d-name').toggleClass('error',!name); $('#wrm-d-phone').toggleClass('error',!phone); $('#wrm-d-address').toggleClass('error',!address); if(name && phone && address) enableOrderBtn(); else disableOrderBtn('Completa los datos de envío'); $('#wrm-cart-total').text(fmt(getTotal())); }
function syncDeliveryModalToBase(){
  var map = ['#wrm-d-name','#wrm-d-phone','#wrm-d-address','#wrm-d-sector','#wrm-d-city','#wrm-d-ref'];
  map.forEach(function(sel){
    var val = $('#wrm-delivery-modal-body').find(sel).val();
    if(typeof val !== 'undefined') $(sel).val(val);
  });
}
function validateDeliveryModal(){
  var $mb = $('#wrm-delivery-modal-body');
  var isOpen = $('#wrm-delivery-modal').hasClass('open');
  if(!isOpen){ validateDelivery(); return; }
  var name    = $mb.find('#wrm-m-name').val()    ? $mb.find('#wrm-m-name').val().trim()    : '';
  var phone   = $mb.find('#wrm-m-phone').val()   ? $mb.find('#wrm-m-phone').val().trim()   : '';
  var address = $mb.find('#wrm-m-address').val() ? $mb.find('#wrm-m-address').val().trim() : '';
  var sector  = $mb.find('#wrm-m-sector').val()  ? $mb.find('#wrm-m-sector').val().trim()  : '';
  var ref     = $mb.find('#wrm-m-ref').val()     ? $mb.find('#wrm-m-ref').val().trim()     : '';
  var city    = getSelectedLocationCity('delivery') || ($('#wrm-d-city').val() ? $('#wrm-d-city').val().trim() : '');
  $('#wrm-d-name').val(name);
  $('#wrm-d-phone').val(phone);
  $('#wrm-d-address').val(address);
  $('#wrm-d-sector').val(sector);
  $('#wrm-d-ref').val(ref);
  if(city){ $('#wrm-d-city').val(city); }
  $mb.find('#wrm-m-name').toggleClass('error', !name);
  $mb.find('#wrm-m-phone').toggleClass('error', !phone);
  $mb.find('#wrm-m-address').toggleClass('error', !address);
  if(name && phone && address){
    enableOrderBtn();
  } else {
    disableOrderBtn('Completa los datos de envío');
  }
}
function enableOrderBtn(){ $('#wrm-wa-send-btn, #wrm-wa-send-btn-delivery').prop('disabled',false); $('#wrm-wa-send-btn span, #wrm-wa-send-btn-delivery span').text('Ordenar por WhatsApp'); }
function disableOrderBtn(msg){ $('#wrm-wa-send-btn, #wrm-wa-send-btn-delivery').prop('disabled',true); $('#wrm-wa-send-btn span, #wrm-wa-send-btn-delivery span').text(msg||'Elige cómo recibirás tu pedido'); }
function buildWhatsAppMessage(){
  var biz=(window.WRMCart&&WRMCart.business_name)?WRMCart.business_name:'el restaurante';
  var lines=['🛒 *Nuevo Pedido — '+biz+'*','','📋 *PRODUCTOS:*'];
  cart.forEach(function(item, i){ var line=(i+1)+'. *'+item.title+'*'; if(item.variant) line += ' ('+item.variant+')'; if(item.extras.length) line += ' + '+item.extras.join(', '); line += ' x'+item.qty+' → '+fmt(item.price*item.qty); if(item.notes) line += '\n   📝 '+item.notes; lines.push(line); });
  lines.push('','─────────────────────────');
  if(deliveryMode==='delivery'){ var feeTxt=(window.WRMCart&&WRMCart.delivery_fee)?WRMCart.delivery_fee:''; if(feeTxt) lines.push('🛵 Envío: '+feeTxt); lines.push('💰 *TOTAL: '+fmt(getTotal())+'*'); } else { lines.push('💰 *TOTAL: '+fmt(getTotal())+'*'); }
  lines.push('─────────────────────────');
  if(deliveryMode==='pickup'){
    syncSelectedLocation('pickup');
    lines.push('','🏪 *RECOGE EN EL LOCAL*');
    if(selectedPickupBranch){
      lines.push('📍 Local seleccionado: '+locationLabel(selectedPickupBranch));
      if(selectedPickupBranch.address) lines.push('📌 Dirección: '+selectedPickupBranch.address);
      if(selectedPickupBranch.maps_enabled === '1' && selectedPickupBranch.maps_url) lines.push('🗺️ Maps: '+selectedPickupBranch.maps_url);
    }
  } else if(deliveryMode==='delivery'){
    syncSelectedLocation('delivery');
    lines.push('','🛵 *DELIVERY A DOMICILIO*');
    if(selectedDeliveryBranch){
      lines.push('🏪 SUCURSAL ORIGEN: '+locationLabel(selectedDeliveryBranch));
      if(selectedDeliveryBranch.address) lines.push('📌 Local origen: '+selectedDeliveryBranch.address);
      if(selectedDeliveryBranch.maps_enabled === '1' && selectedDeliveryBranch.maps_url) lines.push('🗺️ Maps local: '+selectedDeliveryBranch.maps_url);
    }
    var isModal=$('#wrm-delivery-modal').hasClass('open'),$mb=$('#wrm-delivery-modal-body');
    var n=(isModal&&$mb.find('#wrm-m-name').val())?$mb.find('#wrm-m-name').val().trim():$('#wrm-d-name').val().trim();
    var ph=(isModal&&$mb.find('#wrm-m-phone').val())?$mb.find('#wrm-m-phone').val().trim():$('#wrm-d-phone').val().trim();
    var ad=(isModal&&$mb.find('#wrm-m-address').val())?$mb.find('#wrm-m-address').val().trim():$('#wrm-d-address').val().trim();
    var sc=(isModal&&$mb.find('#wrm-m-sector').val())?$mb.find('#wrm-m-sector').val().trim():$('#wrm-d-sector').val().trim();
    var ci=getSelectedLocationCity('delivery')||((isModal&&$mb.find('#wrm-m-city').val())?$mb.find('#wrm-m-city').val().trim():$('#wrm-d-city').val().trim());
    var rf=(isModal&&$mb.find('#wrm-m-ref').val())?$mb.find('#wrm-m-ref').val().trim():$('#wrm-d-ref').val().trim();
    if(n) lines.push('👤 '+n); if(ph) lines.push('📞 '+ph); if(ad) lines.push('📍 '+ad); if(sc) lines.push('🏘️ '+sc); if(ci) lines.push('🌆 '+ci); if(rf) lines.push('🔖 Ref: '+rf);
  }
  lines.push('','¡Gracias por tu pedido! Te confirmo en breve. 🙏');
  return lines.join('\n');
}
$(document).ready(function(){
  /* ── Filtro de categorías (tabs) ──────────────────── */
  $(document).on('click', '.wrm-cat-tab', function(){
    var cat = $(this).data('cat');
    $('.wrm-cat-tab').removeClass('active').attr('aria-selected','false');
    $(this).addClass('active').attr('aria-selected','true');
    var $grid = $('#wrm-items-grid');
    var $cards = $grid.find('.wrm-item-card');
    var searchVal = ($('#wrm-search').val() || '').toLowerCase().trim();
    var visibleCount = 0;
    $cards.each(function(){
      var $card = $(this);
      var cats = ($card.attr('data-cats') || '').split(' ');
      var title = ($card.attr('data-title') || '').toLowerCase();
      var matchCat = (cat === 'all' || cats.indexOf(cat) !== -1);
      var matchSearch = (!searchVal || title.indexOf(searchVal) !== -1);
      if(matchCat && matchSearch){ $card.show(); visibleCount++; } else { $card.hide(); }
    });
    $('#wrm-no-results').toggle(visibleCount === 0);
  });

  /* ── Buscador de productos ───────────────────────── */
  $(document).on('input', '#wrm-search', function(){
    var val = $(this).val().toLowerCase().trim();
    var activeCat = $('.wrm-cat-tab.active').data('cat') || 'all';
    var $cards = $('#wrm-items-grid .wrm-item-card');
    var visibleCount = 0;
    // Mostrar/ocultar botón clear
    $('#wrm-search-clear').toggle(val.length > 0);
    $cards.each(function(){
      var $card = $(this);
      var title = ($card.attr('data-title') || '').toLowerCase();
      var cats = ($card.attr('data-cats') || '').split(' ');
      var matchCat = (activeCat === 'all' || cats.indexOf(activeCat) !== -1);
      var matchSearch = (!val || title.indexOf(val) !== -1);
      if(matchCat && matchSearch){ $card.show(); visibleCount++; } else { $card.hide(); }
    });
    $('#wrm-no-results').toggle(visibleCount === 0);
  });

  /* ── Botón limpiar búsqueda ──────────────────────── */
  $(document).on('click', '#wrm-search-clear', function(){
    $('#wrm-search').val('').trigger('input');
    $(this).hide();
  });

  $(document).on('click','#wrm-cart-fab', openCart);
  $(document).on('click','#wrm-cart-close, #wrm-cart-overlay', closeCart);
  $(document).on('click','.wrm-add-btn', function(){
    var $b=$(this), data={ id:$b.data('id'), title:$b.data('title'), price:$b.data('price'), img:$b.data('img'), desc:$b.closest('.wrm-item-card').find('.wrm-item-desc').text(), variants:[], extras:[] };
    try{ data.variants = JSON.parse($b.attr('data-variants') || '[]'); }catch(e){}
    try{ data.extras = JSON.parse($b.attr('data-extras') || '[]'); }catch(e){}
    if((data.variants && data.variants.length) || (data.extras && data.extras.length)){ openProductModal(data); } else { cart.push({ id:data.id, title:data.title, price:parseFloat(data.price), img:data.img||'', variant:'', extras:[], notes:'', qty:1 }); flashFAB(); renderCart(); openCart(); }
  });
  $(document).on('click','#wrm-modal-close', function(){ $('#wrm-product-modal').hide(); $('body').css('overflow',''); });
  $(document).on('click','#wrm-modal-add-btn', function(){ WRMModal.addToCart(); });
  $(document).on('click','.wrm-qty-inc', function(){ var idx=parseInt($(this).data('idx'),10); if(cart[idx]) cart[idx].qty++; renderCart(); });
  $(document).on('click','.wrm-qty-dec', function(){ var idx=parseInt($(this).data('idx'),10); if(cart[idx]){ cart[idx].qty--; if(cart[idx].qty<=0) cart.splice(idx,1); renderCart(); } });
  $(document).on('click','.wrm-cart-item-del', function(){ var idx=parseInt($(this).data('idx'),10); if(cart[idx]){ cart.splice(idx,1); renderCart(); } });
  $(document).on('input','#wrm-d-name,#wrm-d-phone,#wrm-d-address,#wrm-d-sector,#wrm-d-city,#wrm-d-ref', function(){ validateDelivery(); });
  $(document).on('input keyup paste change','#wrm-m-name,#wrm-m-phone,#wrm-m-address,#wrm-m-sector,#wrm-m-ref,#wrm-m-city', function(){ validateDeliveryModal(); });
  $(document).on('click', '.wrm-branch-card-mini', function(e){
    e.preventDefault();
    e.stopPropagation();
    var mode = String($(this).data('mode') || '');
    var idx = parseInt($(this).data('idx'), 10);
    if(isNaN(idx)) idx = 0;
    if(mode === 'pickup'){
      var plocs = getAvailableLocations();
      selectedPickupBranchId = plocs[idx] && typeof plocs[idx].id !== 'undefined' ? plocs[idx].id : idx;
      syncSelectedLocation('pickup', idx);
      enableOrderBtn();
    }
    if(mode === 'delivery'){
      var dlocs = getAvailableLocations();
      selectedDeliveryBranchId = dlocs[idx] && typeof dlocs[idx].id !== 'undefined' ? dlocs[idx].id : idx;
      syncSelectedLocation('delivery', idx);
      // Actualizar ciudad en base y modal
      var city = getSelectedLocationCity('delivery') || '';
      if(city){ $('#wrm-d-city').val(city); $('#wrm-m-city').val(city); }
      // Revalidar datos para activar botón si ya está todo lleno
      if($('#wrm-delivery-modal').hasClass('open')){
        validateDeliveryModal();
      } else {
        validateDelivery();
      }
    }
  });
  $(document).on('click','#wrm-open-delivery-modal', function(){ if(deliveryMode==='delivery') openDeliveryModal(); });
  $(document).on('click','[data-close-delivery]', function(){ closeDeliveryModal(); });
  $(document).on('click','#wrm-wa-send-btn', function(){
    if(deliveryMode === 'delivery'){
      openDeliveryModal();
      return;
    }
    var wa = currentWA();
    if(!wa){ alert('Configura el número de WhatsApp del negocio o del local.'); return; }
    var msg = buildWhatsAppMessage();
    window.open('https://wa.me/'+wa+'?text='+encodeURIComponent(msg), '_blank');
  });
  $(document).on('click','#wrm-wa-send-btn-delivery', function(){
    var wa = currentWA();
    if(!wa){ alert('Configura el número de WhatsApp del negocio o del local.'); return; }
    var msg = buildWhatsAppMessage();
    window.open('https://wa.me/'+wa+'?text='+encodeURIComponent(msg), '_blank');
  });
  disableOrderBtn();
});
})(jQuery);
