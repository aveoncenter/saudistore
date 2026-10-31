/* Shared WooCommerce card motion: visible-by-default, once-only reveal, delegated hover. */
(function () {
 'use strict';
 function boot() {
  if (getComputedStyle(document.documentElement).getPropertyValue('--ws-card-motion-enabled').trim() !== '1') return;
  var selector = '.ws-card, .wc-block-product-template > .wc-block-product, .wc-block-grid__product, ul.products > li.product';
  var groupSelector = '.ws-home-shelf__track, .ws-carousel__track, .ws-search-grid, .wc-block-product-template, .wc-block-grid__products, ul.products';
  var reduced = matchMedia('(prefers-reduced-motion: reduce)'), fine = matchMedia('(hover: hover) and (pointer: fine)');
  var registered = new WeakSet(), revealed = new WeakSet(), pending = new Set(), entering = new Set();
  var active = null, hoverTrack = null, trackTimers = new WeakMap(), bounds, frame = 0, pointerX = 0, pointerY = 0;
  var observer = null, cleanupTimers = new WeakMap();
  function stopHover() {
   if (frame) cancelAnimationFrame(frame);
   frame = 0;
   if (!active) return;
   active.classList.remove('ws-motion-hover');
   if (hoverTrack) {
    var track = hoverTrack;
    clearTimeout(trackTimers.get(track));
    trackTimers.set(track, setTimeout(function () { track.classList.remove('ws-motion-interacting'); trackTimers.delete(track); }, 320));
    hoverTrack = null;
   }
   active.style.setProperty('--ws-hover-inset', '0px');
   active.style.setProperty('--ws-tilt-x', '0deg'); active.style.setProperty('--ws-tilt-y', '0deg');
   active = null; bounds = null;
  }
  function finishEntrance(card) {
   clearTimeout(cleanupTimers.get(card)); cleanupTimers.delete(card);
   card.classList.remove('ws-motion-entering'); entering.delete(card);
   card.style.removeProperty('--ws-enter-delay');
  }
  function observe(card) {
   if (revealed.has(card) || !observer || reduced.matches) return;
   pending.add(card); observer.observe(card);
  }
  if ('IntersectionObserver' in window) {
   observer = new IntersectionObserver(function (entries) {
    var groups = new Map();
    entries.forEach(function (entry) {
     var card = entry.target;
     if (!entry.isIntersecting || revealed.has(card) || card.closest('[inert], [aria-hidden="true"]')) return;
     revealed.add(card); pending.delete(card); observer.unobserve(card);
     if (reduced.matches) return;
     var parent = card.closest(groupSelector) || card.parentElement;
     if (!groups.has(parent)) groups.set(parent, []);
     groups.get(parent).push(card);
    });
    groups.forEach(function (cards, parent) {
     var order = Array.from(parent.querySelectorAll(selector));
     cards.sort(function (a, b) { return order.indexOf(a) - order.indexOf(b); });
     cards.forEach(function (card, index) {
      card.style.setProperty('--ws-enter-delay', Math.min(index, 5) * 85 + 'ms');
      entering.add(card); card.classList.add('ws-motion-entering');
      cleanupTimers.set(card, setTimeout(function () { finishEntrance(card); }, Math.min(index, 5) * 85 + 750));
     });
    });
   }, { threshold: .08, rootMargin: '0px 0px -16px 0px' });
  }
  function prepareTrack(track) {
   if (!track || track.classList.contains('ws-motion-bleed')) return;
   var style = getComputedStyle(track);
   [['pt','paddingTop'],['pb','paddingBottom'],['mt','marginTop'],['mb','marginBottom']].forEach(function (pair) { track.style.setProperty('--ws-bleed-' + pair[0], style[pair[1]]); });
   track.classList.add('ws-motion-bleed');
  }
  function register(card) {
   if (!card.isConnected) return;
   if (registered.has(card)) { observe(card); return; }
   registered.add(card);
   // Exclude the old hover rules before capturing the unchanged resting design.
   card.classList.add('ws-motion');
   var after = getComputedStyle(card, '::after').content;
   if (after !== 'none' && after !== 'normal') card.classList.add('ws-motion-preserve-after');
   var style = getComputedStyle(card);
   card.style.setProperty('--ws-motion-rest-border', style.borderTopColor);
   card.style.setProperty('--ws-motion-rest-shadow', style.boxShadow === 'none' ? '0 0 0 transparent' : style.boxShadow);
   if (style.position === 'static') card.style.position = 'relative';
   card.setAttribute('data-ws-motion', ''); card.classList.add('ws-motion-ready');
   prepareTrack(card.closest('.ws-carousel__track'));
   if (reduced.matches || !observer) revealed.add(card); else observe(card);
  }
  function scan(node) {
   if (node.nodeType !== 1) return;
   if (node.matches(selector)) register(node);
   node.querySelectorAll(selector).forEach(register);
  }
  scan(document.body);
  document.addEventListener('animationend', function (event) { if (event.animationName === 'ws-card-enter') finishEntrance(event.target); });
  document.addEventListener('animationcancel', function (event) { if (event.animationName === 'ws-card-enter') finishEntrance(event.target); });
  document.addEventListener('focusin', function (event) {
   var card = event.target.closest(selector);
   if (card) { revealed.add(card); pending.delete(card); if (observer) observer.unobserve(card); finishEntrance(card); }
  });
  function draw() {
   frame = 0;
   if (!active || !bounds) return;
   var x = Math.max(0, Math.min(1, (pointerX - bounds.left) / bounds.width));
   var y = Math.max(0, Math.min(1, (pointerY - bounds.top) / bounds.height));
   active.style.setProperty('--ws-tilt-x', ((.5 - y) * 5).toFixed(2) + 'deg');
   active.style.setProperty('--ws-tilt-y', ((x - .5) * 5).toFixed(2) + 'deg');
   active.style.setProperty('--ws-light-x', (x * 100).toFixed(1) + '%');
   active.style.setProperty('--ws-light-y', (y * 100).toFixed(1) + '%');
  }
  function startHover(event) {
   if (event.pointerType !== 'mouse' || reduced.matches || !fine.matches) return;
   var card = event.target.closest(selector);
   if (!card || !registered.has(card) || card === active || card.closest('[inert], [aria-hidden="true"]')) return;
   stopHover(); finishEntrance(card); revealed.add(card); pending.delete(card); if (observer) observer.unobserve(card);
   active = card; hoverTrack = card.closest('.ws-carousel__track');
   if (hoverTrack) { clearTimeout(trackTimers.get(hoverTrack)); hoverTrack.classList.add('ws-motion-interacting'); }
   bounds = card.getBoundingClientRect();
   // Keep an enlarged edge card's outline inside the existing horizontal clip.
   var clip = card.closest('.ws-carousel__track, .ws-home-shelf__viewport'), inset = 0;
   if (clip) { var edge = clip.getBoundingClientRect(); if (edge.right - bounds.right < 8) inset = -6; else if (bounds.left - edge.left < 8) inset = 6; }
   card.style.setProperty('--ws-hover-inset', inset + 'px');
   pointerX = event.clientX; pointerY = event.clientY;
   card.classList.add('ws-motion-hover'); frame = requestAnimationFrame(draw);
  }
  document.addEventListener('pointerover', startHover, { passive: true });
  document.addEventListener('pointermove', function (event) {
   if (!active) startHover(event);
   if (!active || event.pointerType !== 'mouse') return;
   pointerX = event.clientX; pointerY = event.clientY;
   if (!frame) frame = requestAnimationFrame(draw);
  }, { passive: true });
  document.addEventListener('pointerout', function (event) { if (active && active.contains(event.target) && (!event.relatedTarget || !active.contains(event.relatedTarget))) stopHover(); }, { passive: true });
  document.addEventListener('pointercancel', stopHover, { passive: true });
  document.addEventListener('pointerdown', function (event) { if (event.pointerType !== 'mouse') stopHover(); }, { passive: true });
  window.addEventListener('scroll', function (event) { if (event.target !== hoverTrack) stopHover(); }, { passive: true, capture: true });
  document.addEventListener('wheel', stopHover, { passive: true });
  window.addEventListener('resize', stopHover, { passive: true });
  window.addEventListener('blur', stopHover);
  document.addEventListener('visibilitychange', function () { if (document.hidden) stopHover(); });
  function preferencesChanged() {
   stopHover();
   if (reduced.matches) {
    entering.forEach(finishEntrance);
    pending.forEach(function (card) { revealed.add(card); observer.unobserve(card); }); pending.clear();
   }
  }
  reduced.addEventListener('change', preferencesChanged); fine.addEventListener('change', stopHover);
  // One batched observer handles Woo block pagination, Ajax filters, search,
  // infinite scrolling, and reparented carousel cards without reinitializing them.
  var additions = new Set(), removals = new Set(), mutationFrame = 0;
  function flush() {
   mutationFrame = 0; additions.forEach(scan); additions.clear();
   removals.forEach(function (node) {
    if (node.isConnected || node.nodeType !== 1) return;
    var cards = node.matches(selector) ? [node] : [];
    cards = cards.concat(Array.from(node.querySelectorAll(selector)));
    cards.forEach(function (card) { if (observer) observer.unobserve(card); pending.delete(card); finishEntrance(card); if (active === card) stopHover(); });
   }); removals.clear();
  }
  new MutationObserver(function (records) {
   records.forEach(function (record) {
    if (record.type === 'attributes') {
     if (!revealed.has(record.target) && registered.has(record.target)) { if (observer) observer.unobserve(record.target); observe(record.target); }
     return;
    }
    record.addedNodes.forEach(function (node) { if (node.nodeType === 1) additions.add(node); });
    record.removedNodes.forEach(function (node) { if (node.nodeType === 1) removals.add(node); });
   });
   if (!mutationFrame && (additions.size || removals.size)) mutationFrame = requestAnimationFrame(flush);
  }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['inert', 'aria-hidden'] });
 }
 if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true }); else boot();
})();
