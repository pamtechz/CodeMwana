'use strict';

(() => {
  const lab = document.querySelector('[data-code-lab]');
  if (!lab) return;

  const editor = lab.querySelector('[data-code-editor]');
  const lineNumbers = lab.querySelector('[data-line-numbers]');
  const output = lab.querySelector('[data-console-output]');
  const placeholder = lab.querySelector('[data-output-placeholder]');
  const canvas = lab.querySelector('[data-drawing-canvas]');
  const context = canvas.getContext('2d');
  const titleInput = lab.querySelector('[data-project-title]');
  const saveStatus = lab.querySelector('[data-save-status]');
  const initialCode = editor.value;
  let projectId = Number(lab.dataset.projectId || 0);
  let dirty = false;
  let fontSize = 16;
  let activeView = 'console';
  const turtle = { x: canvas.width / 2, y: canvas.height / 2, angle: -90, colour: '#6546d7', penDown: true };

  function updateLineNumbers() {
    const count = Math.max(1, editor.value.split('\n').length);
    lineNumbers.textContent = Array.from({ length: count }, (_, i) => i + 1).join('\n');
    lineNumbers.scrollTop = editor.scrollTop;
  }

  function markDirty() {
    dirty = true;
    saveStatus.textContent = 'Unsaved changes';
    saveStatus.classList.add('unsaved');
  }

  editor.addEventListener('input', () => { updateLineNumbers(); markDirty(); });
  titleInput.addEventListener('input', markDirty);
  editor.addEventListener('scroll', () => { lineNumbers.scrollTop = editor.scrollTop; });
  editor.addEventListener('keydown', (event) => {
    if (event.key === 'Tab') {
      event.preventDefault();
      const start = editor.selectionStart;
      editor.setRangeText('  ', start, editor.selectionEnd, 'end');
      updateLineNumbers();
      markDirty();
    }
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
      event.preventDefault();
      runProgram();
    }
  });

  lab.querySelectorAll('[data-insert]').forEach((button) => {
    button.addEventListener('click', () => {
      const text = button.dataset.insert || '';
      const start = editor.selectionStart;
      const prefix = start > 0 && editor.value[start - 1] !== '\n' ? '\n' : '';
      editor.setRangeText(prefix + text, start, editor.selectionEnd, 'end');
      editor.focus();
      updateLineNumbers();
      markDirty();
    });
  });

  const search = lab.querySelector('[data-command-search]');
  search.addEventListener('input', () => {
    const term = search.value.trim().toLowerCase();
    lab.querySelectorAll('.command-chip').forEach((chip) => {
      chip.hidden = term !== '' && !chip.textContent.toLowerCase().includes(term);
    });
  });

  lab.querySelector('[data-reset-code]').addEventListener('click', () => {
    if (!window.confirm('Reset the editor to its starting code?')) return;
    editor.value = initialCode;
    updateLineNumbers();
    markDirty();
    clearOutput();
  });

  lab.querySelector('[data-font-increase]').addEventListener('click', () => {
    fontSize = Math.min(24, fontSize + 1); editor.style.fontSize = `${fontSize}px`; lineNumbers.style.fontSize = `${fontSize}px`;
  });
  lab.querySelector('[data-font-decrease]').addEventListener('click', () => {
    fontSize = Math.max(12, fontSize - 1); editor.style.fontSize = `${fontSize}px`; lineNumbers.style.fontSize = `${fontSize}px`;
  });

  lab.querySelectorAll('[data-output-tab]').forEach((tab) => {
    tab.addEventListener('click', () => switchOutput(tab.dataset.outputTab));
  });

  function switchOutput(name) {
    activeView = name;
    lab.querySelectorAll('[data-output-tab]').forEach((tab) => {
      const active = tab.dataset.outputTab === name;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
    });
    lab.querySelectorAll('[data-output-view]').forEach((view) => view.classList.toggle('active', view.dataset.outputView === name));
  }

  function clearCanvas() {
    context.clearRect(0, 0, canvas.width, canvas.height);
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.strokeStyle = '#e9e6f4';
    context.lineWidth = 1;
    for (let x = 0; x <= canvas.width; x += 40) { context.beginPath(); context.moveTo(x, 0); context.lineTo(x, canvas.height); context.stroke(); }
    for (let y = 0; y <= canvas.height; y += 40) { context.beginPath(); context.moveTo(0, y); context.lineTo(canvas.width, y); context.stroke(); }
    Object.assign(turtle, { x: canvas.width / 2, y: canvas.height / 2, angle: -90, colour: '#6546d7', penDown: true });
  }

  lab.querySelector('[data-clear-canvas]').addEventListener('click', clearCanvas);

  function clearOutput() {
    output.textContent = '';
    output.classList.remove('has-error');
    placeholder.hidden = false;
  }

  function writeLine(message, type = 'normal') {
    placeholder.hidden = true;
    output.textContent += `${message}\n`;
    if (type === 'error') output.classList.add('has-error');
  }

  function tokenize(source) {
    return source.split(/\r?\n/).map((raw, index) => ({ raw, text: raw.trim(), line: index + 1 })).filter((item) => item.text !== '' && !item.text.startsWith('#') && !item.text.startsWith('//'));
  }

  function parseBlock(lines, startIndex = 0, stops = []) {
    const nodes = [];
    let index = startIndex;
    while (index < lines.length) {
      const line = lines[index];
      const keyword = line.text.split(/\s+/)[0].toUpperCase();
      if (stops.includes(keyword)) return { nodes, index, stop: keyword };

      if (keyword === 'REPEAT') {
        const expression = line.text.slice(6).trim();
        if (!expression) throw syntaxError(line, 'REPEAT needs a number or variable.');
        const child = parseBlock(lines, index + 1, ['END']);
        if (child.stop !== 'END') throw syntaxError(line, 'REPEAT is missing END.');
        nodes.push({ type: 'repeat', expression, body: child.nodes, line: line.line });
        index = child.index + 1;
        continue;
      }

      if (keyword === 'IF') {
        const condition = line.text.slice(2).trim();
        if (!condition) throw syntaxError(line, 'IF needs a condition.');
        const yes = parseBlock(lines, index + 1, ['ELSE', 'END']);
        let no = [];
        let endIndex = yes.index;
        if (yes.stop === 'ELSE') {
          const alternative = parseBlock(lines, yes.index + 1, ['END']);
          if (alternative.stop !== 'END') throw syntaxError(line, 'IF is missing END.');
          no = alternative.nodes;
          endIndex = alternative.index;
        } else if (yes.stop !== 'END') {
          throw syntaxError(line, 'IF is missing END.');
        }
        nodes.push({ type: 'if', condition, yes: yes.nodes, no, line: line.line });
        index = endIndex + 1;
        continue;
      }

      if (keyword === 'END' || keyword === 'ELSE') throw syntaxError(line, `${keyword} does not match an open block.`);
      nodes.push({ type: 'command', text: line.text, line: line.line });
      index += 1;
    }
    return { nodes, index, stop: null };
  }

  function syntaxError(line, message) {
    const error = new Error(`Line ${line.line}: ${message}`);
    error.line = line.line;
    return error;
  }

  function valueOf(expression, variables) {
    const text = expression.trim();
    if ((text.startsWith('"') && text.endsWith('"')) || (text.startsWith("'") && text.endsWith("'"))) return text.slice(1, -1).replace(/\\n/g, '\n');
    if (/^-?\d+(\.\d+)?$/.test(text)) return Number(text);
    if (/^(TRUE|FALSE)$/i.test(text)) return text.toUpperCase() === 'TRUE';
    if (text.includes('+')) {
      const parts = text.split('+').map((part) => valueOf(part, variables));
      return parts.every((part) => typeof part === 'number') ? parts.reduce((a, b) => a + b, 0) : parts.join('');
    }
    if (Object.prototype.hasOwnProperty.call(variables, text)) return variables[text];
    throw new Error(`Unknown value or variable “${text}”.`);
  }

  function compare(condition, variables) {
    const match = condition.match(/^(.+?)\s*(==|!=|>=|<=|>|<)\s*(.+)$/);
    if (!match) return Boolean(valueOf(condition, variables));
    const left = valueOf(match[1], variables);
    const right = valueOf(match[3], variables);
    switch (match[2]) {
      case '==': return left === right;
      case '!=': return left !== right;
      case '>=': return left >= right;
      case '<=': return left <= right;
      case '>': return left > right;
      case '<': return left < right;
      default: return false;
    }
  }

  function moveTurtle(distance) {
    const radians = turtle.angle * Math.PI / 180;
    const nextX = turtle.x + Math.cos(radians) * distance;
    const nextY = turtle.y + Math.sin(radians) * distance;
    if (turtle.penDown) {
      context.strokeStyle = turtle.colour;
      context.lineWidth = 4;
      context.lineCap = 'round';
      context.beginPath(); context.moveTo(turtle.x, turtle.y); context.lineTo(nextX, nextY); context.stroke();
    }
    turtle.x = nextX; turtle.y = nextY;
  }

  function executeCommand(node, state) {
    const { text, line } = node;
    const space = text.indexOf(' ');
    const command = (space === -1 ? text : text.slice(0, space)).toUpperCase();
    const argument = space === -1 ? '' : text.slice(space + 1).trim();
    state.steps += 1;
    if (state.steps > 2000) throw new Error(`Line ${line}: Program stopped after 2,000 steps. Check for excessive repetition.`);

    try {
      if (command === 'SAY' || command === 'PRINT') {
        if (!argument) throw new Error('SAY needs text, a number or a variable.');
        writeLine(String(valueOf(argument, state.variables)));
      } else if (command === 'SET') {
        const match = argument.match(/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.+)$/);
        if (!match) throw new Error('Use SET name = value.');
        state.variables[match[1]] = valueOf(match[2], state.variables);
      } else if (command === 'ADD') {
        const match = argument.match(/^([A-Za-z_][A-Za-z0-9_]*)\s+(.+)$/);
        if (!match) throw new Error('Use ADD variable amount.');
        const current = Number(state.variables[match[1]] ?? 0);
        const amount = Number(valueOf(match[2], state.variables));
        if (!Number.isFinite(current) || !Number.isFinite(amount)) throw new Error('ADD works with numbers only.');
        state.variables[match[1]] = current + amount;
      } else if (command === 'MOVE') {
        const distance = Number(valueOf(argument, state.variables));
        if (!Number.isFinite(distance)) throw new Error('MOVE needs a number.');
        moveTurtle(Math.max(-500, Math.min(500, distance)));
        state.usedDrawing = true;
      } else if (command === 'TURN') {
        const degrees = Number(valueOf(argument, state.variables));
        if (!Number.isFinite(degrees)) throw new Error('TURN needs a number.');
        turtle.angle += degrees; state.usedDrawing = true;
      } else if (command === 'PEN') {
        turtle.colour = String(valueOf(argument, state.variables)); state.usedDrawing = true;
      } else if (command === 'PENUP') {
        turtle.penDown = false; state.usedDrawing = true;
      } else if (command === 'PENDOWN') {
        turtle.penDown = true; state.usedDrawing = true;
      } else if (command === 'CLEAR') {
        clearCanvas(); state.usedDrawing = true;
      } else {
        throw new Error(`Unknown command “${command}”.`);
      }
    } catch (error) {
      throw new Error(`Line ${line}: ${error.message}`);
    }
  }

  function executeNodes(nodes, state) {
    for (const node of nodes) {
      if (node.type === 'command') executeCommand(node, state);
      if (node.type === 'repeat') {
        let count;
        try { count = Number(valueOf(node.expression, state.variables)); } catch (error) { throw new Error(`Line ${node.line}: ${error.message}`); }
        if (!Number.isInteger(count) || count < 0 || count > 100) throw new Error(`Line ${node.line}: REPEAT must use a whole number from 0 to 100.`);
        for (let i = 0; i < count; i += 1) executeNodes(node.body, state);
      }
      if (node.type === 'if') {
        let decision;
        try { decision = compare(node.condition, state.variables); } catch (error) { throw new Error(`Line ${node.line}: ${error.message}`); }
        executeNodes(decision ? node.yes : node.no, state);
      }
    }
  }

  function runProgram() {
    clearOutput(); clearCanvas();
    try {
      const lines = tokenize(editor.value);
      const parsed = parseBlock(lines);
      if (parsed.stop) throw new Error(`Unexpected ${parsed.stop}.`);
      const state = { variables: {}, steps: 0, usedDrawing: false };
      executeNodes(parsed.nodes, state);
      if (!output.textContent.trim()) writeLine('Program finished. No text was displayed.');
      if (state.usedDrawing) switchOutput('drawing'); else switchOutput('console');
    } catch (error) {
      switchOutput('console');
      writeLine(`⚠ ${error.message}`, 'error');
    }
  }

  lab.querySelector('[data-run-code]').addEventListener('click', runProgram);

  async function saveProject() {
    const title = titleInput.value.trim();
    if (!title) { titleInput.focus(); saveStatus.textContent = 'Add a title first'; return; }
    saveStatus.textContent = 'Saving…';
    try {
      const response = await fetch(lab.dataset.saveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': lab.dataset.csrf },
        body: JSON.stringify({ id: projectId, title, code: editor.value }),
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || 'Save failed.');
      projectId = Number(data.id);
      lab.dataset.projectId = String(projectId);
      dirty = false;
      saveStatus.textContent = 'Saved';
      saveStatus.classList.remove('unsaved');
      const nextUrl = new URL(window.location.href);
      nextUrl.searchParams.delete('lesson');
      nextUrl.searchParams.set('project', String(projectId));
      window.history.replaceState({}, '', nextUrl);
    } catch (error) {
      saveStatus.textContent = error.message;
      saveStatus.classList.add('unsaved');
    }
  }

  lab.querySelector('[data-save-project]').addEventListener('click', saveProject);
  window.addEventListener('beforeunload', (event) => {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });

  clearCanvas();
  updateLineNumbers();
})();
