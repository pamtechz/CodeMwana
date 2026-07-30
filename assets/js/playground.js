'use strict';

(() => {
  const lab = document.querySelector('[data-code-lab]');
  if (!lab) return;

  const stateNode = lab.querySelector('[data-code-lab-state]');
  const initial = JSON.parse(stateNode.textContent || '{}');
  const languageMap = new Map((initial.languages || []).map((language) => [language.slug, language]));
  const editor = lab.querySelector('[data-code-editor]');
  const lineNumbers = lab.querySelector('[data-line-numbers]');
  const titleInput = lab.querySelector('[data-project-title]');
  const languageSelect = lab.querySelector('[data-language-select]');
  const fileTree = lab.querySelector('[data-file-tree]');
  const editorTabs = lab.querySelector('[data-editor-tabs]');
  const output = lab.querySelector('[data-console-output]');
  const outputEmpty = lab.querySelector('[data-output-empty]');
  const previewFrame = lab.querySelector('[data-preview-frame]');
  const previewMessage = lab.querySelector('[data-preview-message]');
  const canvas = lab.querySelector('[data-drawing-canvas]');
  const context = canvas.getContext('2d');
  const stdinInput = lab.querySelector('[data-stdin]');
  const saveStatus = lab.querySelector('[data-save-status]');
  const cursorPosition = lab.querySelector('[data-cursor-position]');
  const workspaceSize = lab.querySelector('[data-workspace-size]');
  const snippetList = lab.querySelector('[data-snippet-list]');
  const languageSummary = lab.querySelector('[data-language-summary]');
  const languageName = lab.querySelector('[data-language-name]');
  const languageBadge = lab.querySelector('[data-language-badge]');
  const executionLabel = lab.querySelector('[data-execution-label]');
  const activeFileLabel = lab.querySelector('[data-active-file]');
  const mobileGrid = lab.querySelector('.studio-grid');
  const previewToken = `codemwana-${Math.random().toString(36).slice(2)}`;

  let projectId = Number(initial.projectId || 0);
  let activeLanguage = initial.language || 'mwanacode';
  let files = normaliseFiles(initial.files || {});
  let activeFile = Object.keys(files)[0];
  let baseline = snapshot();
  let dirty = false;
  let fontSize = 15;
  let activeOutput = 'console';
  let runStartedAt = 0;

  const snippets = {
    mwanacode: [
      ['Display text', 'SAY "Hello, coder!"'], ['Variable', 'SET score = 10'],
      ['Decision', 'IF score >= 5\n  SAY "Great work"\nELSE\n  SAY "Try again"\nEND'],
      ['Loop', 'REPEAT 4\n  SAY "Again"\nEND'], ['Draw square', 'REPEAT 4\n  MOVE 100\n  TURN 90\nEND'],
    ],
    html: [['Page section', '<section>\n  <h2>Section title</h2>\n  <p>Section content</p>\n</section>'], ['Accessible button', '<button type="button" aria-label="Open activity">Open activity</button>']],
    css: [['Responsive grid', '.grid {\n  display: grid;\n  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));\n  gap: 1rem;\n}'], ['Mobile query', '@media (max-width: 640px) {\n  .component {\n    padding: 1rem;\n  }\n}']],
    javascript: [['Function', 'function greet(name) {\n  return `Hello, ${name}!`;\n}\n\nconsole.log(greet("Mwamba"));'], ['Array map', 'const doubled = [1, 2, 3].map((number) => number * 2);\nconsole.log(doubled);']],
    python: [['Function', 'def greet(name: str) -> str:\n    return f"Hello, {name}!"\n\nprint(greet("Chanda"))'], ['Loop', 'for number in range(1, 6):\n    print(number)']],
    php: [['Function', 'function greet(string $name): string\n{\n    return "Hello, {$name}!";\n}\n\necho greet("Chanda");'], ['Loop', 'foreach ([1, 2, 3] as $number) {\n    echo $number . "\\n";\n}']],
    react: [['Component', 'function Welcome({ name }) {\n  return <h2>Hello, {name}!</h2>;\n}'], ['State', 'const [value, setValue] = React.useState(0);']],
    nextjs: [['Page component', 'export default function Page() {\n  return <main><h1>My page</h1></main>;\n}'], ['Link', '<Link href="#lesson">Open lesson</Link>']],
    go: [['Function', 'func greet(name string) string {\n\treturn "Hello, " + name\n}'], ['Loop', 'for index, value := range values {\n\tfmt.Println(index, value)\n}']],
    c: [['Function', 'int add(int first, int second) {\n    return first + second;\n}'], ['Loop', 'for (int i = 0; i < 5; i++) {\n    printf("%d\\n", i);\n}']],
    cpp: [['Function', 'int add(int first, int second) {\n    return first + second;\n}'], ['Range loop', 'for (const auto& value : values) {\n    std::cout << value << "\\n";\n}']],
  };

  function currentLanguage() {
    return languageMap.get(activeLanguage) || languageMap.get('mwanacode');
  }

  function normaliseFiles(source) {
    const result = {};
    Object.entries(source || {}).slice(0, 12).forEach(([name, content]) => {
      if (typeof name === 'string' && typeof content === 'string') result[name] = content;
    });
    return Object.keys(result).length ? result : { 'main.txt': '' };
  }

  function snapshot() {
    return JSON.stringify({ title: titleInput ? titleInput.value : initial.title, language: activeLanguage, files, stdin: stdinInput ? stdinInput.value : initial.stdin || '' });
  }

  function setDirty(value = true) {
    dirty = value;
    saveStatus.textContent = value ? 'Unsaved changes' : 'Saved';
    saveStatus.classList.toggle('unsaved', value);
  }

  function refreshDirtyState() {
    setDirty(snapshot() !== baseline);
  }

  function updateLineNumbers() {
    const count = Math.max(1, editor.value.split('\n').length);
    lineNumbers.textContent = Array.from({ length: count }, (_, index) => index + 1).join('\n');
    lineNumbers.scrollTop = editor.scrollTop;
  }

  function updateCursor() {
    const before = editor.value.slice(0, editor.selectionStart).split('\n');
    cursorPosition.textContent = `Ln ${before.length}, Col ${before[before.length - 1].length + 1}`;
  }

  function updateWorkspaceSize() {
    const characters = Object.values(files).reduce((total, content) => total + content.length, 0);
    workspaceSize.textContent = `${characters.toLocaleString()} characters · ${Object.keys(files).length} ${Object.keys(files).length === 1 ? 'file' : 'files'}`;
  }

  function syncEditorToFile() {
    if (activeFile) files[activeFile] = editor.value;
    updateWorkspaceSize();
  }

  function openFile(name) {
    syncEditorToFile();
    if (!Object.prototype.hasOwnProperty.call(files, name)) return;
    activeFile = name;
    editor.value = files[name];
    activeFileLabel.textContent = name;
    renderFiles();
    updateLineNumbers();
    updateCursor();
    editor.focus();
  }

  function fileIcon(name) {
    const extension = name.split('.').pop().toLowerCase();
    return ['html', 'css', 'js', 'jsx', 'py', 'php', 'go', 'c', 'cpp', 'mwana'].includes(extension) ? extension.toUpperCase() : 'FILE';
  }

  function renderFiles() {
    const names = Object.keys(files);
    fileTree.innerHTML = names.map((name) => `
      <div class="file-row ${name === activeFile ? 'active' : ''}">
        <button type="button" data-open-file="${escapeHtml(name)}"><span>${escapeHtml(fileIcon(name))}</span><b>${escapeHtml(name)}</b></button>
        ${names.length > 1 ? `<button type="button" class="file-delete" data-delete-file="${escapeHtml(name)}" aria-label="Delete ${escapeHtml(name)}">×</button>` : ''}
      </div>`).join('');
    editorTabs.innerHTML = names.map((name) => `<button type="button" class="${name === activeFile ? 'active' : ''}" data-open-file="${escapeHtml(name)}"><span>${escapeHtml(fileIcon(name))}</span>${escapeHtml(name)}</button>`).join('');
  }

  function renderSnippets() {
    const entries = snippets[activeLanguage] || [];
    snippetList.innerHTML = entries.map(([label, code]) => `<button type="button" data-snippet="${escapeAttribute(code)}"><strong>${escapeHtml(label)}</strong><code>${escapeHtml(code.split('\n')[0])}</code></button>`).join('');
  }

  function renderLanguage() {
    const language = currentLanguage();
    languageSummary.textContent = language.description;
    languageName.textContent = language.name;
    languageBadge.textContent = language.shortName;
    languageBadge.style.setProperty('--language', language.colour);
    executionLabel.textContent = executionText(language);
    lab.dataset.language = language.slug;
    renderSnippets();
  }

  function executionText(language) {
    const labels = {
      guided: 'Guided browser interpreter', browser: 'Live browser preview',
      'browser-console': 'Isolated browser console', 'react-preview': 'React browser preview',
      'next-preview': 'Next.js component preview', remote: initial.runnerConfigured ? 'Remote sandbox ready' : 'Remote sandbox required',
    };
    return labels[language.executionMode] || language.executionMode;
  }

  function switchLanguage(slug) {
    if (slug === activeLanguage) return;
    if (dirty && !window.confirm('Change language and replace the current unsaved workspace with the selected starter project?')) {
      languageSelect.value = activeLanguage;
      return;
    }
    const language = languageMap.get(slug);
    if (!language) return;
    activeLanguage = slug;
    files = normaliseFiles(JSON.parse(JSON.stringify(language.files || {})));
    activeFile = language.mainFile && Object.prototype.hasOwnProperty.call(files, language.mainFile) ? language.mainFile : Object.keys(files)[0];
    titleInput.value = `Untitled ${language.name} project`;
    stdinInput.value = '';
    projectId = 0;
    baseline = '';
    editor.value = files[activeFile];
    renderFiles(); renderLanguage(); updateLineNumbers(); updateCursor(); updateWorkspaceSize();
    clearOutput();
    setDirty(true);
  }

  function addFile() {
    const name = window.prompt('Enter a file name, including its extension.');
    if (!name) return;
    const clean = name.trim().replace(/\\/g, '/').replace(/^\/+/, '');
    if (!/^[A-Za-z0-9._\-/]{1,120}$/.test(clean) || clean.includes('..')) {
      window.alert('Use letters, numbers, dots, dashes, underscores and folders only.');
      return;
    }
    if (Object.prototype.hasOwnProperty.call(files, clean)) { openFile(clean); return; }
    if (Object.keys(files).length >= 12) { window.alert('A project can contain up to 12 files.'); return; }
    syncEditorToFile();
    files[clean] = '';
    activeFile = clean;
    editor.value = '';
    renderFiles(); updateLineNumbers(); updateWorkspaceSize(); refreshDirtyState();
  }

  function deleteFile(name) {
    if (!Object.prototype.hasOwnProperty.call(files, name) || Object.keys(files).length <= 1) return;
    if (!window.confirm(`Delete ${name} from this project?`)) return;
    delete files[name];
    if (activeFile === name) activeFile = Object.keys(files)[0];
    editor.value = files[activeFile];
    renderFiles(); updateLineNumbers(); updateWorkspaceSize(); refreshDirtyState();
  }

  function insertSnippet(code) {
    const start = editor.selectionStart;
    const prefix = start > 0 && editor.value[start - 1] !== '\n' ? '\n' : '';
    editor.setRangeText(prefix + code, start, editor.selectionEnd, 'end');
    syncEditorToFile(); updateLineNumbers(); refreshDirtyState(); editor.focus();
  }

  function switchOutput(name) {
    activeOutput = name;
    lab.querySelectorAll('[data-output-tab]').forEach((button) => {
      const selected = button.dataset.outputTab === name;
      button.classList.toggle('active', selected);
      button.setAttribute('aria-selected', String(selected));
    });
    lab.querySelectorAll('[data-output-view]').forEach((view) => view.classList.toggle('active', view.dataset.outputView === name));
    if (window.innerWidth <= 900) setMobileView('output');
  }

  function clearOutput() {
    output.textContent = '';
    output.classList.remove('has-error');
    outputEmpty.hidden = false;
    previewFrame.removeAttribute('srcdoc');
    previewMessage.hidden = false;
  }

  function writeLine(message, type = 'normal') {
    outputEmpty.hidden = true;
    output.textContent += `${message}\n`;
    if (type === 'error') output.classList.add('has-error');
  }

  function showRunStart(language) {
    clearOutput();
    switchOutput(language.executionMode === 'guided' ? 'console' : ['browser', 'react-preview', 'next-preview'].includes(language.executionMode) ? 'preview' : 'console');
    runStartedAt = performance.now();
    if (language.executionMode === 'remote') writeLine(`Running ${language.name} in the configured sandbox…`);
  }

  async function runProject() {
    syncEditorToFile();
    const language = currentLanguage();
    showRunStart(language);
    const button = lab.querySelector('[data-run-code]');
    button.disabled = true;
    button.classList.add('busy');
    try {
      if (language.executionMode === 'guided') runMwanaCode();
      else if (language.executionMode === 'browser') runHtmlPreview();
      else if (language.executionMode === 'browser-console') runJavaScriptPreview();
      else if (language.executionMode === 'react-preview') runReactPreview(false);
      else if (language.executionMode === 'next-preview') runReactPreview(true);
      else await runRemote(language);
    } catch (error) {
      switchOutput('console');
      writeLine(error.message || String(error), 'error');
    } finally {
      button.disabled = false;
      button.classList.remove('busy');
    }
  }

  function bridgeScript() {
    const token = JSON.stringify(previewToken);
    return `<script>(function(){const token=${token};const send=(type,args)=>parent.postMessage({source:'codemwana-preview',token,type,args:args.map(v=>typeof v==='string'?v:(()=>{try{return JSON.stringify(v)}catch(e){return String(v)}})())},'*');['log','info','warn','error'].forEach(type=>{const original=console[type];console[type]=(...args)=>{send(type,args);original.apply(console,args)}});window.addEventListener('error',event=>send('error',[event.message+' at line '+event.lineno]));window.addEventListener('unhandledrejection',event=>send('error',[event.reason?.message||String(event.reason)]));parent.postMessage({source:'codemwana-preview',token,type:'ready',args:[]},'*')})();<\/script>`;
  }

  function runHtmlPreview() {
    const htmlName = Object.keys(files).find((name) => name.endsWith('.html')) || currentLanguage().mainFile;
    let documentSource = files[htmlName] || '<!doctype html><html><head></head><body></body></html>';
    const css = Object.entries(files).filter(([name]) => name.endsWith('.css')).map(([, content]) => content).join('\n');
    const scripts = Object.entries(files).filter(([name]) => name.endsWith('.js')).map(([, content]) => content).join('\n');
    documentSource = documentSource.replace(/<link\b[^>]*href=["'][^"']+\.css["'][^>]*>/gi, '');
    documentSource = documentSource.replace(/<script\b[^>]*src=["'][^"']+\.js["'][^>]*><\/script>/gi, '');
    const head = `${bridgeScript()}<style>${escapeStyle(css)}</style>`;
    const foot = `<script>${escapeScript(scripts)}<\/script>`;
    documentSource = /<\/head>/i.test(documentSource) ? documentSource.replace(/<\/head>/i, `${head}</head>`) : head + documentSource;
    documentSource = /<\/body>/i.test(documentSource) ? documentSource.replace(/<\/body>/i, `${foot}</body>`) : documentSource + foot;
    previewMessage.hidden = true;
    previewFrame.srcdoc = documentSource;
  }

  function runJavaScriptPreview() {
    const source = files[currentLanguage().mainFile] ?? Object.values(files)[0] ?? '';
    previewMessage.hidden = true;
    previewFrame.srcdoc = `<!doctype html><html><head><meta charset="utf-8">${bridgeScript()}<style>body{font:15px system-ui;margin:0;padding:24px;color:#172033;background:#fff}.notice{padding:18px;border:1px solid #e3e6ee;border-radius:14px;background:#f7f8fc}</style></head><body><div class="notice"><strong>JavaScript executed.</strong><p>Open the Console tab to view program output.</p></div><script>${escapeScript(source)}<\/script></body></html>`;
    switchOutput('console');
  }

  function runReactPreview(nextMode) {
    const language = currentLanguage();
    let source = files[language.mainFile] ?? Object.values(files)[0] ?? '';
    const css = Object.entries(files).filter(([name]) => name.endsWith('.css')).map(([, content]) => content).join('\n');
    let componentName = 'App';
    if (nextMode) {
      source = source.replace(/export\s+default\s+function\s+([A-Za-z_$][\w$]*)/, 'function $1');
      const match = source.match(/function\s+([A-Za-z_$][\w$]*)\s*\(/);
      componentName = match ? match[1] : 'Page';
    }
    const helpers = nextMode ? `const Link=({href,children,...props})=><a href={href} {...props}>{children}</a>;` : '';
    previewMessage.hidden = true;
    previewFrame.srcdoc = `<!doctype html><html><head><meta charset="utf-8">${bridgeScript()}<style>${escapeStyle(css)}</style><script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"><\/script><script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"><\/script><script src="https://unpkg.com/@babel/standalone/babel.min.js"><\/script></head><body><div id="root"></div><script type="text/babel">${helpers}\n${escapeScript(source)}\nconst root=ReactDOM.createRoot(document.getElementById('root'));root.render(React.createElement(${componentName}));<\/script></body></html>`;
  }

  async function runRemote(language) {
    if (!initial.runnerConfigured) {
      throw new Error(`${language.name} execution requires an isolated runner. Configure CODE_RUNNER_URL in .env. The project can still be written and saved without it.`);
    }
    const response = await fetch(lab.dataset.runUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': lab.dataset.csrf },
      body: JSON.stringify({ project_id: projectId, language: activeLanguage, files, stdin: stdinInput.value }),
    });
    const payload = await response.json().catch(() => ({ ok: false, message: 'The runner returned an unreadable response.' }));
    output.textContent = '';
    if (payload.result?.stdout) writeLine(payload.result.stdout.trimEnd());
    if (payload.result?.stderr) writeLine(payload.result.stderr.trimEnd(), 'error');
    if (!payload.ok) throw new Error(payload.message || 'The program could not be run.');
    if (!payload.result?.stdout && !payload.result?.stderr) writeLine('Program completed without output.');
    const elapsed = payload.result?.execution_time_ms ?? Math.round(performance.now() - runStartedAt);
    writeLine(`\nProcess finished${payload.result?.exit_code !== null ? ` with exit code ${payload.result.exit_code}` : ''} · ${elapsed} ms`);
  }

  async function saveProject() {
    syncEditorToFile();
    const button = lab.querySelector('[data-save-project]');
    button.disabled = true;
    saveStatus.textContent = 'Saving…';
    try {
      const response = await fetch(lab.dataset.saveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': lab.dataset.csrf },
        body: JSON.stringify({ id: projectId, title: titleInput.value.trim(), language: activeLanguage, files, stdin: stdinInput.value }),
      });
      const payload = await response.json().catch(() => ({ ok: false, message: 'The server returned an unreadable response.' }));
      if (!response.ok || !payload.ok) throw new Error(payload.message || 'The project could not be saved.');
      projectId = Number(payload.id);
      baseline = snapshot();
      setDirty(false);
      window.history.replaceState({}, '', `${window.location.pathname}?project=${projectId}`);
    } catch (error) {
      saveStatus.textContent = 'Save failed';
      saveStatus.classList.add('unsaved');
      window.alert(error.message);
    } finally {
      button.disabled = false;
    }
  }

  function runMwanaCode() {
    clearCanvas();
    const source = files[activeFile] || '';
    const lines = source.split(/\r?\n/).map((raw, index) => ({ text: raw.trim(), line: index + 1 })).filter((line) => line.text && !line.text.startsWith('#') && !line.text.startsWith('//'));
    const parsed = parseMwanaBlock(lines, 0, []);
    if (parsed.stop) throw new Error(`Unexpected ${parsed.stop}.`);
    const state = { variables: {}, steps: 0, usedDrawing: false };
    executeMwanaNodes(parsed.nodes, state);
    if (!output.textContent.trim()) writeLine('Program finished. No text was displayed.');
    if (state.usedDrawing) switchOutput('drawing');
  }

  function parseMwanaBlock(lines, start, stops) {
    const nodes = [];
    let index = start;
    while (index < lines.length) {
      const line = lines[index];
      const keyword = line.text.split(/\s+/)[0].toUpperCase();
      if (stops.includes(keyword)) return { nodes, index, stop: keyword };
      if (keyword === 'REPEAT') {
        const child = parseMwanaBlock(lines, index + 1, ['END']);
        if (child.stop !== 'END') throw new Error(`Line ${line.line}: REPEAT is missing END.`);
        nodes.push({ type: 'repeat', expression: line.text.slice(6).trim(), body: child.nodes, line: line.line });
        index = child.index + 1; continue;
      }
      if (keyword === 'IF') {
        const yes = parseMwanaBlock(lines, index + 1, ['ELSE', 'END']);
        let no = []; let end = yes.index;
        if (yes.stop === 'ELSE') {
          const alternative = parseMwanaBlock(lines, yes.index + 1, ['END']);
          if (alternative.stop !== 'END') throw new Error(`Line ${line.line}: IF is missing END.`);
          no = alternative.nodes; end = alternative.index;
        } else if (yes.stop !== 'END') throw new Error(`Line ${line.line}: IF is missing END.`);
        nodes.push({ type: 'if', condition: line.text.slice(2).trim(), yes: yes.nodes, no, line: line.line });
        index = end + 1; continue;
      }
      if (['END', 'ELSE'].includes(keyword)) throw new Error(`Line ${line.line}: ${keyword} does not match an open block.`);
      nodes.push({ type: 'command', text: line.text, line: line.line }); index += 1;
    }
    return { nodes, index, stop: null };
  }

  function mwanaValue(expression, variables) {
    const text = expression.trim();
    if ((text.startsWith('"') && text.endsWith('"')) || (text.startsWith("'") && text.endsWith("'"))) return text.slice(1, -1).replace(/\\n/g, '\n');
    if (/^-?\d+(\.\d+)?$/.test(text)) return Number(text);
    if (/^(TRUE|FALSE)$/i.test(text)) return text.toUpperCase() === 'TRUE';
    if (text.includes('+')) {
      const values = text.split('+').map((part) => mwanaValue(part, variables));
      return values.every((value) => typeof value === 'number') ? values.reduce((sum, value) => sum + value, 0) : values.join('');
    }
    if (Object.prototype.hasOwnProperty.call(variables, text)) return variables[text];
    throw new Error(`Unknown value or variable “${text}”.`);
  }

  function mwanaCompare(condition, variables) {
    const match = condition.match(/^(.+?)\s*(==|!=|>=|<=|>|<)\s*(.+)$/);
    if (!match) return Boolean(mwanaValue(condition, variables));
    const left = mwanaValue(match[1], variables); const right = mwanaValue(match[3], variables);
    return ({ '==': left === right, '!=': left !== right, '>=': left >= right, '<=': left <= right, '>': left > right, '<': left < right })[match[2]];
  }

  function executeMwanaNodes(nodes, state) {
    nodes.forEach((node) => {
      if (node.type === 'command') executeMwanaCommand(node, state);
      if (node.type === 'repeat') {
        const count = Number(mwanaValue(node.expression, state.variables));
        if (!Number.isInteger(count) || count < 0 || count > 100) throw new Error(`Line ${node.line}: REPEAT must use a whole number from 0 to 100.`);
        for (let index = 0; index < count; index += 1) executeMwanaNodes(node.body, state);
      }
      if (node.type === 'if') executeMwanaNodes(mwanaCompare(node.condition, state.variables) ? node.yes : node.no, state);
    });
  }

  const turtle = { x: canvas.width / 2, y: canvas.height / 2, angle: -90, colour: '#6546d7', penDown: true };
  function clearCanvas() {
    context.clearRect(0, 0, canvas.width, canvas.height); context.fillStyle = '#fff'; context.fillRect(0, 0, canvas.width, canvas.height);
    context.strokeStyle = '#ececf4'; context.lineWidth = 1;
    for (let x = 0; x <= canvas.width; x += 50) { context.beginPath(); context.moveTo(x, 0); context.lineTo(x, canvas.height); context.stroke(); }
    for (let y = 0; y <= canvas.height; y += 50) { context.beginPath(); context.moveTo(0, y); context.lineTo(canvas.width, y); context.stroke(); }
    Object.assign(turtle, { x: canvas.width / 2, y: canvas.height / 2, angle: -90, colour: '#6546d7', penDown: true });
  }
  function moveTurtle(distance) {
    const radians = turtle.angle * Math.PI / 180; const nextX = turtle.x + Math.cos(radians) * distance; const nextY = turtle.y + Math.sin(radians) * distance;
    if (turtle.penDown) { context.strokeStyle = turtle.colour; context.lineWidth = 4; context.lineCap = 'round'; context.beginPath(); context.moveTo(turtle.x, turtle.y); context.lineTo(nextX, nextY); context.stroke(); }
    turtle.x = nextX; turtle.y = nextY;
  }

  function executeMwanaCommand(node, state) {
    state.steps += 1; if (state.steps > 2000) throw new Error(`Line ${node.line}: Program stopped after 2,000 steps.`);
    const firstSpace = node.text.indexOf(' '); const command = (firstSpace < 0 ? node.text : node.text.slice(0, firstSpace)).toUpperCase(); const argument = firstSpace < 0 ? '' : node.text.slice(firstSpace + 1).trim();
    try {
      if (['SAY', 'PRINT'].includes(command)) writeLine(String(mwanaValue(argument, state.variables)));
      else if (command === 'SET') { const match = argument.match(/^([A-Za-z_]\w*)\s*=\s*(.+)$/); if (!match) throw new Error('Use SET name = value.'); state.variables[match[1]] = mwanaValue(match[2], state.variables); }
      else if (command === 'ADD') { const match = argument.match(/^([A-Za-z_]\w*)\s+(.+)$/); if (!match) throw new Error('Use ADD variable amount.'); const current = Number(state.variables[match[1]] || 0); const amount = Number(mwanaValue(match[2], state.variables)); if (!Number.isFinite(current) || !Number.isFinite(amount)) throw new Error('ADD works with numbers only.'); state.variables[match[1]] = current + amount; }
      else if (command === 'MOVE') { const distance = Number(mwanaValue(argument, state.variables)); if (!Number.isFinite(distance)) throw new Error('MOVE needs a number.'); moveTurtle(Math.max(-500, Math.min(500, distance))); state.usedDrawing = true; }
      else if (command === 'TURN') { const degrees = Number(mwanaValue(argument, state.variables)); if (!Number.isFinite(degrees)) throw new Error('TURN needs a number.'); turtle.angle += degrees; state.usedDrawing = true; }
      else if (command === 'PEN') { turtle.colour = String(mwanaValue(argument, state.variables)); state.usedDrawing = true; }
      else if (command === 'PENUP') { turtle.penDown = false; state.usedDrawing = true; }
      else if (command === 'PENDOWN') { turtle.penDown = true; state.usedDrawing = true; }
      else if (command === 'CLEAR') { clearCanvas(); state.usedDrawing = true; }
      else throw new Error(`Unknown command “${command}”.`);
    } catch (error) { throw new Error(`Line ${node.line}: ${error.message}`); }
  }

  function setMobileView(view) {
    mobileGrid.dataset.mobileActive = view;
    lab.querySelectorAll('[data-mobile-view]').forEach((button) => button.classList.toggle('active', button.dataset.mobileView === view));
  }

  function escapeHtml(value) { return String(value).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]); }
  function escapeAttribute(value) { return escapeHtml(value).replace(/\n/g, '&#10;'); }
  function escapeScript(value) { return String(value).replace(/<\/script/gi, '<\\/script'); }
  function escapeStyle(value) { return String(value).replace(/<\/style/gi, '<\\/style'); }

  editor.addEventListener('input', () => { syncEditorToFile(); updateLineNumbers(); updateCursor(); refreshDirtyState(); });
  editor.addEventListener('scroll', () => { lineNumbers.scrollTop = editor.scrollTop; });
  editor.addEventListener('click', updateCursor); editor.addEventListener('keyup', updateCursor);
  editor.addEventListener('keydown', (event) => {
    if (event.key === 'Tab') { event.preventDefault(); editor.setRangeText('  ', editor.selectionStart, editor.selectionEnd, 'end'); syncEditorToFile(); updateLineNumbers(); refreshDirtyState(); }
    if (event.key.toLowerCase() === 's' && (event.ctrlKey || event.metaKey)) { event.preventDefault(); saveProject(); }
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) { event.preventDefault(); runProject(); }
  });
  titleInput.addEventListener('input', refreshDirtyState); stdinInput.addEventListener('input', refreshDirtyState);
  languageSelect.addEventListener('change', () => switchLanguage(languageSelect.value));
  lab.addEventListener('click', (event) => {
    const open = event.target.closest('[data-open-file]'); if (open) openFile(open.dataset.openFile);
    const remove = event.target.closest('[data-delete-file]'); if (remove) deleteFile(remove.dataset.deleteFile);
    const snippet = event.target.closest('[data-snippet]'); if (snippet) insertSnippet(snippet.dataset.snippet);
    const mobile = event.target.closest('[data-mobile-view]'); if (mobile) setMobileView(mobile.dataset.mobileView);
  });
  lab.querySelector('[data-add-file]').addEventListener('click', addFile);
  lab.querySelector('[data-save-project]').addEventListener('click', saveProject);
  lab.querySelector('[data-run-code]').addEventListener('click', runProject);
  lab.querySelector('[data-reset-code]').addEventListener('click', () => {
    if (!window.confirm('Reset this language workspace to its starter files?')) return;
    const language = currentLanguage(); files = normaliseFiles(JSON.parse(JSON.stringify(language.files || {}))); activeFile = language.mainFile in files ? language.mainFile : Object.keys(files)[0]; editor.value = files[activeFile]; renderFiles(); updateLineNumbers(); updateWorkspaceSize(); refreshDirtyState(); clearOutput();
  });
  lab.querySelector('[data-font-increase]').addEventListener('click', () => { fontSize = Math.min(24, fontSize + 1); editor.style.fontSize = `${fontSize}px`; lineNumbers.style.fontSize = `${fontSize}px`; });
  lab.querySelector('[data-font-decrease]').addEventListener('click', () => { fontSize = Math.max(12, fontSize - 1); editor.style.fontSize = `${fontSize}px`; lineNumbers.style.fontSize = `${fontSize}px`; });
  lab.querySelectorAll('[data-output-tab]').forEach((button) => button.addEventListener('click', () => switchOutput(button.dataset.outputTab)));
  lab.querySelector('[data-clear-output]').addEventListener('click', clearOutput);
  lab.querySelector('[data-clear-canvas]').addEventListener('click', clearCanvas);
  const stdinDrawer = lab.querySelector('[data-stdin-drawer]');
  lab.querySelector('[data-toggle-stdin]').addEventListener('click', () => { stdinDrawer.classList.add('open'); stdinDrawer.setAttribute('aria-hidden', 'false'); stdinInput.focus(); });
  lab.querySelector('[data-close-stdin]').addEventListener('click', () => { stdinDrawer.classList.remove('open'); stdinDrawer.setAttribute('aria-hidden', 'true'); });
  stdinDrawer.addEventListener('click', (event) => { if (event.target === stdinDrawer) lab.querySelector('[data-close-stdin]').click(); });
  window.addEventListener('message', (event) => {
    const data = event.data || {};
    if (data.source !== 'codemwana-preview' || data.token !== previewToken) return;
    if (data.type === 'ready') { if (activeOutput === 'preview') return; }
    else { if (data.type === 'error') switchOutput('console'); writeLine((data.args || []).join(' '), data.type === 'error' ? 'error' : 'normal'); }
  });
  window.addEventListener('beforeunload', (event) => { if (!dirty) return; event.preventDefault(); event.returnValue = ''; });

  stdinInput.value = initial.stdin || '';
  editor.value = files[activeFile] || '';
  renderFiles(); renderLanguage(); updateLineNumbers(); updateCursor(); updateWorkspaceSize(); clearCanvas();
  baseline = snapshot(); setDirty(false); setMobileView(window.innerWidth <= 720 ? 'editor' : 'editor');
})();
