'use strict';

(() => {
  const body = document.body;

  const publicToggle = document.querySelector('[data-nav-toggle]');
  const publicNav = document.querySelector('[data-nav]');
  if (publicToggle && publicNav) {
    publicToggle.addEventListener('click', () => {
      const open = publicNav.classList.toggle('is-open');
      publicToggle.setAttribute('aria-expanded', String(open));
    });
  }

  const sidebar = document.querySelector('[data-sidebar]');
  const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
  const openSidebar = () => { sidebar?.classList.add('is-open'); sidebarOverlay?.classList.add('is-open'); body.classList.add('nav-open'); };
  const closeSidebar = () => { sidebar?.classList.remove('is-open'); sidebarOverlay?.classList.remove('is-open'); body.classList.remove('nav-open'); };
  document.querySelector('[data-sidebar-open]')?.addEventListener('click', openSidebar);
  document.querySelector('[data-sidebar-close]')?.addEventListener('click', closeSidebar);
  sidebarOverlay?.addEventListener('click', closeSidebar);

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.passwordToggle || '');
      if (!input) return;
      const reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      button.textContent = reveal ? 'Hide' : 'Show';
    });
  });

  document.querySelectorAll('[data-password-strength]').forEach((input) => {
    const meter = input.closest('.field')?.querySelector('[data-password-meter] span');
    if (!meter) return;
    input.addEventListener('input', () => {
      const value = input.value;
      let score = 0;
      if (value.length >= 10) score += 25;
      if (/[a-z]/.test(value)) score += 20;
      if (/[A-Z]/.test(value)) score += 20;
      if (/\d/.test(value)) score += 20;
      if (/[^A-Za-z0-9]/.test(value)) score += 15;
      meter.style.width = `${score}%`;
      meter.dataset.level = score < 50 ? 'weak' : score < 80 ? 'fair' : 'strong';
    });
  });

  document.querySelectorAll('[data-progress-form]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('[data-submit-button]');
      if (!button || button.disabled) return;
      button.disabled = true;
      button.classList.add('is-loading');
      const label = button.querySelector('span');
      if (label) label.dataset.original = label.textContent || '';
      if (label) label.textContent = 'Processing';
    });
  });

  document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || 'Continue with this operation?')) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-toast]').forEach((toast) => {
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    window.setTimeout(() => {
      toast.classList.remove('is-visible');
      window.setTimeout(() => toast.remove(), 250);
    }, 5000);
  });

  document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
    const toggle = dropdown.querySelector('[data-dropdown-toggle]');
    toggle?.addEventListener('click', (event) => {
      event.stopPropagation();
      document.querySelectorAll('[data-dropdown].is-open').forEach((item) => { if (item !== dropdown) item.classList.remove('is-open'); });
      dropdown.classList.toggle('is-open');
    });
  });
  document.addEventListener('click', () => document.querySelectorAll('[data-dropdown].is-open').forEach((item) => item.classList.remove('is-open')));

  document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const modal = document.getElementById(button.dataset.modalOpen || '');
      if (modal && typeof modal.showModal === 'function') modal.showModal();
    });
  });
  document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog')?.close());
  });
  document.querySelectorAll('dialog[data-modal]').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) modal.close();
    });
  });

  document.querySelectorAll('[data-filter-input]').forEach((input) => {
    const selector = input.dataset.filterTarget;
    if (!selector) return;
    input.addEventListener('input', () => {
      const term = input.value.trim().toLowerCase();
      document.querySelectorAll(selector).forEach((item) => {
        item.hidden = term !== '' && !item.textContent.toLowerCase().includes(term);
      });
    });
  });

  document.querySelectorAll('[data-filter-chips]').forEach((group) => {
    group.querySelectorAll('[data-filter]').forEach((button) => {
      button.addEventListener('click', () => {
        group.querySelectorAll('[data-filter]').forEach((item) => item.classList.toggle('is-active', item === button));
        const filter = button.dataset.filter;
        document.querySelectorAll('[data-filter-item][data-state]').forEach((item) => {
          item.hidden = filter !== 'all' && item.dataset.state !== filter;
        });
      });
    });
  });

  const quizForm = document.querySelector('[data-quiz-form]');
  if (quizForm) {
    const radios = [...quizForm.querySelectorAll('input[type="radio"]')];
    const progressText = quizForm.querySelector('[data-quiz-progress]');
    const progressBar = quizForm.querySelector('[data-quiz-progress-bar]');
    const total = quizForm.querySelectorAll('fieldset').length;
    const updateQuizProgress = () => {
      const answered = new Set(radios.filter((radio) => radio.checked).map((radio) => radio.name)).size;
      if (progressText) progressText.textContent = `${answered} of ${total} answered`;
      if (progressBar) progressBar.style.width = `${total ? (answered / total) * 100 : 0}%`;
    };
    radios.forEach((radio) => radio.addEventListener('change', updateQuizProgress));
    updateQuizProgress();
  }

  document.querySelectorAll('[data-copy-code]').forEach((button) => {
    button.addEventListener('click', async () => {
      const code = document.getElementById('starter-code');
      if (!code) return;
      try {
        await navigator.clipboard.writeText(code.textContent || '');
        const text = button.textContent;
        button.textContent = 'Copied';
        window.setTimeout(() => { button.textContent = text; }, 1500);
      } catch {
        button.textContent = 'Select code to copy';
      }
    });
  });

  const guideDialog = document.querySelector('[data-guide-dialog]');
  document.querySelectorAll('[data-open-guide]').forEach((button) => button.addEventListener('click', () => guideDialog?.showModal()));

  if ('serviceWorker' in navigator && window.location.protocol.startsWith('http')) {
    window.addEventListener('load', () => {
      const base = document.querySelector('link[rel="manifest"]')?.href;
      if (base) navigator.serviceWorker.register(new URL('service-worker.js', base).href).catch(() => {});
    });
  }
})();
