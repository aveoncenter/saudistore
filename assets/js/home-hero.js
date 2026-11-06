/**
 * Homepage hero carousel: 3 s autoplay, infinite loop, crossfade, dots/arrows,
 * keyboard, touch swipe. Pauses on hover, keyboard focus, touch, hidden tab and
 * when off-screen; reduced-motion users get no autoplay and an instant switch.
 */
(function () {
	'use strict';

	var FADE_MS = 650;
	var SWIPE_PX = 40;

	function init(root) {
		var slides = Array.prototype.slice.call(root.querySelectorAll('.ws-hero-slide'));
		if (slides.length < 2) {
			return;
		}
		var dots = Array.prototype.slice.call(root.querySelectorAll('[data-hero-dot]'));
		var viewport = root.querySelector('.ws-hero-carousel__viewport');
		var frame = root.querySelector('.ws-hero-carousel__frame');
		var interval = parseInt(root.getAttribute('data-interval'), 10) || 3000;
		var reduce = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
		var rtl = (root.getAttribute('dir') || document.documentElement.dir || 'rtl') === 'rtl';
		var index = 0;
		var timer = 0;
		var leaveTimer = 0;
		var hovering = false;
		var touching = false;
		var keyboardFocus = false;
		var inView = true;
		var suppressClick = false;
		var start = null;

		function go(next) {
			next = (next + slides.length) % slides.length;
			if (next === index) {
				schedule();
				return;
			}
			var prev = slides[index];
			slides.forEach(function (s) { s.classList.remove('is-leaving'); });
			clearTimeout(leaveTimer);
			prev.classList.add('is-leaving');
			leaveTimer = setTimeout(function () { prev.classList.remove('is-leaving'); }, reduce.matches ? 0 : FADE_MS);

			slides.forEach(function (slide, i) {
				var active = i === next;
				var link = slide.querySelector('a');
				slide.classList.toggle('is-active', active);
				if (active) {
					slide.removeAttribute('aria-hidden');
					slide.removeAttribute('inert');
					if (link) { link.removeAttribute('tabindex'); }
				} else {
					slide.setAttribute('aria-hidden', 'true');
					slide.setAttribute('inert', '');
					if (link) { link.setAttribute('tabindex', '-1'); }
				}
			});
			dots.forEach(function (dot, i) {
				dot.classList.toggle('is-active', i === next);
				if (i === next) {
					dot.setAttribute('aria-current', 'true');
				} else {
					dot.removeAttribute('aria-current');
				}
			});
			if (prev.contains(document.activeElement)) {
				var nextLink = slides[next].querySelector('a');
				if (nextLink) { nextLink.focus({ preventScroll: true }); }
			}
			index = next;
			root.setAttribute('data-active-slide', String(index));
			schedule();
		}

		function paused() {
			return reduce.matches || hovering || touching || keyboardFocus || !inView || document.hidden;
		}

		// Every (re)start waits a full interval, so resuming never jumps instantly.
		function schedule() {
			clearTimeout(timer);
			timer = 0;
			root.classList.toggle('is-paused', paused());
			if (!paused()) {
				timer = setTimeout(function () { go(index + 1); }, interval);
			}
		}

		dots.forEach(function (dot) {
			dot.addEventListener('click', function () {
				go(parseInt(dot.getAttribute('data-hero-dot'), 10) || 0);
			});
		});
		var prevBtn = root.querySelector('[data-hero-prev]');
		var nextBtn = root.querySelector('[data-hero-next]');
		if (prevBtn) { prevBtn.addEventListener('click', function () { go(index - 1); }); }
		if (nextBtn) { nextBtn.addEventListener('click', function () { go(index + 1); }); }

		root.addEventListener('keydown', function (e) {
			if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
				return;
			}
			e.preventDefault();
			var forward = (e.key === 'ArrowLeft') === rtl;
			go(index + (forward ? 1 : -1));
		});

		// Hover pause (mouse/pen only; touch has its own lifecycle).
		frame.addEventListener('pointerenter', function (e) {
			if (e.pointerType !== 'touch') { hovering = true; schedule(); }
		});
		frame.addEventListener('pointerleave', function (e) {
			if (e.pointerType !== 'touch') { hovering = false; schedule(); }
		});
		var dotsWrap = root.querySelector('.ws-hero-dots');
		if (dotsWrap) {
			dotsWrap.addEventListener('pointerenter', function (e) {
				if (e.pointerType !== 'touch') { hovering = true; schedule(); }
			});
			dotsWrap.addEventListener('pointerleave', function (e) {
				if (e.pointerType !== 'touch') { hovering = false; schedule(); }
			});
		}

		// Keyboard focus pause (mouse clicks leave non-visible focus and do not pause forever).
		root.addEventListener('focusin', function (e) {
			var el = e.target;
			var visible = true;
			try { visible = el.matches(':focus-visible'); } catch (err) { /* old browsers */ }
			keyboardFocus = visible;
			schedule();
		});
		root.addEventListener('focusout', function (e) {
			if (!root.contains(e.relatedTarget)) {
				keyboardFocus = false;
				schedule();
			}
		});

		// Swipe (touch and mouse drag). touch-action: pan-y keeps vertical page scrolling native.
		viewport.addEventListener('pointerdown', function (e) {
			if (e.pointerType === 'mouse' && e.button !== 0) {
				return;
			}
			start = { x: e.clientX, y: e.clientY, id: e.pointerId };
			suppressClick = false;
			if (e.pointerType === 'touch') { touching = true; schedule(); }
		});
		viewport.addEventListener('pointermove', function (e) {
			if (start && e.pointerId === start.id && Math.abs(e.clientX - start.x) > 8) {
				suppressClick = true;
			}
		});
		function end(e) {
			if (!start || e.pointerId !== start.id) {
				return;
			}
			var dx = e.clientX - start.x;
			var dy = e.clientY - start.y;
			start = null;
			if (e.type !== 'pointercancel' && Math.abs(dx) >= SWIPE_PX && Math.abs(dx) > Math.abs(dy) * 1.2) {
				// RTL: dragging toward the right reveals the next slide.
				var forward = rtl ? dx > 0 : dx < 0;
				go(index + (forward ? 1 : -1));
			}
			if (e.pointerType === 'touch') { touching = false; schedule(); }
		}
		viewport.addEventListener('pointerup', end);
		viewport.addEventListener('pointercancel', function (e) {
			// Vertical scrolling takes over the pointer: just resume.
			start = null;
			if (e.pointerType === 'touch') { touching = false; schedule(); }
		});
		viewport.addEventListener('click', function (e) {
			if (suppressClick) {
				e.preventDefault();
				e.stopPropagation();
				suppressClick = false;
			}
		}, true);
		viewport.addEventListener('dragstart', function (e) { e.preventDefault(); });

		document.addEventListener('visibilitychange', schedule);
		if (reduce.addEventListener) {
			reduce.addEventListener('change', schedule);
		} else if (reduce.addListener) {
			reduce.addListener(schedule);
		}
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				inView = entries[entries.length - 1].isIntersecting;
				schedule();
			}, { threshold: 0.25 }).observe(frame);
		}

		root.classList.add('is-ready');
		root.setAttribute('data-active-slide', '0');
		schedule();
	}

	function boot() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-hero-carousel]'), init);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
