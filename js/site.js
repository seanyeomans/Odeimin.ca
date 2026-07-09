(function () {
  var yearEl = document.getElementById('year');
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

  // Theme toggle: the initial data-theme is set by an inline script in
  // <head> (before CSS) to avoid a flash of the wrong theme.
  var toggle = document.getElementById('theme-toggle');
  if (toggle) {
    function applyLabel() {
      var dark = document.documentElement.getAttribute('data-theme') === 'dark';
      toggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
    }

    toggle.addEventListener('click', function () {
      var dark = document.documentElement.getAttribute('data-theme') === 'dark';
      var next = dark ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try { localStorage.setItem('odeimin-theme', next); } catch (e) {}
      applyLabel();
    });

    applyLabel();
  }
})();
