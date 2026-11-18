(function ($) {
	var PLUS = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>';
	var CART = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2.2l2 11h10.2l2-8H6.4"/><circle cx="9.5" cy="19.5" r="1.4"/><circle cx="16.5" cy="19.5" r="1.4"/></svg>';

	function toCart(btn) {
		var name = btn.getAttribute('data-name') || 'this product';
		btn.classList.remove('ajax_add_to_cart', 'loading', 'added');
		btn.classList.add('is-in-cart', 'is-swapped');
		btn.setAttribute('href', pvCards.cartUrl);
		btn.setAttribute('aria-label', 'View ' + name + ' in your cart');
		btn.innerHTML = btn.classList.contains('pv-loop-add') ? 'View cart' : CART;
		window.setTimeout(function () {
			btn.classList.remove('is-swapped');
		}, 400);
	}

	function toPlus(btn) {
		var name = btn.getAttribute('data-name') || 'this product';
		btn.classList.remove('is-in-cart', 'added', 'loading');
		btn.classList.add('ajax_add_to_cart');
		btn.setAttribute('href', btn.getAttribute('data-add-url'));
		btn.setAttribute('aria-label', 'Add ' + name + ' to cart');
		btn.innerHTML = btn.classList.contains('pv-loop-add') ? 'Add to cart' : PLUS;
	}

	$(document.body).on('added_to_cart', function (e, fragments, hash, button) {
		var btn = button && button.length ? button[0] : null;
		if (btn && (btn.classList.contains('pv-card__add') || btn.classList.contains('pv-loop-add'))) {
			toCart(btn);
		}
	});

	$(document.body).on('removed_from_cart', function (e, fragments, hash, button) {
		var id = button && button.length ? button.attr('data-product_id') : null;
		if (!id) {
			return;
		}
		document.querySelectorAll('.pv-card__add[data-product_id="' + id + '"], .pv-loop-add[data-product_id="' + id + '"]').forEach(toPlus);
	});

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.pv-card__add.is-in-cart, .pv-loop-add.is-in-cart');
		if (!btn) {
			return;
		}
		var toggle = document.querySelector('.elementor-menu-cart__toggle_button');
		if (!toggle) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		window.setTimeout(function () {
			toggle.click();
		}, 0);
	}, true);

	var timers = {};

	function enhanceRows() {
		document.querySelectorAll('.elementor-menu-cart__product').forEach(function (row) {
			if (row.querySelector('.pv-qty')) {
				return;
			}
			var remove = row.querySelector('.elementor_remove_from_cart_button');
			var qtyEl = row.querySelector('.product-quantity');
			var priceBox = row.querySelector('.elementor-menu-cart__product-price');
			if (!remove || !qtyEl || !priceBox) {
				return;
			}
			var key = remove.getAttribute('data-cart_item_key');
			var qty = parseInt(qtyEl.textContent, 10) || 1;
			var amount = priceBox.querySelector('.woocommerce-Price-amount');
			var unit = document.createElement('span');
			unit.className = 'pv-qty__unit';
			unit.innerHTML = amount ? amount.outerHTML + ' each' : '';
			var box = document.createElement('div');
			box.className = 'pv-qty';
			box.setAttribute('data-key', key);
			box.innerHTML = '<button type="button" class="pv-qty__btn" data-dir="-1" aria-label="Decrease quantity">\u2212</button><input class="pv-qty__input" type="number" min="1" max="99" inputmode="numeric" value="' + qty + '" aria-label="Quantity"><button type="button" class="pv-qty__btn" data-dir="1" aria-label="Increase quantity">+</button>';
			priceBox.innerHTML = '';
			priceBox.appendChild(unit);
			priceBox.appendChild(box);
			sync(box);
		});
	}

	function sync(box) {
		var input = box.querySelector('.pv-qty__input');
		box.querySelector('[data-dir="-1"]').disabled = parseInt(input.value, 10) <= 1;
	}

	function push(box) {
		var key = box.getAttribute('data-key');
		var input = box.querySelector('.pv-qty__input');
		var qty = Math.min(99, Math.max(1, parseInt(input.value, 10) || 1));
		input.value = qty;
		sync(box);
		window.clearTimeout(timers[key]);
		timers[key] = window.setTimeout(function () {
			var row = box.closest('.elementor-menu-cart__product');
			row.classList.add('is-updating');
			$.post(pvCards.qtyUrl, { key: key, qty: qty, nonce: pvCards.nonce })
				.done(function (res) {
					if (res && res.success) {
						input.value = res.data.qty;
						sync(box);
					}
					$(document.body).trigger('wc_fragment_refresh');
				})
				.fail(function () {
					row.classList.remove('is-updating');
				});
		}, 350);
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.pv-qty__btn');
		if (!btn) {
			return;
		}
		e.preventDefault();
		var box = btn.closest('.pv-qty');
		var input = box.querySelector('.pv-qty__input');
		input.value = Math.min(99, Math.max(1, (parseInt(input.value, 10) || 1) + parseInt(btn.getAttribute('data-dir'), 10)));
		push(box);
	});

	document.addEventListener('change', function (e) {
		if (e.target.classList && e.target.classList.contains('pv-qty__input')) {
			push(e.target.closest('.pv-qty'));
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && e.target.classList && e.target.classList.contains('pv-qty__input')) {
			e.preventDefault();
			e.target.blur();
		}
	});

	$(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart removed_from_cart updated_cart_totals', function () {
		window.setTimeout(enhanceRows, 60);
	});

	if (document.readyState !== 'loading') {
		enhanceRows();
	} else {
		document.addEventListener('DOMContentLoaded', enhanceRows);
	}

	new MutationObserver(function () {
		enhanceRows();
	}).observe(document.body, { childList: true, subtree: true });
})(jQuery);

