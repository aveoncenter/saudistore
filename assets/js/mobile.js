(function () {
 'use strict';
 var nav = document.querySelector('.ws-mobile-nav');
 if (!nav) return;
 var categories = nav.querySelector('[data-ws-mobile-categories]');
 var open = document.querySelector('.ws-nav .wp-block-navigation__responsive-container-open');
 var panel = document.querySelector('.ws-nav .wp-block-navigation__responsive-container');
 if (categories && panel) {
  /* Do not depend on the tiny/hidden core hamburger button: the bottom-nav
     Categories control opens and closes the native WordPress overlay directly. */
  function setMenu(isOpen) {
   var close = panel.querySelector('.wp-block-navigation__responsive-container-close');
   if (isOpen && !panel.classList.contains('is-menu-open')) {
    if (open) open.click();
    else panel.classList.add('is-menu-open');
   } else if (!isOpen && panel.classList.contains('is-menu-open')) {
    if (close) close.click();
    else panel.classList.remove('is-menu-open');
   }
  }
  categories.addEventListener('click', function () {
   setMenu(!panel.classList.contains('is-menu-open'));
  });
  document.addEventListener('keydown', function (e) {
   if (e.key === 'Escape' && panel.classList.contains('is-menu-open')) setMenu(false);
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
