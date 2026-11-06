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

	/* ---------- Reviews: seamless, slow, pausable marquee ---------- */
	var reviews = document.querySelector('[data-tq-reviews]');
	if (reviews && typeof Element.prototype.animate === 'function') {
		var viewport = reviews.querySelector('[data-tq-reviews-viewport]');
		var track = reviews.querySelector('[data-tq-reviews-track]');
		var group = track.querySelector('.tq-reviews__group');
		var toggle = reviews.querySelector('[data-tq-reviews-toggle]');
		/* Constant, calm drift speed (px/s) regardless of how many reviews there are. */
		var CYCLE_MS = 60000;
		function speed() { return window.innerWidth < 600 ? 32 : 44; }
		var anim = null, setWidth = 0;
		var hovered = false, dragging = false, offscreen = false, userPaused = false, resumeTimer = 0;
		var drag = null;

		function build() {
			setWidth = group.getBoundingClientRect().width;
			if (!setWidth) return;
			var progress = anim && anim.effect && anim.currentTime != null ? (anim.currentTime % CYCLE_MS) / CYCLE_MS : 0;
			if (anim) anim.cancel();
			CYCLE_MS = Math.max(20000, Math.round((setWidth / speed()) * 1000));
			/* LTR track: content drifts towards the right, revealing cards from the left. */
			anim = track.animate(
				[{ transform: 'translate3d(' + (-setWidth) + 'px,0,0)' }, { transform: 'translate3d(0,0,0)' }],
				{ duration: CYCLE_MS, iterations: Infinity, easing: 'linear' }
			);
			anim.currentTime = progress * CYCLE_MS;
			update();
		}
		function shouldRun() { return !hovered && !dragging && !offscreen && !userPaused && !document.hidden; }
		function update() {
			if (!anim) return;
			if (shouldRun()) { if (anim.playState !== 'running') anim.play(); }
			else if (anim.playState === 'running') anim.pause();
		}
		function start() {
			if (reduced.matches) { stop(); return; }
			reviews.classList.add('is-moving');
			if (toggle) toggle.hidden = false;
			build();
		}
		function stop() {
			if (anim) { anim.cancel(); anim = null; }
			reviews.classList.remove('is-moving');
			if (toggle) toggle.hidden = true;
		}
		function wrap(time) { return ((time % CYCLE_MS) + CYCLE_MS) % CYCLE_MS; }

		/* Pause on hover (fine pointers only) and keyboard focus inside the section. */
		reviews.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') { hovered = true; update(); } });
		reviews.addEventListener('pointerleave', function (e) { if (e.pointerType === 'mouse') { hovered = false; update(); } });

		/* Touch drag scrubs the loop horizontally; vertical panning stays native (touch-action: pan-y). */
		viewport.addEventListener('pointerdown', function (e) {
			if (!anim || e.pointerType === 'mouse') return;
			clearTimeout(resumeTimer);
			drag = { id: e.pointerId, x: e.clientX, y: e.clientY, time: anim.currentTime || 0, active: false };
		});
		viewport.addEventListener('pointermove', function (e) {
			if (!drag || e.pointerId !== drag.id || !anim) return;
			var dx = e.clientX - drag.x;
			if (!drag.active) {
				if (Math.abs(dx) < 6 || Math.abs(dx) < Math.abs(e.clientY - drag.y)) return;
				drag.active = true;
				dragging = true;
				update();
				try { viewport.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
			}
			anim.currentTime = wrap(drag.time + (dx / setWidth) * CYCLE_MS);
		});
		function endDrag(e) {
			if (!drag || (e && e.pointerId !== drag.id)) return;
			var wasActive = drag.active;
			drag = null;
			if (!wasActive) return;
			clearTimeout(resumeTimer);
			resumeTimer = setTimeout(function () { dragging = false; update(); }, 1400);
		}
		viewport.addEventListener('pointerup', endDrag);
		viewport.addEventListener('pointercancel', endDrag);
		viewport.addEventListener('lostpointercapture', endDrag);

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
			new IntersectionObserver(function (entries) {
				offscreen = !entries[0].isIntersecting;
				update();
			}, { rootMargin: '120px 0px' }).observe(reviews);
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
		} else {
			window.addEventListener('resize', function () { if (anim) build(); });
		}
		onReducedChange(function () { if (reduced.matches) stop(); else start(); });
		reviews.classList.add('is-ready');
		if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { if (anim) build(); });
		start();
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
