(function () {
	var root = document.querySelector('[data-pv-avatar]');
	var modal = document.getElementById('pv-avatar-modal');
	if (!root || !modal || typeof pvAvatar === 'undefined') {
		return;
	}

	var fileInput = root.querySelector('[data-pv-avatar-file]');
	var removeBtn = root.querySelector('[data-pv-avatar-remove]');
	var message = root.querySelector('[data-pv-avatar-msg]');
	var canvas = modal.querySelector('.pv-crop__canvas');
	var stage = modal.querySelector('[data-pv-crop]');
	var zoom = modal.querySelector('[data-pv-crop-zoom]');
	var saveBtn = modal.querySelector('[data-pv-avatar-save]');
	var ctx = canvas.getContext('2d');
	var SIZE = canvas.width;
	var state = { img: null, base: 1, scale: 1, x: 0, y: 0, drag: null, url: null };
	var lastFocus = null;

	function say(text, isError) {
		message.textContent = text || '';
		message.classList.toggle('is-error', !!isError);
	}

	function swapAvatars(html) {
		document.querySelectorAll('[data-pv-avatar-image], .pv-user__toggle, .pv-account-card__avatar').forEach(function (el) {
			el.innerHTML = html;
		});
	}

	function clamp() {
		var w = state.img.width * state.base * state.scale;
		var h = state.img.height * state.base * state.scale;
		state.x = Math.min(0, Math.max(SIZE - w, state.x));
		state.y = Math.min(0, Math.max(SIZE - h, state.y));
	}

	function draw() {
		ctx.clearRect(0, 0, SIZE, SIZE);
		ctx.fillStyle = '#ffffff';
		ctx.fillRect(0, 0, SIZE, SIZE);
		ctx.drawImage(state.img, state.x, state.y, state.img.width * state.base * state.scale, state.img.height * state.base * state.scale);
	}

	function setScale(next, cx, cy) {
		var prev = state.scale;
		state.scale = Math.min(3, Math.max(1, next));
		var ratio = state.scale / prev;
		state.x = cx - (cx - state.x) * ratio;
		state.y = cy - (cy - state.y) * ratio;
		clamp();
		zoom.value = state.scale;
		draw();
	}

	function openModal() {
		lastFocus = document.activeElement;
		modal.hidden = false;
		document.documentElement.classList.add('pv-lock');
		window.requestAnimationFrame(function () {
			modal.classList.add('is-open');
			saveBtn.focus();
		});
	}

	function closeModal() {
		modal.classList.remove('is-open');
		document.documentElement.classList.remove('pv-lock');
		window.setTimeout(function () {
			modal.hidden = true;
		}, 200);
		if (state.url) {
			URL.revokeObjectURL(state.url);
			state.url = null;
		}
		fileInput.value = '';
		if (lastFocus && lastFocus.focus) {
			lastFocus.focus();
		}
	}

	function loadFile(file) {
		say('');
		if (!file) {
			return;
		}
		if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
			say('Choose a JPG, PNG or WebP image.', true);
			return;
		}
		if (file.size > 15 * 1024 * 1024) {
			say('That image is too large. Choose one under 15 MB.', true);
			return;
		}
		var url = URL.createObjectURL(file);
		var img = new Image();
		img.onload = function () {
			if (img.width < 128 || img.height < 128) {
				URL.revokeObjectURL(url);
				say('That image is too small. Use at least 128 by 128 pixels.', true);
				return;
			}
			state.img = img;
			state.url = url;
			state.base = Math.max(SIZE / img.width, SIZE / img.height);
			state.scale = 1;
			state.x = (SIZE - img.width * state.base) / 2;
			state.y = (SIZE - img.height * state.base) / 2;
			zoom.value = 1;
			draw();
			openModal();
		};
		img.onerror = function () {
			URL.revokeObjectURL(url);
			say('That file could not be read as an image.', true);
		};
		img.src = url;
	}

	function pointer(e) {
		var rect = canvas.getBoundingClientRect();
		var factor = SIZE / rect.width;
		return { x: (e.clientX - rect.left) * factor, y: (e.clientY - rect.top) * factor };
	}

	stage.addEventListener('pointerdown', function (e) {
		var p = pointer(e);
		state.drag = { px: p.x, py: p.y, x: state.x, y: state.y };
		stage.setPointerCapture(e.pointerId);
		stage.classList.add('is-dragging');
	});

	stage.addEventListener('pointermove', function (e) {
		if (!state.drag) {
			return;
		}
		var p = pointer(e);
		state.x = state.drag.x + (p.x - state.drag.px);
		state.y = state.drag.y + (p.y - state.drag.py);
		clamp();
		draw();
	});

	function endDrag() {
		state.drag = null;
		stage.classList.remove('is-dragging');
	}

	stage.addEventListener('pointerup', endDrag);
	stage.addEventListener('pointercancel', endDrag);

	stage.addEventListener('wheel', function (e) {
		e.preventDefault();
		var p = pointer(e);
		setScale(state.scale + (e.deltaY < 0 ? 0.1 : -0.1), p.x, p.y);
	}, { passive: false });

	zoom.addEventListener('input', function () {
		setScale(parseFloat(zoom.value), SIZE / 2, SIZE / 2);
	});

	function exportBlob(done) {
		var out = document.createElement('canvas');
		out.width = 512;
		out.height = 512;
		var octx = out.getContext('2d');
		var k = 512 / SIZE;
		octx.fillStyle = '#ffffff';
		octx.fillRect(0, 0, 512, 512);
		octx.drawImage(state.img, state.x * k, state.y * k, state.img.width * state.base * state.scale * k, state.img.height * state.base * state.scale * k);
		out.toBlob(done, 'image/jpeg', 0.9);
	}

	saveBtn.addEventListener('click', function () {
		saveBtn.disabled = true;
		saveBtn.textContent = 'Saving...';
		exportBlob(function (blob) {
			var body = new FormData();
			body.append('avatar', blob, 'avatar.jpg');
			fetch(pvAvatar.api, { method: 'POST', headers: { 'X-WP-Nonce': pvAvatar.nonce }, credentials: 'same-origin', body: body })
				.then(function (r) {
					return r.json().then(function (data) {
						return { ok: r.ok, data: data };
					});
				})
				.then(function (res) {
					saveBtn.disabled = false;
					saveBtn.textContent = 'Save photo';
					if (!res.ok) {
						say(res.data && res.data.message ? res.data.message : 'The photo could not be saved.', true);
						closeModal();
						return;
					}
					swapAvatars(res.data.html);
					removeBtn.hidden = false;
					say(res.data.message);
					closeModal();
				})
				.catch(function () {
					saveBtn.disabled = false;
					saveBtn.textContent = 'Save photo';
					say('The photo could not be saved. Check your connection and try again.', true);
					closeModal();
				});
		});
	});

	removeBtn.addEventListener('click', function () {
		removeBtn.disabled = true;
		fetch(pvAvatar.api, { method: 'DELETE', headers: { 'X-WP-Nonce': pvAvatar.nonce }, credentials: 'same-origin' })
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				removeBtn.disabled = false;
				swapAvatars(data.html);
				removeBtn.hidden = true;
				say(data.message);
			})
			.catch(function () {
				removeBtn.disabled = false;
				say('The photo could not be removed. Please try again.', true);
			});
	});

	root.addEventListener('click', function (e) {
		if (e.target.closest('[data-pv-avatar-pick]')) {
			e.preventDefault();
			fileInput.click();
		}
	});

	fileInput.addEventListener('change', function () {
		loadFile(fileInput.files && fileInput.files[0]);
	});

	['dragenter', 'dragover'].forEach(function (name) {
		root.addEventListener(name, function (e) {
			e.preventDefault();
			root.classList.add('is-over');
		});
	});

	['dragleave', 'drop'].forEach(function (name) {
		root.addEventListener(name, function (e) {
			e.preventDefault();
			root.classList.remove('is-over');
		});
	});

	root.addEventListener('drop', function (e) {
		var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
		loadFile(file);
	});

	modal.addEventListener('click', function (e) {
		if (e.target.closest('[data-pv-avatar-cancel]')) {
			closeModal();
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && !modal.hidden) {
			closeModal();
		}
	});
})();
