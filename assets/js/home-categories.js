/* One physical leftward step every two seconds, with a seamless DOM rotation. */
(function () {
 'use strict';
 document.querySelectorAll('[data-category-carousel]').forEach(function (root) {
  var viewport = root.querySelector('.ws-category-viewport'), track = root.querySelector('.ws-category-track');
  var slides = Array.from(track.children), total = slides.length;
  var mobileMode = window.matchMedia('(max-width: 600px)').matches;
  if (total < 2) return;
  if (mobileMode) { enableMobileLoop(viewport, track, slides); return; }
  slides.forEach(function (slide, i) { slide.dataset.categoryIndex = i; });
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)'), touchMode = mobileMode;
  var timer, finishTimer, moving = false, finishMove, hovered = false, focused = false, visible = false, stopped = reduced.matches, startX = null, swiped = false;
  root.classList.add('is-ready');
  /* The carousel rotates slides in and out of the scrollport, so a lazily
   * deferred card could rotate in blank. Load them all once JS takes over. */
  track.querySelectorAll('img[loading="lazy"]').forEach(function (image) { image.loading = 'eager'; });
  if (touchMode) enableInfiniteTouchScroll(viewport, track, slides);
  function columns() { return Math.max(1, parseInt(getComputedStyle(root).getPropertyValue('--category-columns'), 10)); }
  function step() { return track.firstElementChild.getBoundingClientRect().width + parseFloat(getComputedStyle(track).gap); }
  function update() {
   Array.from(track.children).forEach(function (slide, i) {
    if (touchMode && slide.hasAttribute('data-loop-clone')) { slide.inert = false; slide.setAttribute('aria-hidden', 'true'); return; }
    var hidden = !touchMode && i >= columns(); slide.inert = hidden; if (hidden) slide.setAttribute('aria-hidden', 'true'); else slide.removeAttribute('aria-hidden');
   });

  }
  function schedule() {
   clearInterval(timer);
   if (!touchMode && !stopped && !reduced.matches && !hovered && !focused && visible && !document.hidden && total > columns()) timer = setInterval(function () { move(1); }, 2000);
  }
  function move(direction) {
   if (touchMode) { viewport.scrollBy({ left: direction > 0 ? step() : -step(), behavior: reduced.matches ? 'auto' : 'smooth' }); return; }
   if (moving || total <= columns()) return;
   moving = true; var distance = step();
   track.style.transition = 'none';
   if (direction < 0) { track.prepend(track.lastElementChild); track.style.transform = 'translateX(-' + distance + 'px)'; }
   else track.style.transform = 'translateX(0)';
   void track.offsetWidth;
   finishMove = function () {
    clearTimeout(finishTimer); track.style.transition = 'none';
    if (direction > 0) track.append(track.firstElementChild);
    track.style.transform = 'translateX(0)'; moving = false; finishMove = null; update();
   };
   if (reduced.matches) { finishMove(); return; }
   track.style.transition = 'transform 850ms cubic-bezier(.25,.65,.3,1)';
   track.style.transform = direction > 0 ? 'translateX(-' + distance + 'px)' : 'translateX(0)';
   finishTimer = setTimeout(function () { if (finishMove) finishMove(); }, 950);
  }
  track.addEventListener('transitionend', function (event) { if (event.target === track && event.propertyName === 'transform' && finishMove) finishMove(); });
  root.addEventListener('mouseenter', function () { hovered = true; schedule(); });
  root.addEventListener('mouseleave', function () { hovered = false; schedule(); });
  root.addEventListener('focusin', function () { focused = true; schedule(); });
  root.addEventListener('focusout', function () { setTimeout(function () { focused = root.contains(document.activeElement); schedule(); }, 0); });
  viewport.addEventListener('dragstart', function (event) { event.preventDefault(); });
  if (!touchMode) {
   viewport.addEventListener('pointerdown', function (event) { startX = event.clientX; swiped = false; clearInterval(timer); });
   viewport.addEventListener('pointerup', function (event) {
    if (startX !== null && Math.abs(event.clientX - startX) > 40) { swiped = true; move(event.clientX < startX ? 1 : -1); }
    startX = null; schedule();
   });
   viewport.addEventListener('pointercancel', function () { startX = null; schedule(); });
   viewport.addEventListener('click', function (event) { if (swiped) { event.preventDefault(); swiped = false; } }, true);
  }
  viewport.addEventListener('keydown', function (event) {
   if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
   event.preventDefault(); viewport.focus(); move(event.key === 'ArrowLeft' ? 1 : -1);
  });
  document.addEventListener('visibilitychange', schedule);
  reduced.addEventListener('change', function () { if (reduced.matches) stopped = true; update(); schedule(); });
  new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; schedule(); }, { threshold: 0.15 }).observe(root);
  new ResizeObserver(function () { if (finishMove) finishMove(); update(); schedule(); }).observe(viewport);
  update();
 });

  /* A native scrollport with visually identical buffer sets. The motion clock
   * stops for interaction, focus, reduced motion, hidden tabs and offscreen content. */
  function enableMobileLoop(viewport, track, originals) {
   var reduced = matchMedia('(prefers-reduced-motion: reduce)');
   var visible = false, down = false, focused = false, resumeAt = 0, last = 0, remainder = 0, auto = false, frame = 0, wake = 0;
   var startX = 0, startScroll = 0, dragging = false, suppressClick = false, autoUntil = 0;
   rootFor(viewport).classList.add('is-ready');
   function clone(node) {
    var copy = node.cloneNode(true);
    copy.setAttribute('aria-hidden', 'true');
    copy.querySelectorAll('[id]').forEach(function (el) { el.removeAttribute('id'); });
    copy.querySelectorAll('a,button,[tabindex]').forEach(function (el) { el.tabIndex = -1; });
    return copy;
   }
   var before = document.createDocumentFragment(), after = document.createDocumentFragment();
   originals.forEach(function (node) { before.appendChild(clone(node)); after.appendChild(clone(node)); });
   track.prepend(before); track.append(after);
   function width() { return originals.length * (originals[0].getBoundingClientRect().width + (parseFloat(getComputedStyle(track).gap) || 0)); }
   function jump(value) { auto = true; autoUntil = performance.now() + 60; viewport.scrollLeft = value; auto = false; }
   function wrap() {
    var w = width(), x = viewport.scrollLeft;
    if (w && x < w) { jump(x + w); if (dragging) startScroll += w; }
    else if (w && x >= 2 * w) { jump(x - w); if (dragging) startScroll -= w; }
   }
   function place() { jump(width()); }
   place();
   if (document.fonts && document.fonts.ready) document.fonts.ready.then(place);
   window.addEventListener('resize', place, { passive: true });
   viewport.addEventListener('scroll', function () { wrap(); if (!auto && !down && performance.now() > autoUntil) resumeAt = performance.now() + 2200; }, { passive: true });
   viewport.addEventListener('pointerdown', function (e) {
    down = true; resumeAt = performance.now() + 2200;
    if (e.pointerType === 'mouse' && e.button === 0) { startX = e.clientX; startScroll = viewport.scrollLeft; dragging = false; }
   });
   viewport.addEventListener('pointermove', function (e) {
    if (!down || e.pointerType !== 'mouse') return;
    if (Math.abs(e.clientX - startX) > 5) dragging = true;
    if (dragging) { viewport.scrollLeft = startScroll - (e.clientX - startX); wrap(); e.preventDefault(); }
   });
   function release() { if (dragging) { suppressClick = true; setTimeout(function () { suppressClick = false; }, 500); } down = false; dragging = false; resumeAt = performance.now() + 2200; schedule(); }
   window.addEventListener('pointerup', release); window.addEventListener('pointercancel', release);
   viewport.addEventListener('wheel', function () { resumeAt = performance.now() + 2200; schedule(); }, { passive: true });
   viewport.addEventListener('keydown', function () { resumeAt = performance.now() + 2200; schedule(); });
   viewport.addEventListener('dragstart', function (e) { e.preventDefault(); });
   viewport.addEventListener('click', function (e) { if (suppressClick) { e.preventDefault(); e.stopPropagation(); suppressClick = false; } }, true);
   viewport.addEventListener('focusin', function (e) { focused = e.target.matches(':focus-visible'); if (focused) schedule(); });
   viewport.addEventListener('focusout', function () { focused = false; resumeAt = performance.now() + 2200; schedule(); });
   if ('IntersectionObserver' in window) new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; schedule(); }, { threshold: 0.1 }).observe(viewport);
   else visible = true;
   function schedule() {
    cancelAnimationFrame(frame); clearTimeout(wake); frame = 0; last = 0;
    if (!visible || document.hidden || reduced.matches || down || focused) return;
    var wait = resumeAt - performance.now();
    if (wait > 0) wake = setTimeout(schedule, wait);
    else frame = requestAnimationFrame(tick);
   }
   function tick(now) {
    if (!visible || document.hidden || reduced.matches || down || focused || now < resumeAt) { schedule(); return; }
    if (last) {
     remainder += Math.min(now - last, 50) * 0.0144; // 14.4 px/second, 20% faster
     if (remainder >= 1) { var delta = Math.floor(remainder); remainder -= delta; jump(viewport.scrollLeft + delta); wrap(); }
    }
    last = now; frame = requestAnimationFrame(tick);
   }
   document.addEventListener('visibilitychange', schedule);
   reduced.addEventListener('change', schedule);
   schedule();
  }
  function rootFor(viewport) { return viewport.closest('[data-category-carousel]'); }
})();
