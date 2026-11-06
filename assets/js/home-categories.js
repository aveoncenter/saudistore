/* One physical leftward step every two seconds, with a seamless DOM rotation. */
(function () {
 'use strict';
 document.querySelectorAll('[data-category-carousel]').forEach(function (root) {
  var viewport = root.querySelector('.ws-category-viewport'), track = root.querySelector('.ws-category-track');
  var slides = Array.from(track.children), total = slides.length;
  var mobileMode = window.matchMedia('(max-width: 600px)').matches;
  if (total < 2) return;
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

  /* Duplicate one complete card set on both sides. While the user scrolls
   * through a duplicate set, shift by exactly one set width so the motion
   * remains visually continuous and never reaches a hard end. */
  function enableInfiniteTouchScroll(viewport, track, originals) {
   if (originals.length < 3) return;
   function cloneForLoop(node) {
    var clone = node.cloneNode(true);
    clone.setAttribute('data-loop-clone', 'true');
    clone.setAttribute('aria-hidden', 'true');
    clone.removeAttribute('id');
    clone.querySelectorAll('[id]').forEach(function (item) { item.removeAttribute('id'); });
    clone.querySelectorAll('a, button, input, select, textarea, [tabindex]').forEach(function (item) { item.setAttribute('tabindex', '-1'); });
    return clone;
   }
   var before = document.createDocumentFragment(), after = document.createDocumentFragment();
   originals.forEach(function (item) { before.appendChild(cloneForLoop(item)); });
   originals.forEach(function (item) { after.appendChild(cloneForLoop(item)); });
   track.insertBefore(before, track.firstChild);
   track.appendChild(after);
   function setWidth() {
    var item = track.children[originals.length];
    if (!item) return 0;
    var gap = parseFloat(getComputedStyle(track).gap) || 0;
    return originals.length * (item.getBoundingClientRect().width + gap);
   }
   /* Reposition without smooth scrolling, and ignore the scroll events the jump
    * itself emits, otherwise the handler below keeps adding a set width and the
    * strip lands in the middle of the loop instead of on the first card. */
   var jumpUntil = 0;
   function placeAtRealSet() {
    var width = setWidth();
    if (width <= 0) return;
    var previous = viewport.style.scrollBehavior;
    jumpUntil = Date.now() + 150;
    viewport.style.scrollBehavior = 'auto';
    viewport.scrollLeft = width;
    viewport.style.scrollBehavior = previous;
   }
   placeAtRealSet();
   viewport.addEventListener('scroll', function () {
    if (Date.now() < jumpUntil) return;
    var width = setWidth();
    if (!width) return;
    var position = viewport.scrollLeft;
    if (position < width - 1) {
     viewport.scrollLeft = position + width;
    } else if (position >= (2 * width) - 1) {
     viewport.scrollLeft = position - width;
    }
   }, { passive: true });
   if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(placeAtRealSet);
   }
   window.addEventListener('resize', placeAtRealSet, { passive: true });
  }

})();
