(function () {
 'use strict';
 function init() {
 var nav = document.querySelector('.ws-mobile-nav');
 if (!nav) return;
 var categories = nav.querySelector('[data-ws-mobile-categories]');
 var dialog = document.getElementById('ws-mobile-category-dialog');
 if (categories && dialog && typeof dialog.showModal === 'function') {
  categories.addEventListener('click', function (event) {
   event.preventDefault();
   if (dialog.open) { dialog.close(); return; }
   dialog.showModal();
   categories.setAttribute('aria-expanded', 'true');
   categories.classList.add('is-open');
   var close = dialog.querySelector('[data-ws-category-close]');
   if (close) close.focus();
  });
  dialog.addEventListener('click', function (event) {
   if (event.target === dialog || event.target.closest('[data-ws-category-close]')) dialog.close();
  });
  dialog.addEventListener('close', function () {
   categories.setAttribute('aria-expanded', 'false');
   categories.classList.remove('is-open');
  });
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
 }
 if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
 else init();
})();