(function () {
	var scoped = /(woocommerce-shop|tax-product_cat|single-product|post-type-archive-product|woocommerce-cart)/.test(document.body.className);
	if (!scoped) {
		return;
	}
	var box = document.createElement('div');
	box.id = 'pv-toasts';
	box.setAttribute('role', 'status');
	box.setAttribute('aria-live', 'polite');
	document.body.appendChild(box);

	function dismiss(note) {
		if (note.classList.contains('is-leaving')) {
			return;
		}
		note.classList.add('is-leaving');
		window.setTimeout(function () {
			note.remove();
		}, 320);
	}

	function scan() {
		document.querySelectorAll('.woocommerce-message, .woocommerce-info, .woocommerce-error').forEach(function (note) {
			if (box.contains(note) || note.closest('form, .elementor-menu-cart__container')) {
				return;
			}
			box.appendChild(note);
			note.addEventListener('click', function (e) {
				if (!e.target.closest('a')) {
					dismiss(note);
				}
			});
			window.setTimeout(function () {
				dismiss(note);
			}, 6000);
		});
	}

	scan();
	new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
})();

(function ($) {
	if (!/woocommerce-cart/.test(document.body.className)) {
		return;
	}
	var timer = null;

	function submitUpdate() {
		var btn = document.querySelector('.woocommerce-cart-form [name="update_cart"]');
		if (!btn) {
			return;
		}
		btn.disabled = false;
		btn.removeAttribute('aria-disabled');
		btn.click();
	}

	function queue() {
		window.clearTimeout(timer);
		timer = window.setTimeout(submitUpdate, 450);
	}

	function limits(input) {
		var max = parseInt(input.getAttribute('max'), 10);
		return max > 0 ? max : 0;
	}

	function sync(wrap) {
		var input = wrap.querySelector('input.qty');
		var max = limits(input);
		var val = parseInt(input.value, 10) || 1;
		wrap.querySelector('[data-dir="-1"]').disabled = val <= 1;
		wrap.querySelector('[data-dir="1"]').disabled = max > 0 && val >= max;
	}

	function clamp(input) {
		var value = Math.max(1, parseInt(input.value, 10) || 1);
		var max = limits(input);
		if (max > 0) {
			value = Math.min(max, value);
		}
		return value;
	}

	function enhance() {
		document.querySelectorAll('.woocommerce-cart-form .quantity').forEach(function (wrap) {
			var input = wrap.querySelector('input.qty');
			if (!input || wrap.classList.contains('pv-step')) {
				return;
			}
			wrap.classList.add('pv-step');
			input.setAttribute('min', '1');
			input.value = clamp(input);
			var minus = document.createElement('button');
			minus.type = 'button';
			minus.className = 'pv-step__btn';
			minus.setAttribute('data-dir', '-1');
			minus.setAttribute('aria-label', 'Decrease quantity');
			minus.textContent = '\u2212';
			var plus = document.createElement('button');
			plus.type = 'button';
			plus.className = 'pv-step__btn';
			plus.setAttribute('data-dir', '1');
			plus.setAttribute('aria-label', 'Increase quantity');
			plus.textContent = '+';
			wrap.insertBefore(minus, input);
			wrap.appendChild(plus);
			sync(wrap);
		});
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.pv-step__btn');
		if (!btn) {
			return;
		}
		e.preventDefault();
		var wrap = btn.closest('.pv-step');
		var input = wrap.querySelector('input.qty');
		var next = (parseInt(input.value, 10) || 1) + parseInt(btn.getAttribute('data-dir'), 10);
		input.value = next;
		input.value = clamp(input);
		sync(wrap);
		$(input).trigger('input');
		queue();
	});

	document.addEventListener('change', function (e) {
		var input = e.target;
		if (input.classList && input.classList.contains('qty') && input.closest('.pv-step')) {
			input.value = clamp(input);
			sync(input.closest('.pv-step'));
			queue();
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && e.target.classList && e.target.classList.contains('qty') && e.target.closest('.pv-step')) {
			e.preventDefault();
			e.target.blur();
		}
	});

	enhance();
	new MutationObserver(enhance).observe(document.body, { childList: true, subtree: true });
})(jQuery);

