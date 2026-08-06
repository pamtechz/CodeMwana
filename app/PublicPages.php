<?php

declare(strict_types=1);

final class PublicPages
{
    private static array $cache = [];

    public static function defaults(): array
    {
        return [
            [
                'slug' => 'about',
                'navigation_label' => 'About',
                'title' => 'About CodeMwana',
                'eyebrow' => 'About the platform',
                'hero_title' => 'A clear path from first idea to working code.',
                'hero_text' => 'CodeMwana brings guided learning, practical coding, quizzes, projects and progress together in one learner-friendly platform.',
                'content_html' => <<<'HTML'
<h2>Explore CodeMwana</h2>
<p>CodeMwana is a programming learning platform for children, schools and independent learners. It helps beginners understand each idea, practise it and use it in a project.</p>
<h3>About us</h3>
<p>Read why the platform was created and how it supports clear, practical learning.</p>
<h3>About the app</h3>
<p>Learn how lessons, Code Lab, quizzes, projects and progress work together.</p>
<h3>About the developers</h3>
<p>Meet the team and the principles that guide the platform’s design and development.</p>
HTML,
                'meta_description' => 'Learn what CodeMwana is, why it was created and how its learning experience supports young programmers.',
                'cta_label' => 'Read our purpose',
                'cta_url' => 'about-us.php',
                'show_in_header' => 1,
                'show_in_footer' => 1,
                'is_published' => 1,
                'sort_order' => 10,
            ],
            [
                'slug' => 'about-us',
                'navigation_label' => 'About Us',
                'title' => 'About Us',
                'eyebrow' => 'Our purpose',
                'hero_title' => 'Make the first steps in coding understandable.',
                'hero_text' => 'Programming becomes easier when learners meet one clear idea at a time and use it through practical work.',
                'content_html' => <<<'HTML'
<h2>Our purpose</h2>
<h3>Make the first steps in coding understandable</h3>
<p>Programming can feel difficult when learners meet too many new ideas at once. CodeMwana organises learning into short lessons, examples, practical challenges and quizzes so each concept can be understood before the next one begins.</p>
<h3>Learn by creating</h3>
<p>Learners do more than read explanations. They write programs, test ideas, correct errors, build projects and review the progress saved to their accounts.</p>
<h3>Support for different learning stages</h3>
<p>Beginners can start with computational thinking and guided activities before moving into widely used programming languages and web-development workspaces.</p>
<h3>Designed for schools and independent learners</h3>
<p>The platform works across phones, tablets and computers. Teachers can guide learning and review progress, while learners keep their lessons, results and projects together.</p>
<h2>Learning experience</h2>
<h3>What learners can do</h3>
<ul>
<li>Follow ordered learning paths</li>
<li>Practise concepts in Code Lab</li>
<li>Complete quizzes with feedback</li>
<li>Save and continue coding projects</li>
<li>Review progress and achievements</li>
<li>Learn on different screen sizes</li>
</ul>
<h2>Need guidance?</h2>
<p>Use the Help centre to find clear instructions for signing in, beginning lessons, running programs, providing program input and saving projects.</p>
HTML,
                'meta_description' => 'Discover CodeMwana’s purpose and its approach to understandable, practical programming education.',
                'cta_label' => 'Use the Help centre',
                'cta_url' => 'help.php',
                'show_in_header' => 0,
                'show_in_footer' => 1,
                'is_published' => 1,
                'sort_order' => 20,
            ],
            [
                'slug' => 'about-app',
                'navigation_label' => 'About the App',
                'title' => 'About the App',
                'eyebrow' => 'The learning experience',
                'hero_title' => 'Learn, practise, build and review progress in one place.',
                'hero_text' => 'The app combines structured learning paths with practical coding workspaces and saved learner progress.',
                'content_html' => <<<'HTML'
<h2>How learning works</h2>
<p>Learners choose a published learning path, complete lessons in order, practise the ideas in Code Lab and answer quizzes that provide immediate feedback.</p>
<h2>Code Lab</h2>
<p>Code Lab provides guided and mainstream programming workspaces. Learners can write programs, supply input, run code, correct errors and save projects to continue later.</p>
<h2>Progress and achievements</h2>
<p>Lesson status, quiz results, achievements and projects remain connected to the learner account so progress can continue across sessions and devices.</p>
<h2>Support for learning</h2>
<p>Teachers can review learner progress and provide guidance. Learners can use the Help centre whenever they need instructions for a platform task.</p>
<h2>Responsive access</h2>
<p>The interface adapts to phones, tablets, laptops and desktop computers so learners can use the available device comfortably.</p>
HTML,
                'meta_description' => 'See how CodeMwana combines lessons, coding practice, quizzes, projects and progress tracking.',
                'cta_label' => 'Explore learning paths',
                'cta_url' => 'index.php#learning',
                'show_in_header' => 0,
                'show_in_footer' => 1,
                'is_published' => 1,
                'sort_order' => 30,
            ],
            [
                'slug' => 'developers',
                'navigation_label' => 'About the Developers',
                'title' => 'About the Developers',
                'eyebrow' => 'Designed and developed in Zambia',
                'hero_title' => 'Technology built around real learning needs.',
                'hero_text' => 'CodeMwana is developed by Pamtech I.T Solutions with a focus on clarity, accessibility, dependable operation and practical education.',
                'content_html' => <<<'HTML'
<h2>The development team</h2>
<p>CodeMwana is designed and developed in Zambia by Pamtech I.T Solutions. The project brings together experience in information and communication technology, education and practical software development.</p>
<h2>Our development approach</h2>
<ul>
<li>Keep the learner experience clear and age-appropriate.</li>
<li>Use responsive interfaces that work across common devices.</li>
<li>Protect accounts and learning records through responsible engineering.</li>
<li>Build features that solve real classroom and independent-learning needs.</li>
<li>Improve the platform through testing, feedback and careful maintenance.</li>
</ul>
<h2>Working with schools and organisations</h2>
<p>The platform can be configured for a school, learning centre or education programme while keeping the same structured learning experience.</p>
HTML,
                'meta_description' => 'Learn about the Zambia-based team and development principles behind CodeMwana.',
                'cta_label' => 'Contact us',
                'cta_url' => 'contact.php',
                'show_in_header' => 0,
                'show_in_footer' => 1,
                'is_published' => 1,
                'sort_order' => 40,
            ],
            [
                'slug' => 'contact',
                'navigation_label' => 'Contact Us',
                'title' => 'Contact Us',
                'eyebrow' => 'Get assistance',
                'hero_title' => 'Reach the team responsible for {{site_name}}.',
                'hero_text' => 'Contact us about account access, learning support, school use, partnerships or general enquiries.',
                'content_html' => <<<'HTML'
<h2>How to contact us</h2>
<p>Email: <strong>{{support_email}}</strong></p>
<p>Organisation: <strong>{{organisation_name}}</strong></p>
<h2>Before sending a support request</h2>
<ul>
<li>Describe what you were trying to do.</li>
<li>Include the page or feature you were using.</li>
<li>Copy the message shown on screen when relevant.</li>
<li>Never include your password in a message.</li>
</ul>
<h2>Learning enquiries</h2>
<p>For questions about lessons, projects or progress, include the learning path or programming language involved so the support team can respond clearly.</p>
HTML,
                'meta_description' => 'Contact the team responsible for CodeMwana for learning, account and organisation enquiries.',
                'cta_label' => 'Email support',
                'cta_url' => 'mailto:{{support_email}}',
                'show_in_header' => 1,
                'show_in_footer' => 1,
                'is_published' => 1,
                'sort_order' => 50,
            ],
            [
                'slug' => 'privacy',
                'navigation_label' => 'Privacy and Safety',
                'title' => 'Privacy and Safety',
                'eyebrow' => 'Focused learning',
                'hero_title' => 'Learning records are used to support the learner experience.',
                'hero_text' => 'The platform limits unnecessary public social features and keeps account, progress and project information within authorised areas.',
                'content_html' => <<<'HTML'
<h2>Information used by the platform</h2>
<p>{{site_name}} keeps the account information and learning records needed to provide sign-in, lessons, quiz results, achievements, projects and authorised reports.</p>
<h2>How information is used</h2>
<p>Account information supports access to the platform. Learning records help learners continue across sessions and help authorised teachers provide appropriate support.</p>
<h2>Safety by design</h2>
<p>The platform does not provide public chat, direct messaging, public profile search, advertising, location tracking or public photo sharing.</p>
<h2>Account responsibility</h2>
<p>Users should keep passwords private, sign out on shared devices and report unexpected account activity to the organisation operating the platform.</p>
<h2>Organisation responsibilities</h2>
<p>The organisation operating {{site_name}} is responsible for appropriate account administration, access control, retention practices and support for its learners.</p>
HTML,
                'meta_description' => 'Understand how CodeMwana uses learning information and supports privacy and safer account use.',
                'cta_label' => 'Contact support',
                'cta_url' => 'contact.php',
                'show_in_header' => 0,
                'show_in_footer' => 1,
                'is_published' => 1,
                'sort_order' => 60,
            ],
            [
                'slug' => 'help',
                'navigation_label' => 'Help Centre',
                'title' => 'Help Centre',
                'eyebrow' => 'Find the next step',
                'hero_title' => 'Learn how to use {{site_name}}.',
                'hero_text' => 'Use these guides for account access, lessons, Code Lab, projects, progress and common problems.',
                'content_html' => <<<'HTML'
<h2>Getting started</h2>
<details open><summary>How do I access the platform?</summary><p>Use the Sign in page with your username or email address and password. {{registration_message}}</p></details>
<details><summary>What should I do if I cannot sign in?</summary><p>Check the spelling of your username or email address, confirm that Caps Lock is off and try again carefully. Do not share your password with other learners.</p></details>
<h2>Learning paths and lessons</h2>
<details><summary>Which learning paths are available?</summary><p>{{course_list}}</p></details>
<details><summary>How do I complete a lesson?</summary><p>Open a learning path, select a lesson, work through the explanation and practical activity, then complete the quiz. Your best result and lesson status are saved.</p></details>
<details><summary>Can I repeat a lesson or quiz?</summary><p>Yes. Reopen the lesson whenever you need more practice. New attempts help you improve while your best result remains available in Progress.</p></details>
<h2>Code Lab</h2>
<details><summary>Which coding workspaces can I use?</summary><p>{{language_list}}</p></details>
<details><summary>How do I run a program?</summary><p>Select a language, write or edit the code, then press Run. Output and helpful error messages appear in the output area.</p></details>
<details><summary>My program asks for a value. What should I do?</summary><p>Press Input, enter one answer per line, close the input panel and run the program again. A program with two input questions normally needs two lines.</p></details>
<h2>Projects and progress</h2>
<details><summary>How do I save my work?</summary><p>Give the project a clear title and press Save. Open My projects later to continue from the saved version.</p></details>
<details><summary>Where can I see my results?</summary><p>Open Progress to view completed lessons, quiz results, achievements and overall learning activity.</p></details>
<h2>Troubleshooting</h2>
<details><summary>The page is not responding. What should I try?</summary><p>Check the internet connection, wait a few seconds and refresh the page once. Repeatedly pressing Run or Save can create duplicate requests.</p></details>
<details><summary>The coding workspace is still loading.</summary><p>Some coding workspaces need an internet connection before they are ready. Allow the page to finish loading, then press Run once.</p></details>
HTML,
                'meta_description' => 'Find clear instructions for signing in, learning, running programs, saving projects and reviewing progress.',
                'cta_label' => 'Contact support',
                'cta_url' => 'contact.php',
                'show_in_header' => 1,
                'show_in_footer' => 1,
                'is_published' => 1,
                'sort_order' => 70,
            ],
        ];
    }

