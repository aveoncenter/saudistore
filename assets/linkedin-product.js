/**
 * LinkedIn Product Page Interactive Features
 * Handles: Read More, Plan Selection, Credentials Toggle
 */

(function() {
	'use strict';

	// Wait for DOM to be ready
	document.addEventListener('DOMContentLoaded', function() {

		// ===== Read More Functionality =====
		const shortDesc = document.querySelector('.product-short-description');
		const fullDesc = document.querySelector('.product-full-description');

		if (shortDesc && fullDesc) {
			const readMoreLink = shortDesc.querySelector('.wp-block-post-excerpt__more-link');

			if (readMoreLink) {
				readMoreLink.addEventListener('click', function(e) {
					e.preventDefault();

					// Toggle visibility
					if (fullDesc.style.display === 'none') {
						fullDesc.style.display = 'block';
						shortDesc.style.display = 'none';
					}
				});
			}
		}

		// ===== Plan Selection Functionality =====
		const planSelector = document.getElementById('linkedin-plan-type');
		const planDisplay = document.querySelector('.plan-selector-custom');
		const arrow = document.querySelector('.plan-selector-arrow');
		const priceDisplay = document.querySelector('.selected-plan-price');
		const newPriceElem = document.querySelector('.new-price');
		const oldPriceElem = document.querySelector('.old-price');

		if (planSelector && planDisplay) {
			planSelector.addEventListener('change', function() {
				const selectedOption = this.options[this.selectedIndex];
				const price = selectedOption.getAttribute('data-price');
				const label = selectedOption.textContent;

				if (price && this.value !== '') {
					// Update custom display
					planDisplay.textContent = label;
					planDisplay.style.borderColor = '#a855f7';
					planDisplay.style.color = '#fff';
					if (arrow) arrow.style.fill = '#a855f7';

					// Calculate old price (approx 4x markup for display)
					const oldPrice = Math.round(price * 4);

					// Update prices
					newPriceElem.textContent = price + ' ﷼';
					oldPriceElem.textContent = oldPrice + ' ﷼';

					// Show price display
					priceDisplay.style.display = 'block';

					// Update main product price if exists
					const mainPrice = document.querySelector('.woocommerce-Price-amount');
					if (mainPrice) {
						mainPrice.innerHTML = '<bdi>' + price + '&nbsp;<span class="woocommerce-Price-currencySymbol">﷼</span></bdi>';
					}
				} else {
					// Reset
					planDisplay.textContent = 'اختر';
					planDisplay.style.borderColor = '#374151';
					planDisplay.style.color = '#fff';
					if (arrow) arrow.style.fill = '#9ca3af';

					// Hide price display if no plan selected
					priceDisplay.style.display = 'none';
				}
			});

			// Focus/blur styles
			planSelector.addEventListener('focus', function() {
				if (planDisplay) {
					planDisplay.style.borderColor = '#a855f7';
					planDisplay.style.boxShadow = '0 0 0 3px rgba(168, 85, 247, 0.2)';
				}
				if (arrow) arrow.style.fill = '#a855f7';
			});

			planSelector.addEventListener('blur', function() {
				if (planDisplay) {
					planDisplay.style.borderColor = '#374151';
					planDisplay.style.boxShadow = 'none';
				}
				if (arrow) arrow.style.fill = '#9ca3af';
			});
		}

		// ===== Conditions Checkbox & Credentials Toggle =====
		const conditionCheckbox = document.getElementById('linkedin-condition');
		const credentialsSection = document.querySelector('.linkedin-credentials');
		const conditionWrapper = document.querySelector('.condition-checkbox-wrapper');

		if (conditionCheckbox && credentialsSection) {
			conditionCheckbox.addEventListener('change', function() {
				if (this.checked) {
					// Show credentials fields
					credentialsSection.style.display = 'block';

					// Add visual feedback to checkbox wrapper
					if (conditionWrapper) {
						conditionWrapper.style.borderColor = '#a855f7';
						conditionWrapper.style.background = 'rgba(168, 85, 247, 0.1)';
					}
				} else {
					// Hide credentials fields
					credentialsSection.style.display = 'none';

					// Reset wrapper style
					if (conditionWrapper) {
						conditionWrapper.style.borderColor = '#374151';
						conditionWrapper.style.background = '#0f172a';
					}

					// Clear credential fields
					const emailField = document.getElementById('linkedin-email');
					const passwordField = document.getElementById('linkedin-password');
					if (emailField) emailField.value = '';
					if (passwordField) passwordField.value = '';
				}
			});
		}

		// ===== Add to Cart Validation =====
		const addToCartForm = document.querySelector('.cart');

		if (addToCartForm) {
			const addToCartBtn = addToCartForm.querySelector('.single_add_to_cart_button');

			if (addToCartBtn) {
				addToCartBtn.addEventListener('click', function(e) {
					let isValid = true;
					let errorMsg = '';

					// Check if plan is selected
					if (planSelector && planSelector.value === '') {
						isValid = false;
						errorMsg = 'الرجاء اختيار نوع الباقة';
						planSelector.style.borderColor = '#dc2626';
					} else if (planSelector) {
						planSelector.style.borderColor = '#374151';
					}

					// Check if condition is checked
					if (conditionCheckbox && !conditionCheckbox.checked) {
						isValid = false;
						errorMsg = 'الرجاء الموافقة على الشروط';
						if (conditionWrapper) {
							conditionWrapper.style.borderColor = '#dc2626';
						}
					}

					// Check credentials if condition is checked
					if (conditionCheckbox && conditionCheckbox.checked) {
						const emailField = document.getElementById('linkedin-email');
						const passwordField = document.getElementById('linkedin-password');

						if (emailField && !emailField.value.trim()) {
							isValid = false;
							errorMsg = 'الرجاء إدخال ايميل لينكد ان';
							emailField.style.borderColor = '#dc2626';
						} else if (emailField) {
							emailField.style.borderColor = '#374151';
						}

						if (passwordField && !passwordField.value.trim()) {
							isValid = false;
							errorMsg = 'الرجاء إدخال كلمة المرور';
							passwordField.style.borderColor = '#dc2626';
						} else if (passwordField) {
							passwordField.style.borderColor = '#374151';
						}
					}

					// Prevent form submission if validation fails
					if (!isValid) {
						e.preventDefault();
						e.stopPropagation();

						// Show error message
						showErrorNotice(errorMsg);

						return false;
					}

					// Store selected data in hidden fields or session storage
					if (isValid) {
						storeLinkedInData();
					}
				});
			}
		}

		// ===== Helper Functions =====

		/**
		 * Show error notice
		 */
		function showErrorNotice(message) {
			// Remove existing notices
			const existingNotice = document.querySelector('.linkedin-error-notice');
			if (existingNotice) {
				existingNotice.remove();
			}

			// Create notice
			const notice = document.createElement('div');
			notice.className = 'linkedin-error-notice';
			notice.style.cssText = 'position:fixed;top:20px;right:20px;background:#dc2626;color:#fff;padding:16px 24px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.3);z-index:9999;animation:slideIn 0.3s ease;';
			notice.textContent = message;

			document.body.appendChild(notice);

			// Auto-remove after 3 seconds
			setTimeout(function() {
				notice.style.animation = 'slideOut 0.3s ease';
				setTimeout(function() {
					notice.remove();
				}, 300);
			}, 3000);
		}

		/**
		 * Store LinkedIn credentials and plan data
		 */
		function storeLinkedInData() {
			const planValue = planSelector ? planSelector.value : '';
			const planText = planSelector ? planSelector.options[planSelector.selectedIndex].text : '';
			const emailValue = document.getElementById('linkedin-email') ? document.getElementById('linkedin-email').value : '';
			const passwordValue = document.getElementById('linkedin-password') ? document.getElementById('linkedin-password').value : '';

			// Create hidden fields in the form to pass data to WooCommerce
			const form = document.querySelector('.cart');
			if (form) {
				// Remove old hidden fields if they exist
				const oldFields = form.querySelectorAll('input[name^="linkedin_"]');
				oldFields.forEach(field => field.remove());

				// Add new hidden fields
				addHiddenField(form, 'linkedin_plan', planValue);
				addHiddenField(form, 'linkedin_plan_text', planText);
				addHiddenField(form, 'linkedin_email', emailValue);
				addHiddenField(form, 'linkedin_password', passwordValue);
			}
		}

		/**
		 * Add hidden input field to form
		 */
		function addHiddenField(form, name, value) {
			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			input.value = value;
			form.appendChild(input);
		}
	});

	// Add animation keyframes
	const style = document.createElement('style');
	style.textContent = `
		@keyframes slideIn {
			from {
				transform: translateX(100%);
				opacity: 0;
			}
			to {
				transform: translateX(0);
				opacity: 1;
			}
		}
		@keyframes slideOut {
			from {
				transform: translateX(0);
				opacity: 1;
			}
			to {
				transform: translateX(100%);
				opacity: 0;
			}
		}
	`;
	document.head.appendChild(style);

})();