(function () {
	var list = document.querySelector('.pv-archive ul.products');
	var nav = document.querySelector('.pv-archive nav.woocommerce-pagination');
	if (!list || !nav) {
		return;
	}
	var next = nav.querySelector('a.next');
	if (!next) {
		return;
	}
	var url = next.getAttribute('href');
	var count = document.querySelector('.pv-archive .woocommerce-result-count');
	var total = 0;
	var shown = list.querySelectorAll('li.product').length;
	if (count) {
		var m = count.textContent.match(/of\s+(\d+)/);
		total = m ? parseInt(m[1], 10) : 0;
	}

	var wrap = document.createElement('div');
	wrap.className = 'pv-more';
	var btn = document.createElement('button');
	btn.type = 'button';
	btn.className = 'pv-more__btn';
	btn.innerHTML = '<span class="pv-more__label">Load more products</span><span class="pv-more__spin" aria-hidden="true"></span>';
	var note = document.createElement('p');
	note.className = 'pv-more__note';
	wrap.appendChild(btn);
	wrap.appendChild(note);
	list.parentNode.insertBefore(wrap, list.nextSibling);
	document.documentElement.classList.add('pv-more-ready');

	function progress() {
		if (total) {
			note.textContent = 'Showing ' + shown + ' of ' + total + ' products';
			if (count) {
				count.textContent = 'Showing 1\u2013' + shown + ' of ' + total + ' results';
			}
		}
	}
	progress();

	function finish() {
		btn.hidden = true;
		if (total) {
			note.textContent = 'You have seen all ' + total + ' products';
		}
	}

	btn.addEventListener('click', function () {
		if (btn.classList.contains('is-loading') || !url) {
			return;
		}
		btn.classList.add('is-loading');
		btn.setAttribute('aria-busy', 'true');
		btn.disabled = true;
		var started = Date.now();
		fetch(url, { credentials: 'same-origin' })
			.then(function (res) {
				if (!res.ok) {
					throw new Error('bad');
				}
				return res.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var items = doc.querySelectorAll('.pv-archive ul.products li.product');
				var link = doc.querySelector('.pv-archive nav.woocommerce-pagination a.next');
				var wait = Math.max(0, 500 - (Date.now() - started));
				return new Promise(function (resolve) {
					window.setTimeout(function () {
						resolve({ items: items, link: link });
					}, wait);
				});
			})
			.then(function (data) {
				var first = null;
				data.items.forEach(function (li) {
					var node = document.importNode(li, true);
					node.classList.add('pv-fresh');
					list.appendChild(node);
					if (!first) {
						first = node;
					}
				});
				shown += data.items.length;
				url = data.link ? data.link.getAttribute('href') : '';
				progress();
				if (!url) {
					finish();
				}
				window.setTimeout(function () {
					list.querySelectorAll('.pv-fresh').forEach(function (n) {
						n.classList.remove('pv-fresh');
					});
				}, 600);
			})
			.catch(function () {
				note.textContent = 'Products could not be loaded. Please try again.';
			})
			.then(function () {
				btn.classList.remove('is-loading');
				btn.removeAttribute('aria-busy');
				btn.disabled = false;
			});
	});
})();
