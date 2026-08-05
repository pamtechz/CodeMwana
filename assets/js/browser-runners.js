'use strict';

(() => {
  const lab = document.querySelector('[data-code-lab]');
  if (!lab) return;

  const supported = new Set(['python', 'php']);
  const compiled = new Set(['c', 'cpp', 'go']);
  const codapiUrl = 'https://unpkg.com/@antonz/codapi@0.20.0/dist/snippet.js';
  const stateNode = lab.querySelector('[data-code-lab-state]');
  const initial = JSON.parse(stateNode?.textContent || '{}');
  const languageSelect = lab.querySelector('[data-language-select]');
  const editor = lab.querySelector('[data-code-editor]');
  const stdin = lab.querySelector('[data-stdin]');
  const output = lab.querySelector('[data-console-output]');
  const outputEmpty = lab.querySelector('[data-output-empty]');
  const executionLabel = lab.querySelector('[data-execution-label]');
  const runnerState = lab.querySelector('[data-runner-state]');
  const runButton = lab.querySelector('[data-run-code]');
  let codapiPromise = null;
  let running = false;
  let runnerSequence = 0;

  function activeLanguage() {
    return String(languageSelect?.value || '').toLowerCase();
  }

  function selectConsole() {
    const tab = lab.querySelector('[data-output-tab="console"]');
    if (tab && !tab.classList.contains('active')) tab.click();
    if (window.innerWidth <= 900) {
      const mobile = lab.querySelector('[data-mobile-view="output"]');
      if (mobile) mobile.click();
    }
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

  function updateStatus() {
    const language = activeLanguage();
    if (supported.has(language)) {
      executionLabel.textContent = 'Free browser WebAssembly runtime';
      if (runnerState && !runnerState.classList.contains('ready')) {
        runnerState.textContent = 'Python and PHP run in the browser';
        runnerState.classList.add('ready');
      }
      return;
    }
    if (compiled.has(language) && executionLabel && !lab.querySelector('[data-code-lab-state]')?.textContent.includes('"runnerConfigured":true')) {
      executionLabel.textContent = 'External compiler required';
    }
  }

  function loadCodapi() {
    if (window.customElements?.get('codapi-snippet')) return Promise.resolve();
    if (codapiPromise) return codapiPromise;

    codapiPromise = new Promise((resolve, reject) => {
      const existing = document.querySelector(`script[src="${codapiUrl}"]`);
      if (existing) {
        existing.addEventListener('load', resolve, { once: true });
        existing.addEventListener('error', () => reject(new Error('The browser runtime could not be downloaded. Check the internet connection and try again.')), { once: true });
        return;
      }
      const script = document.createElement('script');
      script.src = codapiUrl;
      script.crossOrigin = 'anonymous';
      script.referrerPolicy = 'no-referrer';
      script.addEventListener('load', resolve, { once: true });
      script.addEventListener('error', () => reject(new Error('The browser runtime could not be downloaded. Check the internet connection and try again.')), { once: true });
      document.head.appendChild(script);
    }).then(() => window.customElements.whenDefined('codapi-snippet'));

    return codapiPromise;
  }

  function pythonSource(source, standardInput) {
    if (!standardInput) return source;
    const lines = standardInput.replace(/\r/g, '').split('\n');
    return [
      'import builtins',
      `__codemwana_inputs = iter(${JSON.stringify(lines)})`,
      'def __codemwana_input(prompt=""):',
      '    if prompt:',
      '        print(prompt, end="")',
      '    try:',
      '        return next(__codemwana_inputs)',
      '    except StopIteration:',
      '        return ""',
      'builtins.input = __codemwana_input',
      `exec(compile(${JSON.stringify(source)}, "main.py", "exec"))`,
    ].join('\n');
  }

  function phpSource(source, standardInput) {
    if (!standardInput) return source;
    const lines = standardInput.replace(/\r/g, '').split('\n');
    const body = source
      .replace(/^\s*<\?php\b/i, '')
      .replace(/fgets\s*\(\s*STDIN\s*\)/gi, 'codemwana_input()')
      .replace(/stream_get_contents\s*\(\s*STDIN\s*\)/gi, 'implode("\\n", array_slice($__codemwana_inputs, $__codemwana_index))');
    return `<?php\n$__codemwana_inputs = ${JSON.stringify(lines)};\n$__codemwana_index = 0;\nfunction codemwana_input(): string {\n    global $__codemwana_inputs, $__codemwana_index;\n    $value = $__codemwana_inputs[$__codemwana_index] ?? '';\n    $__codemwana_index++;\n    return $value . PHP_EOL;\n}\n${body}`;
  }

  function prepareSource(language, source, standardInput) {
    return language === 'python' ? pythonSource(source, standardInput) : phpSource(source, standardInput);
  }

  function currentProjectId() {
    const queryId = Number(new URLSearchParams(window.location.search).get('project') || 0);
    return queryId > 0 ? queryId : Number(initial.projectId || 0);
  }

  function logBrowserRun(language, result, elapsed, standardInput) {
    if (!lab.dataset.logRunUrl) return;
    const payload = {
      project_id: currentProjectId(),
      language,
      status: result.ok === false ? 'failed' : 'completed',
      stdout: String(result.stdout || ''),
      stderr: String(result.stderr || ''),
      exit_code: result.ok === false ? 1 : 0,
      execution_time_ms: Math.max(0, Math.round(elapsed)),
      stdin: standardInput,
    };
    fetch(lab.dataset.logRunUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': lab.dataset.csrf },
      body: JSON.stringify(payload),
      keepalive: true,
    }).catch(() => {});
  }

  async function executeWithCodapi(language, source, standardInput) {
    await loadCodapi();

    const id = `codemwana-browser-source-${Date.now()}-${runnerSequence += 1}`;
    const host = document.createElement('div');
    host.hidden = true;
    host.setAttribute('aria-hidden', 'true');

    const pre = document.createElement('pre');
    const code = document.createElement('code');
    code.id = id;
    code.textContent = prepareSource(language, source, standardInput);
    pre.appendChild(code);

    const snippet = document.createElement('codapi-snippet');
    snippet.setAttribute('engine', 'wasi');
    snippet.setAttribute('sandbox', language);
    snippet.setAttribute('command', 'run');
    snippet.setAttribute('selector', `#${id}`);
    snippet.setAttribute('editor', 'off');
    host.append(pre, snippet);
    document.body.appendChild(host);

    if (!snippet.ready) {
      await new Promise((resolve) => snippet.addEventListener('load', resolve, { once: true }));
    }

    return new Promise((resolve, reject) => {
      const cleanup = () => host.remove();
      snippet.addEventListener('result', (event) => {
        cleanup();
        resolve(event.detail || {});
      }, { once: true });
      snippet.addEventListener('error', (event) => {
        cleanup();
        reject(event.detail instanceof Error ? event.detail : new Error('The browser runtime failed to execute the program.'));
      }, { once: true });
      snippet.execute();
    });
  }

  async function runBrowserProgram() {
    const language = activeLanguage();
    if (!supported.has(language) || running) return;

    running = true;
    runButton.disabled = true;
    runButton.classList.add('busy');
    clearConsole();

    const name = language === 'python' ? 'Python' : 'PHP';
    write(`Loading the free ${name} browser runtime…`);
    write(language === 'python'
      ? 'The first run downloads approximately 26 MB. Later runs use the browser cache.'
      : 'The first run downloads approximately 13 MB. Later runs use the browser cache.');

    const started = performance.now();
    try {
      const standardInput = stdin?.value || '';
      const result = await executeWithCodapi(language, editor.value, standardInput);
      output.textContent = '';
      if (result.stdout) write(String(result.stdout).trimEnd());
      if (result.stderr) write(String(result.stderr).trimEnd(), true);
      if (!result.stdout && !result.stderr) write('Program completed without output.');
      const elapsed = Number(result.elapsed ?? result.duration ?? Math.round(performance.now() - started));
      write(`\nProcess finished${result.ok === false ? ' with errors' : ''} · ${Math.max(0, Math.round(elapsed))} ms`);
      if (result.ok === false) output.classList.add('has-error');
      logBrowserRun(language, result, elapsed, standardInput);
    } catch (error) {
      output.textContent = '';
      const message = error?.message || String(error);
      write(message, true);
      logBrowserRun(language, { ok: false, stdout: '', stderr: message }, performance.now() - started, stdin?.value || '');
    } finally {
      running = false;
      runButton.disabled = false;
      runButton.classList.remove('busy');
    }
  }

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-run-code]');
    if (!button || !supported.has(activeLanguage())) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    runBrowserProgram();
  }, true);

  document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey) || event.key !== 'Enter' || !supported.has(activeLanguage())) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    runBrowserProgram();
  }, true);

  languageSelect?.addEventListener('change', () => window.setTimeout(updateStatus, 0));
  window.setTimeout(updateStatus, 0);
})();
