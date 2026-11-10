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

	/* ---------- Reviews: continuously moving, with native touch swiping ---------- */
	var reviews = document.querySelector('[data-tq-reviews]');
	if (reviews) {
		var viewport = reviews.querySelector('[data-tq-reviews-viewport]');
		var track = reviews.querySelector('[data-tq-reviews-track]');
		var group = track && track.querySelector('.tq-reviews__group');
		var toggle = reviews.querySelector('[data-tq-reviews-toggle]');
		var CYCLE_MS = 60000, anim = null, setWidth = 0;
		var hoveredCard = null, offscreen = false, userPaused = false;

		function speed() { return window.innerWidth < 600 ? 32 : 44; }
		function wrap(time) { return ((time % CYCLE_MS) + CYCLE_MS) % CYCLE_MS; }
		function update() {
			if (!anim) return;
			var shouldRun = !hoveredCard && !offscreen && !userPaused && !document.hidden;
			if (shouldRun && anim.playState !== 'running') anim.play();
			else if (!shouldRun && anim.playState === 'running') anim.pause();
		}
		function build() {
			if (!group || !track || typeof track.animate !== 'function') return false;
			setWidth = group.getBoundingClientRect().width;
			if (!setWidth) return false;
			var progress = anim && anim.currentTime != null ? (anim.currentTime % CYCLE_MS) / CYCLE_MS : 0;
			if (anim) anim.cancel();
			CYCLE_MS = Math.max(20000, Math.round(setWidth / speed() * 1000));
			anim = track.animate(
				[{ transform: 'translate3d(' + (-setWidth) + 'px,0,0)' }, { transform: 'translate3d(0,0,0)' }],
				{ duration: CYCLE_MS, iterations: Infinity, easing: 'linear' }
			);
			anim.currentTime = progress * CYCLE_MS;
			update();
			return true;
		}
		function cardFromTarget(target) { return target && target.closest ? target.closest('.tq-review') : null; }

		if (track && group && viewport && typeof track.animate === 'function') {
			track.addEventListener('pointerover', function (e) {
				if (e.pointerType !== 'mouse') return;
				var card = cardFromTarget(e.target);
				if (!card || !track.contains(card)) return;
				hoveredCard = card;
				update();
			});
			track.addEventListener('pointerout', function (e) {
				if (e.pointerType !== 'mouse') return;
				var card = cardFromTarget(e.target);
				if (!card || card !== hoveredCard || cardFromTarget(e.relatedTarget) === card) return;
				hoveredCard = null;
				update();
			});

			/* Touch swipes must never pause the automatic review movement.
			   The viewport remains natively horizontally scrollable on mobile. */
			if (toggle) {
				toggle.addEventListener('click', function () {
					userPaused = !userPaused;
					toggle.setAttribute('aria-pressed', String(userPaused));
					toggle.setAttribute('aria-label', userPaused ? 'تشغيل حركة التقييمات' : 'إيقاف حركة التقييمات');
					reviews.classList.toggle('is-paused', userPaused);
					update();
				});
			}
			if ('IntersectionObserver' in window) {
				new IntersectionObserver(function (entries) { offscreen = !entries[0].isIntersecting; update(); }, { rootMargin: '120px 0px' }).observe(reviews);
			}
			document.addEventListener('visibilitychange', update);
			if (window.ResizeObserver) {
				var lastWidth = 0;
				new ResizeObserver(function () {
					var width = group.getBoundingClientRect().width;
					if (Math.abs(width - lastWidth) < 1) return;
					lastWidth = width;
					if (reviews.classList.contains('is-moving')) build();
				}).observe(group);
			} else window.addEventListener('resize', function () { if (anim) build(); });
			if (reduced.matches) {
				reviews.classList.remove('is-moving');
				if (toggle) toggle.hidden = true;
			} else {
				reviews.classList.add('is-moving');
				if (toggle) toggle.hidden = false;
				build();
			}
			onReducedChange(function () {
				if (reduced.matches) {
					if (anim) anim.cancel();
					anim = null;
					reviews.classList.remove('is-moving');
					if (toggle) toggle.hidden = true;
				} else {
					reviews.classList.add('is-moving');
					if (toggle) toggle.hidden = false;
					build();
				}
			});
			if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { if (anim) build(); });
		} else {
			/* CSS/DOM fallback: leave the review track manually swipeable. */
			reviews.classList.remove('is-moving');
			if (toggle) toggle.hidden = true;
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
