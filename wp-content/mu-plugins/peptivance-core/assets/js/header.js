(function () {
	var html = document.documentElement;
	var lastFocus = null;
	var activeModal = null;
	var viaKeyboard = false;
	var defaultBody = '';
	var requestId = 0;
	var timer = null;

	function getModal(name) {
		return document.getElementById('pv-' + name + '-modal');
	}

	function closeUserMenus() {
		document.querySelectorAll('.pv-user').forEach(function (u) {
			var menu = u.querySelector('.pv-user__menu');
			var toggle = u.querySelector('.pv-user__toggle');
			if (menu && !menu.hidden) {
				menu.hidden = true;
				toggle.setAttribute('aria-expanded', 'false');
			}
		});
	}

	function placeUserMenu(menu, toggle) {
		if (window.innerWidth <= 767) {
			menu.style.top = Math.round(toggle.getBoundingClientRect().bottom + 12) + 'px';
		} else {
			menu.style.top = '';
		}
	}

	function openModal(name, keyboard) {
		var modal = getModal(name);
		if (!modal) {
			return;
		}
		closeUserMenus();
		lastFocus = document.activeElement;
		viaKeyboard = !!keyboard;
		activeModal = modal;
		modal.hidden = false;
		html.classList.add('pv-lock');
		window.requestAnimationFrame(function () {
			modal.classList.add('is-open');
			var target = modal.querySelector('.pv-search__input') || modal.querySelector('.pv-btn--ghost');
			if (target) {
				target.focus();
			}
		});
	}

	function closeModal() {
		if (!activeModal) {
			return;
		}
		var modal = activeModal;
		modal.classList.remove('is-open');
		activeModal = null;
		html.classList.remove('pv-lock');
		window.setTimeout(function () {
			modal.hidden = true;
		}, 200);
		if (viaKeyboard && lastFocus && lastFocus.focus) {
			lastFocus.focus();
		} else if (document.activeElement && document.activeElement.blur) {
			document.activeElement.blur();
		}
	}

	function renderMessage(body, text) {
		body.innerHTML = '';
		var p = document.createElement('p');
		p.className = 'pv-search__hint';
		p.textContent = text;
		body.appendChild(p);
	}

	function renderResults(body, items, query) {
		body.innerHTML = '';
		if (!items.length) {
			renderMessage(body, 'No products found for “' + query + '”. Try another name.');
			return;
		}
		var list = document.createElement('div');
		list.className = 'pv-results';
		items.forEach(function (item) {
			var a = document.createElement('a');
			a.className = 'pv-result';
			a.href = item.permalink;
			var thumb = document.createElement('span');
			thumb.className = 'pv-result__img';
			if (item.image) {
				var img = document.createElement('img');
				img.src = item.image;
				img.alt = '';
				img.loading = 'lazy';
				thumb.appendChild(img);
			}
			var info = document.createElement('span');
			info.className = 'pv-result__info';
			var name = document.createElement('span');
			name.className = 'pv-result__name';
			name.textContent = item.name;
			info.appendChild(name);
			if (item.category) {
				var cat = document.createElement('span');
				cat.className = 'pv-result__cat';
				cat.textContent = item.category;
				info.appendChild(cat);
			}
			var price = document.createElement('span');
			price.className = 'pv-result__price';
			price.textContent = item.price;
			a.appendChild(thumb);
			a.appendChild(info);
			a.appendChild(price);
			list.appendChild(a);
		});
		body.appendChild(list);
		var all = document.createElement('a');
		all.className = 'pv-search__all';
		all.href = pvHeader.search + '?s=' + encodeURIComponent(query) + '&post_type=product';
		all.textContent = 'View all results for “' + query + '”';
		body.appendChild(all);
	}

	function runSearch(input, body, immediate) {
		var query = input.value.trim();
		window.clearTimeout(timer);
		if (query.length < 2) {
			requestId++;
			body.innerHTML = defaultBody;
			return;
		}
		timer = window.setTimeout(function () {
			var current = ++requestId;
			body.classList.add('is-loading');
			fetch(pvHeader.api + '?q=' + encodeURIComponent(query))
				.then(function (r) {
					return r.json();
				})
				.then(function (items) {
					if (current !== requestId) {
						return;
					}
					body.classList.remove('is-loading');
					renderResults(body, Array.isArray(items) ? items : [], query);
				})
				.catch(function () {
					if (current !== requestId) {
						return;
					}
					body.classList.remove('is-loading');
					renderMessage(body, 'Search is unavailable right now. Please try again.');
				});
		}, immediate ? 0 : 220);
	}

	var searchModal = getModal('search');
	if (searchModal) {
		var input = searchModal.querySelector('.pv-search__input');
		var body = searchModal.querySelector('.pv-search__body');
		defaultBody = body.innerHTML;
		input.addEventListener('input', function () {
			runSearch(input, body);
		});
		searchModal.querySelector('form').addEventListener('submit', function (e) {
			if (input.value.trim().length < 1) {
				e.preventDefault();
				input.focus();
			}
		});
	}

	document.addEventListener('click', function (e) {
		var opener = e.target.closest('[data-pv-open]');
		if (opener) {
			e.preventDefault();
			openModal(opener.getAttribute('data-pv-open'), e.detail === 0);
			return;
		}
		if (e.target.closest('[data-pv-close]')) {
			e.preventDefault();
			closeModal();
			return;
		}
		var chip = e.target.closest('[data-pv-query]');
		if (chip && searchModal) {
			var field = searchModal.querySelector('.pv-search__input');
			field.value = chip.getAttribute('data-pv-query');
			runSearch(field, searchModal.querySelector('.pv-search__body'), true);
			field.focus();
			return;
		}
		var toggle = e.target.closest('.pv-user__toggle');
		if (toggle) {
			var menu = toggle.parentNode.querySelector('.pv-user__menu');
			var willOpen = menu.hidden;
			closeUserMenus();
			menu.hidden = !willOpen;
			toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
			if (willOpen) {
				placeUserMenu(menu, toggle);
			}
			return;
		}
		if (!e.target.closest('.pv-user__menu')) {
			closeUserMenus();
		}
	});

	window.addEventListener('resize', closeUserMenus);
	window.addEventListener('scroll', function () {
		if (window.innerWidth <= 767) {
			closeUserMenus();
		}
	}, { passive: true });

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			if (activeModal) {
				closeModal();
			}
			closeUserMenus();
			return;
		}
		if (e.key === 'Tab' && activeModal) {
			var nodes = activeModal.querySelectorAll('a[href],button:not([disabled]),input:not([type=hidden])');
			if (!nodes.length) {
				return;
			}
			var first = nodes[0];
			var last = nodes[nodes.length - 1];
			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		}
	});
})();

