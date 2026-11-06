/* Taraqqi light/dark appearance toggle. The <head> bootstrap (inc/theme-mode.php) has
 * already applied the saved or device-preferred theme before first paint. */
(function () {
	'use strict';
	var KEY = 'taraqqi-theme';
	var root = document.documentElement;
	var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: light)') : null;
	var reduced = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var LABELS = { light: 'التبديل إلى الوضع الداكن', dark: 'التبديل إلى الوضع الفاتح' };
	var switchTimer = 0;

	function saved() {
		try {
			var value = localStorage.getItem(KEY);
			return value === 'light' || value === 'dark' ? value : null;
		} catch (e) { return null; }
	}
	function current() { return root.getAttribute('data-theme') === 'light' ? 'light' : 'dark'; }
	function sync() {
		var theme = current();
		document.querySelectorAll('[data-ws-theme-toggle]').forEach(function (button) {
			button.setAttribute('aria-label', LABELS[theme]);
			button.setAttribute('title', LABELS[theme]);
		});
		var meta = document.querySelector('meta[data-tq-theme-color]');
		if (meta) meta.setAttribute('content', theme === 'light' ? '#f3f6fd' : '#060818');
	}
	function write(theme) {
		root.setAttribute('data-theme', theme);
		root.style.colorScheme = theme;
		sync();
		document.dispatchEvent(new CustomEvent('taraqqi:themechange', { detail: { theme: theme } }));
	}
	function apply(theme, animate) {
		if (theme === current()) { sync(); return; }
		if (!animate || reduced.matches) { write(theme); return; }
		/* Smooth crossfade where View Transitions exist; otherwise brief colour transitions. */
		if (document.startViewTransition && !document.hidden) {
			root.classList.add('tq-theme-vt');
			var vt = document.startViewTransition(function () { write(theme); });
			vt.finished.then(done, done);
			return;
		}
		root.classList.add('tq-theme-switching');
		write(theme);
		clearTimeout(switchTimer);
		switchTimer = setTimeout(done, 450);
	}
	function done() { root.classList.remove('tq-theme-vt', 'tq-theme-switching'); }

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-ws-theme-toggle]');
		if (!button) return;
		var next = current() === 'light' ? 'dark' : 'light';
		try { localStorage.setItem(KEY, next); } catch (e) { /* private mode: session only */ }
		apply(next, true);
	});

	/* Follow the device setting until the customer makes an explicit choice. */
	if (media) {
		var onScheme = function () { if (!saved()) apply(media.matches ? 'light' : 'dark', true); };
		if (media.addEventListener) media.addEventListener('change', onScheme);
		else if (media.addListener) media.addListener(onScheme);
	}
	/* Keep other open tabs in step. */
	window.addEventListener('storage', function (event) {
		if (event.key === KEY && (event.newValue === 'light' || event.newValue === 'dark')) apply(event.newValue, false);
	});

	sync();
})();
