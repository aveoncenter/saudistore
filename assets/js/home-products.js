/* Shared live-product shelves. Only the most-requested shelf autoplays. */
(function () {
 'use strict';
 document.querySelectorAll('[data-home-shelf]').forEach(function (root) {
  var viewport = root.querySelector('.ws-home-shelf__viewport'), track = root.querySelector('.ws-home-shelf__track');
  if (!track) return;
  var cards = Array.from(track.children), total = cards.length;
  var arrows = root.querySelector('.ws-home-shelf__arrows'), reduced = matchMedia('(prefers-reduced-motion: reduce)');
  var timer, fallback, moving = false, finish, hovered = false, focused = false, visible = false, startX = null, swiped = false;
  var nextIndex = total;
  var interval = Number(root.dataset.autoplay), rtl = getComputedStyle(track).direction === 'rtl';
  cards.forEach(function (card, index) { card.dataset.shelfIndex = index; });
  root.classList.add('is-ready');
  function columns(includePartial) {
   var card = track.firstElementChild;
   if (!card) return 1;
   var gap = parseFloat(getComputedStyle(track).gap) || 0;
   var count = (track.clientWidth + gap) / (parseFloat(getComputedStyle(card).width) + gap);
   return Math.max(1, includePartial ? Math.ceil(count - 0.01) : Math.floor(count + 0.01));
  }
  function update() {
   total = track.children.length;
   Array.from(track.children).forEach(function (card) { if (!card.hasAttribute('data-shelf-index')) card.dataset.shelfIndex = nextIndex++; });
   arrows.hidden = total <= columns();
   root.classList.toggle('is-static', total <= columns());
   Array.from(track.children).forEach(function (card, index) { card.inert = index >= columns(true); if (index >= columns(true)) card.setAttribute('aria-hidden', 'true'); else card.removeAttribute('aria-hidden'); });
  }
  function schedule() {
   clearInterval(timer);
   if (interval && total > columns() && !reduced.matches && !hovered && !focused && visible && !document.hidden && !document.querySelector('.ws-quick[open]')) timer = setInterval(function () { move(rtl ? -1 : 1); }, interval);
  }
  function move(direction) {
   if (moving || total <= columns()) return;
   moving = true;
   var step = parseFloat(getComputedStyle(track.firstElementChild).width) + parseFloat(getComputedStyle(track).gap);
   track.style.transition = 'none';
   if (direction < 0) { track.prepend(track.lastElementChild); track.style.transform = 'translateX(' + (rtl ? step : -step) + 'px)'; }
   else track.style.transform = 'translateX(0)';
   void track.offsetWidth;
   finish = function () {
    clearTimeout(fallback); track.style.transition = 'none';
    if (direction > 0) track.append(track.firstElementChild);
    track.style.transform = 'translateX(0)'; moving = false; finish = null; update();
   };
   if (reduced.matches) { finish(); return; }
   track.style.transition = 'transform 700ms cubic-bezier(.25,.65,.3,1)';
   track.style.transform = direction > 0 ? 'translateX(' + (rtl ? step : -step) + 'px)' : 'translateX(0)';
   fallback = setTimeout(function () { if (finish) finish(); }, 800);
  }
  track.addEventListener('transitionend', function (event) { if (event.target === track && event.propertyName === 'transform' && finish) finish(); });
  root.querySelector('[data-shelf-next]').addEventListener('click', function () { move(1); schedule(); });
  root.querySelector('[data-shelf-prev]').addEventListener('click', function () { move(-1); schedule(); });
  root.addEventListener('mouseenter', function () { hovered = true; schedule(); });
  root.addEventListener('mouseleave', function () { hovered = false; schedule(); });
  root.addEventListener('focusin', function () { focused = true; schedule(); });
  root.addEventListener('focusout', function () { setTimeout(function () { focused = root.contains(document.activeElement); schedule(); }, 0); });
  viewport.addEventListener('dragstart', function (event) { event.preventDefault(); });
  viewport.addEventListener('pointerdown', function (event) { startX = event.clientX; swiped = false; clearInterval(timer); });
  viewport.addEventListener('pointerup', function (event) { if (startX !== null && Math.abs(event.clientX - startX) > 40) { swiped = true; move((event.clientX < startX ? 1 : -1) * (rtl ? -1 : 1)); } startX = null; schedule(); });
  viewport.addEventListener('pointercancel', function () { startX = null; schedule(); });
  viewport.addEventListener('click', function (event) { if (swiped) { event.preventDefault(); swiped = false; } }, true);
  viewport.addEventListener('keydown', function (event) { if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return; event.preventDefault(); viewport.focus(); move((event.key === 'ArrowLeft' ? 1 : -1) * (rtl ? -1 : 1)); });
  document.addEventListener('visibilitychange', schedule);
  var dialog = document.querySelector('.ws-quick');
  if (dialog) new MutationObserver(schedule).observe(dialog, { attributes: true, attributeFilter: ['open'] });
  reduced.addEventListener('change', schedule);
  if ('IntersectionObserver' in window) new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; schedule(); }, { threshold: 0.15 }).observe(root);
  else { visible = true; schedule(); }
  function resize() { if (finish) finish(); update(); schedule(); }
  if ('ResizeObserver' in window) new ResizeObserver(resize).observe(viewport);
  else window.addEventListener('resize', resize);
  new MutationObserver(function () { if (!moving) { update(); schedule(); } }).observe(track, { childList: true });
  update();
 });
})();
