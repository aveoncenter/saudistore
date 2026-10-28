/* Saudistore – floating glass header, with no layout jump. */
(function () {
	'use strict';
	var header = document.querySelector('.ws-header');
	if (!header || !header.parentNode) return;
	var root = document.documentElement;
	var slot = document.createElement('div');
	slot.className = 'ws-header-slot';
	header.parentNode.insertBefore(slot, header);
	slot.appendChild(header);

	var search = header.querySelector('.ws-search');
	var start = header.querySelector('.ws-header__start');
	var toggle;
	if (search && start) {
		search.id = 'ws-header-search';
		toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'ws-search-toggle';
		toggle.setAttribute('aria-label', 'البحث عن منتج');
		toggle.setAttribute('aria-controls', search.id);
		toggle.setAttribute('aria-expanded', 'false');
		toggle.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/></svg>';
		start.appendChild(toggle);
		toggle.addEventListener('click', function () {
			var open = !header.classList.contains('is-search-open');
			header.classList.toggle('is-search-open', open);
			toggle.setAttribute('aria-expanded', String(open));
			updateOffset();
			if (open) {
				var input = search.querySelector('input[type="search"]');
				if (input) input.focus({ preventScroll: true });
			}
		});
		header.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape' || !header.classList.contains('is-search-open')) return;
			header.classList.remove('is-search-open');
			toggle.setAttribute('aria-expanded', 'false');
			toggle.focus({ preventScroll: true });
			updateOffset();
		});
	}

	var floating = false;
	var threshold = 0;
	var expandedHeight = 0;
	var queued = false;
	function adminOffset() {
		var bar = document.getElementById('wpadminbar');
		return bar ? Math.max(0, bar.getBoundingClientRect().bottom) : 0;
	}
	function updateOffset() {
		root.style.setProperty('--ws-admin-offset', adminOffset() + 'px');
		if (floating) {
			/* Use the final top, not an in-progress entry-animation position. */
			root.style.setProperty('--ws-gallery-top', (adminOffset() + 12 + header.offsetHeight + 24) + 'px');
		} else {
			root.style.removeProperty('--ws-gallery-top');
		}
	}
	function measure() {
		header.classList.add('is-measuring');
		header.classList.remove('is-floating');
		slot.style.height = '';
		expandedHeight = header.offsetHeight;
		threshold = slot.getBoundingClientRect().top + window.scrollY + 64;
		if (floating) {
			slot.style.height = expandedHeight + 'px';
			header.classList.add('is-floating');
		}
		updateOffset();
		/* Flush the unanimated measurement before restoring entry animations. */
		void header.offsetHeight;
		header.classList.remove('is-measuring');
	}
	function update() {
		queued = false;
		var next = window.scrollY > threshold - (floating ? 16 : 0);
		/* Do not hide a focused search field while a keyboard user is typing. */
		if (next && !floating && search && search.contains(document.activeElement)) {
			header.classList.add('is-search-open');
			if (toggle) toggle.setAttribute('aria-expanded', 'true');
		}
		if (next !== floating) {
			floating = next;
			slot.style.height = floating ? expandedHeight + 'px' : '';
			header.classList.toggle('is-floating', floating);
			if (!floating) {
				header.classList.remove('is-search-open');
				if (toggle) toggle.setAttribute('aria-expanded', 'false');
			}
		}
		updateOffset();
	}
	function schedule() {
		if (!queued) { queued = true; requestAnimationFrame(update); }
	}
	measure();
	update();
	window.addEventListener('scroll', schedule, { passive: true });
	window.addEventListener('resize', function () { measure(); schedule(); });
	window.addEventListener('load', function () { measure(); schedule(); });
	if (document.fonts && document.fonts.ready) {
		document.fonts.ready.then(function () { measure(); schedule(); });
	}
	if (window.ResizeObserver) new ResizeObserver(updateOffset).observe(header);
})();