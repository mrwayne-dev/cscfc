/* ─── register.js ────────────────────────────────────────────────────────────
 * SPA page module: Player profile registration.
 * Exposes window.CSCFC.registerPage.init() — called by router.js each time
 * the /register route is mounted.
 *
 * Lets any rostered player record their full name, matric number, and email
 * without going through the payment flow. POSTs to api/update_profile.php.
 * ─────────────────────────────────────────────────────────────────────────── */

'use strict';

window.CSCFC = window.CSCFC || {};

CSCFC.registerPage = (function () {

  /* ── Campaign summary (mirrors payment page) ────────────────────────────── */
  async function loadSummary() {
    try {
      var res  = await fetch(API_BASE + '/get_summary.php');
      var data = await res.json();

      var pct = Math.min(100, Math.round((data.total_collected / data.target) * 100)) || 0;

      var coll = document.getElementById('collectedLabel');
      var pctL = document.getElementById('percentLabel');
      var bar  = document.getElementById('progressBar');
      var trk  = document.getElementById('progressBarTrack');
      var plbl = document.getElementById('paidPlayersLabel');
      var tgt  = document.getElementById('teamTarget');

      if (coll) coll.textContent = '₦' + Number(data.total_collected).toLocaleString() + ' collected';
      if (pctL) pctL.textContent = pct + '%';
      if (bar)  bar.style.width  = pct + '%';
      if (trk)  trk.setAttribute('aria-valuenow', pct);
      if (plbl) plbl.textContent = data.fully_paid + ' / ' + (data.fully_paid + data.partial + data.unpaid) + ' players fully paid';
      if (tgt)  tgt.textContent  = '₦' + Number(data.target).toLocaleString();
    } catch (_) {
      var el = document.getElementById('collectedLabel');
      if (el) el.textContent = 'Could not load';
    }
  }

  /* ── Player list + prefill ──────────────────────────────────────────────── */
  var _playersById = {};

  async function loadPlayers() {
    var select = document.getElementById('regPlayerSelect');
    if (!select) return;
    try {
      var res     = await fetch(API_BASE + '/get_players.php');
      var data    = await res.json();
      var players = Array.isArray(data.players) ? data.players : [];

      players.forEach(function (p) {
        _playersById[p.id] = p;
        var opt         = document.createElement('option');
        opt.value       = p.id;
        opt.textContent = p.name;
        select.appendChild(opt);
      });
    } catch (_) {
      var opt = document.createElement('option');
      opt.disabled    = true;
      opt.textContent = 'Error loading players';
      select.appendChild(opt);
    }
  }

  function prefillFromPlayer(playerId) {
    var p = _playersById[playerId];
    if (!p) return;
    var fn = document.getElementById('regFullName');
    var mn = document.getElementById('regMatricNumber');
    var em = document.getElementById('regEmail');
    // Reset first so switching players never leaves another player's data behind
    if (fn) fn.value = p.full_name     || '';
    if (mn) mn.value = p.matric_number || '';
    if (em) em.value = p.email         || '';
    renderPlayerStatus(p);
  }

  function renderPlayerStatus(p) {
    var hint = document.getElementById('regPlayerStatus');
    if (!hint) return;

    var onFile = [];
    if (p.full_name)     onFile.push('full name');
    if (p.matric_number) onFile.push('matric number');
    if (p.email)         onFile.push('email');

    var parts = [];
    if (p.status === 'fully_paid') {
      parts.push('You are fully paid up — you can still update your details below.');
    } else if (p.status === 'partial') {
      parts.push('You have a partial payment on file.');
    } else {
      parts.push('No payment recorded yet.');
    }
    if (onFile.length === 3) {
      parts.push('Everything is already on file — submitting will overwrite it.');
    } else if (onFile.length > 0) {
      parts.push('We already have your ' + onFile.join(', ') + ' — just fill in the rest.');
    } else {
      parts.push('Nothing on file yet — please fill in all three fields.');
    }
    hint.textContent = parts.join(' ');
  }

  /* ── Validation + submit ────────────────────────────────────────────────── */
  function setFieldError(id, msg) {
    var el = document.getElementById(id);
    if (el) el.textContent = msg;
  }

  function clearErrors() {
    ['regPlayerError', 'regFullNameError', 'regMatricNumberError', 'regEmailError']
      .forEach(function (id) { setFieldError(id, ''); });
    showBanner('', '');
  }

  function clearStatus() {
    var hint = document.getElementById('regPlayerStatus');
    if (hint) hint.textContent = '';
  }

  function showBanner(msg, type) {
    var el = document.getElementById('formMsg');
    if (!el) return;
    el.textContent  = msg;
    el.className    = 'form-msg' + (type ? ' is-' + type : '');
    el.style.display = msg ? 'block' : 'none';
  }

  function setLoading(on) {
    var btn = document.getElementById('regSaveBtn');
    if (!btn) return;
    btn.disabled    = on;
    btn.textContent = on ? 'Saving…' : 'Save Profile';
  }

  function validateForm(playerId, fullName, matric, email) {
    var valid = true;

    if (!playerId) {
      setFieldError('regPlayerError', 'Please select your name.');
      valid = false;
    }
    if (!fullName) {
      setFieldError('regFullNameError', 'Please enter your full name.');
      valid = false;
    }
    if (!matric) {
      setFieldError('regMatricNumberError', 'Please enter your matric number.');
      valid = false;
    }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setFieldError('regEmailError', 'Please enter a valid email address.');
      valid = false;
    }
    return valid;
  }

  async function handleSubmit(e) {
    e.preventDefault();
    clearErrors();

    var playerId = document.getElementById('regPlayerSelect').value;
    var fullName = document.getElementById('regFullName').value.trim();
    var matric   = document.getElementById('regMatricNumber').value.trim();
    var email    = document.getElementById('regEmail').value.trim();

    if (!validateForm(playerId, fullName, matric, email)) return;

    setLoading(true);
    try {
      var res = await fetch(API_BASE + '/update_profile.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
          player_id:     Number(playerId),
          full_name:     fullName,
          matric_number: matric,
          email:         email,
        }),
      });
      var data = await res.json();

      if (data.status === 'success') {
        // Use the server's message if present so the user sees the email-confirmation hint
        showBanner(data.message || 'Profile saved.', 'success');
        // Refresh the in-memory cache so re-submits show pre-filled values
        if (_playersById[playerId]) {
          _playersById[playerId].full_name     = fullName;
          _playersById[playerId].matric_number = matric;
          _playersById[playerId].email         = email;
          renderPlayerStatus(_playersById[playerId]);
        }
      } else {
        showBanner(data.message || 'Could not save your profile. Please try again.', 'error');
      }
    } catch (_) {
      showBanner('Network error — please check your connection and try again.', 'error');
    } finally {
      setLoading(false);
    }
  }

  /* ── Init (called by router after fragment is injected) ─────────────────── */
  function init() {
    _playersById = {};

    loadSummary();
    loadPlayers();

    var sel = document.getElementById('regPlayerSelect');
    if (sel) {
      sel.addEventListener('change', function () {
        if (this.value) {
          prefillFromPlayer(this.value);
        } else {
          clearStatus();
        }
      });
    }

    var form = document.getElementById('registerForm');
    if (form) form.addEventListener('submit', handleSubmit);
  }

  return { init: init };

})();
