/* ─── router.js — CSCFC SPA Router ───────────────────────────────────────────
 *
 * Handles client-side routing for GitHub Pages deployment.
 * Pages are fetched from pages/{name}.html and injected into #app.
 * Each page module exposes window.CSCFC.<module>.init() which is called
 * after the HTML fragment is mounted.
 *
 * Clean URLs served:  /        → pay form
 *                     /pay     → pay form
 *                     /leaderboard  → leaderboard
 *                     /receipt     → receipt (keeps ?reference= query)
 * ─────────────────────────────────────────────────────────────────────────── */

'use strict';

(function () {

  /* ── Route map ────────────────────────────────────────────────────────────
   * Each entry maps a pathname to a page fragment + page title + init call.
   * Old .html paths are also mapped for backward compat (bookmarks / Paystack). */
  var ROUTES = {
    '/':                 { page: 'pay',         title: 'CSCFC Equipment Fund \u2014 Contribute' },
    '/pay':              { page: 'pay',         title: 'CSCFC Equipment Fund \u2014 Contribute' },
    '/index.html':       { page: 'pay',         title: 'CSCFC Equipment Fund \u2014 Contribute' },
    '/leaderboard':      { page: 'leaderboard', title: 'CSCFC \u2014 Payment Leaderboard'      },
    '/leaderboard.html': { page: 'leaderboard', title: 'CSCFC \u2014 Payment Leaderboard'      },
    '/receipt':          { page: 'receipt',     title: 'CSCFC \u2014 Payment Receipt'          },
    '/receipt.html':     { page: 'receipt',     title: 'CSCFC \u2014 Payment Receipt'          },
  };

  /* ── Page init dispatch ───────────────────────────────────────────────────
   * Each page JS file registers its init function here via window.CSCFC. */
  var PAGE_INIT = {
    pay:         function () { window.CSCFC && CSCFC.payPage         && CSCFC.payPage.init();         },
    leaderboard: function () { window.CSCFC && CSCFC.leaderboardPage && CSCFC.leaderboardPage.init(); },
    receipt:     function () { window.CSCFC && CSCFC.receiptPage     && CSCFC.receiptPage.init();     },
  };

  /* ── Loader helpers ───────────────────────────────────────────────────────
   * Reuses the #pageLoader already in the shell. */
  function showLoader() {
    var el = document.getElementById('pageLoader');
    if (!el) return;
    el.removeAttribute('hidden');
    el.classList.remove('loader-out');
  }

  function hideLoader() {
    var el = document.getElementById('pageLoader');
    if (!el) return;
    el.classList.add('loader-out');
    setTimeout(function () { el.setAttribute('hidden', ''); }, 520);
  }

  /* ── Route resolution ─────────────────────────────────────────────────────
   * Strips trailing slash (except root), falls back to pay page. */
  function resolve(path) {
    var p = path.split('?')[0];           // drop query string for lookup
    if (p.length > 1 && p.slice(-1) === '/') p = p.slice(0, -1);
    return ROUTES[p] || ROUTES['/'];
  }

  /* ── Core: load a route ───────────────────────────────────────────────────
   * Fetches the HTML fragment, injects it, updates <title>, calls page init. */
  var _currentPage = null;

  function loadRoute(path) {
    var route = resolve(path);
    var app   = document.getElementById('app');

    // If we're already on this page, just re-init (e.g. receipt with new ref)
    if (route.page === _currentPage) {
      PAGE_INIT[route.page]();
      return;
    }

    showLoader();

    fetch(BASE_PATH + 'pages/' + route.page + '.html')
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.text();
      })
      .then(function (html) {
        app.innerHTML      = html;
        document.title     = route.title;
        _currentPage       = route.page;
        // Small tick to let the browser paint the injected DOM before init runs
        setTimeout(function () {
          PAGE_INIT[route.page]();
          hideLoader();
        }, 30);
      })
      .catch(function (err) {
        console.error('[router] Failed to load page "' + route.page + '":', err);
        hideLoader();
      });
  }

  /* ── Public navigate() — use instead of href for SPA links ───────────────
   * Called by onclick or programmatically to push a new route. */
  window.navigate = function (path) {
    history.pushState(null, null, path);
    loadRoute(path);
  };

  /* ── Global link intercept ────────────────────────────────────────────────
   * Catches clicks on <a href="..."> inside #app so we never get a page reload.
   * External links, #hash links, and /admin/ links are let through normally. */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a) return;

    var href = a.getAttribute('href');
    if (!href) return;

    // Let external, protocol-relative, hash, mailto, tel, and admin links through
    if (/^(https?:|\/\/|mailto:|tel:|#)/.test(href)) return;
    if (href.indexOf('/admin') === 0)                  return;

    e.preventDefault();

    // Resolve relative hrefs against the current location
    var url = new URL(href, location.href);
    history.pushState(null, null, url.pathname + url.search);
    loadRoute(url.pathname + url.search);
  });

  /* ── Browser back/forward ─────────────────────────────────────────────────*/
  window.addEventListener('popstate', function () {
    loadRoute(location.pathname + location.search);
  });

  /* ── Boot: load the page for the current URL ──────────────────────────────*/
  loadRoute(location.pathname + location.search);

})();