    public static function seedDefaults(): void
    {
        if (!Database::tableExists('public_pages')) return;

        foreach (self::defaults() as $page) {
            $existing = Database::fetch('SELECT id FROM public_pages WHERE slug = ?', [$page['slug']]);
            if ($existing) continue;

            Database::query(
                'INSERT INTO public_pages (slug, navigation_label, title, eyebrow, hero_title, hero_text, content_html, meta_description, cta_label, cta_url, show_in_header, show_in_footer, is_published, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $page['slug'], $page['navigation_label'], $page['title'], $page['eyebrow'],
                    $page['hero_title'], $page['hero_text'], sanitize_public_html($page['content_html']),
                    $page['meta_description'], $page['cta_label'], $page['cta_url'],
                    $page['show_in_header'], $page['show_in_footer'], $page['is_published'], $page['sort_order'],
                ]
            );
        }
        self::reset();
    }

    public static function all(bool $publishedOnly = false): array
    {
        if (!Database::tableExists('public_pages')) return [];
        $key = $publishedOnly ? 'published' : 'all';
        if (isset(self::$cache[$key])) return self::$cache[$key];

        $where = $publishedOnly ? 'WHERE is_published = 1' : '';
        return self::$cache[$key] = Database::fetchAll("SELECT * FROM public_pages {$where} ORDER BY sort_order, id");
    }

    public static function find(string $slug, bool $publishedOnly = true): ?array
    {
        $slug = strtolower(trim($slug));
        if (!preg_match('/^[a-z0-9-]{2,80}$/', $slug) || !Database::tableExists('public_pages')) return null;
        $suffix = $publishedOnly ? ' AND is_published = 1' : '';
        return Database::fetch('SELECT * FROM public_pages WHERE slug = ?' . $suffix . ' LIMIT 1', [$slug]);
    }

    public static function navigation(string $location): array
    {
        if (!in_array($location, ['header', 'footer'], true) || !Database::tableExists('public_pages')) return [];
        $column = $location === 'header' ? 'show_in_header' : 'show_in_footer';
        return Database::fetchAll("SELECT slug, navigation_label, title FROM public_pages WHERE is_published = 1 AND {$column} = 1 ORDER BY sort_order, id");
    }

    public static function routeMap(): array
    {
        return [
            'about' => 'about.php',
            'about-us' => 'about-us.php',
            'about-app' => 'about-app.php',
            'developers' => 'developers.php',
            'contact' => 'contact.php',
            'privacy' => 'privacy.php',
            'help' => 'help.php',
        ];
    }

    public static function urlFor(string $slug): string
    {
        $route = self::routeMap()[strtolower(trim($slug))] ?? 'about.php';
        return url($route);
    }

    public static function resolveText(string $text): string
    {
        return strtr($text, self::tokens(false));
    }

    public static function resolveHtml(string $html): string
    {
        return strtr(sanitize_public_html($html), self::tokens(true));
    }

    public static function resolveUrl(?string $urlValue): string
    {
        $value = trim(strtr((string) $urlValue, self::tokens(false)));
        if ($value === '' || preg_match('/[\r\n]/', $value)) return '';
        if (preg_match('#^(?:https?://|mailto:|tel:)#i', $value)) return $value;
        if (str_starts_with($value, '#')) return $value;
        if (preg_match('/^[a-z0-9][a-z0-9._\/-]*(?:\?[a-z0-9%&=._-]*)?(?:#[a-z0-9._-]*)?$/i', $value)) return url($value);
        return '';
    }

    public static function reset(): void
    {
        self::$cache = [];
    }

    private static function tokens(bool $escape): array
    {
        $siteName = (string) setting('site_name', 'CodeMwana');
        $organisation = trim((string) setting('organisation_name', '')) ?: $siteName;
        $supportEmail = trim((string) setting('support_email', ''));
        $registrationOpen = (string) setting('registration_open', '1') === '1';

        try {
            $languages = array_values(array_filter(array_map(
                static fn (array $language): string => trim((string) ($language['name'] ?? '')),
                Learning::languages(true)
            )));
        } catch (Throwable) {
            $languages = [];
        }

        try {
            $courses = array_values(array_filter(array_map(
                static fn (array $course): string => trim((string) ($course['title'] ?? '')),
                Learning::courses()
            )));
        } catch (Throwable) {
            $courses = [];
        }

        $values = [
            '{{site_name}}' => $siteName,
            '{{organisation_name}}' => $organisation,
            '{{support_email}}' => $supportEmail !== '' ? $supportEmail : 'Support contact will be published here.',
            '{{language_list}}' => $languages ? implode(', ', $languages) . '.' : 'Available coding workspaces are shown in Code Lab.',
            '{{course_list}}' => $courses ? 'The currently published learning paths are ' . implode(', ', $courses) . '.' : 'Published learning paths will appear in the learning area.',
            '{{registration_message}}' => $registrationOpen
                ? 'New learners may create an account from the registration page.'
                : 'New accounts are currently provided by the organisation.',
            '{{current_year}}' => date('Y'),
        ];

        if (!$escape) return $values;
        return array_map(static fn (string $value): string => e($value), $values);
    }
}
