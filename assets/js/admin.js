/**
 * Dark Mode Toggle for Elementor: settings page behaviour.
 * Tabs, color pickers, copy buttons and the live toggle preview.
 */
(function () {
	'use strict';

	var I18N = window.DMT_ADMIN || { copy: 'Copy', copied: 'Copied ✓' };

	function $(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}

	function $$(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

	/* ---------- Tabs (remembers the last open tab) ---------- */
	var tabs = $$('.dmt-tabs .nav-tab');
	var panels = $$('.dmt-panel');

	function showTab(name) {
		tabs.forEach(function (t) {
			t.classList.toggle('nav-tab-active', t.getAttribute('data-tab') === name);
		});
		panels.forEach(function (p) {
			p.hidden = p.getAttribute('data-panel') !== name;
		});
		try {
			sessionStorage.setItem('dmt_tab', name);
		} catch (e) {}
	}

	tabs.forEach(function (t) {
		t.addEventListener('click', function (e) {
			e.preventDefault();
			showTab(t.getAttribute('data-tab'));
		});
	});

	var start = 'colors';
	try {
		start = sessionStorage.getItem('dmt_tab') || start;
	} catch (e) {}
	if (!tabs.some(function (t) { return t.getAttribute('data-tab') === start; })) {
		start = 'colors';
	}
	showTab(start);

	/* ---------- Dark color rows: picker <-> hex field ---------- */
	function normHex(v) {
		v = (v || '').trim();
		if (v && v.charAt(0) !== '#') {
			v = '#' + v;
		}
		return v;
	}

	$$('.dmt-dark-row').forEach(function (row) {
		var txt = $('.dmt-hex', row);
		var pick = $('.dmt-picker', row);
		var clear = $('.dmt-clear', row);

		pick.addEventListener('input', function () {
			txt.value = pick.value.toUpperCase();
		});
		txt.addEventListener('input', function () {
			var v = normHex(txt.value);
			if (/^#[0-9a-f]{6}$/i.test(v)) {
				pick.value = v;
			}
		});
		clear.addEventListener('click', function () {
			txt.value = '';
		});
	});

	var clearAll = $('#dmt-clear-all');
	if (clearAll) {
		clearAll.addEventListener('click', function () {
			$$('.dmt-dark-row .dmt-hex').forEach(function (el) {
				el.value = '';
			});
		});
	}

	/* ---------- Copy buttons ---------- */
	function fallbackCopy(text) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.setAttribute('readonly', '');
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild(ta);
		ta.select();
		var ok = false;
		try {
			ok = document.execCommand('copy');
		} catch (e) {}
		document.body.removeChild(ta);
		return ok;
	}

	function copyText(text) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(text).then(function () {
				return true;
			}, function () {
				return fallbackCopy(text);
			});
		}
		return Promise.resolve(fallbackCopy(text));
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('[data-copy], [data-copy-from]') : null;
		if (!btn) {
			return;
		}
		e.preventDefault();

		var text = btn.getAttribute('data-copy');
		if (text === null) {
			var src = $(btn.getAttribute('data-copy-from'));
			text = src ? src.value : '';
		}

		var original = btn.getAttribute('data-label') || btn.textContent;
		btn.setAttribute('data-label', original);

		copyText(text).then(function (ok) {
			if (!ok) {
				return;
			}
			btn.textContent = I18N.copied;
			btn.classList.add('is-copied');
			clearTimeout(btn._dmtTimer);
			btn._dmtTimer = setTimeout(function () {
				btn.textContent = original;
				btn.classList.remove('is-copied');
			}, 1500);
		});
	});

	/* ---------- Live toggle preview ---------- */
	var pxVars = { size: '--dmt-size', radius: '--dmt-radius', border_width: '--dmt-bw' };
	var colorVars = {
		bg: '--dmt-bg',
		icon: '--dmt-icon',
		border: '--dmt-border',
		bg_h: '--dmt-bg-h',
		bg_d: '--dmt-bg-d',
		icon_d: '--dmt-icon-d',
		border_d: '--dmt-border-d',
		bg_h_d: '--dmt-bg-h-d'
	};

	function updatePreview() {
		var panelsEl = $$('.dmt-preview-panel');
		if (!panelsEl.length) {
			return;
		}

		var values = {};
		$$('[data-design]').forEach(function (el) {
			var key = el.getAttribute('data-design');
			values[key] = el.type === 'checkbox' ? el.checked : el.value;
		});

		panelsEl.forEach(function (panel) {
			Object.keys(pxVars).forEach(function (k) {
				var n = parseInt(values[k], 10);
				if (!isNaN(n)) {
					panel.style.setProperty(pxVars[k], n + 'px');
				}
			});
			Object.keys(colorVars).forEach(function (k) {
				if (values[k]) {
					panel.style.setProperty(colorVars[k], values[k]);
				}
			});
			panel.style.setProperty('--dmt-shadow', values.shadow ? '0 2px 10px rgba(0,0,0,.15)' : 'none');
			panel.setAttribute('data-style', values.style === 'switch' ? 'switch' : 'button');
		});
	}

	$$('[data-design]').forEach(function (el) {
		el.addEventListener('input', updatePreview);
		el.addEventListener('change', updatePreview);
	});
	updatePreview();
})();
