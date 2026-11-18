(function () {
	var KEY = 'pv_cookie_consent';
	var box = document.getElementById('pv-consent');
	if (!box) {
		return;
	}

	function read() {
		var match = document.cookie.match(new RegExp('(?:^|; )' + KEY + '=([^;]*)'));
		if (match) {
			return decodeURIComponent(match[1]);
		}
		try {
			return window.localStorage.getItem(KEY);
		} catch (e) {
			return null;
		}
	}

	function write(value) {
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = KEY + '=' + encodeURIComponent(value) + '; Max-Age=15552000; Path=/; SameSite=Lax' + secure;
		try {
			window.localStorage.setItem(KEY, value);
		} catch (e) {}
	}

	function close() {
		document.body.classList.remove('pv-consent-open');
		box.classList.remove('is-visible');
		window.setTimeout(function () {
			box.hidden = true;
		}, 300);
	}

	if (read()) {
		return;
	}

	window.setTimeout(function () {
		box.hidden = false;
		document.body.classList.add('pv-consent-open');
		window.requestAnimationFrame(function () {
			box.classList.add('is-visible');
		});
	}, 800);

	box.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-consent]');
		if (!btn) {
			return;
		}
		var value = btn.getAttribute('data-consent');
		write(value);
		document.dispatchEvent(new CustomEvent('pv:consent', { detail: { value: value } }));
		close();
	});
})();
