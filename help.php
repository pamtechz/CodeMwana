<?php
require_once __DIR__ . '/app/bootstrap.php';

$user = Auth::check() ? current_user() : null;
$userId = $user ? (int) $user['id'] : null;
$siteName = (string) setting('site_name', 'CodeMwana');
$supportEmail = trim((string) setting('support_email', ''));
$registrationOpen = (string) setting('registration_open', '1') === '1';
$courses = Learning::courses($userId);
$languages = Learning::languages(true);
$languageNames = array_values(array_filter(array_map(
    static fn (array $language): string => trim((string) ($language['name'] ?? '')),
    $languages
)));
$firstName = $user ? (preg_split('/\s+/', trim((string) $user['name']))[0] ?? 'Learner') : '';

$pageTitle = 'Help centre';
$pageDescription = 'Simple guidance for using ' . $siteName . ', learning, running code and managing projects.';
$bodyClass = 'content-page';
require base_path('partials/header.php');
?>
<section class="content-hero">
    <div class="container content-hero-grid">
        <div>
            <span class="eyebrow">Help centre</span>
            <h1><?= $user ? 'Welcome, ' . e($firstName) . '. Find the next step.' : 'Learn how to use ' . e($siteName) . '.' ?></h1>
            <p>Follow these short guides to access your account, begin a learning path, use Code Lab, save projects and review progress.</p>
            <div class="hero-actions">
                <?php if ($user): ?>
                    <a class="button" href="<?= e(url('dashboard.php')) ?>"><?= icon('home') ?>Open my workspace</a>
                    <a class="button button-secondary" href="<?= e(url('playground.php')) ?>"><?= icon('terminal') ?>Open Code Lab</a>
                <?php else: ?>
                    <a class="button" href="<?= e(url('login.php')) ?>">Sign in</a>
                    <?php if ($registrationOpen): ?><a class="button button-secondary" href="<?= e(url('register.php')) ?>">Create account</a><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="content-hero-stats">
            <div><strong><?= number_format(count($courses)) ?></strong><span>Learning paths</span></div>
            <div><strong><?= number_format(count($languageNames)) ?></strong><span>Coding workspaces</span></div>
            <div><strong><?= $registrationOpen ? 'Open' : 'Invite' ?></strong><span>Account access</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container help-layout">
        <nav class="panel help-nav" aria-label="Help topics">
            <span class="eyebrow">Topics</span>
            <a href="#getting-started">Getting started</a>
            <a href="#learning">Learning paths and lessons</a>
            <a href="#code-lab">Code Lab</a>
            <a href="#projects">Projects and saving</a>
            <a href="#progress">Progress and account</a>
            <a href="#troubleshooting">Troubleshooting</a>
            <?php if ($supportEmail !== ''): ?><a href="#support">Contact support</a><?php endif; ?>
        </nav>

        <div class="faq-stack">
            <section id="getting-started">
                <span class="eyebrow">Getting started</span>
                <?php if (!$user): ?>
                    <details open>
                        <summary>How do I access the platform?</summary>
                        <p>Use the Sign in page with your username or email address and password. <?= $registrationOpen ? 'New learners may create an account from the registration page.' : 'New accounts are currently provided by the organisation.' ?></p>
                    </details>
                <?php else: ?>
                    <details open>
                        <summary>Where should I begin after signing in?</summary>
                        <p>Open your workspace to view your next lesson, announcements and recent activity. Use Learning paths to choose a course, or open Code Lab to continue a project.</p>
                    </details>
                <?php endif; ?>
                <details>
                    <summary>What should I do if I cannot sign in?</summary>
                    <p>Check the spelling of your username or email address, confirm that Caps Lock is off, and try again carefully. Do not share your password with other learners.</p>
                </details>
            </section>

            <section id="learning">
                <span class="eyebrow">Learning paths and lessons</span>
                <details open>
                    <summary>Which learning paths are available?</summary>
                    <?php if ($courses): ?>
                        <p>The currently published paths are <?= e(implode(', ', array_map(static fn (array $course): string => (string) $course['title'], array_slice($courses, 0, 8)))) ?><?= count($courses) > 8 ? ', and more' : '' ?>.</p>
                    <?php else: ?>
                        <p>Learning paths will appear here when they are published.</p>
                    <?php endif; ?>
                </details>
                <details>
                    <summary>How do I start and complete a lesson?</summary>
                    <p>Open a learning path, select a lesson and work through the explanation and practical activity. Complete the quiz at the end. Your best result and lesson status are saved to your account.</p>
                </details>
                <details>
                    <summary>Can I repeat a lesson or quiz?</summary>
                    <p>Yes. Reopen the lesson whenever you need more practice. New quiz attempts help you improve while your best result remains available in Progress.</p>
                </details>
            </section>

            <section id="code-lab">
                <span class="eyebrow">Code Lab</span>
                <details open>
                    <summary>Which coding workspaces can I use?</summary>
                    <p><?= $languageNames ? e(implode(', ', $languageNames)) : 'Available workspaces are shown in the language selector inside Code Lab.' ?>.</p>
                </details>
                <details>
                    <summary>How do I run a program?</summary>
                    <p>Select a language, write or edit the code, then press Run. Output and helpful error messages appear in the output area. Correct the code and run it again as often as needed.</p>
                </details>
                <details>
                    <summary>My program asks for a name or another value. What should I do?</summary>
                    <p>Press Input, enter one answer per line, close the input panel and run the program again. A program with two input questions normally needs two lines.</p>
                </details>
                <details>
                    <summary>Why does a program show an error?</summary>
                    <p>Errors usually identify a line or instruction that needs attention. Check spelling, punctuation, brackets and variable names. Make one correction at a time, then run the program again.</p>
                </details>
            </section>

            <section id="projects">
                <span class="eyebrow">Projects and saving</span>
                <details open>
                    <summary>How do I save my work?</summary>
                    <p>Give the project a clear title and press Save. Open My projects later to continue from the saved version.</p>
                </details>
                <details>
                    <summary>Can a project contain more than one file?</summary>
                    <p>Workspaces that support multiple files show the project files in the editor. Choose the file you need before editing, and save the project after making changes.</p>
                </details>
                <details>
                    <summary>What happens if I close the page before saving?</summary>
                    <p>Unsaved changes may be lost. Save after completing an important step and before leaving Code Lab.</p>
                </details>
            </section>

            <section id="progress">
                <span class="eyebrow">Progress and account</span>
                <details open>
                    <summary>Where can I see my results?</summary>
                    <p>Open Progress to view completed lessons, quiz results, achievements and overall learning activity.</p>
                </details>
                <details>
                    <summary>How do I update my account information?</summary>
                    <p>Open your profile from the top-right account menu. Review the available fields, make the required changes and save them.</p>
                </details>
                <details>
                    <summary>How do I sign out safely?</summary>
                    <p>Use Sign out in the navigation menu, especially on a shared computer or school device.</p>
                </details>
            </section>

            <section id="troubleshooting">
                <span class="eyebrow">Troubleshooting</span>
                <details open>
                    <summary>The page is not responding. What should I try?</summary>
                    <p>Check the internet connection, wait a few seconds and refresh the page once. Repeatedly pressing Run or Save can create unnecessary duplicate requests.</p>
                </details>
                <details>
                    <summary>My latest work is not visible.</summary>
                    <p>Open My projects and confirm that you selected the correct project. Refresh the page after saving. When using a shared device, confirm that you are signed in to your own account.</p>
                </details>
                <details>
                    <summary>The coding workspace is still loading.</summary>
                    <p>Some coding workspaces need an internet connection before they are ready. Allow the page to finish loading, then press Run once.</p>
                </details>
            </section>

            <?php if ($supportEmail !== ''): ?>
                <section id="support">
                    <span class="eyebrow">Contact support</span>
                    <div class="panel">
                        <h2>Still need help?</h2>
                        <p>Describe what you were trying to do, the page you were using and the message shown on screen. Do not include your password.</p>
                        <a class="button button-secondary" href="mailto:<?= e($supportEmail) ?>"><?= icon('mail') ?>Email support</a>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>
