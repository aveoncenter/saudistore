/* Shared, subpixel looping carousel. Visual copies never become new records. */
(function () {
 'use strict';
 window.WordsiteSmoothCarousel = function (options) {
  var root = options.root, viewport = options.viewport, track = options.track;
  var originals = options.items, reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (!root || !viewport || !track || !originals.length) return null;
  var base = 0, cycle = 0, position = 0, raf = 0, wake = 0, lastFrame = 0;
  var visible = !('IntersectionObserver' in window), hovered = false, focused = false;
  var paused = false, pointer = null, suppressClick = false, resumeAt = 0;
  var resizeTimer, lastViewportWidth = 0;
  function mod(value, length) { return ((value % length) + length) % length; }
  function copy(node) {
   var el = node.cloneNode(true);
   el.setAttribute('data-smooth-clone', '');
   el.setAttribute('aria-hidden', 'true');
   if (el.hasAttribute('id')) el.removeAttribute('id');
   el.querySelectorAll('[id]').forEach(function (child) { child.removeAttribute('id'); });
   el.querySelectorAll('a,button,input,select,textarea,[tabindex]').forEach(function (child) { child.tabIndex = -1; });
   return el;
  }
  function render() {
   if (!cycle) return;
   position = mod(position, cycle);
   track.style.setProperty('transform', 'translate3d(' + (-(base + position)) + 'px,0,0)', 'important');
  }
  function build() {
   var progress = cycle ? position / cycle : 0;
   track.querySelectorAll('[data-smooth-clone]').forEach(function (el) { el.remove(); });
   track.style.removeProperty('transform');
   var rect = originals[0].getBoundingClientRect();
   var last = originals[originals.length - 1].getBoundingClientRect();
   var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
   cycle = last.right - rect.left + gap;
   if (!(cycle > 0) || !viewport.clientWidth) { cycle = 0; return; }
   var buffers = Math.max(1, Math.ceil(viewport.clientWidth / cycle));
   var before = document.createDocumentFragment(), after = document.createDocumentFragment();
   for (var i = 0; i < buffers; i++) originals.forEach(function (item) { before.appendChild(copy(item)); after.appendChild(copy(item)); });
   track.prepend(before); track.append(after);
   var trackStart = track.getBoundingClientRect().left;
   base = originals[0].getBoundingClientRect().left - trackStart;
   /* Measure the actual set spacing rather than relying on rounded CSS widths. */
   var next = originals[originals.length - 1].nextElementSibling;
   if (next) cycle = next.getBoundingClientRect().left - originals[0].getBoundingClientRect().left;
   position = progress * cycle;
   viewport.scrollLeft = 0;
   render();
  }
  function schedule() {
   cancelAnimationFrame(raf); clearTimeout(wake); raf = 0; lastFrame = 0;
   if (!visible || document.hidden || reduced.matches || hovered || focused || paused || pointer || !cycle) return;
   var delay = resumeAt - performance.now();
   if (delay > 0) wake = setTimeout(schedule, delay);
   else raf = requestAnimationFrame(tick);
  }
  function tick(now) {
   if (lastFrame) {
    position += options.direction * options.speed(cycle, originals.length) * Math.min(now - lastFrame, 50) / 1000;
    render();
   }
   lastFrame = now;
   raf = requestAnimationFrame(tick);
  }
  function settle() { resumeAt = performance.now() + 650; schedule(); }
  root.classList.add('ws-smooth-active');
  build();
  root.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') { hovered = true; schedule(); } });
  root.addEventListener('pointerleave', function (e) { if (e.pointerType === 'mouse') { hovered = false; schedule(); } });
  viewport.addEventListener('pointerdown', function (e) {
   if (!e.isPrimary || (e.pointerType === 'mouse' && e.button !== 0)) return;
   pointer = { id: e.pointerId, x: e.clientX, y: e.clientY, previousX: e.clientX, dragging: false, vertical: false };
   schedule();
  });
  window.addEventListener('pointermove', function (e) {
   if (!pointer || e.pointerId !== pointer.id || pointer.vertical) return;
   var dx = e.clientX - pointer.x, dy = e.clientY - pointer.y;
   if (!pointer.dragging && Math.max(Math.abs(dx), Math.abs(dy)) > 6) {
    if (Math.abs(dy) >= Math.abs(dx)) { pointer.vertical = true; return; }
    pointer.dragging = true;
    viewport.classList.add('is-dragging');
    try { viewport.setPointerCapture(e.pointerId); } catch (_) {}
   }
   if (pointer.dragging) {
    position -= e.clientX - pointer.previousX;
    render();
    if (e.cancelable) e.preventDefault();
   }
   pointer.previousX = e.clientX;
  }, { passive: false });
  function release(e) {
   if (!pointer || e.pointerId !== pointer.id) return;
   if (pointer.dragging) { suppressClick = true; setTimeout(function () { suppressClick = false; }, 500); }
   pointer = null; viewport.classList.remove('is-dragging'); settle();
  }
  window.addEventListener('pointerup', release);
  window.addEventListener('pointercancel', release);
  viewport.addEventListener('dragstart', function (e) { e.preventDefault(); });
  viewport.addEventListener('click', function (e) { if (suppressClick) { e.preventDefault(); e.stopPropagation(); suppressClick = false; } }, true);
  viewport.addEventListener('keydown', function (e) {
   if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
   e.preventDefault(); position += e.key === 'ArrowRight' ? 100 : -100; render(); settle();
  });
  root.addEventListener('focusin', function (e) {
   focused = e.target.matches(':focus-visible');
   if (focused && track.contains(e.target)) {
    var target = e.target.getBoundingClientRect(), bounds = viewport.getBoundingClientRect();
    if (target.left < bounds.left) position += target.left - bounds.left - 8;
    else if (target.right > bounds.right) position += target.right - bounds.right + 8;
    viewport.scrollLeft = 0; render();
   }
   schedule();
  });
  root.addEventListener('focusout', function () { setTimeout(function () { focused = root.contains(document.activeElement) && document.activeElement.matches(':focus-visible'); schedule(); }, 0); });
  document.addEventListener('visibilitychange', schedule);
  if (reduced.addEventListener) reduced.addEventListener('change', schedule);
  else if (reduced.addListener) reduced.addListener(schedule);
  if ('IntersectionObserver' in window) new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; schedule(); }, { threshold: 0.1 }).observe(root);
  function resize() { clearTimeout(resizeTimer); resizeTimer = setTimeout(function () { build(); schedule(); }, 100); }
  window.addEventListener('resize', resize, { passive: true });
  if ('ResizeObserver' in window) new ResizeObserver(function () { var w = viewport.clientWidth; if (w !== lastViewportWidth) { lastViewportWidth = w; resize(); } }).observe(viewport);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { build(); schedule(); });
  schedule();
  return { setPaused: function (value) { paused = value; schedule(); } };
 };
})();
