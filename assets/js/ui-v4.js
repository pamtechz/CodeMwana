'use strict';

(() => {
  const body = document.body;
  if (!body || body.dataset.uiV4Ready === 'true') return;
  body.dataset.uiV4Ready = 'true';
  body.classList.add('ui-v4-ready');

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const smoothBehaviour = () => reduceMotion.matches ? 'auto' : 'smooth';

  function iconArrow(direction) {
    return direction === 'left' ? '‹' : direction === 'right' ? '›' : direction === 'top' ? '↑' : '↓';
  }

  function createPageScrollControls() {
    if (body.classList.contains('code-lab-page')) return;

    const progress = document.createElement('div');
    progress.className = 'page-scroll-progress';
    progress.setAttribute('aria-hidden', 'true');
    progress.innerHTML = '<span></span>';

    const dock = document.createElement('div');
    dock.className = 'page-scroll-dock';
    dock.setAttribute('aria-label', 'Page scrolling controls');

    const topButton = document.createElement('button');
    topButton.type = 'button';
    topButton.className = 'page-scroll-button';
    topButton.dataset.scrollPage = 'top';
    topButton.setAttribute('aria-label', 'Scroll to top');
    topButton.setAttribute('title', 'Scroll to top');
    topButton.innerHTML = `<span aria-hidden="true">${iconArrow('top')}</span>`;

    const bottomButton = document.createElement('button');
    bottomButton.type = 'button';
    bottomButton.className = 'page-scroll-button';
    bottomButton.dataset.scrollPage = 'bottom';
    bottomButton.setAttribute('aria-label', 'Scroll to bottom');
    bottomButton.setAttribute('title', 'Scroll to bottom');
    bottomButton.innerHTML = `<span aria-hidden="true">${iconArrow('bottom')}</span>`;

    dock.append(topButton, bottomButton);
    body.append(progress, dock);

    const progressBar = progress.querySelector('span');
    let scheduled = false;

    const update = () => {
      scheduled = false;
      const root = document.documentElement;
      const maxScroll = Math.max(0, root.scrollHeight - window.innerHeight);
      const current = Math.max(0, window.scrollY || root.scrollTop || 0);
      const percent = maxScroll > 0 ? Math.min(100, (current / maxScroll) * 100) : 0;
      if (progressBar) progressBar.style.width = `${percent}%`;
      topButton.classList.toggle('is-visible', current > 420);
      bottomButton.classList.toggle('is-visible', maxScroll > 500 && current < maxScroll - 420);
      body.classList.toggle('is-scrolled', current > 10);
    };

    const requestUpdate = () => {
      if (scheduled) return;
      scheduled = true;
      window.requestAnimationFrame(update);
    };

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate, { passive: true });
    topButton.addEventListener('click', () => window.scrollTo({ top: 0, behavior: smoothBehaviour() }));
    bottomButton.addEventListener('click', () => window.scrollTo({ top: document.documentElement.scrollHeight, behavior: smoothBehaviour() }));
    update();
  }

  function enhanceHorizontalScroller(target) {
    if (!(target instanceof HTMLElement) || target.dataset.scrollEnhanced === 'true') return;
    if (target.closest('.horizontal-scroll-shell')) return;

    const shell = document.createElement('div');
    shell.className = 'horizontal-scroll-shell';
    target.parentNode?.insertBefore(shell, target);
    shell.appendChild(target);
    target.classList.add('horizontal-scroll-target');
    target.dataset.scrollEnhanced = 'true';

    const left = document.createElement('button');
    left.type = 'button';
    left.className = 'scroll-edge-button';
    left.dataset.scrollDirection = 'left';
    left.setAttribute('aria-label', 'Scroll content left');
    left.setAttribute('title', 'Scroll left');
    left.textContent = iconArrow('left');

    const right = document.createElement('button');
    right.type = 'button';
    right.className = 'scroll-edge-button';
    right.dataset.scrollDirection = 'right';
    right.setAttribute('aria-label', 'Scroll content right');
    right.setAttribute('title', 'Scroll right');
    right.textContent = iconArrow('right');

    shell.append(left, right);

    const update = () => {
      const overflow = target.scrollWidth - target.clientWidth;
      const canScroll = overflow > 4;
      const atStart = target.scrollLeft <= 3;
      const atEnd = target.scrollLeft >= overflow - 3;
      left.hidden = !canScroll || atStart;
      right.hidden = !canScroll || atEnd;
      shell.classList.toggle('can-scroll-left', canScroll && !atStart);
      shell.classList.toggle('can-scroll-right', canScroll && !atEnd);
    };

    const scrollAmount = () => Math.max(220, Math.round(target.clientWidth * 0.72));
    left.addEventListener('click', () => target.scrollBy({ left: -scrollAmount(), behavior: smoothBehaviour() }));
    right.addEventListener('click', () => target.scrollBy({ left: scrollAmount(), behavior: smoothBehaviour() }));
    target.addEventListener('scroll', update, { passive: true });

    if ('ResizeObserver' in window) {
      const observer = new ResizeObserver(update);
      observer.observe(target);
      [...target.children].slice(0, 50).forEach((child) => observer.observe(child));
    } else {
      window.addEventListener('resize', update, { passive: true });
    }

    window.requestAnimationFrame(update);
  }

  function enhanceHorizontalScrollers(root = document) {
    const selectors = [
      '.data-table',
      '.responsive-table',
      '.leaderboard-table',
      '.editor-tabs',
      '.table-scroll',
      '[data-horizontal-scroll]'
    ];
    root.querySelectorAll(selectors.join(',')).forEach(enhanceHorizontalScroller);
  }

  function improveButtons(root = document) {
    root.querySelectorAll('button:not([type])').forEach((button) => button.setAttribute('type', 'button'));
    root.querySelectorAll('.icon-button:not([title]), .studio-icon-button:not([title])').forEach((button) => {
      const label = button.getAttribute('aria-label');
      if (label) button.setAttribute('title', label);
    });
  }

  function monitorDynamicUi() {
    if (!('MutationObserver' in window)) return;
    const observer = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        mutation.addedNodes.forEach((node) => {
          if (!(node instanceof HTMLElement)) return;
          if (node.matches?.('.data-table,.responsive-table,.leaderboard-table,.editor-tabs,.table-scroll,[data-horizontal-scroll]')) {
            enhanceHorizontalScroller(node);
          }
          enhanceHorizontalScrollers(node);
          improveButtons(node);
        });
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  function closeMobileNavigationOnOrientationChange() {
    const close = () => {
      document.querySelector('[data-sidebar]')?.classList.remove('is-open');
      document.querySelector('[data-sidebar-overlay]')?.classList.remove('is-open');
      body.classList.remove('nav-open');
    };
    window.addEventListener('orientationchange', close, { passive: true });
  }

  function addKeyboardScrolling() {
    document.addEventListener('keydown', (event) => {
      if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) return;
      const active = document.activeElement;
      if (active instanceof HTMLInputElement || active instanceof HTMLTextAreaElement || active instanceof HTMLSelectElement || active?.isContentEditable) return;
      if (event.key === 'Home' && event.shiftKey) {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: smoothBehaviour() });
      }
      if (event.key === 'End' && event.shiftKey) {
        event.preventDefault();
        window.scrollTo({ top: document.documentElement.scrollHeight, behavior: smoothBehaviour() });
      }
    });
  }

  createPageScrollControls();
  enhanceHorizontalScrollers();
  improveButtons();
  monitorDynamicUi();
  closeMobileNavigationOnOrientationChange();
  addKeyboardScrolling();
})();
