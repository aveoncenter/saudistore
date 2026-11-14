/* Continuous category cards, with bidirectional touch and mouse dragging. */
(function () {
 'use strict';
 if (!window.WordsiteSmoothCarousel) return;
 document.querySelectorAll('[data-category-carousel]').forEach(function (root) {
  var viewport = root.querySelector('.ws-category-viewport');
  var track = root.querySelector('.ws-category-track');
  if (!viewport || !track || track.children.length < 2) return;
  root.classList.add('is-ready');
  track.querySelectorAll('img[loading="lazy"]').forEach(function (img) { img.loading = 'eager'; });
  window.WordsiteSmoothCarousel({ root: root, viewport: viewport, track: track,
   items: Array.from(track.children), direction: 1,
   /* Move 10% slower than the previous version on all screen sizes. */
   speed: function (cycle, count) { return (window.innerWidth <= 600 ? 18 : cycle / count / 1.6) * 0.9; }
  });
 });
})();
