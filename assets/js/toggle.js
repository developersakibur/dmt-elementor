/**
 * Dark Mode Toggle for Elementor: front-end behaviour.
 *
 * The theme is already set on <html data-dmt-theme="..."> by the inline script in <head>.
 * Order: saved choice (localStorage) > device theme > light.
 *
 * Triggers: any [data-dmt-toggle] (the shortcode) or any element with class "dmt-trigger".
 * Event:    document "themechange" with detail.theme = "light" | "dark".
 * API:      window.DMT.get() / .set("dark"|"light") / .toggle()
 */
(function () {
	'use strict';

	var cfg = window.__DMT || { key: 'dmt_theme', ms: 300 };
	var root = document.documentElement;
	var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var timer;

	function stored() {
		try {
			var s = localStorage.getItem(cfg.key);
			return (s === 'dark' || s === 'light') ? s : null;
		} catch (e) {
			return null;
		}
	}

	function save(theme) {
		try {
			localStorage.setItem(cfg.key, theme);
		} catch (e) {}
	}

	function system() {
		return (mq && mq.matches) ? 'dark' : 'light';
	}

	function current() {
		return root.getAttribute('data-dmt-theme') === 'dark' ? 'dark' : 'light';
	}

	function labels() {
		var dark = current() === 'dark';
		var els = document.querySelectorAll('[data-dmt-toggle]');
		for (var i = 0; i < els.length; i++) {
			var el = els[i];
			el.setAttribute('aria-pressed', dark ? 'true' : 'false');
			var label = el.getAttribute(dark ? 'data-label-dark' : 'data-label-light');
			if (label) {
				el.setAttribute('aria-label', label);
			}
		}
	}

	function apply(theme, animate) {
		if (root.hasAttribute('data-dmt-theme') && theme === current()) {
			labels();
			return;
		}
		if (animate && cfg.ms > 0 && !reduce) {
			root.classList.add('dmt-transition');
			clearTimeout(timer);
			timer = setTimeout(function () {
				root.classList.remove('dmt-transition');
			}, cfg.ms + 60);
		}
		root.setAttribute('data-dmt-theme', theme);
		labels();
		try {
			document.dispatchEvent(new CustomEvent('themechange', { detail: { theme: theme } }));
		} catch (e) {}
	}

	function set(theme) {
		if (theme !== 'dark' && theme !== 'light') {
			return;
		}
		save(theme);
		apply(theme, true);
	}

	function toggle() {
		set(current() === 'dark' ? 'light' : 'dark');
	}

	document.addEventListener('click', function (e) {
		var el = e.target && e.target.closest ? e.target.closest('[data-dmt-toggle], .dmt-trigger') : null;
		if (!el) {
			return;
		}
		e.preventDefault();
		toggle();
	});

	// Another tab changed the choice.
	window.addEventListener('storage', function (e) {
		if (e.key === cfg.key || e.key === null) {
			apply(stored() || system(), true);
		}
	});

	// Device theme changed (for example automatic dark at night): follow it until the visitor chooses.
	if (mq) {
		var onSystem = function () {
			if (!stored()) {
				apply(system(), true);
			}
		};
		if (mq.addEventListener) {
			mq.addEventListener('change', onSystem);
		} else if (mq.addListener) {
			mq.addListener(onSystem);
		}
	}

	// Back/forward cache restore.
	window.addEventListener('pageshow', function (e) {
		if (e.persisted) {
			apply(stored() || system(), false);
		}
	});

	// First run: normally already set by the head script; this is only a safety net.
	if (!root.hasAttribute('data-dmt-theme')) {
		apply(stored() || system(), false);
	}
	labels();

	window.DMT = { get: current, set: set, toggle: toggle };
})();
