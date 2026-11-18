/* Header cart total: refresh WooCommerce's real total after any cart change, without a
 * page reload. Server-rendered by inc/header-cart-total.php; updated from the Store API. */
(function () {
	'use strict';
	if (!document.querySelector('[data-ws-cart-total]')) return;

	var timer = 0;
	var inflight = null;
	var queued = false;
	var hiddenAt = 0;
	var confirmTimer = 0;
	var cartRevision = 0;
	var lastFragmentUpdate = 0;

	function nodes() { return document.querySelectorAll('[data-ws-cart-total]'); }
	function endpoint() {
		var el = document.querySelector('[data-ws-cart-total][data-endpoint]');
		return el ? el.getAttribute('data-endpoint') : '/wp-json/wc/store/v1/cart';
	}
	function group(digits, sep) { return digits.replace(/\B(?=(\d{3})+(?!\d))/g, sep); }
	function escapeHtml(value) {
		return String(value).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
	}
	/* Mirrors wc_price(): same symbol, position, separators and decimals as the store. */
	function format(totals) {
		var minor = parseInt(totals.currency_minor_unit, 10) || 0;
		var raw = String(totals.total_price || '0').replace(/[^\d-]/g, '') || '0';
		var negative = raw.charAt(0) === '-';
		raw = raw.replace('-', '');
		while (raw.length <= minor) raw = '0' + raw;
		var whole = minor ? raw.slice(0, -minor) : raw;
		var fraction = minor ? raw.slice(-minor) : '';
		var number = (negative ? '-' : '') + group(whole.replace(/^0+(?=\d)/, ''), totals.currency_thousand_separator || ',') + (minor ? (totals.currency_decimal_separator || '.') + fraction : '');
		var symbol = '<span class="woocommerce-Price-currencySymbol">' + escapeHtml(totals.currency_symbol || '') + '</span>';
		var prefix = totals.currency_prefix ? escapeHtml(totals.currency_prefix).replace(escapeHtml(totals.currency_symbol), symbol) : '';
		var suffix = totals.currency_suffix ? escapeHtml(totals.currency_suffix).replace(escapeHtml(totals.currency_symbol), symbol) : '';
		return {
			html: '<span class="woocommerce-Price-amount amount"><bdi>' + prefix.replace(/ /g, '&nbsp;') + escapeHtml(number) + suffix.replace(/ /g, '&nbsp;') + '</bdi></span>',
			spoken: number
		};
	}
	function render(cart) {
		if (!cart || !cart.totals) return;
		var out = format(cart.totals);
		nodes().forEach(function (el) {
			var amount = el.querySelector('.ws-cart-total__amount');
			var spoken = el.querySelector('[data-ws-cart-total-spoken]');
			if (amount && amount.innerHTML !== out.html) {
				amount.innerHTML = out.html;
				el.classList.remove('is-updated');
				void el.offsetWidth;
				el.classList.add('is-updated');
			}
			if (spoken) spoken.textContent = out.spoken + ' ' + (el.getAttribute('data-currency-name') || cart.totals.currency_code || '');
			if (typeof cart.items_count === 'number') el.setAttribute('data-count', String(cart.items_count));
		});
		if (typeof cart.items_count === 'number') setMiniCartBadge(cart.items_count);
	}

	/* Update the Mini-Cart badge from the same server response as the total. */
	function setMiniCartBadge(count) {
		count = Math.max(0, parseInt(count, 10) || 0);
		document.querySelectorAll('.wc-block-mini-cart__button').forEach(function (button) {
			var badge = button.querySelector('.wc-block-mini-cart__badge');
			if (!count) {
				if (badge) badge.remove();
				return;
			}
			if (!badge) {
				badge = document.createElement('span');
				badge.className = 'wc-block-mini-cart__badge';
				badge.setAttribute('aria-hidden', 'true');
				button.appendChild(badge);
			}
			badge.hidden = false;
			badge.textContent = String(count);
		});
	}

	/* The add-to-cart response already contains the exact, server-calculated total
	 * and item count. Apply it immediately instead of waiting for another REST GET. */
	function applyCartFragments(fragments) {
		var html = fragments && fragments['span.ws-cart-total'];
		if (!html) return false;
		var wrapper = document.createElement('div');
		wrapper.innerHTML = html;
		var fresh = wrapper.querySelector('[data-ws-cart-total]');
		if (!fresh) return false;

		cartRevision++;
		queued = false;
		var count = parseInt(fresh.getAttribute('data-count'), 10);
		nodes().forEach(function (current) {
			var replacement = fresh.cloneNode(true);
			replacement.classList.remove('is-updated');
			current.replaceWith(replacement);
			void replacement.offsetWidth;
			replacement.classList.add('is-updated');
		});
		if (!isNaN(count)) setMiniCartBadge(count);
		lastFragmentUpdate = Date.now();
		return true;
	}

	function refresh() {
		if (inflight) { queued = true; return; }
		var requestRevision = cartRevision;
		inflight = fetch(endpoint(), { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' })
			.then(function (response) { return response.ok ? response.json() : null; })
			.then(function (cart) {
				/* Never let an older in-flight response overwrite newer AJAX fragments. */
				if (requestRevision === cartRevision) render(cart);
			})
			.catch(function () { /* keep the last known total */ })
			.then(function () {
				inflight = null;
				if (queued) { queued = false; refresh(); }
			});
	}
	function schedule(delay) {
		clearTimeout(timer);
		timer = setTimeout(refresh, typeof delay === 'number' ? delay : 120);
	}

	/* Classic WooCommerce (jQuery) events: add to cart, cart page, checkout, fragments. */
	/* jQuery may print after this footer script, so bind as soon as it exists. */
	var jqBound = false;
	function bindJQuery() {
		if (jqBound || !window.jQuery) return;
		jqBound = true;
		var $body = window.jQuery(document.body);
		$body.on('added_to_cart', function (event, fragments) {
			if (!applyCartFragments(fragments)) schedule(0);
		});
		$body.on('removed_from_cart wc_fragments_refreshed wc_fragments_loaded updated_cart_totals updated_wc_div updated_checkout wc_cart_emptied', function (event, fragments) {
			if (!applyCartFragments(fragments)) schedule(0);
		});
	}
	bindJQuery();
	document.addEventListener('DOMContentLoaded', bindJQuery);
	window.addEventListener('load', bindJQuery);
	/* Block / Interactivity API cart events. */
	['wc-blocks_added_to_cart', 'wc-blocks_removed_from_cart', 'wc-blocks_store_sync_required'].forEach(function (name) {
		var handler = function (event) {
			/* Classic added_to_cart is bridged to this event by WooCommerce Blocks.
			 * Its matching server fragment has already updated the total and badge. */
			if (name === 'wc-blocks_added_to_cart' && Date.now() - lastFragmentUpdate < 1800) return;
			if (name === 'wc-blocks_added_to_cart' && event.detail && event.detail.preserveCartData) return;
			schedule(0);
		};
		document.addEventListener(name, handler);
		window.addEventListener(name, handler);
	});
	/* Mini-cart drawer quantity/remove edits (Interactivity API) only change the
	 * badge, button label and drawer subtotal. The block may re-render its button,
	 * so watch the wrapper and compare a small signature instead of fixed nodes. */
	if (window.MutationObserver) {
		var signature = '';
		var readSignature = function () {
			var parts = [];
			document.querySelectorAll('.wc-block-mini-cart').forEach(function (cart) {
				var button = cart.querySelector('.wc-block-mini-cart__button');
				var badge = cart.querySelector('.wc-block-mini-cart__badge');
				var subtotal = cart.querySelector('.wc-block-mini-cart__footer-subtotal, .wc-block-components-totals-item__value');
				parts.push((button ? button.getAttribute('aria-label') : '') + '|' + (badge ? badge.textContent : '') + '|' + (subtotal ? subtotal.textContent : ''));
			});
			return parts.join('#');
		};
		var watchCart = function () {
			var next = readSignature();
			if (next !== signature) {
				var first = !signature;
				signature = next;
				if (!first && Date.now() - lastFragmentUpdate >= 1800) {
					/* Mini-Cart quantity/removal edits are block-owned; confirm those
					 * changes with the Store API. Ignore the re-render after an add whose
					 * exact count was already supplied by WooCommerce's AJAX fragment. */
					schedule(250);
					clearTimeout(confirmTimer);
					confirmTimer = setTimeout(refresh, 1400);
				}
			}
		};
		var observer = new MutationObserver(watchCart);
		document.querySelectorAll('.wc-block-mini-cart').forEach(function (cart) {
			observer.observe(cart, { attributes: true, attributeFilter: ['aria-label', 'class'], subtree: true, characterData: true, childList: true });
		});
		signature = readSignature();
	}
	/* Block cart/checkout pages keep their state in the wc/store/cart data store. */
	var unsubscribe = null;
	var lastTotals = '';
	function watchStore() {
		if (unsubscribe || !window.wp || !window.wp.data || !window.wp.data.select) return;
		var select;
		try { select = window.wp.data.select('wc/store/cart'); } catch (e) { return; }
		if (!select || !select.getCartTotals) return;
		unsubscribe = window.wp.data.subscribe(function () {
			var totals = select.getCartTotals();
			var key = totals ? totals.total_price + '|' + totals.currency_code : '';
			if (key && key !== lastTotals) {
				var first = !lastTotals;
				lastTotals = key;
				if (!first) render({ totals: totals, items_count: select.getCartData ? select.getCartData().itemsCount : undefined });
			}
		});
	}
	window.addEventListener('load', watchStore);
	document.addEventListener('wc-blocks_render_blocks_frontend', watchStore);

	/* Clicking the amount opens the existing mini-cart drawer. */
	document.addEventListener('click', function (event) {
		var total = event.target.closest('[data-ws-cart-total]');
		if (!total) return;
		var button = total.parentNode && total.parentNode.querySelector('.wc-block-mini-cart__button');
		if (button) button.click();
	});

	/* Back/forward cache, a possibly cached page, and returning to the tab. */
	window.addEventListener('pageshow', function (event) { if (event.persisted) refresh(); });
	document.addEventListener('visibilitychange', function () {
		if (document.hidden) { hiddenAt = Date.now(); return; }
		if (hiddenAt && Date.now() - hiddenAt > 15000) refresh();
	});
	var el = document.querySelector('[data-ws-cart-total]');
	var hasItemsCookie = /(?:^|;\s*)woocommerce_items_in_cart=/.test(document.cookie);
	if (hasItemsCookie !== (parseInt(el.getAttribute('data-count'), 10) > 0)) refresh();
})();
