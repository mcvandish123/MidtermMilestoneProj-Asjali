/**
 * Small page behaviours used everywhere:
 * mobile menu, flash dismiss, delete confirmations, comment edit toggle,
 * character counters, category auto-submit, and toast messages.
 */
(() => {
  'use strict';

  // ---------- Toasts (used by favorites.js too) ----------
  const toastRegion = document.querySelector('.toast-region');

  function toast(message, type = 'info') {
    if (!toastRegion) return;
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.textContent = message; // textContent: never inject HTML
    toastRegion.append(el);
    requestAnimationFrame(() => el.classList.add('is-visible'));
    setTimeout(() => {
      el.classList.remove('is-visible');
      setTimeout(() => el.remove(), 300);
    }, 2600);
  }

  window.Kusina = Object.assign(window.Kusina || {}, { toast });

  // ---------- Mobile navigation ----------
  const navToggle = document.querySelector('.nav-toggle');
  const nav = document.getElementById('site-nav');
  if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
      const open = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) {
        navToggle.setAttribute('aria-expanded', 'false');
        nav.classList.remove('is-open');
        navToggle.focus();
      }
    });
  }

  // ---------- Click delegation ----------
  document.addEventListener('click', (e) => {
    const close = e.target.closest('.flash-close');
    if (close) {
      close.closest('.flash')?.remove();
      return;
    }

    const toggle = e.target.closest('[data-edit-toggle]');
    if (toggle) {
      const form = document.getElementById(toggle.dataset.editToggle);
      if (!form) return;
      const body = form.closest('.comment-main')?.querySelector('.comment-body');
      const actions = form.closest('.comment-main')?.querySelector('.comment-actions');
      const opening = form.hidden;
      form.hidden = !opening;
      if (body) body.hidden = opening;
      if (actions) actions.hidden = opening;
      if (opening) form.querySelector('textarea')?.focus();
    }
  });

  // ---------- Confirm before destructive submits ----------
  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) {
      e.preventDefault();
    }
  });

  // ---------- Character counters ----------
  document.querySelectorAll('textarea[data-counter][maxlength]').forEach((field) => {
    const counter = document.createElement('p');
    counter.className = 'char-counter';
    counter.setAttribute('aria-live', 'polite');
    field.insertAdjacentElement('afterend', counter);

    const update = () => {
      const max = Number(field.getAttribute('maxlength'));
      const used = [...field.value].length;
      counter.textContent = `${used} / ${max}`;
      counter.classList.toggle('is-near', used > max * 0.9);
    };
    field.addEventListener('input', update);
    update();
  });

  // ---------- Category select submits the search form ----------
  document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form?.submit());
  });
})();
