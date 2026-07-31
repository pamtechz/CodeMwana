'use strict';

(() => {
  const forms = document.querySelectorAll('[data-curriculum-form]');

  function slugify(value) {
    return value.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 170);
  }

  function setupSlug(form) {
    const source = form.querySelector('[data-slug-source]');
    const target = form.querySelector('[data-slug-target]');
    if (!source || !target) return;
    let manuallyEdited = target.value.trim() !== '';
    target.addEventListener('input', () => {
      manuallyEdited = target.value.trim() !== '';
      target.value = slugify(target.value);
    });
    source.addEventListener('input', () => {
      if (!manuallyEdited) target.value = slugify(source.value);
    });
  }

  function setupColour(form) {
    const input = form.querySelector('input[type="color"]');
    const output = form.querySelector('[data-colour-value]');
    if (!input || !output) return;
    const update = () => { output.textContent = input.value.toUpperCase(); };
    input.addEventListener('input', update);
    update();
  }

  function setupDocumentEditor(form) {
    const editor = form.querySelector('[data-document-editor]');
    if (!editor) return null;
    const surface = editor.querySelector('[data-editor-surface]');
    const input = editor.querySelector('[data-editor-input]');
    const wordCount = editor.querySelector('[data-word-count]');
    const characterCount = editor.querySelector('[data-character-count]');
    if (!surface || !input) return null;

    const sync = () => {
      input.value = surface.innerHTML.trim();
      const text = (surface.innerText || '').trim();
      const words = text === '' ? 0 : text.split(/\s+/).filter(Boolean).length;
      if (wordCount) wordCount.textContent = `${words} word${words === 1 ? '' : 's'}`;
      if (characterCount) characterCount.textContent = `${text.length} character${text.length === 1 ? '' : 's'}`;
    };

    const focusSurface = () => surface.focus({ preventScroll: true });
    editor.querySelectorAll('[data-editor-command]').forEach((button) => {
      button.addEventListener('mousedown', (event) => event.preventDefault());
      button.addEventListener('click', () => {
        focusSurface();
        document.execCommand(button.dataset.editorCommand || '', false);
        sync();
      });
    });

    editor.querySelector('[data-block-format]')?.addEventListener('change', (event) => {
      focusSurface();
      document.execCommand('formatBlock', false, event.currentTarget.value);
      sync();
    });

    editor.querySelector('[data-editor-action="link"]')?.addEventListener('click', () => {
      const href = window.prompt('Enter the complete link address, beginning with https://');
      if (!href) return;
      if (!/^https?:\/\//i.test(href.trim())) {
        window.alert('Use a complete http:// or https:// address.');
        return;
      }
      focusSurface();
      document.execCommand('createLink', false, href.trim());
      sync();
    });

    editor.querySelector('[data-editor-action="table"]')?.addEventListener('click', () => {
      focusSurface();
      document.execCommand('insertHTML', false, '<table><thead><tr><th>Heading</th><th>Heading</th></tr></thead><tbody><tr><td>Content</td><td>Content</td></tr></tbody></table><p><br></p>');
      sync();
    });

    editor.querySelector('[data-editor-action="fullscreen"]')?.addEventListener('click', (event) => {
      editor.classList.toggle('is-fullscreen');
      document.body.classList.toggle('editor-fullscreen', editor.classList.contains('is-fullscreen'));
      event.currentTarget.textContent = editor.classList.contains('is-fullscreen') ? 'Exit full screen' : 'Full screen';
      focusSurface();
    });

    surface.addEventListener('input', sync);
    surface.addEventListener('blur', sync);
    surface.addEventListener('paste', (event) => {
      const plain = event.clipboardData?.getData('text/plain');
      if (typeof plain !== 'string') return;
      event.preventDefault();
      document.execCommand('insertText', false, plain);
    });

    sync();
    return { surface, input, sync };
  }

  function fieldPayload(form, editorApi) {
    editorApi?.sync();
    const payload = {};
    form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
      if (field.name === '_csrf' || field.type === 'password' || field.type === 'file') return;
      if (field.type === 'checkbox') payload[field.name] = field.checked ? '1' : '0';
      else if (field.type !== 'radio' || field.checked) payload[field.name] = field.value;
    });
    return payload;
  }

  function restorePayload(form, payload, editorApi) {
    Object.entries(payload || {}).forEach(([name, value]) => {
      form.querySelectorAll(`[name="${CSS.escape(name)}"]`).forEach((field) => {
        if (field.type === 'checkbox') field.checked = value === '1' || value === true;
        else if (field.type === 'radio') field.checked = field.value === value;
        else field.value = String(value ?? '');
      });
    });
    if (editorApi && typeof payload.content_html === 'string') {
      editorApi.surface.innerHTML = payload.content_html;
      editorApi.sync();
    }
    form.querySelector('input[type="color"]')?.dispatchEvent(new Event('input'));
  }

  function setupDrafts(form, editorApi) {
    const draftKey = `codemwana:${form.dataset.draftKey || window.location.pathname}`;
    const status = form.querySelector('[data-draft-status]');
    const serverUpdated = Date.parse(form.dataset.serverUpdated || '') || 0;
    let timer = 0;
    let dirty = false;

    const setStatus = (message, className = '') => {
      if (!status) return;
      status.textContent = message;
      status.classList.remove('is-saving', 'is-saved');
      if (className) status.classList.add(className);
    };

    const save = () => {
      if (!dirty || form.dataset.submitting === 'true') return;
      const draft = { savedAt: Date.now(), fields: fieldPayload(form, editorApi) };
      try {
        localStorage.setItem(draftKey, JSON.stringify(draft));
        dirty = false;
        setStatus(`Draft saved at ${new Date(draft.savedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`, 'is-saved');
      } catch (error) {
        setStatus('Draft could not be saved on this device');
      }
    };

    const schedule = () => {
      dirty = true;
      setStatus('Saving draft…', 'is-saving');
      window.clearTimeout(timer);
      timer = window.setTimeout(save, 900);
    };

    form.addEventListener('input', schedule);
    form.addEventListener('change', schedule);
    form.addEventListener('submit', () => {
      editorApi?.sync();
      form.dataset.submitting = 'true';
      window.clearTimeout(timer);
      localStorage.removeItem(draftKey);
    });
    window.addEventListener('pagehide', save);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') save(); });

    try {
      const stored = JSON.parse(localStorage.getItem(draftKey) || 'null');
      if (stored && stored.fields && Number(stored.savedAt) > serverUpdated) {
        restorePayload(form, stored.fields, editorApi);
        setStatus(`Restored draft from ${new Date(stored.savedAt).toLocaleString()}`, 'is-saved');
      } else setStatus('Draft protection ready');
    } catch (error) {
      localStorage.removeItem(draftKey);
      setStatus('Draft protection ready');
    }
  }

  forms.forEach((form) => {
    setupSlug(form);
    setupColour(form);
    const editorApi = setupDocumentEditor(form);
    setupDrafts(form, editorApi);
  });
})();
