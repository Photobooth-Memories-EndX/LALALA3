/*!
* Tabler v1.0.0-beta19 (https://tabler.io)
* @version 1.0.0-beta19
* @link https://tabler.io
* Copyright 2018-2023 The Tabler Authors
* Copyright 2018-2023 codecalm.net Paweł Kuna
* Licensed under MIT (https://github.com/tabler/tabler/blob/master/LICENSE)
*/
(function (factory) {
	typeof define === 'function' && define.amd ? define(factory) :
	factory();
})((function () { 'use strict';

	var validThemes = ['light', 'dark'];
	var params = new Proxy(new URLSearchParams(window.location.search), {
	  get: function get(searchParams, prop) {
	    return searchParams.get(prop);
	  }
	});
	var normalize = function(val) {
	  if (!val) return null;
	  var s = String(val).trim().toLowerCase();
	  if (s === 'true') return 'dark';
	  if (s === 'false') return 'light';
	  return validThemes.indexOf(s) !== -1 ? s : null;
	};
	var urlTheme = normalize(params.theme);
	var storedTheme = normalize(localStorage.getItem('owl-dark-mode')) || normalize(localStorage.getItem('tablerTheme'));
	var selectedTheme = urlTheme || storedTheme;
	if (!selectedTheme) {
	  selectedTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
	}
	localStorage.setItem('tablerTheme', selectedTheme);
	localStorage.setItem('owl-dark-mode', selectedTheme);
	document.cookie = 'owl-theme=' + selectedTheme + ';path=/;max-age=31536000;SameSite=Lax';

	if (selectedTheme === 'dark') {
	  document.documentElement.setAttribute('data-bs-theme', 'dark');
	  document.documentElement.setAttribute('data-theme', 'dark');
	  document.documentElement.classList.add('dark-mode');
	  if (document.body) {
	    document.body.setAttribute('data-bs-theme', 'dark');
	    document.body.classList.add('dark-mode');
	  }
	} else {
	  document.documentElement.setAttribute('data-bs-theme', 'light');
	  document.documentElement.removeAttribute('data-theme');
	  document.documentElement.classList.remove('dark-mode');
	  if (document.body) {
	    document.body.removeAttribute('data-bs-theme');
	    document.body.classList.remove('dark-mode');
	  }
	}

}));
