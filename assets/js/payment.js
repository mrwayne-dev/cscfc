/* ─── payment.js ─────────────────────────────────────────────────────────────
 * SPA page module: Payment form + campaign summary.
 * Exposes window.CSCFC.payPage.init() — called by router.js each time the
 * pay route is mounted.  All DOM interaction is scoped to the injected fragment.
 * ─────────────────────────────────────────────────────────────────────────── */

'use strict';

/* ── Module namespace ────────────────────────────────────────────────────── */
window.CSCFC = window.CSCFC || {};

CSCFC.payPage = (function () {

  /* ══════════════════════════════════════════════════════════════════════════
     §1 — CAMPAIGN SUMMARY  (progress bar + stat cards)
     ══════════════════════════════════════════════════════════════════════════ */

  async function loadSummary() {
    try {
      var res  = await fetch(API_BASE + '/get_summary.php');
      var data = await res.json();

      var pct = Math.min(100, Math.round((data.total_collected / data.target) * 100)) || 0;

      document.getElementById('collectedLabel').textContent =
        '\u20a6' + Number(data.total_collected).toLocaleString() + ' collected';
      document.getElementById('percentLabel').textContent = pct + '%';
      document.getElementById('progressBar').style.width  = pct + '%';

      var track = document.getElementById('progressBarTrack');
      if (track) track.setAttribute('aria-valuenow', pct);

      document.getElementById('paidPlayersLabel').textContent =
        data.fully_paid + ' / ' + (data.fully_paid + data.partial + data.unpaid) + ' players fully paid';

      document.getElementById('teamTarget').textContent =
        '\u20a6' + Number(data.target).toLocaleString();

    } catch (_) {
      var el = document.getElementById('collectedLabel');
      if (el) el.textContent = 'Could not load';
    }
  }


  /* ══════════════════════════════════════════════════════════════════════════
     §2 — PLAYER LIST
     ══════════════════════════════════════════════════════════════════════════ */

  async function loadPlayers() {
    var select = document.getElementById('playerSelect');
    if (!select) return;

    try {
      var res              = await fetch(API_BASE + '/get_players.php');
      var data             = await res.json();
      var players          = Array.isArray(data.players) ? data.players : [];

      players.forEach(function (p) {
        var opt         = document.createElement('option');
        opt.value       = p.id;
        opt.textContent = p.name;
        select.appendChild(opt);
      });

    } catch (_) {
      var opt = document.createElement('option');
      opt.disabled = true;
      opt.textContent = 'Error loading players';
      select.appendChild(opt);
    }
  }


  /* ══════════════════════════════════════════════════════════════════════════
     §3 — BALANCE FETCH + AMOUNT FIELD LOGIC
     ══════════════════════════════════════════════════════════════════════════ */

  var playerBalance = { total_paid: 0, remaining_balance: PLAYER_TARGET };

  async function fetchBalance(playerId) {
    try {
      var res  = await fetch(API_BASE + '/get_user_balance.php?player_id=' + playerId);
      var data = await res.json();
      playerBalance = data;
    } catch (_) {
      playerBalance = { total_paid: 0, remaining_balance: PLAYER_TARGET };
    }
    updateAmountField();
  }

  function updateAmountField() {
    var amountInput = document.getElementById('amountInput');
    var amountHint  = document.getElementById('amountHint');
    var isFull      = document.getElementById('payFull') && document.getElementById('payFull').checked;
    var remaining   = playerBalance.remaining_balance;
    if (!amountInput) return;

    if (isFull) {
      amountInput.value    = remaining;
      amountInput.readOnly = true;
      amountHint.textContent = 'Full remaining balance: \u20a6' + Number(remaining).toLocaleString();
    } else {
      amountInput.readOnly = false;
      if (amountInput.value === '' || Number(amountInput.value) > remaining) {
        amountInput.value = '';
      }
      amountHint.textContent =
        'Minimum \u20a6' + MIN_INSTALLMENT.toLocaleString() +
        ' \u2014 max \u20a6' + Number(remaining).toLocaleString() + ' remaining';
    }

    setFieldError('amountError', '');
  }


  /* ══════════════════════════════════════════════════════════════════════════
     §4 — FORM VALIDATION + SUBMISSION
     ══════════════════════════════════════════════════════════════════════════ */

  function setFieldError(id, msg) {
    var el = document.getElementById(id);
    if (el) el.textContent = msg;
  }

  function clearErrors() {
    ['playerError', 'emailError', 'typeError', 'amountError']
      .forEach(function (id) { setFieldError(id, ''); });
    showBanner('', '');
  }

  function showBanner(msg, type) {
    var el = document.getElementById('formMsg');
    if (!el) return;
    el.textContent = msg;
    el.className   = 'form-msg' + (type ? ' is-' + type : '');
    el.style.display = msg ? 'block' : 'none';
  }

  function setLoading(on) {
    var btn = document.getElementById('payBtn');
    if (!btn) return;
    btn.disabled    = on;
    btn.textContent = on ? 'Processing\u2026' : 'Proceed to Pay';
  }

  function validateForm(playerId, email, paymentType, amount) {
    var valid = true;
    var remaining = playerBalance.remaining_balance;

    if (!playerId) {
      setFieldError('playerError', 'Please select your name.');
      valid = false;
    }

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setFieldError('emailError', 'Please enter a valid email address.');
      valid = false;
    }

    if (!paymentType) {
      setFieldError('typeError', 'Please select a payment type.');
      valid = false;
    }

    var amt = Number(amount);

    if (!amount || isNaN(amt) || amt <= 0) {
      setFieldError('amountError', 'Please enter an amount.');
      valid = false;
    } else if (remaining <= 0) {
      setFieldError('amountError', 'You are already fully paid!');
      valid = false;
    } else if (amt < MIN_INSTALLMENT) {
      setFieldError('amountError', 'Minimum installment is \u20a6' + MIN_INSTALLMENT.toLocaleString() + '.');
      valid = false;
    } else if (amt > remaining) {
      setFieldError('amountError',
        'Amount exceeds your remaining balance of \u20a6' + Number(remaining).toLocaleString() + '.');
      valid = false;
    }

    return valid;
  }

  async function handleSubmit(e) {
    e.preventDefault();
    clearErrors();

    var playerId    = document.getElementById('playerSelect').value;
    var email       = document.getElementById('emailInput').value.trim();
    var radioChecked = document.querySelector('input[name="paymentType"]:checked');
    var paymentType = radioChecked ? radioChecked.value : null;
    var amount      = document.getElementById('amountInput').value;

    if (!validateForm(playerId, email, paymentType, amount)) return;

    setLoading(true);

    try {
      var res  = await fetch(API_BASE + '/initiate-payment.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
          player_id:    Number(playerId),
          email:        email,
          amount:       Number(amount),
          payment_type: paymentType,
        }),
      });

      var data = await res.json();

      if (data.status === 'success' && data.authorization_url) {
        window.location.href = data.authorization_url;
      } else {
        setLoading(false);
        showBanner(data.message || 'Payment could not be initiated. Please try again.', 'error');
      }

    } catch (_) {
      setLoading(false);
      showBanner('Network error \u2014 please check your connection and try again.', 'error');
    }
  }


  /* ══════════════════════════════════════════════════════════════════════════
     §5 — INIT  (called by router after fragment is injected)
     ══════════════════════════════════════════════════════════════════════════ */

  function init() {
    // Reset per-player state for a fresh page visit
    playerBalance = { total_paid: 0, remaining_balance: PLAYER_TARGET };

    // Kick off data fetches
    loadSummary();
    loadPlayers();

    // Player dropdown → fetch balance
    var playerSelect = document.getElementById('playerSelect');
    if (playerSelect) {
      playerSelect.addEventListener('change', function () {
        if (this.value) fetchBalance(this.value);
        else {
          playerBalance = { total_paid: 0, remaining_balance: PLAYER_TARGET };
          updateAmountField();
        }
      });
    }

    // Payment type radios → recalculate amount field
    document.querySelectorAll('input[name="paymentType"]').forEach(function (radio) {
      radio.addEventListener('change', updateAmountField);
    });

    // Form submit
    var form = document.getElementById('paymentForm');
    if (form) form.addEventListener('submit', handleSubmit);

    // Set initial amount field state
    updateAmountField();
  }

  return { init: init };

})();
