/* Homepage: feature-icon entrance, continuous review carousel, FAQ accordion.
 * Markup: inc/home-sections.php. No dependencies. */
(function () {
	'use strict';
	var reduced = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false, addEventListener: function () {} };
	function onReducedChange(fn) {
		if (reduced.addEventListener) reduced.addEventListener('change', fn);
		else if (reduced.addListener) reduced.addListener(fn);
	}

	/* ---------- Feature icons: staggered entrance once in view ---------- */
	var features = document.querySelector('[data-tq-features]');
	if (features) {
		if ('IntersectionObserver' in window && !reduced.matches) {
			features.classList.add('is-armed');
			var io = new IntersectionObserver(function (entries) {
				if (!entries.some(function (e) { return e.isIntersecting; })) return;
				features.classList.add('is-inview');
				io.disconnect();
			}, { rootMargin: '0px 0px -12% 0px', threshold: 0.15 });
			io.observe(features);
			/* Never leave the row invisible if the observer does not fire. */
			setTimeout(function () { features.classList.add('is-inview'); }, 6000);
		} else {
			features.classList.add('is-inview');
		}
	}

	/* ---------- Reviews: one smooth loop, with manual movement both ways ---------- */
	var reviews = document.querySelector('[data-tq-reviews]');
	if (reviews && window.WordsiteSmoothCarousel) {
		var viewport = reviews.querySelector('[data-tq-reviews-viewport]');
		var track = reviews.querySelector('[data-tq-reviews-track]');
		var group = track && track.querySelector('.tq-reviews__group');
		var toggle = reviews.querySelector('[data-tq-reviews-toggle]');
		if (viewport && track && group && group.children.length > 1) {
			var oldCopy = track.querySelector('.tq-reviews__group--clone');
			if (oldCopy) oldCopy.remove();
			reviews.classList.add('is-moving');
			viewport.tabIndex = 0;
			viewport.setAttribute('aria-label', 'تجارب العملاء: اسحب أو استخدم مفاتيح الأسهم للتنقل');
			var carousel = window.WordsiteSmoothCarousel({ root: reviews, viewport: viewport, track: track,
				items: [group], direction: -1, speed: function () { return window.innerWidth < 600 ? 32 : 44; }
			});
			if (toggle && carousel) {
				toggle.hidden = reduced.matches;
				var userPaused = false;
				toggle.addEventListener('click', function () {
					userPaused = !userPaused;
					toggle.setAttribute('aria-pressed', String(userPaused));
					toggle.setAttribute('aria-label', userPaused ? 'تشغيل حركة التقييمات' : 'إيقاف حركة التقييمات');
					reviews.classList.toggle('is-paused', userPaused);
					carousel.setPaused(userPaused);
				});
				onReducedChange(function () { toggle.hidden = reduced.matches; });
			}
		}
	}

	/* ---------- FAQ: native <details>, smooth open/close ---------- */
	document.querySelectorAll('[data-tq-faq] .tq-faq__item').forEach(function (item) {
		var summary = item.querySelector('summary');
		var panel = item.querySelector('.tq-faq__a');
		var running = null;
		summary.addEventListener('click', function (e) {
			if (reduced.matches || typeof panel.animate !== 'function') return; /* native toggle */
			e.preventDefault();
			var opening = !item.open || item.classList.contains('is-closing');
			var from = panel.getBoundingClientRect().height;
			if (running) running.cancel();
			if (opening) {
				item.classList.remove('is-closing');
				item.open = true;
				item.classList.add('is-open');
				var to = panel.scrollHeight;
				running = panel.animate([{ height: from + 'px', opacity: 0.35 }, { height: to + 'px', opacity: 1 }], { duration: 360, easing: 'cubic-bezier(.2,.75,.25,1)' });
			} else {
				item.classList.add('is-closing');
				item.classList.remove('is-open');
				running = panel.animate([{ height: from + 'px', opacity: 1 }, { height: '0px', opacity: 0 }], { duration: 300, easing: 'cubic-bezier(.4,0,.2,1)' });
			}
			running.onfinish = function () {
				running = null;
				if (!opening) { item.open = false; item.classList.remove('is-closing'); }
			};
		});
		/* Keep state classes right when opened natively (e.g. find-in-page). */
		item.addEventListener('toggle', function () {
			if (!item.classList.contains('is-closing')) item.classList.toggle('is-open', item.open);
		});
	});
})();
