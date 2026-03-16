/* admin.js */
'use strict';

/* Loader */
const _loader = (function () {
  const MIN_MS = 1400, HARD_MS = 3500;
  let _minDone = false, _resolved = 0, _tasks = 0, _dismissed = false;
  function _dismiss() {
    if (_dismissed) return;
    _dismissed = true;
    const el = document.getElementById('pageLoader');
    if (!el) return;
    el.classList.add('loader-out');
    setTimeout(function () { el.remove(); }, 520);
  }
  function _tryDismiss() { if (_minDone && _resolved >= _tasks) _dismiss(); }
  setTimeout(function () { _minDone = true; _tryDismiss(); }, MIN_MS);
  setTimeout(_dismiss, HARD_MS);
  return { track: function () { _tasks++; }, done: function () { _resolved++; _tryDismiss(); } };
})();

/* Helpers */
function fmt(n) { return '\u20a6' + Number(n).toLocaleString(); }
function fmtDate(iso) {
  try {
    return new Date(iso).toLocaleDateString('en-NG', {
      year: 'numeric', month: 'short', day: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  } catch (_) { return iso; }
}
function badge(status) {
  // Player statuses (Players section)
  if (status === 'fully_paid') return '<span class="badge badge-paid">Fully Paid</span>';
  if (status === 'partial')    return '<span class="badge badge-partial">Partial</span>';
  if (status === 'unpaid')     return '<span class="badge badge-unpaid">Unpaid</span>';
  // Payment transaction statuses (Payments section)
  if (status === 'success')    return '<span class="badge badge-paid">Success</span>';
  if (status === 'pending')    return '<span class="badge badge-partial">Pending</span>';
  if (status === 'failed')     return '<span class="badge badge-unpaid">Failed</span>';
  return '<span class="badge badge-unpaid">—</span>';
}

/* Live date */
(function () {
  const el = document.getElementById('topbarDate');
  if (!el) return;
  function tick() {
    el.textContent = new Date().toLocaleDateString('en-NG', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
  }
  tick();
  setInterval(tick, 60000);
})();

/* Auth guard */
async function authCheck() {
  try {
    const res = await fetch(API_BASE + '/admin/auth_check.php', { credentials: 'include' });
    if (res.status === 401) {
      window.location.href = 'login.html';
    }
  } catch (_) {
    /* Network error — still allow page to render */
  }
}

/* Section switching */
const sectionTitles = { dashboard: 'Dashboard', players: 'Players', payments: 'Payments' };
function showSection(name) {
  document.querySelectorAll('.admin-section').forEach(function (s) {
    s.hidden = true; s.classList.remove('active');
  });
  document.querySelectorAll('[data-section]').forEach(function (b) {
    b.classList.toggle('active', b.dataset.section === name);
  });
  const sec = document.getElementById('sec-' + name);
  if (sec) { sec.hidden = false; sec.classList.add('active'); }
  const title = document.getElementById('topbarTitle');
  if (title) title.textContent = sectionTitles[name] || name;
}

/* ────────────────── DASHBOARD ────────────────── */
let _barChart = null, _donutChart = null;

function renderBarChart(monthlyData) {
  const ctx = document.getElementById('barChart');
  if (!ctx) return;
  if (_barChart) { _barChart.destroy(); _barChart = null; }
  _barChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: monthlyData.map(function (d) { return d.month; }),
      datasets: [{
        label: 'Collected',
        data: monthlyData.map(function (d) { return d.amount; }),
        backgroundColor: 'rgba(74,143,255,0.55)',
        borderColor: '#4a8fff',
        borderWidth: 1,
        borderRadius: 5,
        borderSkipped: false,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: 'rgba(255,255,255,0.45)', font: { size: 11 } } },
        y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: 'rgba(255,255,255,0.45)', font: { size: 11 }, callback: function (v) { return '\u20a6' + (v/1000).toFixed(0) + 'k'; } }, beginAtZero: true },
      },
    },
  });
}

