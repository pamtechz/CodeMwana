'use strict';

(() => {
  const lab = document.querySelector('[data-code-lab]');
  if (!lab) return;

  const supported = new Set(['python', 'php', 'c', 'cpp', 'go']);
  const stateNode = lab.querySelector('[data-code-lab-state]');
  const initial = JSON.parse(stateNode?.textContent || '{}');
  const languageMap = new Map((initial.languages || []).map((language) => [language.slug, language]));
  const languageSelect = lab.querySelector('[data-language-select]');
  const editor = lab.querySelector('[data-code-editor]');
  const stdin = lab.querySelector('[data-stdin]');
  const output = lab.querySelector('[data-console-output]');
  const outputEmpty = lab.querySelector('[data-output-empty]');
  const runButton = lab.querySelector('[data-run-code]');
  const saveButton = lab.querySelector('[data-save-project]');
  const executionLabel = lab.querySelector('[data-execution-label]');
  const runnerState = lab.querySelector('[data-runner-state]');
  const mobileGrid = lab.querySelector('.studio-grid');
  const editorPanel = lab.querySelector('.studio-editor');
  const managedShell = lab.querySelector('[data-external-runner]');
  const managedFrame = lab.querySelector('[data-external-runner-frame]');
  const fallbackBaseUrl = String(lab.dataset.fallbackUrl || '').replace(/\/$/, '');
  const managedOrigin = 'https://onecompiler.com';

  let projectId = Number(initial.projectId || 0);
  let managedLanguage = '';
  let managedFiles = {};
  let frameSequence = 0;
  let running = false;
  let syncing = false;

  const embedLanguages = {
    python: 'python',
    php: 'php',
    c: 'c',
    cpp: 'cpp',
    go: 'go',
  };

  if (editorPanel && managedShell && managedShell.parentElement !== editorPanel) {
    editorPanel.appendChild(managedShell);
  }

  function activeLanguage() {
    return String(languageSelect?.value || '').toLowerCase();
  }

  function languageDefinition(slug = activeLanguage()) {
    return languageMap.get(slug) || null;
  }

  function normaliseFiles(source) {
    const result = {};
    Object.entries(source || {}).slice(0, 12).forEach(([name, content]) => {
      if (typeof name !== 'string' || typeof content !== 'string') return;
      const clean = name.trim().replace(/\\/g, '/').replace(/^\/+/, '');
      if (!clean || clean.includes('..')) return;
      result[clean] = content;
    });
    return result;
  }

  function starterFiles(slug) {
    const language = languageDefinition(slug);
    if (slug === initial.language) return normaliseFiles(initial.files || {});
    return normaliseFiles(language?.files || {});
  }

  function currentProjectId() {
    const queryId = Number(new URLSearchParams(window.location.search).get('project') || 0);
    return queryId > 0 ? queryId : projectId;
  }

  function mainFileName(files = managedFiles) {
    const language = languageDefinition(managedLanguage || activeLanguage());
    const preferred = String(language?.mainFile || '');
    if (preferred && Object.prototype.hasOwnProperty.call(files, preferred)) return preferred;
    return Object.keys(files)[0] || preferred || `main.${activeLanguage()}`;
  }

  function embedUrl(language) {
    if (!fallbackBaseUrl || !embedLanguages[language]) return '';
    const params = new URLSearchParams({
      hideLanguageSelection: 'true',
      hideNew: 'true',
      hideRun: 'true',
      hideNewFileOption: 'true',
      hideTitle: 'true',
      hideEditorOptions: 'true',
      listenToEvents: 'true',
      codeChangeEvent: 'true',
      theme: 'dark',
      fontSize: '15',
    });
    return `${fallbackBaseUrl}/${embedLanguages[language]}?${params.toString()}`;
  }

  function setMobileView(view) {
    if (!mobileGrid || window.innerWidth > 900) return;
    mobileGrid.dataset.mobileActive = view;
    lab.querySelectorAll('[data-mobile-view]').forEach((button) => {
      button.classList.toggle('active', button.dataset.mobileView === view);
    });
  }

  function populateManagedEditor(files = managedFiles, triggerRun = false) {
    if (!managedFrame?.contentWindow || !supported.has(activeLanguage())) return;
    const language = activeLanguage();
    const payload = Object.entries(files).map(([name, content]) => ({
      name: String(name),
      content: String(content),
    }));

    managedFrame.contentWindow.postMessage({
      eventType: 'populateCode',
      language: embedLanguages[language],
      files: payload,
    }, managedOrigin);

    if (triggerRun) {
      window.setTimeout(() => {
        managedFrame.contentWindow?.postMessage({ eventType: 'triggerRun' }, managedOrigin);
      }, 180);
    }
  }

  function schedulePopulate(sequence, triggerRun = false) {
    [0, 350, 950].forEach((delay, index) => {
      window.setTimeout(() => {
        if (sequence !== frameSequence || activeLanguage() !== managedLanguage) return;
        populateManagedEditor(managedFiles, triggerRun && index === 2);
      }, delay);
    });
  }

  function deactivateManagedEditor() {
    delete lab.dataset.managedEditor;
    if (managedShell) managedShell.hidden = true;
  }

  function activateManagedEditor() {
    const language = activeLanguage();
    if (!supported.has(language)) {
      deactivateManagedEditor();
      return;
    }

    lab.dataset.managedEditor = 'true';
    setMobileView('editor');
    if (executionLabel) executionLabel.textContent = 'Managed online workspace';
    if (runnerState) {
      runnerState.textContent = 'Online execution ready';
      runnerState.classList.add('ready');
    }

    if (!managedShell || !managedFrame) return;
    managedShell.hidden = false;

    if (managedLanguage !== language) {
      managedLanguage = language;
      managedFiles = starterFiles(language);
      const main = mainFileName(managedFiles);
      if (editor?.value && main) managedFiles[main] = editor.value;
    }

    const url = embedUrl(language);
    if (!url) {
      deactivateManagedEditor();
      return;
    }

    const currentUrl = managedFrame.getAttribute('src') || '';
    if (!currentUrl.includes(`/embed/${embedLanguages[language]}`)) {
      const sequence = ++frameSequence;
      managedFrame.onload = () => schedulePopulate(sequence, false);
      managedFrame.src = url;
    } else {
      schedulePopulate(frameSequence, false);
    }
  }

  function extractFiles(data) {
    const candidate = data?.files ?? data?.code?.files ?? data?.code;
    if (Array.isArray(candidate)) {
      const result = {};
      candidate.forEach((file) => {
        const name = String(file?.name || '').trim();
        const content = file?.content;
        if (name && typeof content === 'string') result[name] = content;
      });
      return normaliseFiles(result);
    }
    if (candidate && typeof candidate === 'object') {
      return normaliseFiles(candidate);
    }
    if (typeof candidate === 'string' || typeof data?.content === 'string') {
      return {
        [mainFileName()]: String(typeof candidate === 'string' ? candidate : data.content),
      };
    }
    return {};
  }

  function openLocalFile(name) {
    const button = Array.from(lab.querySelectorAll('[data-open-file]'))
      .find((item) => item.dataset.openFile === name);
    if (button) button.click();
  }

  function syncManagedToCodeMwana() {
    if (syncing || !supported.has(activeLanguage()) || !editor) return;
    const files = normaliseFiles(managedFiles);
    const names = Object.keys(files);
    if (!names.length) return;

    syncing = true;
    try {
      names.forEach((name) => {
        openLocalFile(name);
        editor.value = files[name];
        editor.dispatchEvent(new Event('input', { bubbles: true }));
      });

      const main = mainFileName(files);
      openLocalFile(main);
      editor.value = files[main] || '';
      editor.dispatchEvent(new Event('input', { bubbles: true }));
    } finally {
      syncing = false;
    }
  }

  function workspace() {
    if (!Object.keys(managedFiles).length) managedFiles = starterFiles(activeLanguage());
    return normaliseFiles(managedFiles);
  }

  function selectConsole() {
    const tab = lab.querySelector('[data-output-tab="console"]');
    if (tab && !tab.classList.contains('active')) tab.click();
    setMobileView('output');
  }

  function clearConsole() {
    selectConsole();
    output.textContent = '';
    output.classList.remove('has-error');
    outputEmpty.hidden = true;
  }

  function write(message, error = false) {
    outputEmpty.hidden = true;
    output.textContent += `${String(message)}\n`;
    if (error) output.classList.add('has-error');
  }

  function renderResult(result, startedAt) {
    clearConsole();
    if (result?.stdout) write(String(result.stdout).trimEnd());
    if (result?.stderr) write(String(result.stderr).trimEnd(), true);
    if (!result?.stdout && !result?.stderr) write('Program completed without output.');
    const elapsed = result?.execution_time_ms ?? Math.round(performance.now() - startedAt);
    const exitText = result?.exit_code !== null && result?.exit_code !== undefined
      ? ` with exit code ${result.exit_code}`
      : '';
    write(`\nProcess finished${exitText} · ${elapsed} ms`);
  }

  function runInManagedWorkspace(files) {
    setMobileView('editor');
    populateManagedEditor(files, false);
    window.setTimeout(() => populateManagedEditor(files, true), 360);
  }

  async function runManagedProgram() {
    const language = activeLanguage();
    if (!supported.has(language) || running) return;

    running = true;
    runButton.disabled = true;
    runButton.classList.add('busy');
    syncManagedToCodeMwana();
    clearConsole();
    write(`Running ${languageDefinition()?.name || language}…`);
    const files = workspace();
    const startedAt = performance.now();

    try {
      const response = await fetch(lab.dataset.runUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': lab.dataset.csrf,
        },
        body: JSON.stringify({
          project_id: currentProjectId(),
          language,
          files,
          stdin: stdin?.value || '',
        }),
      });
      const payload = await response.json().catch(() => ({
        ok: false,
        message: 'The execution service returned an unreadable response.',
      }));

      if (payload.fallback) {
        output.textContent = '';
        outputEmpty.hidden = false;
        runInManagedWorkspace(files);
        return;
      }
      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'The program could not be executed.');
      }
      renderResult(payload.result || {}, startedAt);
    } catch (error) {
      clearConsole();
      write(error?.message || String(error), true);
    } finally {
      running = false;
      runButton.disabled = false;
      runButton.classList.remove('busy');
    }
  }

  window.addEventListener('message', (event) => {
    if (event.origin !== managedOrigin || event.source !== managedFrame?.contentWindow) return;
    const files = extractFiles(event.data || {});
    if (!Object.keys(files).length) return;
    managedFiles = files;
    syncManagedToCodeMwana();
  });

  document.addEventListener('click', (event) => {
    const runTarget = event.target instanceof Element ? event.target.closest('[data-run-code]') : null;
    if (runTarget && supported.has(activeLanguage())) {
      event.preventDefault();
      event.stopImmediatePropagation();
      runManagedProgram();
      return;
    }

    const saveTarget = event.target instanceof Element ? event.target.closest('[data-save-project]') : null;
    if (saveTarget && supported.has(activeLanguage())) {
      syncManagedToCodeMwana();
    }
  }, true);

  document.addEventListener('keydown', (event) => {
    if (!supported.has(activeLanguage()) || !(event.ctrlKey || event.metaKey)) return;
    if (event.key === 'Enter') {
      event.preventDefault();
      event.stopImmediatePropagation();
      runManagedProgram();
    }
    if (event.key.toLowerCase() === 's') {
      syncManagedToCodeMwana();
    }
  }, true);

  languageSelect?.addEventListener('change', () => {
    window.setTimeout(activateManagedEditor, 0);
  });

  if (saveButton) {
    saveButton.addEventListener('focus', () => {
      if (supported.has(activeLanguage())) syncManagedToCodeMwana();
    });
  }

  window.setTimeout(activateManagedEditor, 0);
})();
