<?php
require_once __DIR__ . '/app/bootstrap.php';
if (Auth::check()) {
    redirect('dashboard.php');
}
$pageTitle = 'Programming made playful';
$bodyClass = 'home-page';
require base_path('partials/header.php');
?>
<section class="hero">
    <div class="hero-orb hero-orb-one" aria-hidden="true"></div>
    <div class="hero-orb hero-orb-two" aria-hidden="true"></div>
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><span aria-hidden="true">★</span> Made for curious young minds</span>
            <h1>Programming becomes easier when children can <span>see ideas come alive.</span></h1>
            <p>CodeMwana turns algorithms, variables, decisions and loops into short stories, visual challenges and safe coding experiments.</p>
            <div class="hero-actions">
                <a class="button button-large" href="<?= e(url('register.php')) ?>">Create a free learner account <span aria-hidden="true">→</span></a>
                <a class="button button-large button-secondary" href="<?= e(url('login.php')) ?>">I already have an account</a>
            </div>
            <div class="trust-row" aria-label="Key benefits">
                <span><strong>No adverts</strong><small>Focused learning</small></span>
                <span><strong>Low data</strong><small>Works on mobile</small></span>
                <span><strong>Safe code</strong><small>No unrestricted execution</small></span>
            </div>
        </div>
        <div class="hero-visual" aria-label="Example CodeMwana coding activity">
            <div class="code-card floating-card">
                <div class="code-card-top">
                    <span class="window-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                    <strong>My first program</strong>
                    <span class="code-status">Ready</span>
                </div>
                <pre><code><span class="code-keyword">SAY</span> <span class="code-string">"Hello, Zambia!"</span>
<span class="code-keyword">SET</span> stars = <span class="code-number">3</span>
<span class="code-keyword">REPEAT</span> stars
  <span class="code-keyword">SAY</span> <span class="code-string">"I can code!"</span>
<span class="code-keyword">END</span></code></pre>
                <div class="code-output"><span aria-hidden="true">▶</span> Hello, Zambia!<br>I can code!<br>I can code!<br>I can code!</div>
            </div>
            <div class="mini-card mini-card-left"><span aria-hidden="true">🏅</span><strong>New badge!</strong><small>First Steps</small></div>
            <div class="mini-card mini-card-right"><span aria-hidden="true">🔥</span><strong>3-day streak</strong><small>Keep learning</small></div>
        </div>
    </div>
</section>

<section class="section stats-strip" aria-label="Platform highlights">
    <div class="container stats-grid">
        <div><strong>3</strong><span>guided learning paths</span></div>
        <div><strong>10+</strong><span>interactive activities</span></div>
        <div><strong>100%</strong><span>responsive layout</span></div>
        <div><strong>0</strong><span>public chats or adverts</span></div>
    </div>
</section>

<section class="section" id="features">
    <div class="container">
        <div class="section-heading centered">
            <span class="eyebrow">Learning by doing</span>
            <h2>More than reading about code</h2>
            <p>Each feature is designed to move a learner from understanding an idea to applying it independently.</p>
        </div>
        <div class="feature-grid">
            <article class="feature-card accent-purple">
                <div class="feature-icon" aria-hidden="true">🧩</div>
                <h3>Small, clear lessons</h3>
                <p>Concepts are broken into short explanations, worked examples and one focused challenge at a time.</p>
            </article>
            <article class="feature-card accent-orange">
                <div class="feature-icon" aria-hidden="true">💻</div>
                <h3>Safe MwanaCode lab</h3>
                <p>A purpose-built beginner language teaches sequence, variables, conditions and loops without running arbitrary scripts.</p>
            </article>
            <article class="feature-card accent-green">
                <div class="feature-icon" aria-hidden="true">📈</div>
                <h3>Progress that persists</h3>
                <p>Lesson completion, quiz scores, saved projects, points and badges are stored in the database across sessions.</p>
            </article>
            <article class="feature-card accent-blue">
                <div class="feature-icon" aria-hidden="true">👩🏾‍🏫</div>
                <h3>Teacher overview</h3>
                <p>Teachers can identify active learners, completion levels and areas where additional classroom support is needed.</p>
            </article>
            <article class="feature-card accent-pink">
                <div class="feature-icon" aria-hidden="true">🏆</div>
                <h3>Positive motivation</h3>
                <p>Badges reward meaningful milestones, while the leaderboard uses learning points rather than paid advantages.</p>
            </article>
            <article class="feature-card accent-yellow">
                <div class="feature-icon" aria-hidden="true">📱</div>
                <h3>Built for limited data</h3>
                <p>No heavy frameworks or videos are required. Pages remain usable on phones, tablets and school computers.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section-soft" id="how-it-works">
    <div class="container split-section">
        <div>
            <span class="eyebrow">A simple learning cycle</span>
            <h2>Understand. Try. Check. Create.</h2>
            <p>CodeMwana uses a mastery-focused flow. Learners first meet the concept, practise with guidance, check understanding and then create something of their own.</p>
            <ol class="step-list">
                <li><span>1</span><div><strong>Choose a path</strong><p>Begin with computational thinking, MwanaCode or web creation.</p></div></li>
                <li><span>2</span><div><strong>Complete a lesson</strong><p>Read a child-friendly example and attempt the coding task.</p></div></li>
                <li><span>3</span><div><strong>Take a quick quiz</strong><p>Immediate feedback explains why an answer is correct.</p></div></li>
                <li><span>4</span><div><strong>Save a project</strong><p>Return later and improve the program across multiple sessions.</p></div></li>
            </ol>
        </div>
        <div class="learning-map" aria-label="Learning path illustration">
            <div class="map-card completed"><span>✓</span><div><strong>Algorithms</strong><small>Completed</small></div></div>
            <div class="map-line"></div>
            <div class="map-card active"><span>2</span><div><strong>Variables</strong><small>Current lesson</small></div></div>
            <div class="map-line"></div>
            <div class="map-card"><span>3</span><div><strong>Decisions</strong><small>Up next</small></div></div>
            <div class="map-line"></div>
            <div class="map-card"><span>4</span><div><strong>Loops</strong><small>Locked</small></div></div>
        </div>
    </div>
</section>

<section class="section" id="safety">
    <div class="container safety-panel">
        <div class="safety-illustration" aria-hidden="true">🛡️</div>
        <div>
            <span class="eyebrow">Child-centred by design</span>
            <h2>A learning space without public messaging or distracting adverts</h2>
            <p>The system collects only information needed for learning accounts. Learners cannot contact strangers, post public comments or run unrestricted browser code. Teachers see academic progress, not private conversations.</p>
            <a class="text-link" href="<?= e(url('privacy.php')) ?>">Read the privacy and safety approach <span aria-hidden="true">→</span></a>
        </div>
    </div>
</section>

<section class="section cta-section">
    <div class="container cta-card">
        <div><span class="eyebrow">Ready for the first command?</span><h2>Write “Hello, Zambia!” and begin building.</h2></div>
        <a class="button button-large button-light" href="<?= e(url('register.php')) ?>">Start learning now</a>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>