(function () {
	var widget = document.querySelector('.elementor-widget-woocommerce-menu-cart');
	if (!widget || !window.MutationObserver) {
		return;
	}
	function sync() {
		document.documentElement.classList.toggle('pv-lock', widget.classList.contains('elementor-menu-cart--shown'));
	}
	new MutationObserver(sync).observe(widget, { attributes: true, attributeFilter: ['class'] });
	sync();
})();

(function () {
	var state = null;
	var suppress = false;
	document.addEventListener('pointerdown', function (e) {
		suppress = false;
		if (e.pointerType === 'touch') {
			return;
		}
		var el = e.target.closest('.pv-drag');
		if (!el) {
			return;
		}
		state = { el: el, x: e.clientX, left: el.scrollLeft, moved: false };
	});
	window.addEventListener('pointermove', function (e) {
		if (!state) {
			return;
		}
		var dx = e.clientX - state.x;
		if (Math.abs(dx) > 4) {
			state.moved = true;
			state.el.classList.add('is-dragging');
		}
		state.el.scrollLeft = state.left - dx;
	});
	window.addEventListener('pointerup', function () {
		if (!state) {
			return;
		}
		suppress = state.moved;
		state.el.classList.remove('is-dragging');
		state = null;
	});
	document.addEventListener('click', function (e) {
		if (suppress) {
			e.preventDefault();
			e.stopPropagation();
			suppress = false;
		}
	}, true);
	document.addEventListener('dragstart', function (e) {
		if (e.target.closest && e.target.closest('.pv-drag')) {
			e.preventDefault();
		}
	});
})();

(function () {
	var list = document.querySelector('.woocommerce-MyAccount-navigation ul');
	if (!list) {
		return;
	}
	list.classList.add('pv-drag');
	list.style.position = 'relative';
	function focusActive() {
		var active = list.querySelector('.is-active');
		if (active && list.scrollWidth > list.clientWidth) {
			list.scrollLeft = Math.max(0, active.offsetLeft - 16);
		}
	}
	focusActive();
	window.addEventListener('resize', focusActive);
})();
