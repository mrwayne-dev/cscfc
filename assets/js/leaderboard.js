/* ─── leaderboard.js ─────────────────────────────────────────────────────────
 * SPA page module: Leaderboard table + campaign summary.
 * Exposes window.CSCFC.leaderboardPage.init() — called by router.js each time
 * the leaderboard route is mounted.
 * ─────────────────────────────────────────────────────────────────────────── */

'use strict';

window.CSCFC = window.CSCFC || {};

CSCFC.leaderboardPage = (function () {

  /* ── Helpers ──────────────────────────────────────────────────────────── */

  function fmt(n) { return '\u20a6' + Number(n).toLocaleString(); }

  function initials(name) {
    var parts = String(name).trim().split(' ');
    return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
  }

  function statusOf(player) {
    if (player.status) return player.status;
    var paid   = Number(player.amount_paid || 0);
    var target = typeof PLAYER_TARGET !== 'undefined' ? PLAYER_TARGET : 7000;
    if (paid >= target) return 'fully_paid';
    if (paid > 0)       return 'partial';
    return 'unpaid';
  }


  /* ── Summary ──────────────────────────────────────────────────────────── */

  async function loadSummary() {
    try {
      var res  = await fetch(API_BASE + '/get_summary.php');
      var data = await res.json();
      var pct  = Math.min(100, Math.round((data.total_collected / data.target) * 100)) || 0;

      document.getElementById('lb-collected').textContent = fmt(data.total_collected);
      document.getElementById('lb-target').textContent    = fmt(data.target);
      document.getElementById('lb-paid-count').textContent =
        data.fully_paid + ' / ' + (data.fully_paid + data.partial + data.unpaid);
      document.getElementById('lb-pct').textContent           = pct + '%';
      document.getElementById('lb-progress-bar').style.width  = pct + '%';

    } catch (_) {
      var el = document.getElementById('lb-collected');
      if (el) el.textContent = '\u2014';
    }
  }


  /* ── Player list ──────────────────────────────────────────────────────── */

  async function loadPlayers() {
    try {
      var res     = await fetch(API_BASE + '/get_players.php');
      var data    = await res.json();
      var players = Array.isArray(data.players) ? data.players : [];
      renderTable(players);
    } catch (_) {
      var tbody = document.getElementById('lbBody');
      var err   = document.getElementById('lbError');
      if (tbody) tbody.innerHTML = '';
      if (err)   err.hidden = false;
    }
  }


  /* ── Table renderer ───────────────────────────────────────────────────── */

  function renderTable(players) {
    var order  = { fully_paid: 0, partial: 1, unpaid: 2 };
    var sorted = players.slice().sort(function (a, b) {
      var sa = order[statusOf(a)] !== undefined ? order[statusOf(a)] : 2;
      var sb = order[statusOf(b)] !== undefined ? order[statusOf(b)] : 2;
      if (sa !== sb) return sa - sb;
      return Number(b.amount_paid || 0) - Number(a.amount_paid || 0);
    });

    var tbody = document.getElementById('lbBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    sorted.forEach(function (p, i) {
      var status    = statusOf(p);
      var rank      = i + 1;
      var paid      = Number(p.amount_paid || 0);
      var target    = typeof PLAYER_TARGET !== 'undefined' ? PLAYER_TARGET : 7000;
      var remaining = Number(p.remaining_balance !== undefined ? p.remaining_balance : (target - paid));

      var avatarClass = '';
      var badgeClass  = 'badge-paid';
      var badgeLabel  = 'Fully Paid';
      if (status === 'partial') { avatarClass = 'avatar-partial'; badgeClass = 'badge-partial'; badgeLabel = 'Partial'; }
      if (status === 'unpaid')  { avatarClass = 'avatar-unpaid';  badgeClass = 'badge-unpaid';  badgeLabel = 'Unpaid'; }

      var rankClass = rank <= 3 ? 'rank-' + rank : '';

      var tr = document.createElement('tr');
      if (rankClass) tr.className = rankClass;
      tr.innerHTML =
        '<td class="col-rank">' + rank + '</td>' +
        '<td class="col-name"><div class="player-name">' +
          '<div class="player-avatar ' + avatarClass + '">' + initials(p.name) + '</div>' +
          '<span>' + p.name + '</span>' +
        '</div></td>' +
        '<td class="col-paid">' + (paid > 0 ? fmt(paid) : '\u2014') + '</td>' +
        '<td class="col-balance">' +
          (remaining > 0 ? fmt(remaining) : '<span style="color:#4a8fff">Complete</span>') +
        '</td>' +
        '<td class="col-status"><span class="badge ' + badgeClass + '">' + badgeLabel + '</span></td>';
      tbody.appendChild(tr);
    });
  }


  /* ── Init ─────────────────────────────────────────────────────────────── */

  function init() {
    loadSummary();
    loadPlayers();
  }

  return { init: init };

})();
