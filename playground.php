<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user = current_user();
$lesson = null;
$project = null;
$starterCode = "SAY \"Hello, Zambia!\"\nSET stars = 3\nREPEAT stars\n  SAY \"I can code!\"\nEND";
$projectTitle = 'My MwanaCode Project';
if ($lessonId = request_int('lesson')) {
    $lesson = Learning::lesson($lessonId);
    if ($lesson && $lesson['starter_code']) {
        $starterCode = $lesson['starter_code'];
        $projectTitle = $lesson['title'] . ' challenge';
    }
}
if ($projectId = request_int('project')) {
    $project = Learning::project($projectId, (int) $user['id']);
    if ($project) {
        $starterCode = $project['code'];
        $projectTitle = $project['title'];
    }
}
$pageTitle = 'MwanaCode Lab';
$bodyClass = 'code-lab-page';
$pageScript = 'playground.js';
require base_path('partials/header.php');
?>
<section class="code-lab-shell" data-code-lab data-save-url="<?= e(url('api/save-project.php')) ?>" data-csrf="<?= e(csrf_token()) ?>" data-project-id="<?= (int) ($project['id'] ?? 0) ?>">
    <div class="code-lab-toolbar">
        <div class="code-lab-title"><a href="<?= e(url('dashboard.php')) ?>" aria-label="Return to dashboard">←</a><div><span>MwanaCode Lab</span><input type="text" value="<?= e($projectTitle) ?>" maxlength="100" aria-label="Project title" data-project-title></div></div>
        <div class="code-lab-actions"><span class="save-status" data-save-status>Not saved</span><button class="button button-secondary button-small" type="button" data-reset-code>Reset</button><button class="button button-small" type="button" data-save-project>Save project</button><button class="button button-small run-button" type="button" data-run-code><span aria-hidden="true">▶</span> Run</button></div>
    </div>
    <div class="code-lab-layout">
        <aside class="command-panel" aria-label="MwanaCode command reference">
            <div class="panel-tabs" role="tablist"><button class="active" type="button" role="tab" aria-selected="true">Commands</button></div>
            <div class="command-search"><label class="sr-only" for="command-search">Search commands</label><input id="command-search" placeholder="Search commands" data-command-search></div>
            <div class="command-groups">
                <section><h2>Output</h2><button type="button" class="command-chip purple" data-insert='SAY "Hello!"'><code>SAY "Hello!"</code><small>Display a message</small></button></section>
                <section><h2>Information</h2><button type="button" class="command-chip blue" data-insert='SET name = "Mwamba"'><code>SET name = "Mwamba"</code><small>Create a variable</small></button><button type="button" class="command-chip blue" data-insert='ADD score 1'><code>ADD score 1</code><small>Increase a number</small></button></section>
                <section><h2>Control</h2><button type="button" class="command-chip orange" data-insert="REPEAT 4\n  SAY \"Again\"\nEND"><code>REPEAT 4 ... END</code><small>Repeat commands</small></button><button type="button" class="command-chip orange" data-insert="IF score >= 5\n  SAY \"Great work\"\nELSE\n  SAY \"Keep trying\"\nEND"><code>IF ... ELSE ... END</code><small>Make a decision</small></button></section>
                <section><h2>Drawing turtle</h2><button type="button" class="command-chip green" data-insert="MOVE 80"><code>MOVE 80</code><small>Move forward</small></button><button type="button" class="command-chip green" data-insert="TURN 90"><code>TURN 90</code><small>Turn in degrees</small></button><button type="button" class="command-chip green" data-insert='PEN "purple"'><code>PEN "purple"</code><small>Change pen colour</small></button><button type="button" class="command-chip green" data-insert="CLEAR"><code>CLEAR</code><small>Clear the drawing</small></button></section>
            </div>
        </aside>
        <section class="editor-panel">
            <div class="panel-heading"><div><span class="status-dot"></span><strong>Editor</strong><small>MwanaCode</small></div><div><button type="button" class="icon-button" title="Decrease text size" data-font-decrease>A−</button><button type="button" class="icon-button" title="Increase text size" data-font-increase>A+</button></div></div>
            <div class="editor-wrap"><div class="line-numbers" aria-hidden="true" data-line-numbers></div><textarea spellcheck="false" aria-label="MwanaCode editor" data-code-editor><?= e($starterCode) ?></textarea></div>
            <div class="editor-help"><span><kbd>Ctrl</kbd> + <kbd>Enter</kbd> Run</span><span><kbd>Tab</kbd> Indent</span><button type="button" data-open-guide>Language guide</button></div>
        </section>
        <section class="output-panel">
            <div class="output-tabs" role="tablist"><button type="button" class="active" role="tab" aria-selected="true" data-output-tab="console">Console</button><button type="button" role="tab" aria-selected="false" data-output-tab="drawing">Drawing</button></div>
            <div class="output-view active" data-output-view="console"><div class="output-placeholder" data-output-placeholder><span aria-hidden="true">▶</span><h2>Your output will appear here</h2><p>Press Run after writing a program.</p></div><pre class="console-output" aria-live="polite" data-console-output></pre></div>
            <div class="output-view drawing-view" data-output-view="drawing"><canvas width="700" height="520" data-drawing-canvas aria-label="Drawing made by MwanaCode turtle commands"></canvas><button type="button" class="canvas-clear" data-clear-canvas>Clear drawing</button></div>
        </section>
    </div>
    <div class="code-lab-footer"><span>Safe interpreter: MwanaCode does not execute arbitrary JavaScript.</span><a href="<?= e(url('projects.php')) ?>">My saved projects</a></div>
</section>
<dialog class="guide-dialog" data-guide-dialog>
    <form method="dialog"><button class="dialog-close" aria-label="Close guide">×</button></form>
    <span class="eyebrow">MwanaCode guide</span><h2>Commands for beginner programs</h2>
    <div class="guide-grid"><div><code>SAY "text"</code><p>Displays text. Use a variable name without quotation marks to display its value.</p></div><div><code>SET score = 5</code><p>Creates or changes a variable.</p></div><div><code>ADD score 1</code><p>Adds a number to an existing numeric variable.</p></div><div><code>REPEAT 4 ... END</code><p>Runs the commands inside the block four times.</p></div><div><code>IF score &gt;= 5 ... ELSE ... END</code><p>Chooses one of two command blocks.</p></div><div><code>MOVE 80 / TURN 90</code><p>Moves the drawing turtle or changes its direction.</p></div></div>
</dialog>
<?php require base_path('partials/footer.php'); ?>
