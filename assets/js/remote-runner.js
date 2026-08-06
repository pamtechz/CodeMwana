'use strict';

(() => {
  const lab = document.querySelector('[data-code-lab]');
  if (!lab) return;

  const supported = new Set(['c', 'cpp', 'go']);
  const stateNode = lab.querySelector('[data-code-lab-state]');
  const initial = JSON.parse(stateNode?.textContent || '{}');
  const languageMap = new Map((initial.languages || []).map((language) => [language.slug, language]));
  const languageSelect = lab.querySelector('[data-language-select]');
  const editor = lab.querySelector('[data-code-editor]');
  const stdin = lab.querySelector('[data-stdin]');
  const output = lab.querySelector('[data-console-output]');
  const outputEmpty = lab.querySelector('[data-output-empty]');
  const runButton = lab.querySelector('[data-run-code]');
  const executionLabel = lab.querySelector('[data-execution-label]');
  const runnerState = lab.querySelector('[data-runner-state]');
  const previewSurface = lab.querySelector('[data-output-view="preview"]');
  const localPreview = lab.querySelector('[data-preview-frame]');
  const previewMessage = lab.querySelector('[data-preview-message]');
  const fallbackShell = lab.querySelector('[data-external-runner]');
  const fallbackFrame = lab.querySelector('[data-external-runner-frame]');
  const fallbackBaseUrl = String(lab.dataset.fallbackUrl || '').replace(/\/$/, '');
  const fallbackOrigin = 'https://onecompiler.com';
  let running = false;
  let fallbackLoadSequence = 0;

  const embedLanguages = {
    c: 'c',
    cpp: 'cpp',
    go: 'go',
  };

  function activeLanguage() {
    return String(languageSelect?.value || '').toLowerCase();
  }

  function languageDefinition() {
    return languageMap.get(activeLanguage()) || null;
  }

  function currentProjectId() {
    const queryId = Number(new URLSearchParams(window.location.search).get('project') || 0);
    return queryId > 0 ? queryId : Number(initial.projectId || 0);
  }

  function workspace() {
    const language = languageDefinition();
    const source = initial.language === activeLanguage()
      ? { ...(initial.files || {}) }
      : { ...(language?.files || {}) };
    const mainFile = language?.mainFile || Object.keys(source)[0] || `main.${activeLanguage()}`;
    source[mainFile] = editor?.value || '';
    return source;
  }

  function selectOutput(name) {
    const tab = lab.querySelector(`[data-output-tab="${name}"]`);
    if (tab && !tab.classList.contains('active')) tab.click();
    if (window.innerWidth <= 900) {
      const mobile = lab.querySelector('[data-mobile-view="output"]');
      if (mobile) mobile.click();
    }
  }

  function clearConsole() {
    selectOutput('console');
    output.textContent = '';
    output.classList.remove('has-error');
    outputEmpty.hidden = true;
  }

  function write(message, error = false) {
    outputEmpty.hidden = true;
    output.textContent += `${String(message)}\n`;
    if (error) output.classList.add('has-error');
  }

  function hideFallback() {
    fallbackLoadSequence += 1;
    previewSurface?.classList.remove('has-external-runner');
    if (fallbackShell) fallbackShell.hidden = true;
    if (fallbackFrame) fallbackFrame.removeAttribute('src');
    if (localPreview) localPreview.hidden = false;
  }

  function fallbackUrl(language) {
    if (!fallbackBaseUrl || !embedLanguages[language]) return '';
    const params = new URLSearchParams({
      hideLanguageSelection: 'true',
      hideNew: 'true',
      hideNewFileOption: 'true',
      hideTitle: 'true',
      hideEditorOptions: 'true',
      listenToEvents: 'true',
      theme: 'dark',
      fontSize: '15',
    });
    return `${fallbackBaseUrl}/${embedLanguages[language]}?${params.toString()}`;
  }

  function sendFallbackCode(sequence, files) {
    if (!fallbackFrame?.contentWindow || sequence !== fallbackLoadSequence) return;
    const language = activeLanguage();
    const filePayload = Object.entries(files).map(([name, content]) => ({
      name: String(name),
      content: String(content),
    }));
    fallbackFrame.contentWindow.postMessage({
      eventType: 'populateCode',
      language: embedLanguages[language],
      files: filePayload,
    }, fallbackOrigin);
    fallbackFrame.contentWindow.postMessage({ eventType: 'triggerRun' }, fallbackOrigin);
  }

  function openFallback(files) {
    const language = activeLanguage();
    const url = fallbackUrl(language);
    if (!url || !fallbackFrame || !fallbackShell) {
      clearConsole();
      write('The execution environment is temporarily unavailable. Try again later.', true);
      return;
    }

    const sequence = ++fallbackLoadSequence;
    previewSurface?.classList.add('has-external-runner');
    fallbackShell.hidden = false;
    if (localPreview) localPreview.hidden = true;
    if (previewMessage) previewMessage.hidden = true;
    selectOutput('preview');

    fallbackFrame.onload = () => {
      sendFallbackCode(sequence, files);
      window.setTimeout(() => sendFallbackCode(sequence, files), 500);
      window.setTimeout(() => sendFallbackCode(sequence, files), 1400);
    };
    fallbackFrame.src = url;
  }

  function renderResult(result, startedAt) {
    hideFallback();
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

  async function runRemoteProgram() {
    const language = activeLanguage();
    if (!supported.has(language) || running) return;

    running = true;
    runButton.disabled = true;
    runButton.classList.add('busy');
    hideFallback();
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
        openFallback(files);
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

  function updateStatus() {
    const language = activeLanguage();
    if (supported.has(language)) {
      if (executionLabel) executionLabel.textContent = 'Managed online execution';
      if (runnerState) {
        runnerState.textContent = 'Online execution ready';
        runnerState.classList.add('ready');
      }
    }
    hideFallback();
  }

  document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target.closest('[data-run-code]') : null;
    if (!target || !supported.has(activeLanguage())) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    runRemoteProgram();
  }, true);

  document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey) || event.key !== 'Enter' || !supported.has(activeLanguage())) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    runRemoteProgram();
  }, true);

  languageSelect?.addEventListener('change', () => window.setTimeout(updateStatus, 0));
  window.setTimeout(updateStatus, 0);
})();
