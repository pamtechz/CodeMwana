<?php
require_once __DIR__ . '/app/bootstrap.php';
$siteName = (string) setting('site_name', 'CodeMwana');
$supportEmail = trim((string) setting('support_email', ''));
$pageTitle = 'Privacy and safety';
$pageDescription = 'How ' . $siteName . ' uses learner information and supports safe use of the platform.';
$bodyClass = 'content-page';
require base_path('partials/header.php');
?>
<section class="content-hero">
    <div class="container">
        <span class="eyebrow">Privacy and safety</span>
        <h1>Learning information is used to support your account and progress.</h1>
        <p><?= e($siteName) ?> collects only the information needed to provide accounts, learning activities, saved work and authorised educational support.</p>
    </div>
</section>

<section class="section">
    <div class="container prose-shell">
        <article class="panel prose-panel">
            <h2>Information connected to your account</h2>
            <p>This may include your name, username, email address, age group, school or learning centre, course enrolments, lesson progress, quiz results, achievements and saved coding projects.</p>

            <h2>How the information is used</h2>
            <p>Your information helps the platform keep you signed in, remember your learning progress, save your work and show relevant learning activities. Authorised teachers may use learning records to provide guidance and support.</p>

            <h2>Safe use</h2>
            <p>Keep your password private, sign out on shared devices and avoid placing personal information inside coding projects. Do not submit home addresses, private phone numbers, passwords or other sensitive details in lessons or program input.</p>

            <h2>Sharing and visibility</h2>
            <p>Learner information is not intended for public display. Access is limited according to the responsibilities assigned to each account.</p>

            <h2>Retention and account requests</h2>
            <p>The organisation operating this platform decides how long learning records are kept and how correction or account-removal requests are handled. Contact the organisation responsible for your account when you need help with your information.</p>

            <h2>Changes to this notice</h2>
            <p>This notice may be updated when platform features or institutional requirements change. The current version shown on this page applies whenever you use the service.</p>
        </article>

        <aside class="content-side">
            <section class="panel">
                <span class="eyebrow">Remember</span>
                <h2>Protect your account</h2>
                <ul class="check-list">
                    <li>Use a password that others cannot guess</li>
                    <li>Do not share sign-in details</li>
                    <li>Sign out on shared computers</li>
                    <li>Keep personal details out of project code</li>
                    <li>Report unexpected account activity</li>
                </ul>
            </section>
            <?php if ($supportEmail !== ''): ?>
                <section class="panel">
                    <span class="eyebrow">Privacy questions</span>
                    <h2>Contact the organisation</h2>
                    <p>Use the support address for questions about your account or learning information. Never include your password in an email.</p>
                    <a class="button button-secondary" href="mailto:<?= e($supportEmail) ?>">Email support</a>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>
