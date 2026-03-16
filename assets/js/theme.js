/* ─── Theme (dark / light) ──────────────────────────────────────────────────
 * Loaded FIRST in every page <head> so the correct class is applied before
 * the first paint — prevents flash of wrong theme.
 * ─────────────────────────────────────────────────────────────────────────── */

(function () {
  const saved = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const theme = saved || (prefersDark ? 'dark' : 'light');
  document.documentElement.classList.add(theme === 'light' ? 'light' : 'dark');
})();

/* ── Toggle exposed globally so any page can call it ── */
function toggleTheme() {
  const html  = document.documentElement;
  const isLight = html.classList.contains('light');
  html.classList.toggle('dark',  isLight);
  html.classList.toggle('light', !isLight);
  localStorage.setItem('theme', isLight ? 'dark' : 'light');

  const icon = document.getElementById('themeIcon');
  if (icon) icon.textContent = isLight ? '☀' : '☽';
}

/* Wire up button after DOM is ready (works even if this script is in <head>) */
document.addEventListener('DOMContentLoaded', function () {
  const btn  = document.getElementById('themeToggle');
  const icon = document.getElementById('themeIcon');
  if (!btn || !icon) return;

  const isLight = document.documentElement.classList.contains('light');
  icon.textContent = isLight ? '☽' : '☀';

  btn.addEventListener('click', toggleTheme);
});