function renderDonutChart(counts) {
  const ctx = document.getElementById('donutChart');
  if (!ctx) return;
  if (_donutChart) { _donutChart.destroy(); _donutChart = null; }
  const labels = ['Fully Paid', 'Partial', 'Unpaid'];
  const data   = [counts.paid || 0, counts.partial || 0, counts.unpaid || 0];
  const colors = ['#4a8fff', '#c8832a', '#3d4460'];
  _donutChart = new Chart(ctx, {
    type: 'doughnut',
    data: { labels: labels, datasets: [{ data: data, backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }] },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '70%',
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + c.label + ': ' + c.parsed; } } } },
    },
  });
  const legend = document.getElementById('donutLegend');
  if (legend) {
    legend.innerHTML = labels.map(function (l, i) {
      return '<div class="donut-legend-item"><div class="donut-legend-dot" style="background:' + colors[i] + '"></div>' + l + ': ' + data[i] + '</div>';
    }).join('');
  }
}

async function loadDashboard() {
  _loader.track();
  try {
    const [sumRes, pmtRes] = await Promise.all([
      fetch(API_BASE + '/get_summary.php', { credentials: 'include' }),
      fetch(API_BASE + '/admin/get_all_payments.php', { credentials: 'include' }),
    ]);
    const sum = await sumRes.json();
    const pmt = await pmtRes.json();

    /* Stat cards */
    const collected = Number(sum.total_collected || 0);
    const target    = Number(sum.target || 175000);
    const pct       = target > 0 ? Math.min(100, Math.round((collected / target) * 100)) : 0;

    document.getElementById('sc-collected').textContent = fmt(collected);
    document.getElementById('sc-txcount').textContent   = sum.payment_count || '0';
    document.getElementById('sc-players').textContent   = sum.player_count  || '0';
    document.getElementById('sc-fullypaid').textContent = sum.fully_paid    || '0';

    /* Progress */
    document.getElementById('dash-pct').textContent           = pct + '%';
    document.getElementById('dash-bar').style.width           = pct + '%';
    document.getElementById('dash-collected-text').textContent = fmt(collected) + ' collected';
    document.getElementById('dash-target-text').textContent   = 'target ' + fmt(target);

    /* Monthly collections: derive from payments list */
    const payments  = Array.isArray(pmt.payments) ? pmt.payments : [];
    const monthMap  = {};
    payments.forEach(function (p) {
      if (!p.paid_at) return;
      const key = new Date(p.paid_at).toLocaleDateString('en-NG', { year: 'numeric', month: 'short' });
      monthMap[key] = (monthMap[key] || 0) + Number(p.amount || 0);
    });
    const monthlyData = Object.keys(monthMap).slice(-6).map(function (k) { return { month: k, amount: monthMap[k] }; });
    if (monthlyData.length === 0) monthlyData.push({ month: 'No data', amount: 0 });
    renderBarChart(monthlyData);

    /* Donut counts */
    renderDonutChart({ paid: Number(sum.fully_paid || 0), partial: Number(sum.partial || 0), unpaid: Number(sum.unpaid || 0) });

  } catch (_) { /* silent */ }
  finally { _loader.done(); }
}

/* ────────────────── PLAYERS ────────────────── */
let _allPlayers = [];

function renderPlayers(players) {
  const tbody = document.getElementById('playersBody');
  if (!tbody) return;
  if (!players.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-subtle);">No players found.</td></tr>';
    return;
  }
  tbody.innerHTML = players.map(function (p, i) {
    return (
      '<tr>' +
      '<td>' + (i + 1) + '</td>' +
      '<td class="cell-name">' + esc(p.name || '') + '</td>' +
      '<td class="cell-mono">' + esc(p.email || '—') + '</td>' +
      '<td class="cell-amount">' + fmt(p.amount_paid || 0) + '</td>' +
      '<td>' + fmt(p.remaining_balance || 0) + '</td>' +
      '<td>' + badge(p.status || 'unpaid') + '</td>' +
      '<td><button class="btn-icon-sm" data-player-id="' + p.id + '" data-player-name="' + esc(p.name) + '" aria-label="Remove player"><i class="ph ph-trash"></i></button></td>' +
      '</tr>'
    );
  }).join('');

  tbody.querySelectorAll('[data-player-id]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openConfirm(
        'Remove ' + btn.dataset.playerName + '?',
        'This will permanently remove this player from the roster.',
        async function () {
          await removePlayer(btn.dataset.playerId);
        }
      );
    });
  });
}

