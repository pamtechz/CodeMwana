<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();

$user = current_user();
$languages = Learning::languages(true);
$languageMap = [];
foreach ($languages as $item) {
    $item['files'] = LanguageCatalog::workspace($item);
    $languageMap[$item['slug']] = $item;
}

$project = null;
$lesson = null;
$projectId = request_int('project');
if ($projectId > 0) {
    $project = Learning::project($projectId, (int) $user['id']);
    if (!$project) {
        flash('error', 'That project is not available in your library.');
        redirect('projects.php');
    }
}

$requestedLanguage = strtolower(trim((string) ($_GET['language'] ?? 'mwanacode')));
$activeSlug = $project['language'] ?? (array_key_exists($requestedLanguage, $languageMap) ? $requestedLanguage : 'mwanacode');
$activeLanguage = $languageMap[$activeSlug] ?? LanguageCatalog::guided();
$workspace = $project['workspace'] ?? LanguageCatalog::workspace($activeLanguage);
$stdin = (string) ($project['stdin'] ?? '');
$projectTitle = (string) ($project['title'] ?? ('Untitled ' . $activeLanguage['name'] . ' project'));

$lessonIdentifier = trim((string) ($_GET['lesson'] ?? ''));
if (!$project && $lessonIdentifier !== '') {
    $lesson = Learning::lesson($lessonIdentifier);
    if ($lesson && trim((string) $lesson['starter_code']) !== '') {
        $activeSlug = 'mwanacode';
        $activeLanguage = $languageMap[$activeSlug] ?? LanguageCatalog::guided();
        $workspace = [$activeLanguage['main_file'] => (string) $lesson['starter_code']];
        $projectTitle = $lesson['title'] . ' challenge';
    }
}

$payload = [
    'projectId' => (int) ($project['id'] ?? 0),
    'title' => $projectTitle,
    'language' => $activeSlug,
    'files' => $workspace,
    'stdin' => $stdin,
    'runnerConfigured' => CodeRunner::configured(),
    'fallbackAvailable' => CodeRunner::fallbackAvailable(),
    'browserRunners' => [],
    'remoteRunners' => ['python', 'php', 'c', 'cpp', 'go'],
    'languages' => array_values(array_map(static function (array $language): array {
        return [
            'slug' => $language['slug'],
            'name' => $language['name'],
            'shortName' => $language['short_name'],
            'category' => $language['category'],
            'description' => $language['description'],
            'editorMode' => $language['editor_mode'],
            'executionMode' => $language['execution_mode'],
            'mainFile' => $language['main_file'],
            'colour' => $language['colour'],
            'files' => $language['files'],
        ];
    }, $languages)),
];

$pageTitle = 'Code Lab';
$bodyClass = 'code-lab-page';
$pageStyles = ['remote-runner.css'];
$pageScripts = ['remote-runner.js', 'playground.js'];
require base_path('partials/header.php');
?>
<section
    class="code-studio"
    data-code-lab
    data-save-url="<?= e(url('api/save-project.php')) ?>"
    data-run-url="<?= e(url('api/run-code.php')) ?>"
    data-log-run-url="<?= e(url('api/log-browser-run.php')) ?>"
    data-fallback-url="<?= e(CodeRunner::fallbackAvailable() ? CodeRunner::fallbackUrl() : '') ?>"
    data-csrf="<?= e(csrf_token()) ?>"
