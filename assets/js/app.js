'use strict';

const navToggle = document.querySelector('[data-nav-toggle]');
const nav = document.querySelector('[data-nav]');
if (navToggle && nav) {
  navToggle.addEventListener('click', () => {
    const expanded = navToggle.getAttribute('aria-expanded') === 'true';
    navToggle.setAttribute('aria-expanded', String(!expanded));
    nav.classList.toggle('open', !expanded);
  });
}

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (!input) return;
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    button.textContent = showing ? 'Show password' : 'Hide password';
  });
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!window.confirm(form.dataset.confirm || 'Continue?')) event.preventDefault();
  });
});

document.querySelectorAll('[data-copy-code]').forEach((button) => {
  button.addEventListener('click', async () => {
    const code = document.getElementById('starter-code');
    if (!code) return;
    try {
      await navigator.clipboard.writeText(code.textContent || '');
      const original = button.textContent;
      button.textContent = 'Copied';
      setTimeout(() => { button.textContent = original; }, 1500);
    } catch {
      button.textContent = 'Select and copy';
    }
  });
});

document.querySelectorAll('[data-toast]').forEach((toast) => {
  setTimeout(() => toast.classList.add('show'), 50);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 4500);
});

const guideDialog = document.querySelector('[data-guide-dialog]');
document.querySelectorAll('[data-open-guide]').forEach((button) => {
  button.addEventListener('click', () => {
    if (guideDialog && typeof guideDialog.showModal === 'function') guideDialog.showModal();
  });
});

if ('serviceWorker' in navigator && window.location.protocol.startsWith('http')) {
  window.addEventListener('load', () => {
    const manifest = document.querySelector('link[rel="manifest"]');
    const workerUrl = manifest ? new URL('service-worker.js', manifest.href).href : '/service-worker.js';
    navigator.serviceWorker.register(workerUrl).catch(() => {});
  });
}
