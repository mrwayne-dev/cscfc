/* ─── router.js — CSCFC SPA Router ───────────────────────────────────────────
 *
 * Works for both:
 *   • GitHub Project site  (mrwayne-dev.github.io/cscfc)  BASE_PATH = '/cscfc/'
 *   • Local dev / custom domain                            BASE_PATH = '/'
 *
 * Strategy:
 *   - BASE_PREFIX = BASE_PATH with trailing slash removed ('/cscfc' or '')
 *   - All incoming pathnames have BASE_PREFIX stripped before route lookup
 *   - All outgoing push/replaceState calls have BASE_PREFIX re-added
 * ─────────────────────────────────────────────────────────────────────────── */

'use strict';

(function () {

  /* ── Derive base prefix ───────────────────────────────────────────────────
   * BASE_PATH is defined in config.js.
   * '/cscfc/' → '/cscfc'   |   '/' → ''                                    */
  var BASE  = (typeof BASE_PATH !== 'undefined') ? BASE_PATH : '/';
  var BPFX  = BASE === '/' ? '' : BASE.replace(/\/$/, '');   // e.g. '/cscfc'

  /* ── Route map ────────────────────────────────────────────────────────────
   * Keys are clean route paths — no base prefix, no query string.           */
  var ROUTES = {
    '/':                 { page: 'pay',         title: 'CSCFC Equipment Fund \u2014 Contribute' },
    '/pay':              { page: 'pay',         title: 'CSCFC Equipment Fund \u2014 Contribute' },
    '/index.html':       { page: 'pay',         title: 'CSCFC Equipment Fund \u2014 Contribute' },
    '/leaderboard':      { page: 'leaderboard', title: 'CSCFC \u2014 Payment Leaderboard'       },
    '/leaderboard.html': { page: 'leaderboard', title: 'CSCFC \u2014 Payment Leaderboard'       },
    '/receipt':          { page: 'receipt',     title: 'CSCFC \u2014 Payment Receipt'           },
    '/receipt.html':     { page: 'receipt',     title: 'CSCFC \u2014 Payment Receipt'           },
  };

  /* ── Page init dispatch ───────────────────────────────────────────────────*/
  var PAGE_INIT = {
    pay:         function () { window.CSCFC && CSCFC.payPage         && CSCFC.payPage.init();         },
    leaderboard: function () { window.CSCFC && CSCFC.leaderboardPage && CSCFC.leaderboardPage.init(); },
    receipt:     function () { window.CSCFC && CSCFC.receiptPage     && CSCFC.receiptPage.init();     },
  };

  /* ── Loader helpers ───────────────────────────────────────────────────────*/
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

  /* ── toKey(fullPath) → clean route key ───────────────────────────────────
   * '/cscfc/leaderboard'  → '/leaderboard'
   * '/receipt?ref=xxx'    → '/receipt'
   * '/cscfc/'             → '/'                                             */
  function toKey(path) {
    var p = path.split('?')[0];                    // strip query string
    if (BPFX && p.indexOf(BPFX) === 0) {           // strip base prefix
      p = p.slice(BPFX.length) || '/';
    }
    if (p.length > 1 && p.slice(-1) === '/') p = p.slice(0, -1); // strip trailing /
    return p || '/';
  }

  /* ── toFull(routePath) → full path with base prefix ─────────────────────
   * '/leaderboard' → '/cscfc/leaderboard'
   * '/'            → '/cscfc/'                                              */
  function toFull(routePath) {
    if (routePath === '/') return BPFX + '/';
    return BPFX + routePath;
  }

  /* ── Resolve a full path to a route entry ────────────────────────────────*/
  function resolve(fullPath) {
    return ROUTES[toKey(fullPath)] || ROUTES['/'];
  }

  /* ── Load a route ─────────────────────────────────────────────────────────
   * Fetches the page fragment, injects into #app, calls page init.          */
  function loadRoute(fullPath) {
    var route = resolve(fullPath);
    showLoader();

    fetch(BASE_PATH + 'pages/' + route.page + '.html')
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.text();
      })
      .then(function (html) {
        document.getElementById('app').innerHTML = html;
        document.title = route.title;
        // Small tick so the browser paints injected DOM before init runs
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

  /* ── Public navigate(routePath) ──────────────────────────────────────────
   * Call with a clean route like navigate('/leaderboard').
   * Adds the base prefix before pushing to history.                         */
  window.navigate = function (routePath) {
    var full = toFull(routePath);
    history.pushState(null, null, full);
    loadRoute(full);
  };

  /* ── Global link intercept ────────────────────────────────────────────────
   * Catches clicks on <a href="..."> so we never do a full page reload.
   * Fragment hrefs use clean route paths like '/leaderboard' or '/'.        */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a) return;

    var href = a.getAttribute('href');
    if (!href) return;

    // Let external, protocol-relative, hash, mailto, tel, and admin links through
    if (/^(https?:|\/\/|mailto:|tel:|#)/.test(href)) return;
    if (href.indexOf('/admin') === 0)                  return;

    e.preventDefault();

    // href is a clean route path ('/leaderboard', '/', etc.) — add base prefix
    var full = href === '/' ? BPFX + '/' : BPFX + href;
    history.pushState(null, null, full);
    loadRoute(full);
  });

  /* ── Browser back / forward ──────────────────────────────────────────────*/
  window.addEventListener('popstate', function () {
    loadRoute(location.pathname + location.search);
  });

  /* ── Boot ────────────────────────────────────────────────────────────────*/
  loadRoute(location.pathname + location.search);

})();