>
    <script type="application/json" data-code-lab-state><?= json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

    <header class="studio-toolbar">
        <div class="studio-identity">
            <a class="studio-icon-button" href="<?= e(url('projects.php')) ?>" aria-label="Return to project library"><?= icon('arrow-left') ?></a>
            <div class="studio-title-field">
                <span>CodeMwana Code Lab</span>
                <input type="text" value="<?= e($projectTitle) ?>" maxlength="120" aria-label="Project title" data-project-title>
            </div>
        </div>
        <div class="studio-toolbar-centre">
            <label for="studio-language" class="sr-only">Programming language</label>
            <select id="studio-language" data-language-select>
                <?php foreach ($languages as $language): ?>
                    <option value="<?= e($language['slug']) ?>" <?= $language['slug'] === $activeSlug ? 'selected' : '' ?>><?= e($language['name']) ?> · <?= e($language['category']) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="runner-state ready" data-runner-state>Online execution ready</span>
        </div>
        <div class="studio-actions">
            <span class="save-status" data-save-status><?= $project ? 'Saved' : 'New project' ?></span>
            <button class="studio-button secondary" type="button" data-toggle-stdin><?= icon('terminal') ?><span>Input</span></button>
            <button class="studio-button secondary" type="button" data-save-project><?= icon('save') ?><span>Save</span></button>
            <button class="studio-button run" type="button" data-run-code><?= icon('play') ?><span>Run</span></button>
        </div>
    </header>

    <nav class="studio-mobile-tabs" aria-label="Code Lab panels">
        <button type="button" class="active" data-mobile-view="files"><?= icon('folder-code') ?><span>Files</span></button>
        <button type="button" data-mobile-view="editor"><?= icon('edit') ?><span>Editor</span></button>
        <button type="button" data-mobile-view="output"><?= icon('play') ?><span>Output</span></button>
    </nav>

    <div class="studio-grid" data-mobile-active="editor">
        <aside class="studio-files" data-studio-panel="files">
            <div class="studio-panel-head">
                <div><strong>Project files</strong><small data-language-summary><?= e($activeLanguage['description']) ?></small></div>
                <button class="studio-icon-button" type="button" data-add-file aria-label="Add project file"><?= icon('plus') ?></button>
            </div>
            <div class="file-tree" data-file-tree></div>
            <div class="studio-snippets">
                <div class="studio-panel-head compact"><div><strong>Quick insert</strong><small>Language-aware snippets</small></div></div>
                <div class="snippet-list" data-snippet-list></div>
            </div>
            <div class="language-note">
                <span data-language-badge style="--language:<?= e($activeLanguage['colour']) ?>"><?= e($activeLanguage['short_name']) ?></span>
                <div><strong data-language-name><?= e($activeLanguage['name']) ?></strong><small data-execution-label><?= e($activeLanguage['execution_mode']) ?></small></div>
            </div>
        </aside>

        <main class="studio-editor" data-studio-panel="editor">
            <div class="editor-tabs" data-editor-tabs></div>
            <div class="editor-statusbar top">
                <span><i class="status-dot"></i><b data-active-file><?= e((string) array_key_first($workspace)) ?></b></span>
                <div>
                    <button type="button" data-font-decrease aria-label="Decrease editor font size">A−</button>
                    <button type="button" data-font-increase aria-label="Increase editor font size">A+</button>
                    <button type="button" data-reset-code>Reset</button>
                </div>
            </div>
            <div class="code-editor-wrap">
                <pre class="line-numbers" aria-hidden="true" data-line-numbers></pre>
                <textarea spellcheck="false" autocapitalize="off" autocomplete="off" aria-label="Code editor" data-code-editor></textarea>
            </div>
            <div class="editor-statusbar bottom">
                <span><kbd>Ctrl</kbd> + <kbd>Enter</kbd> run</span>
                <span><kbd>Ctrl</kbd> + <kbd>S</kbd> save</span>
                <span data-cursor-position>Ln 1, Col 1</span>
            </div>
        </main>

        <section class="studio-output" data-studio-panel="output">
            <div class="output-tabbar" role="tablist">
                <button type="button" class="active" role="tab" aria-selected="true" data-output-tab="console">Console</button>
                <button type="button" role="tab" aria-selected="false" data-output-tab="preview">Preview</button>
                <button type="button" role="tab" aria-selected="false" data-output-tab="drawing">Drawing</button>
                <button class="clear-output" type="button" data-clear-output>Clear</button>
            </div>
            <div class="output-surface active" data-output-view="console">
                <div class="output-empty" data-output-empty><?= icon('play') ?><h2>Ready to run</h2><p>Run the current project to view output, compilation messages or errors.</p></div>
                <pre class="console-output" aria-live="polite" data-console-output></pre>
            </div>
            <div class="output-surface preview-surface" data-output-view="preview">
                <iframe sandbox="allow-scripts allow-modals" title="Code preview" data-preview-frame></iframe>
                <div class="preview-message" data-preview-message>Preview is available for browser projects.</div>
                <div class="external-runner-shell" data-external-runner hidden>
                    <iframe
                        title="Code execution workspace"
                        data-external-runner-frame
                        sandbox="allow-scripts allow-forms allow-modals"
                        referrerpolicy="strict-origin-when-cross-origin"
                    ></iframe>
                </div>
            </div>
            <div class="output-surface drawing-surface" data-output-view="drawing">
                <canvas width="900" height="650" data-drawing-canvas aria-label="Drawing created by MwanaCode"></canvas>
                <button type="button" class="canvas-clear" data-clear-canvas>Clear drawing</button>
            </div>
        </section>
    </div>

    <aside class="stdin-drawer" data-stdin-drawer aria-hidden="true">
        <div class="stdin-card">
            <div class="studio-panel-head"><div><strong>Standard input</strong><small>Text supplied to programs that read from input.</small></div><button class="studio-icon-button" type="button" data-close-stdin aria-label="Close standard input panel"><?= icon('x') ?></button></div>
            <label for="standard-input">Program input</label>
            <textarea id="standard-input" rows="8" data-stdin></textarea>
            <p>Each line is supplied to managed programs that read standard input.</p>
        </div>
    </aside>

    <footer class="studio-footer">
        <span><?= icon('shield-check') ?> Browser projects use isolated previews. Python, PHP, C, C++ and Go use one synchronized managed workspace.</span>
        <span data-workspace-size>0 characters</span>
    </footer>
</section>
<?php require base_path('partials/footer.php'); ?>
