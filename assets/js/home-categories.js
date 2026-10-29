/* One physical leftward step every two seconds, with a seamless DOM rotation. */
(function () {
 'use strict';
 document.querySelectorAll('[data-category-carousel]').forEach(function (root) {
  var viewport = root.querySelector('.ws-category-viewport'), track = root.querySelector('.ws-category-track');
  var controls = root.querySelector('.ws-category-controls'), pause = root.querySelector('[data-category-pause]');
  var slides = Array.from(track.children), total = slides.length;
  if (total < 2) return;
  slides.forEach(function (slide, i) { slide.dataset.categoryIndex = i; });
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  var timer, finishTimer, moving = false, finishMove, hovered = false, focused = false, visible = false, stopped = reduced.matches, startX = null, swiped = false;
  root.classList.add('is-ready'); controls.hidden = false;
  function columns() { return Math.max(1, parseInt(getComputedStyle(root).getPropertyValue('--category-columns'), 10)); }
  function step() { return track.firstElementChild.getBoundingClientRect().width + parseFloat(getComputedStyle(track).gap); }
  function update() {
   Array.from(track.children).forEach(function (slide, i) { slide.inert = i >= columns(); if (i >= columns()) slide.setAttribute('aria-hidden', 'true'); else slide.removeAttribute('aria-hidden'); });
   root.querySelector('.ws-category-position').textContent = (Number(track.firstElementChild.dataset.categoryIndex) + 1) + ' / ' + total;
   pause.hidden = reduced.matches;
   pause.setAttribute('aria-pressed', String(stopped)); pause.textContent = stopped ? 'تشغيل الحركة' : 'إيقاف الحركة';
  }
  function schedule() {
   clearInterval(timer);
   if (!stopped && !reduced.matches && !hovered && !focused && visible && !document.hidden && total > columns()) timer = setInterval(function () { move(1); }, 2000);
  }
  function move(direction) {
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
  root.querySelector('[data-category-next]').addEventListener('click', function () { move(1); schedule(); });
  root.querySelector('[data-category-prev]').addEventListener('click', function () { move(-1); schedule(); });
  pause.addEventListener('click', function () { stopped = !stopped; update(); schedule(); });
  root.addEventListener('mouseenter', function () { hovered = true; schedule(); });
  root.addEventListener('mouseleave', function () { hovered = false; schedule(); });
  root.addEventListener('focusin', function () { focused = true; schedule(); });
  root.addEventListener('focusout', function () { setTimeout(function () { focused = root.contains(document.activeElement); schedule(); }, 0); });
  viewport.addEventListener('dragstart', function (event) { event.preventDefault(); });
  viewport.addEventListener('pointerdown', function (event) { startX = event.clientX; swiped = false; clearInterval(timer); });
  viewport.addEventListener('pointerup', function (event) {
   if (startX !== null && Math.abs(event.clientX - startX) > 40) { swiped = true; move(event.clientX < startX ? 1 : -1); }
   startX = null; schedule();
  });
  viewport.addEventListener('pointercancel', function () { startX = null; schedule(); });
  viewport.addEventListener('click', function (event) { if (swiped) { event.preventDefault(); swiped = false; } }, true);
  viewport.addEventListener('keydown', function (event) {
   if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
   event.preventDefault(); root.querySelector(event.key === 'ArrowLeft' ? '[data-category-next]' : '[data-category-prev]').focus(); move(event.key === 'ArrowLeft' ? 1 : -1);
  });
  document.addEventListener('visibilitychange', schedule);
  reduced.addEventListener('change', function () { if (reduced.matches) stopped = true; update(); schedule(); });
  new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; schedule(); }, { threshold: 0.15 }).observe(root);
  new ResizeObserver(function () { if (finishMove) finishMove(); update(); schedule(); }).observe(viewport);
  update();
 });
})();
