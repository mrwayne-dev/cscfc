/* ─── receipt.js ──────────────────────────────────────────────────────────────
 * SPA page module: Payment receipt / verification.
 * Exposes window.CSCFC.receiptPage.init() — called by router.js each time the
 * receipt route is mounted.  Reads ?reference= from location.search.
 * ─────────────────────────────────────────────────────────────────────────── */

'use strict';

window.CSCFC = window.CSCFC || {};

CSCFC.receiptPage = (function () {

  /* ── Helpers ──────────────────────────────────────────────────────────── */

  function fmt(n) { return '\u20a6' + Number(n).toLocaleString(); }

  function fmtDate(iso) {
    try {
      return new Date(iso).toLocaleDateString('en-NG', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
      });
    } catch (_) { return iso; }
  }


  /* ── State renderers ──────────────────────────────────────────────────── */

  function showSuccess(data) {
    document.getElementById('detailPlayer').textContent = data.player_name || '\u2014';
    document.getElementById('detailAmount').textContent = fmt(data.amount);
    document.getElementById('detailRef').textContent    = data.reference   || '\u2014';
    document.getElementById('detailDate').textContent   = data.paid_at ? fmtDate(data.paid_at) : '\u2014';

    document.getElementById('receiptSub').textContent =
      'Your contribution to the CSCFC Equipment Fund has been recorded.';

    var remaining = Number(data.remaining_balance || 0);
    if (remaining > 0) {
      var callout = document.getElementById('remainingCallout');
      if (callout) callout.hidden = false;
      var remText = document.getElementById('remainingText');
      if (remText) remText.textContent =
        'You still have a balance of ' + fmt(remaining) + '. You can make another payment anytime.';
    }

    var stateSuccess = document.getElementById('stateSuccess');
    if (stateSuccess) stateSuccess.hidden = false;
  }

  function showError(msg) {
    var errMsg = document.getElementById('errorMsg');
    if (errMsg) errMsg.textContent =
      msg || "We couldn\u2019t verify this payment. It may still be processing \u2014 check back shortly.";
    var stateError = document.getElementById('stateError');
    if (stateError) stateError.hidden = false;
  }


  /* ── Init ─────────────────────────────────────────────────────────────── */

  async function init() {
    var params = new URLSearchParams(location.search);
    var ref    = params.get('reference') || params.get('trxref');

    if (!ref) {
      showError('No payment reference found in the URL.');
      return;
    }

    try {
      // verify_payment.php checks DB first; if still pending, polls Paystack
      // directly and updates the record before responding.
      var res  = await fetch(API_BASE + '/verify_payment.php?reference=' + encodeURIComponent(ref));
      var data = await res.json();

      if (data.status === 'success') {
        showSuccess(data);
      } else {
        showError(data.message || null);
      }
    } catch (_) {
      showError('Network error \u2014 could not reach the server.');
    }
  }

  return { init: init };

})();
