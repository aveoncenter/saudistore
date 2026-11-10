(function () {
 'use strict';
 var nav = document.querySelector('.ws-mobile-nav');
 if (!nav) return;
 var categories = nav.querySelector('[data-ws-mobile-categories]');
 var open = document.querySelector('.ws-nav .wp-block-navigation__responsive-container-open');
 var panel = document.querySelector('.ws-nav .wp-block-navigation__responsive-container');
 if (categories && open && panel) {
  categories.addEventListener('click', function () {
   var close = panel.querySelector('.wp-block-navigation__responsive-container-close');
   if (panel.classList.contains('is-menu-open') && close) close.click(); else open.click();
  });
  function syncMenu() {
   var active = panel.classList.contains('is-menu-open');
   categories.setAttribute('aria-expanded', String(active));
   categories.classList.toggle('is-open', active);
  }
  new MutationObserver(syncMenu).observe(panel, { attributes: true, attributeFilter: ['class'] });
  syncMenu();
 }
 var badge = nav.querySelector('[data-ws-mobile-cart-count]');
 function update() {
  var total = document.querySelector('[data-ws-cart-total]');
  if (!total || !badge) return;
  var count = parseInt(total.getAttribute('data-count'), 10);
  if (!Number.isFinite(count)) return;
  if (badge.textContent !== String(count)) badge.textContent = count;
  var label = count + ' منتجات في السلة';
  if (badge.getAttribute('aria-label') !== label) badge.setAttribute('aria-label', label);
 }
 // The existing cart integration replaces fragments and updates data-count for
 // Store API, classic AJAX, block cart and checkout changes.
 var header = document.querySelector('.ws-header');
 if (header) new MutationObserver(update).observe(header, { subtree: true, childList: true, attributes: true, attributeFilter: ['data-count'] });
 update();
})();
