(function () {
	var card = document.querySelector('[data-pv-auth]');
	document.documentElement.classList.add('pau-js');

	function setMode(mode) {
		if (!card) {
			return;
		}
		card.setAttribute('data-mode', mode);
		card.querySelectorAll('[data-pv-panel]').forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-pv-panel') !== mode;
		});
		card.querySelectorAll('.pau-tab').forEach(function (tab) {
			var on = tab.getAttribute('data-pv-tab') === mode;
			tab.classList.toggle('is-active', on);
			tab.setAttribute('aria-selected', on ? 'true' : 'false');
		});
		try {
			var url = new URL(window.location.href);
			if (mode === 'register') {
				url.searchParams.set('mode', 'register');
			} else {
				url.searchParams.delete('mode');
			}
			window.history.replaceState(null, '', url.toString());
		} catch (e) {}
		var first = card.querySelector('[data-pv-panel="' + mode + '"] input:not([type=hidden])');
		if (first && window.matchMedia('(min-width: 768px)').matches) {
			first.focus({ preventScroll: true });
		}
	}

	document.addEventListener('click', function (e) {
		var tab = e.target.closest('[data-pv-tab]');
		if (tab) {
			e.preventDefault();
			setMode(tab.getAttribute('data-pv-tab'));
			return;
		}
		var eye = e.target.closest('[data-pv-eye]');
		if (eye) {
			var input = eye.parentNode.querySelector('input');
			var show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			eye.setAttribute('aria-pressed', show ? 'true' : 'false');
			eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
			eye.classList.toggle('is-on', show);
		}
	});

	document.addEventListener('keydown', function (e) {
		var tab = e.target.closest && e.target.closest('.pau-tab');
		if (!tab || (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight')) {
			return;
		}
		var tabs = Array.prototype.slice.call(card.querySelectorAll('.pau-tab'));
		var next = tabs[(tabs.indexOf(tab) + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
		next.focus();
		setMode(next.getAttribute('data-pv-tab'));
	});

	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form.classList || !form.classList.contains('pau-form')) {
			return;
		}
		var btn = form.querySelector('.pau-submit');
		if (btn && btn.classList.contains('is-loading')) {
			e.preventDefault();
			return;
		}
		if (btn) {
			window.setTimeout(function () {
				btn.classList.add('is-loading');
			}, 0);
		}
	});

	window.addEventListener('pageshow', function () {
		document.querySelectorAll('.pau-submit.is-loading').forEach(function (b) {
			b.classList.remove('is-loading');
		});
	});

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

	function toast(note) {
		if (box.contains(note)) {
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
		}, note.classList.contains('woocommerce-error') ? 9000 : 6000);
	}

	function scan() {
		document.querySelectorAll('.pau-content .woocommerce-error, .pau-content .woocommerce-message, .pau-content .woocommerce-info').forEach(toast);
	}

	scan();
	new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
})();
