(function () {
	var tracks = document.querySelectorAll('.pv-voices__track');
	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	tracks.forEach(function (el) {
		var originals = Array.prototype.slice.call(el.children);
		if (originals.length < 2) {
			return;
		}

		function makeClones() {
			return originals.map(function (node) {
				var clone = node.cloneNode(true);
				clone.setAttribute('aria-hidden', 'true');
				clone.removeAttribute('id');
				clone.querySelectorAll('[id]').forEach(function (n) {
					n.removeAttribute('id');
				});
				return clone;
			});
		}

		makeClones().forEach(function (clone) {
			el.insertBefore(clone, originals[0]);
		});
		makeClones().forEach(function (clone) {
			el.appendChild(clone);
		});

		var setWidth = 0;
		var step = 0;
		var down = false;
		var startX = 0;
		var startLeft = 0;
		var hovering = false;
		var visible = false;
		var timer = null;
		var settle = null;

		function measure() {
			setWidth = originals[0].offsetLeft - el.children[0].offsetLeft;
			step = el.children[1].offsetLeft - el.children[0].offsetLeft;
		}

		function normalise() {
			if (!setWidth) {
				return;
			}
			var sl = el.scrollLeft;
			while (sl >= setWidth * 2) {
				sl -= setWidth;
			}
			while (sl < setWidth) {
				sl += setWidth;
			}
			if (sl !== el.scrollLeft) {
				el.style.scrollBehavior = 'auto';
				el.scrollLeft = sl;
				el.style.scrollBehavior = '';
			}
		}

		function advance() {
			if (down || hovering || !visible || document.hidden) {
				return;
			}
			normalise();
			el.scrollBy({ left: step, behavior: 'smooth' });
		}

		function start() {
			if (reduced || timer) {
				return;
			}
			timer = window.setInterval(advance, 3000);
		}

		function stop() {
			window.clearInterval(timer);
			timer = null;
		}

		function init() {
			measure();
			el.style.scrollBehavior = 'auto';
			el.scrollLeft = setWidth;
			el.style.scrollBehavior = '';
		}

		el.addEventListener('scroll', function () {
			window.clearTimeout(settle);
			settle = window.setTimeout(function () {
				if (!down) {
					normalise();
				}
			}, 160);
		}, { passive: true });

		el.addEventListener('pointerdown', function (e) {
			if (e.pointerType === 'touch') {
				return;
			}
			down = true;
			startX = e.clientX;
			startLeft = el.scrollLeft;
			el.classList.add('is-dragging');
		});

		window.addEventListener('pointermove', function (e) {
			if (!down) {
				return;
			}
			el.scrollLeft = startLeft - (e.clientX - startX);
		});

		window.addEventListener('pointerup', function () {
			if (!down) {
				return;
			}
			down = false;
			el.classList.remove('is-dragging');
			normalise();
		});

		el.addEventListener('dragstart', function (e) {
			e.preventDefault();
		});

		el.addEventListener('mouseenter', function () {
			hovering = true;
		});

		el.addEventListener('mouseleave', function () {
			hovering = false;
		});

		window.addEventListener('resize', function () {
			var index = step ? Math.round((el.scrollLeft - setWidth) / step) : 0;
			measure();
			el.style.scrollBehavior = 'auto';
			el.scrollLeft = setWidth + index * step;
			el.style.scrollBehavior = '';
		});

		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				visible = entries[0].isIntersecting;
			}, { threshold: 0.25 }).observe(el);
		} else {
			visible = true;
		}

		if (document.readyState === 'complete') {
			init();
		} else {
			window.addEventListener('load', init);
		}
		start();
	});
})();

(function () {
	document.querySelectorAll('.pv-chips').forEach(function (el) {
		var down = false;
		var moved = false;
		var startX = 0;
		var startLeft = 0;
		el.addEventListener('pointerdown', function (e) {
			if (e.pointerType === 'touch') {
				return;
			}
			down = true;
			moved = false;
			startX = e.clientX;
			startLeft = el.scrollLeft;
		});
		window.addEventListener('pointermove', function (e) {
			if (!down) {
				return;
			}
			var dx = e.clientX - startX;
			if (Math.abs(dx) > 4) {
				moved = true;
				el.classList.add('is-dragging');
			}
			el.scrollLeft = startLeft - dx;
		});
		window.addEventListener('pointerup', function () {
			if (!down) {
				return;
			}
			down = false;
			window.setTimeout(function () {
				el.classList.remove('is-dragging');
			}, 0);
		});
		el.addEventListener('click', function (e) {
			if (moved) {
				e.preventDefault();
				moved = false;
			}
		}, true);
		el.addEventListener('dragstart', function (e) {
			e.preventDefault();
		});
		el.scrollLeft = 0;
	});
})();

(function () {
	function clean(path) {
		return path.replace(/\/+$/, '') || '/';
	}
	var here = clean(window.location.pathname);
	document.querySelectorAll('.pv-footer__links a').forEach(function (a) {
		var url;
		try {
			url = new URL(a.href, window.location.origin);
		} catch (e) {
			return;
		}
		if (url.origin === window.location.origin && url.hash === '' && clean(url.pathname) === here) {
			a.classList.add('is-active');
			a.setAttribute('aria-current', 'page');
		}
	});
})();

(function () {
	function clean(path) {
		return path.replace(/\/+$/, '') || '/';
	}
	var here = clean(window.location.pathname);
	var shopPath = '/shop';
	document.querySelectorAll('.pv-nav a.elementor-item').forEach(function (a) {
		var url;
		try {
			url = new URL(a.href, window.location.origin);
		} catch (e) {
			return;
		}
		if (url.origin !== window.location.origin || url.hash !== '') {
			return;
		}
		var target = clean(url.pathname);
		var match = target === here;
		if (target === shopPath && (here.indexOf('/product-category') === 0 || here.indexOf('/product/') === 0)) {
			match = true;
		}
		if (match) {
			a.classList.add('is-active');
			a.setAttribute('aria-current', 'page');
		}
	});
})();