async function loadPlayers() {
  _loader.track();
  try {
    const res  = await fetch(API_BASE + '/get_players.php', { credentials: 'include' });
    const data = await res.json();
    _allPlayers = Array.isArray(data.players) ? data.players : (Array.isArray(data) ? data : []);
    renderPlayers(_allPlayers);
  } catch (_) { /* silent */ }
  finally { _loader.done(); }
}

async function addPlayer() {
  const name  = document.getElementById('newPlayerName').value.trim();
  const email = document.getElementById('newPlayerEmail').value.trim();
  const msgEl = document.getElementById('addPlayerMsg');
  if (!name) { showAdmMsg(msgEl, 'Player name is required.', 'error'); return; }
  const btn = document.getElementById('submitAddPlayer');
  btn.disabled = true;
  try {
    const res  = await fetch(API_BASE + '/admin/add_player.php', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: name, email: email }),
    });
    const data = await res.json();
    if (data.status === 'success') {
      showAdmMsg(msgEl, 'Player added successfully.', 'success');
      document.getElementById('newPlayerName').value = '';
      document.getElementById('newPlayerEmail').value = '';
      loadPlayers();
    } else {
      showAdmMsg(msgEl, data.message || 'Failed to add player.', 'error');
    }
  } catch (_) {
    showAdmMsg(msgEl, 'Network error.', 'error');
  } finally {
    btn.disabled = false;
  }
}

async function removePlayer(id) {
  try {
    const res  = await fetch(API_BASE + '/admin/remove_player.php', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ player_id: id }),
    });
    const data = await res.json();
    if (data.status === 'success') loadPlayers();
  } catch (_) { /* silent */ }
}

/* ────────────────── PAYMENTS ────────────────── */
let _allPayments = [], _payFilter = 'all', _paySearch = '';

function renderPayments() {
  const tbody = document.getElementById('paymentsBody');
  if (!tbody) return;
  let rows = _allPayments;
  if (_payFilter !== 'all') rows = rows.filter(function (p) { return p.status === _payFilter || p.payment_status === _payFilter; });
  if (_paySearch) {
    const q = _paySearch.toLowerCase();
    rows = rows.filter(function (p) { return (p.player_name || '').toLowerCase().includes(q) || (p.email || '').toLowerCase().includes(q); });
  }
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-subtle);">No payments found.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map(function (p) {
    const st = p.status || p.payment_status || 'unpaid';
    return (
      '<tr>' +
      '<td class="cell-name">' + esc(p.player_name || '—') + '</td>' +
      '<td class="cell-mono">' + esc(p.email || '—') + '</td>' +
      '<td class="cell-amount">' + fmt(p.amount || 0) + '</td>' +
      '<td class="cell-mono">' + esc(p.reference || '—') + '</td>' +
      '<td>' + (p.paid_at ? fmtDate(p.paid_at) : '—') + '</td>' +
      '<td>' + badge(st) + '</td>' +
      '</tr>'
    );
  }).join('');
}

async function loadPayments() {
  _loader.track();
  try {
    const res  = await fetch(API_BASE + '/admin/get_all_payments.php', { credentials: 'include' });
    const data = await res.json();
    _allPayments = Array.isArray(data.payments) ? data.payments : (Array.isArray(data) ? data : []);
    renderPayments();
  } catch (_) { /* silent */ }
  finally { _loader.done(); }
}

