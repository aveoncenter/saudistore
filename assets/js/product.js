/* Saudistore – single product page. */
(function () {
	'use strict';

	/* Share: native share sheet, or copy the link. */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-ws-share]');
		if (!btn) return;
		e.preventDefault();
		var data = { title: document.title, url: location.href };
		if (navigator.share) {
			navigator.share(data).catch(function () {});
		} else if (navigator.clipboard) {
			navigator.clipboard.writeText(location.href).then(function () {
				btn.classList.add('is-copied');
				setTimeout(function () { btn.classList.remove('is-copied'); }, 1600);
			});
		}
	});

	/* Show / hide password. */
	document.addEventListener('click', function (e) {
		var t = e.target.closest('[data-ws-pass]');
		if (!t) return;
		var input = t.parentNode.querySelector('input');
		var show = input.type === 'password';
		input.type = show ? 'text' : 'password';
		t.classList.toggle('is-on', show);
		t.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
	});

	/* Carousels. */
	document.querySelectorAll('[data-ws-carousel]').forEach(function (c) {
		var track = c.querySelector('.ws-carousel__track');
		var prev = c.querySelector('[data-dir="prev"]');
		var next = c.querySelector('[data-dir="next"]');
		var rtl = getComputedStyle(track).direction === 'rtl';
		function update() {
			var max = track.scrollWidth - track.clientWidth;
			var pos = Math.abs(track.scrollLeft);
			prev.disabled = pos <= 2;
			next.disabled = pos >= max - 2;
			c.classList.toggle('is-static', max <= 2);
		}
		function go(dir) {
			var step = track.clientWidth * 0.8 * dir;
			track.scrollBy({ left: rtl ? -step : step, behavior: 'smooth' });
		}
		prev.addEventListener('click', function () { go(-1); });
		next.addEventListener('click', function () { go(1); });
		track.addEventListener('scroll', update, { passive: true });
		window.addEventListener('resize', update);
		update();
	});

	/* Quick view. */
	var dlg = document.getElementById('ws-quick');
	if (dlg && typeof dlg.showModal === 'function') {
		var body = dlg.querySelector('.ws-quick__body');
		document.addEventListener('click', function (e) {
			var q = e.target.closest('[data-ws-quick]');
			if (!q) return;
			var tpl = q.closest('.ws-card').querySelector('template');
			body.replaceChildren(tpl.content.cloneNode(true));
			dlg.showModal();
		});
		dlg.addEventListener('click', function (e) {
			if (e.target === dlg || e.target.closest('[data-ws-close]')) dlg.close();
		});
	} else {
		document.addEventListener('click', function (e) {
			var q = e.target.closest('[data-ws-quick]');
			if (q) location.href = q.closest('.ws-card').querySelector('a').href;
		});
	}

	/* Account fields: shown only after a plan is chosen (variable) and the terms box is ticked. */
	function syncAccount(form) {
		var acc = form.querySelectorAll('.ws-field--account');
		if (!acc.length) return;
		var terms = form.querySelector('input[name="ws_terms"]');
		var isVar = form.classList.contains('variations_form');
		var planOk = !isVar || form.getAttribute('data-ws-plan') === '1';
		var show = planOk && (!terms || terms.checked);
		acc.forEach(function (el) {
			var was = el.hidden;
			el.hidden = !show;
			el.querySelectorAll('input').forEach(function (i) { i.disabled = !show; });
			if (show && was) {
				el.classList.remove('ws-reveal');
				void el.offsetWidth;
				el.classList.add('ws-reveal');
			}
		});
	}
	document.addEventListener('change', function (e) {
		if (e.target && e.target.name === 'ws_terms') {
			var f = e.target.closest('form');
			if (f) syncAccount(f);
		}
	});
	document.querySelectorAll('form.cart').forEach(syncAccount);

	/* Variable products: live price, discount, fields, buy-now state. */
	if (!window.jQuery) return;
	jQuery(function ($) {
		var $form = $('form.variations_form');
		if (!$form.length) return;
		var $prices = $('[data-ws-price]');
		var $badge = $('[data-ws-discount]');
		var origPrices = $prices.map(function () { return this.innerHTML; }).get();
		var origBadge = { html: $badge.find('bdi').html(), hidden: $badge.prop('hidden') };
		var $buyNow = $form.find('.ws-buy-now');

		function fmt(n) { return String(Math.round(n * 100) / 100); }

		$form.on('found_variation', function (e, v) {
			if (v.price_html) $prices.html(v.price_html);
			var reg = parseFloat(v.display_regular_price), now = parseFloat(v.display_price);
			if (reg > now && reg > 0) {
				$badge.find('bdi').text(fmt((reg - now) / reg * 100));
				$badge.prop('hidden', false);
			} else {
				$badge.prop('hidden', true);
			}
			$form.attr('data-ws-plan', '1');
			syncAccount($form[0]);
			$buyNow.toggleClass('disabled', !v.is_purchasable || !v.is_in_stock);
		});

		$form.on('reset_data hide_variation', function () {
			$prices.each(function (i) { this.innerHTML = origPrices[i]; });
			$badge.find('bdi').html(origBadge.html);
			$badge.prop('hidden', origBadge.hidden);
			$form.attr('data-ws-plan', '0');
			syncAccount($form[0]);
			$buyNow.addClass('disabled');
		});

		$(document).on('click', '.ws-buy-now.disabled', function (e) {
			e.preventDefault();
			$form.find('.single_add_to_cart_button').trigger('click');
		});
	});
})();
