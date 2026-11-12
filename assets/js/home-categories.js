/* Continuous category loop: native touch scrolling, mouse drag and accessible pause. */
(function () {
 'use strict';
 document.querySelectorAll('[data-category-carousel]').forEach(function (root) {
  var viewport = root.querySelector('.ws-category-viewport');
  var track = root.querySelector('.ws-category-track');
  if (!viewport || !track) return;
  var originals = Array.from(track.children);
  if (originals.length < 2) return;

  var reduced = matchMedia('(prefers-reduced-motion: reduce)');
  var mobile = matchMedia('(max-width: 600px)');
  var visible = false, hovered = false, touching = false, mouseDown = false, focused = false;
  var dragging = false, suppressClick = false, startX = 0, startScroll = 0;
  var cycle = 0, base = 0, fractional = 0, previous = 0, frame = 0, wake = 0, resumeAt = 0, autoUntil = 0;
  var pausedFor = 650; // Allow native touch momentum to finish before resuming.

  function clone(node) {
   var copy = node.cloneNode(true);
   copy.dataset.categoryClone = 'true';
   copy.setAttribute('aria-hidden', 'true');
   if (copy.hasAttribute('id')) copy.removeAttribute('id');
   copy.querySelectorAll('[id]').forEach(function (el) { el.removeAttribute('id'); });
   copy.querySelectorAll('a,button,input,select,textarea,[tabindex]').forEach(function (el) { el.tabIndex = -1; });
   return copy;
  }
  function jump(x) { autoUntil = performance.now() + 60; viewport.scrollLeft = x; }
  function wrap() {
   if (!cycle) return;
   var x = viewport.scrollLeft, shifted = 0;
   if (x < base) shifted = Math.ceil((base - x) / cycle) * cycle;
   else if (x >= base + cycle) shifted = -Math.floor((x - base) / cycle) * cycle;
   if (shifted) { jump(x + shifted); if (dragging) startScroll += shifted; }
  }
  function build() {
   var progress = cycle ? ((viewport.scrollLeft - base) % cycle + cycle) % cycle / cycle : 0;
   track.querySelectorAll('[data-category-clone]').forEach(function (el) { el.remove(); });
   var cardWidth = originals[0].getBoundingClientRect().width;
   var gap = parseFloat(getComputedStyle(track).gap) || 0;
   cycle = originals.length * (cardWidth + gap);
   if (!cycle) return;
   /* Enough full copies to cover the viewport even when few categories exist. */
   var buffers = Math.max(1, Math.ceil((viewport.clientWidth + gap) / cycle));
   var before = document.createDocumentFragment(), after = document.createDocumentFragment();
   for (var i = 0; i < buffers; i++) {
    originals.forEach(function (node) { before.appendChild(clone(node)); after.appendChild(clone(node)); });
   }
   track.prepend(before); track.append(after); base = buffers * cycle;
   jump(base + progress * cycle);
  }
  function schedule() {
   cancelAnimationFrame(frame); clearTimeout(wake); frame = 0; previous = 0;
   if (!visible || document.hidden || reduced.matches || hovered || touching || mouseDown || focused || !cycle) return;
   var delay = resumeAt - performance.now();
   if (delay > 0) wake = setTimeout(schedule, delay);
   else frame = requestAnimationFrame(tick);
  }
  function tick(now) {
   if (!visible || document.hidden || reduced.matches || hovered || touching || mouseDown || focused || now < resumeAt) { schedule(); return; }
   if (previous) {
    // Mobile was 14.4 px/s; 18 is 25% faster. On larger screens preserve
    // the former two-second step's average speed, also increased by 25%.
    var speed = mobile.matches ? 18 : cycle / originals.length / 1.6;
    fractional += Math.min(now - previous, 50) * speed / 1000;
    if (fractional >= 1) { var pixels = Math.floor(fractional); fractional -= pixels; jump(viewport.scrollLeft + pixels); wrap(); }
   }
   previous = now; frame = requestAnimationFrame(tick);
  }
  function waitForMotionToSettle() { resumeAt = performance.now() + pausedFor; schedule(); }
  root.classList.add('is-ready');
  track.querySelectorAll('img[loading="lazy"]').forEach(function (img) { img.loading = 'eager'; });
  build();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { build(); schedule(); });
  window.addEventListener('resize', function () { build(); schedule(); }, { passive: true });
  viewport.addEventListener('scroll', function () {
   wrap();
   if (performance.now() > autoUntil) waitForMotionToSettle();
  }, { passive: true });
  root.addEventListener('pointerenter', function (event) { if (event.pointerType === 'mouse') { hovered = true; schedule(); } });
  root.addEventListener('pointerleave', function (event) { if (event.pointerType === 'mouse') { hovered = false; schedule(); } });
  viewport.addEventListener('touchstart', function () { touching = true; schedule(); }, { passive: true });
  viewport.addEventListener('touchend', function () { touching = false; waitForMotionToSettle(); }, { passive: true });
  viewport.addEventListener('touchcancel', function () { touching = false; waitForMotionToSettle(); }, { passive: true });
  viewport.addEventListener('pointerdown', function (event) {
   if (event.pointerType !== 'mouse' || event.button !== 0) return;
   mouseDown = true; dragging = false; startX = event.clientX; startScroll = viewport.scrollLeft; schedule();
  });
  window.addEventListener('pointermove', function (event) {
   if (!mouseDown || event.pointerType !== 'mouse') return;
   if (Math.abs(event.clientX - startX) > 5) dragging = true;
   if (dragging) { viewport.scrollLeft = startScroll - (event.clientX - startX); wrap(); event.preventDefault(); }
  });
  function releaseMouse(event) {
   if (!mouseDown || event.pointerType !== 'mouse') return;
   if (dragging) { suppressClick = true; setTimeout(function () { suppressClick = false; }, 500); }
   mouseDown = false; dragging = false; waitForMotionToSettle();
  }
  window.addEventListener('pointerup', releaseMouse);
  window.addEventListener('pointercancel', releaseMouse);
  viewport.addEventListener('dragstart', function (event) { event.preventDefault(); });
  viewport.addEventListener('click', function (event) { if (suppressClick) { event.preventDefault(); event.stopPropagation(); suppressClick = false; } }, true);
  viewport.addEventListener('wheel', waitForMotionToSettle, { passive: true });
  viewport.addEventListener('keydown', function (event) {
   if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
   event.preventDefault();
   viewport.scrollBy({ left: event.key === 'ArrowRight' ? 100 : -100, behavior: 'auto' });
   waitForMotionToSettle();
  });
  root.addEventListener('focusin', function (event) { focused = event.target.matches(':focus-visible'); if (focused) schedule(); });
  root.addEventListener('focusout', function () { setTimeout(function () { focused = root.contains(document.activeElement) && document.activeElement.matches(':focus-visible'); schedule(); }, 0); });
  document.addEventListener('visibilitychange', schedule);
  reduced.addEventListener('change', schedule);
  if ('IntersectionObserver' in window) new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; schedule(); }, { threshold: 0.1 }).observe(root);
  else { visible = true; schedule(); }
 });
})();
