'use strict';

(() => {
  const frame = document.querySelector('[data-external-runner-frame]');
  if (!frame) return;

  // OneCompiler's official embed expects its normal HTTPS origin so that its
  // local storage, fonts and execution requests remain same-origin. The frame
  // has no external src yet, so remove the sandbox before remote-runner.js
  // assigns https://onecompiler.com/embed/.... The browser's same-origin policy
  // still prevents the cross-origin frame from reading CodeMwana's document.
  frame.removeAttribute('sandbox');
  frame.setAttribute('allow', 'clipboard-read; clipboard-write; fullscreen');
  frame.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
})();