async function sendReminders() {
  const btn = document.getElementById('sendRemindersBtn');
  if (btn) btn.disabled = true;
  try {
    const res  = await fetch(API_BASE + '/admin/send_reminders.php', {
      method: 'POST', credentials: 'include',
    });
    const data = await res.json();
    alert(data.message || 'Reminders sent.');
  } catch (_) {
    alert('Network error sending reminders.');
  } finally {
    if (btn) btn.disabled = false;
  }
}

/* ────────────────── MODAL ────────────────── */
let _confirmCallback = null;

function openConfirm(title, body, onConfirm) {
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalBody').textContent  = body;
  _confirmCallback = onConfirm;
  document.getElementById('confirmModal').hidden = false;
}
function closeConfirm() {
  document.getElementById('confirmModal').hidden = true;
  _confirmCallback = null;
}

/* ────────────────── UTIL ────────────────── */
function esc(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function showAdmMsg(el, msg, type) {
  if (!el) return;
  el.textContent = msg;
  el.className   = 'adm-msg is-' + type;
  el.hidden      = false;
  setTimeout(function () { el.hidden = true; }, 4000);
}

/* ────────────────── INIT ────────────────── */
document.addEventListener('DOMContentLoaded', async function () {

  await authCheck();

  /* Sidebar + dock nav */
  document.querySelectorAll('[data-section]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const sec = btn.dataset.section;
      showSection(sec);
      if (sec === 'dashboard') loadDashboard();
      if (sec === 'players')   loadPlayers();
      if (sec === 'payments')  loadPayments();
    });
  });

  /* Logout (sidebar) */
  document.getElementById('logoutBtn').addEventListener('click', async function () {
    try { await fetch(API_BASE + '/admin/logout.php', { method: 'POST', credentials: 'include' }); } catch (_) {}
    window.location.href = 'login.html';
  });

  /* Logout (mobile dock) */
  var dockLogout = document.getElementById('dockLogoutBtn');
  if (dockLogout) {
    dockLogout.addEventListener('click', async function () {
      try { await fetch(API_BASE + '/admin/logout.php', { method: 'POST', credentials: 'include' }); } catch (_) {}
      window.location.href = 'login.html';
    });
  }

  /* Refresh */
  document.getElementById('refreshBtn').addEventListener('click', function () {
    const active = document.querySelector('[data-section].active');
    const sec = active ? active.dataset.section : 'dashboard';
    if (sec === 'dashboard') loadDashboard();
    if (sec === 'players')   loadPlayers();
    if (sec === 'payments')  loadPayments();
  });

  /* Add player toggle */
  document.getElementById('toggleAddPlayer').addEventListener('click', function () {
    const form = document.getElementById('addPlayerForm');
    form.hidden = !form.hidden;
  });
  document.getElementById('cancelAddPlayer').addEventListener('click', function () {
    document.getElementById('addPlayerForm').hidden = true;
  });
  document.getElementById('submitAddPlayer').addEventListener('click', addPlayer);

  /* Payments tabs */
  document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      _payFilter = btn.dataset.filter;
      renderPayments();
    });
  });

  /* Search */
  document.getElementById('paymentsSearch').addEventListener('input', function () {
    _paySearch = this.value.trim();
    renderPayments();
  });

  /* Send reminders */
  document.getElementById('sendRemindersBtn').addEventListener('click', function () {
    openConfirm(
      'Send Payment Reminders',
      'This will send reminder emails to all players with an outstanding balance. Continue?',
      sendReminders
    );
  });

  /* Modal */
  document.getElementById('modalConfirmBtn').addEventListener('click', function () {
    closeConfirm();
    if (_confirmCallback) _confirmCallback();
  });
  document.getElementById('modalCancelBtn').addEventListener('click', closeConfirm);
  document.getElementById('confirmModal').addEventListener('click', function (e) {
    if (e.target === this) closeConfirm();
  });

  /* Load default section */
  loadDashboard();
});
