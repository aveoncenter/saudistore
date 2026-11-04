/* Taraqqi homepage banners: cross-fade automatically every three seconds. */
(function () {
	'use strict';

	function initBannerCarousels() {
		document.querySelectorAll('[data-taraqqi-banner-carousel]').forEach(function (carousel) {
			var slides = Array.prototype.slice.call(carousel.querySelectorAll('.ws-banner-carousel__slide'));
			if (slides.length < 2) return;

			var activeIndex = 0;
			var intervalId = null;

			function showSlide(nextIndex) {
				slides[activeIndex].classList.remove('is-active');
				slides[activeIndex].setAttribute('aria-hidden', 'true');
				activeIndex = nextIndex % slides.length;
				slides[activeIndex].classList.add('is-active');
				slides[activeIndex].setAttribute('aria-hidden', 'false');
			}

			function startRotation() {
				if (intervalId !== null) return;
				intervalId = window.setInterval(function () {
					if (!document.hidden) showSlide(activeIndex + 1);
				}, 3000);
			}

			function stopRotation() {
				if (intervalId === null) return;
				window.clearInterval(intervalId);
				intervalId = null;
			}

			document.addEventListener('visibilitychange', function () {
				if (document.hidden) stopRotation();
				else startRotation();
			});

			startRotation();
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initBannerCarousels, { once: true });
	} else {
		initBannerCarousels();
	}
})();
