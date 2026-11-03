/* Saudistore – single product page. */
(function () {
	'use strict';

	function resetProductAddedButton(button) {
		if (!button) return;
		button.classList.remove('ws-added-to-cart');
		button.removeAttribute('aria-disabled');
		button.removeAttribute('data-ws-added-tooltip');
		button.removeAttribute('data-ws-added-variation');
		button.removeAttribute('data-ws-added-quantity');
		button.removeAttribute('title');
		var form = button.closest('form.cart');
		if (form) {
			delete form.dataset.wsCartRequestToken;
			form.dataset.wsAjaxPending = '0';
		}
	}

	/* Read more: expand here, above the purchase options, never navigate to the tabs. */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-ws-readmore]');
		if (!btn) return;
		var excerpt = btn.closest('.ws-excerpt');
		var full = excerpt.querySelector('.ws-excerpt__full');
		var expanded = btn.getAttribute('aria-expanded') !== 'true';
		btn.setAttribute('aria-expanded', String(expanded));
		btn.textContent = btn.getAttribute(expanded ? 'data-less' : 'data-more');
		if (full) {
			full.hidden = !expanded;
		} else {
			excerpt.querySelector('.ws-excerpt__text').classList.toggle('is-expanded', expanded);
		}
	});

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


	/* Product animation, existing mini-cart drawer, and quick-view confirmation. */
	if (window.jQuery) {
		(function ($) {
			var lastAddButton = null;
			var quickToastTimer = null;

			document.addEventListener('click', function (event) {
				var clicked = event.target.closest('.ws-card .ajax_add_to_cart, .ws-quick .ajax_add_to_cart');
				if (clicked) lastAddButton = clicked;
			}, true);

			function animateProductToCart(button) {
				if (!button || typeof Element.prototype.animate !== 'function') return;
				var card = button.closest('.ws-card');
				var image = card ? card.querySelector('.ws-card__img img') : null;
				if (!image) {
					var quickView = button.closest('.ws-quick');
					image = quickView ? quickView.querySelector('.ws-quick__media img') : null;
				}
				if (!image && button.closest('form.cart')) {
					var media = document.querySelector('.ws-product__media');
					image = media ? media.querySelector('.woocommerce-product-gallery__image img, img.wp-post-image, img') : null;
				}
				var cartButton = document.querySelector('.ws-header .wc-block-mini-cart__button, .wc-block-mini-cart__button');
				if (!image || !cartButton) return;

				var from = image.getBoundingClientRect();
				var to = cartButton.getBoundingClientRect();
				if (!from.width || !from.height || !to.width || !to.height) return;

				var flyer = image.cloneNode(false);
				flyer.removeAttribute('srcset');
				flyer.removeAttribute('sizes');
				flyer.src = image.currentSrc || image.src;
				flyer.alt = '';
				flyer.setAttribute('aria-hidden', 'true');
				Object.assign(flyer.style, {
					position: 'fixed', left: from.left + 'px', top: from.top + 'px',
					width: from.width + 'px', height: from.height + 'px',
					margin: '0', maxWidth: 'none', zIndex: '100000',
					pointerEvents: 'none', objectFit: 'cover', borderRadius: '14px',
					boxShadow: '0 12px 36px rgba(0,0,0,.38)', transformOrigin: 'center center'
				});
				document.body.appendChild(flyer);

				var dx = (to.left + to.width / 2) - (from.left + from.width / 2);
				var dy = (to.top + to.height / 2) - (from.top + from.height / 2);
				var scale = Math.max(0.12, Math.min(0.24, Math.min(to.width / from.width, to.height / from.height)));
				var arcX = dx * 0.48;
				var arcY = dy * 0.48 - (dy < 0 ? 42 : -42);
				var animation = flyer.animate([
					{ transform: 'translate3d(0,0,0) scale(1)', opacity: 1, offset: 0 },
					{ transform: 'translate3d(' + arcX + 'px,' + arcY + 'px,0) scale(.62)', opacity: .92, offset: .58 },
					{ transform: 'translate3d(' + dx + 'px,' + dy + 'px,0) scale(' + scale + ')', opacity: .28, offset: 1 }
				], { duration: 720, easing: 'cubic-bezier(.2,.72,.25,1)', fill: 'forwards' });
				animation.onfinish = function () { flyer.remove(); };
				animation.oncancel = function () { flyer.remove(); };

				cartButton.classList.remove('ws-cart-arrived');
				void cartButton.offsetWidth;
				cartButton.classList.add('ws-cart-arrived');
				window.setTimeout(function () { cartButton.classList.remove('ws-cart-arrived'); }, 750);
			}

			function openProductPageMiniCart() {
				var cartButton = document.querySelector('.ws-header .wc-block-mini-cart__button, .wc-block-mini-cart__button');
				if (!cartButton) return;
				var drawer = document.querySelector('.wc-block-mini-cart__drawer');
				if (drawer && drawer.getAttribute('aria-hidden') === 'false') return;
				cartButton.click();
			}

			function showQuickViewAddedToast(button) {
				var quickView = button ? button.closest('.ws-quick') : null;
				if (!quickView) return;
				var image = quickView.querySelector('.ws-quick__media img');
				var title = quickView.querySelector('.ws-quick__info h3');
				var price = quickView.querySelector('.ws-quick__price');
				var previous = document.querySelector('.ws-added-toast');
				if (previous) previous.remove();
				if (quickToastTimer) window.clearTimeout(quickToastTimer);

				var toast = document.createElement('section');
				toast.className = 'ws-added-toast';
				toast.setAttribute('role', 'status');
				toast.setAttribute('aria-live', 'polite');
				toast.setAttribute('dir', 'rtl');

				var timer = document.createElement('div');
				timer.className = 'ws-added-toast__timer';
				var timerBar = document.createElement('span');
				timer.appendChild(timerBar);
				toast.appendChild(timer);

				var head = document.createElement('div');
				head.className = 'ws-added-toast__head';
				var check = document.createElement('span');
				check.className = 'ws-added-toast__check';
				check.textContent = '✓';
				var message = document.createElement('strong');
				message.className = 'ws-added-toast__message';
				message.textContent = 'تمت إضافة المنتج إلى سلة التسوق';
				var close = document.createElement('button');
				close.type = 'button';
				close.className = 'ws-added-toast__close';
				close.setAttribute('aria-label', 'إغلاق');
				close.textContent = '×';
			head.append(check, message, close);
				toast.appendChild(head);

				var product = document.createElement('div');
				product.className = 'ws-added-toast__product';
				if (image) {
					var img = document.createElement('img');
					img.src = image.currentSrc || image.src;
					img.alt = '';
					product.appendChild(img);
				}
				var productName = document.createElement('strong');
				productName.className = 'ws-added-toast__name';
				productName.textContent = title ? title.textContent.trim() : 'المنتج';
				product.appendChild(productName);
				if (price) {
					var productPrice = document.createElement('span');
					productPrice.className = 'ws-added-toast__price';
					productPrice.innerHTML = price.innerHTML;
					product.appendChild(productPrice);
				}
				toast.appendChild(product);

				var actions = document.createElement('div');
				actions.className = 'ws-added-toast__actions';
				var viewCart = document.createElement('a');
				viewCart.className = 'ws-added-toast__view';
				viewCart.href = window.wordsiteProduct && window.wordsiteProduct.cartUrl ? window.wordsiteProduct.cartUrl : '/cart/';
				viewCart.textContent = 'عرض السلة';
				var checkout = document.createElement('a');
				checkout.className = 'ws-added-toast__checkout';
				checkout.href = window.wordsiteProduct && window.wordsiteProduct.checkoutUrl ? window.wordsiteProduct.checkoutUrl : '/checkout/';
				checkout.textContent = 'إتمام الطلب';
				actions.append(viewCart, checkout);
				toast.appendChild(actions);

				document.body.appendChild(toast);
				close.addEventListener('click', function () {
					if (quickToastTimer) window.clearTimeout(quickToastTimer);
					toast.remove();
				});
				quickToastTimer = window.setTimeout(function () {
					toast.classList.add('is-leaving');
					toast.remove();
					quickToastTimer = null;
				}, 3000);
			}

			function markProductAdded(button) {
				if (!button || !button.matches('.single_add_to_cart_button')) return;
				button.classList.remove('loading');
				button.classList.add('ws-added-to-cart');
				button.setAttribute('aria-disabled', 'true');
				button.setAttribute('data-ws-added-tooltip', 'تمت إضافة المنتج إلى السلة');
				button.setAttribute('title', 'تمت إضافة المنتج إلى السلة');
				var form = button.closest('form.cart');
				var quantity = form ? form.querySelector('input[name="quantity"]') : null;
				button.setAttribute('data-ws-added-quantity', quantity ? quantity.value : '1');
				if (form && form.classList.contains('variations_form')) {
					var variation = form.querySelector('input[name="variation_id"]');
					button.setAttribute('data-ws-added-variation', variation ? variation.value : '');
				}
			}

			function showAddError(form, html) {
				var wrapper = document.querySelector('.woocommerce-notices-wrapper');
				if (!wrapper) {
					wrapper = document.createElement('div');
					wrapper.className = 'woocommerce-notices-wrapper';
					var product = document.querySelector('.ws-product__summary') || form.parentNode;
					product.insertBefore(wrapper, product.firstChild);
				}
				wrapper.innerHTML = html || '<ul class="woocommerce-error" role="alert"><li>تعذر إضافة المنتج إلى السلة. يرجى المحاولة مرة أخرى.</li></ul>';
				wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			}

			$(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
				var button = $button && $button.length ? $button[0] : lastAddButton;
				lastAddButton = null;
				if (button && button.closest('form.cart') && document.body.classList.contains('single-product')) {
					markProductAdded(button);
					animateProductToCart(button);
					window.setTimeout(openProductPageMiniCart, 760);
				} else {
					animateProductToCart(button);
					if (button && button.closest('.ws-quick')) showQuickViewAddedToast(button);
				}
			});

			/* Keep the quick-view/homepage AJAX links in their existing flow. Only
			 * prevent duplicate clicks on a product-page button after success. */
			document.addEventListener('click', function (event) {
				var addedButton = event.target.closest('.single_add_to_cart_button.ws-added-to-cart');
				if (addedButton) {
					event.preventDefault();
					event.stopImmediatePropagation();
				}
			}, true);

			/* AJAX add on single product pages only. The event is captured before
			 * WooCommerce's native submit handler, preventing a second form POST. */
			document.addEventListener('submit', function (event) {
				var form = event.target;
				if (!form.matches('body.single-product form.cart')) return;
				var button = event.submitter || form.querySelector('.single_add_to_cart_button');
				if (!button || button.classList.contains('ws-buy-now')) return;
				if (button.classList.contains('ws-added-to-cart') || form.dataset.wsAjaxPending === '1') {
					event.preventDefault();
					event.stopImmediatePropagation();
					return;
				}
				if (!window.wordsiteProduct || !window.wordsiteProduct.ajaxUrl || !window.wordsiteProduct.nonce) return;
				if (form.checkValidity && !form.checkValidity()) return;

				var productField = form.querySelector('input[name="product_id"]');
				var addField = form.querySelector('input[name="add-to-cart"]');
				var productId = productField ? productField.value : (button.value || (addField ? addField.value : ''));
				var quantityField = form.querySelector('input[name="quantity"]');
				var quantity = quantityField ? quantityField.value : '1';
				var variationField = form.querySelector('input[name="variation_id"]');
				var variationId = variationField ? variationField.value : '0';
				if (!productId) return;
				if (form.classList.contains('variations_form') && (!variationId || variationId === '0')) return;

				event.preventDefault();
				event.stopImmediatePropagation();
				form.dataset.wsAjaxPending = '1';
				button.classList.add('loading');
				button.setAttribute('aria-busy', 'true');

				if (!form.dataset.wsCartRequestToken) {
					form.dataset.wsCartRequestToken = window.crypto && window.crypto.randomUUID
						? window.crypto.randomUUID()
						: ('ws' + Date.now().toString(36) + Math.random().toString(36).slice(2, 14));
				}
				var data = $(form).serialize();
				data += '&action=wordsite_add_to_cart';
				data += '&security=' + encodeURIComponent(window.wordsiteProduct.nonce);
				data += '&product_id=' + encodeURIComponent(productId);
				data += '&quantity=' + encodeURIComponent(quantity);
				data += '&request_token=' + encodeURIComponent(form.dataset.wsCartRequestToken);
				if (variationField) data += '&variation_id=' + encodeURIComponent(variationId);

				$(document.body).trigger('adding_to_cart', [$(button), data]);
				$.ajax({
					type: 'POST',
					url: window.wordsiteProduct.ajaxUrl,
					data: data,
					dataType: 'json'
				}).done(function (response) {
					if (response && response.success && response.data && response.data.duplicate_request) {
						return;
					}
					form.dataset.wsAjaxPending = '0';
					button.classList.remove('loading');
					button.removeAttribute('aria-busy');
					if (!response || response.success === false || response.error) {
						delete form.dataset.wsCartRequestToken;
						showAddError(form, response && response.data ? response.data.messages : '');
						return;
					}
					$(document.body).trigger('added_to_cart', [response.data.fragments || {}, response.data.cart_hash || '', $(button)]);
				}).fail(function () {
					form.dataset.wsAjaxPending = '0';
					delete form.dataset.wsCartRequestToken;
					button.classList.remove('loading');
					button.removeAttribute('aria-busy');
					showAddError(form, '<ul class="woocommerce-error" role="alert"><li>تعذر الاتصال. تحقق من الإنترنت وحاول مرة أخرى.</li></ul>');
				});
			}, true);
		})(window.jQuery);
	}

	/* A changed quantity is an intentional new purchase, so unlock the button. */
	document.addEventListener('change', function (event) {
		var quantity = event.target;
		if (!quantity.matches('body.single-product form.cart input[name="quantity"]')) return;
		var form = quantity.closest('form.cart');
		var button = form && form.querySelector('.single_add_to_cart_button.ws-added-to-cart');
		if (button && button.getAttribute('data-ws-added-quantity') !== String(quantity.value)) {
			resetProductAddedButton(button);
		}
	});

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
			var $addButton = $form.find('.single_add_to_cart_button');
			if ($addButton.hasClass('ws-added-to-cart') && $addButton.attr('data-ws-added-variation') !== String(v.variation_id)) {
				resetProductAddedButton($addButton[0]);
			}
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
			resetProductAddedButton($form.find('.single_add_to_cart_button')[0]);
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
