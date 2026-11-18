(function () {
	var root = document.querySelector('[data-pva]');
	if (!root) {
		return;
	}
	var side = document.getElementById('pva-side');
	var burger = root.querySelector('[data-pva-open]');

	function setOpen(open) {
		root.classList.toggle('is-open', open);
		document.documentElement.classList.toggle('pva-lock', open);
		if (burger) {
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		if (open) {
			var first = side.querySelector('a, button');
			if (first) {
				first.focus({ preventScroll: true });
			}
		} else if (burger && document.activeElement && side.contains(document.activeElement)) {
			burger.focus({ preventScroll: true });
		}
	}

	root.addEventListener('click', function (e) {
		if (e.target.closest('[data-pva-open]')) {
			setOpen(true);
		} else if (e.target.closest('[data-pva-close]')) {
			setOpen(false);
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && root.classList.contains('is-open')) {
			setOpen(false);
		}
	});

	window.matchMedia('(min-width: 1025px)').addEventListener('change', function (e) {
		if (e.matches) {
			setOpen(false);
		}
	});

	var chips = document.querySelectorAll('[data-pv-filter]');
	if (chips.length) {
		var rows = document.querySelectorAll('.woocommerce-orders-table tbody tr');
		var table = document.querySelector('.woocommerce-orders-table');
		var empty = document.createElement('p');
		empty.className = 'pv-filter__empty';
		empty.hidden = true;
		empty.textContent = 'No orders with this status.';
		if (table) {
			table.parentNode.insertBefore(empty, table.nextSibling);
		}
		chips.forEach(function (chip) {
			chip.addEventListener('click', function () {
				var key = chip.getAttribute('data-pv-filter');
				var shown = 0;
				chips.forEach(function (c) {
					var on = c === chip;
					c.classList.toggle('is-active', on);
					c.setAttribute('aria-selected', on ? 'true' : 'false');
				});
				rows.forEach(function (row) {
					var match = key === 'all' || row.className.indexOf('woocommerce-orders-table__row--status-' + key) !== -1;
					row.hidden = !match;
					if (match) {
						shown++;
					}
				});
				empty.hidden = shown > 0;
			});
		});
	}
})();
