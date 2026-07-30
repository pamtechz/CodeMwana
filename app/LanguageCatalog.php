<?php

declare(strict_types=1);

final class LanguageCatalog
{
    public static function definitions(): array
    {
        return [
            [
                'slug' => 'html',
                'name' => 'HTML',
                'short_name' => 'HTML',
                'category' => 'Web',
                'description' => 'Build semantic web pages and preview them instantly in a sandboxed browser frame.',
                'editor_mode' => 'html',
                'execution_mode' => 'browser',
                'runner_language' => null,
                'runner_version' => null,
                'main_file' => 'index.html',
                'colour' => '#e34f26',
                'sort_order' => 10,
                'files' => [
                    'index.html' => "<!doctype html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n  <title>My CodeMwana page</title>\n  <link rel=\"stylesheet\" href=\"styles.css\">\n</head>\n<body>\n  <main class=\"card\">\n    <p class=\"eyebrow\">CodeMwana Web Lab</p>\n    <h1>Hello, young creator!</h1>\n    <p>Edit the HTML, CSS and JavaScript files, then run the project.</p>\n    <button id=\"welcome-button\">Try the button</button>\n    <p id=\"message\" aria-live=\"polite\"></p>\n  </main>\n  <script src=\"script.js\"></script>\n</body>\n</html>",
                    'styles.css' => ":root { font-family: system-ui, sans-serif; color: #172033; background: #f4f2ff; }\nbody { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; }\n.card { width: min(100%, 520px); padding: 32px; border-radius: 24px; background: white; box-shadow: 0 20px 60px rgba(40, 32, 95, .15); }\n.eyebrow { color: #5b4bdb; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; }\nbutton { border: 0; border-radius: 12px; padding: 12px 18px; background: #5b4bdb; color: white; font-weight: 750; cursor: pointer; }",
                    'script.js' => "const button = document.querySelector('#welcome-button');\nconst message = document.querySelector('#message');\n\nbutton.addEventListener('click', () => {\n  message.textContent = 'Your web project is working!';\n  console.log('Button clicked successfully.');\n});",
                ],
            ],
            [
                'slug' => 'css',
                'name' => 'CSS',
                'short_name' => 'CSS',
                'category' => 'Web',
                'description' => 'Practise responsive layout, colour, spacing and component styling with a live preview.',
                'editor_mode' => 'css',
                'execution_mode' => 'browser',
                'runner_language' => null,
                'runner_version' => null,
                'main_file' => 'styles.css',
                'colour' => '#1572b6',
                'sort_order' => 20,
                'files' => [
                    'index.html' => "<!doctype html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n  <title>CSS practice</title>\n  <link rel=\"stylesheet\" href=\"styles.css\">\n</head>\n<body>\n  <section class=\"profile-card\">\n    <div class=\"avatar\">CM</div>\n    <div>\n      <p class=\"label\">CSS challenge</p>\n      <h1>Responsive profile card</h1>\n      <p>Change the colours, spacing and layout. Resize the preview to test it.</p>\n    </div>\n  </section>\n</body>\n</html>",
                    'styles.css' => "* { box-sizing: border-box; }\nbody { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; font-family: system-ui, sans-serif; background: linear-gradient(135deg, #ece9ff, #f8fbff); color: #172033; }\n.profile-card { width: min(100%, 680px); display: grid; grid-template-columns: 96px 1fr; gap: 24px; align-items: center; padding: clamp(22px, 5vw, 44px); border: 1px solid #ddd8ff; border-radius: 28px; background: rgba(255, 255, 255, .92); box-shadow: 0 24px 70px rgba(45, 39, 92, .16); }\n.avatar { display: grid; place-items: center; width: 96px; aspect-ratio: 1; border-radius: 28px; background: #5b4bdb; color: white; font-size: 1.5rem; font-weight: 900; }\n.label { margin: 0; color: #5b4bdb; font-weight: 850; text-transform: uppercase; letter-spacing: .1em; }\nh1 { margin: 8px 0; }\n@media (max-width: 520px) { .profile-card { grid-template-columns: 1fr; text-align: center; } .avatar { margin-inline: auto; } }",
                ],
            ],
            [
                'slug' => 'javascript',
                'name' => 'JavaScript',
                'short_name' => 'JS',
                'category' => 'Web',
                'description' => 'Run JavaScript in an isolated browser frame with captured console output and errors.',
                'editor_mode' => 'javascript',
                'execution_mode' => 'browser-console',
                'runner_language' => 'javascript',
                'runner_version' => '*',
                'main_file' => 'main.js',
                'colour' => '#d5b500',
                'sort_order' => 30,
                'files' => [
                    'main.js' => <<<'JS'
const learners = [
  { name: 'Chanda', score: 72 },
  { name: 'Mwamba', score: 88 },
  { name: 'Thandiwe', score: 65 },
];

const average = learners.reduce((total, learner) => total + learner.score, 0) / learners.length;

console.log('Learner scores');
learners.forEach((learner) => console.log(`${learner.name}: ${learner.score}%`));
console.log(`Average: ${average.toFixed(1)}%`);
JS,
                ],
            ],
            [
                'slug' => 'python',
                'name' => 'Python',
                'short_name' => 'PY',
                'category' => 'General purpose',
                'description' => 'Write Python programs with standard input and run them through the configured sandbox service.',
                'editor_mode' => 'python',
                'execution_mode' => 'remote',
                'runner_language' => 'python',
                'runner_version' => '*',
                'main_file' => 'main.py',
                'colour' => '#3776ab',
                'sort_order' => 40,
                'files' => [
                    'main.py' => "def grade_message(score: int) -> str:\n    if score >= 75:\n        return 'Excellent progress'\n    if score >= 50:\n        return 'Good work—keep practising'\n    return 'Review the lesson and try again'\n\nname = input().strip() or 'Learner'\nscore = 78\nprint(f'{name}: {grade_message(score)} ({score}%)')",
                ],
            ],
            [
                'slug' => 'php',
                'name' => 'PHP',
                'short_name' => 'PHP',
                'category' => 'Server side',
                'description' => 'Practise PHP syntax and server-side logic in an isolated remote execution container.',
                'editor_mode' => 'php',
                'execution_mode' => 'remote',
                'runner_language' => 'php',
                'runner_version' => '*',
                'main_file' => 'main.php',
                'colour' => '#777bb4',
                'sort_order' => 50,
                'files' => [
                    'main.php' => <<<'PHP_CODE'
<?php

declare(strict_types=1);

$subjects = [
    'Computer Studies' => 84,
    'Mathematics' => 76,
    'English' => 71,
];

$average = array_sum($subjects) / count($subjects);

foreach ($subjects as $subject => $mark) {
    echo $subject . ': ' . $mark . "%\n";
}

echo 'Average: ' . number_format($average, 1) . "%\n";
PHP_CODE,
                ],
            ],
            [
                'slug' => 'react',
                'name' => 'React',
                'short_name' => 'React',
                'category' => 'Web framework',
                'description' => 'Build an interactive React component with JSX and preview it in a sandboxed browser frame.',
                'editor_mode' => 'jsx',
                'execution_mode' => 'react-preview',
                'runner_language' => null,
                'runner_version' => null,
                'main_file' => 'App.jsx',
                'colour' => '#087ea4',
                'sort_order' => 60,
                'files' => [
                    'App.jsx' => "function App() {\n  const [count, setCount] = React.useState(0);\n\n  return (\n    <main className=\"app-card\">\n      <p className=\"eyebrow\">React Code Lab</p>\n      <h1>Build with components</h1>\n      <p>You have clicked the button <strong>{count}</strong> times.</p>\n      <button onClick={() => setCount((value) => value + 1)}>Increase count</button>\n    </main>\n  );\n}",
                    'styles.css' => "* { box-sizing: border-box; }\nbody { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; font-family: system-ui, sans-serif; background: #eef8fc; color: #172033; }\n.app-card { width: min(100%, 520px); padding: 36px; border-radius: 26px; background: white; box-shadow: 0 22px 70px rgba(8, 126, 164, .18); }\n.eyebrow { color: #087ea4; font-weight: 850; text-transform: uppercase; letter-spacing: .1em; }\nbutton { border: 0; border-radius: 12px; padding: 12px 18px; background: #087ea4; color: white; font-weight: 800; cursor: pointer; }",
                ],
            ],
            [
                'slug' => 'nextjs',
                'name' => 'Next.js',
                'short_name' => 'Next',
                'category' => 'Web framework',
                'description' => 'Preview an App Router page component. Server actions and route handlers require a full Node deployment.',
                'editor_mode' => 'jsx',
                'execution_mode' => 'next-preview',
                'runner_language' => null,
                'runner_version' => null,
                'main_file' => 'app/page.jsx',
                'colour' => '#111111',
                'sort_order' => 70,
                'files' => [
                    'app/page.jsx' => "export default function Page() {\n  const topics = ['Routing', 'Components', 'Data fetching'];\n\n  return (\n    <main className=\"page-shell\">\n      <p className=\"eyebrow\">Next.js App Router</p>\n      <h1>My learning dashboard</h1>\n      <p>This browser preview renders the page component without server-only Next.js features.</p>\n      <ul>{topics.map((topic) => <li key={topic}>{topic}</li>)}</ul>\n      <Link href=\"#next-step\">Continue learning</Link>\n    </main>\n  );\n}",
                    'app/globals.css' => "* { box-sizing: border-box; }\nbody { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; font-family: Arial, sans-serif; background: #f5f5f5; color: #111; }\n.page-shell { width: min(100%, 640px); padding: 40px; border: 1px solid #ddd; border-radius: 22px; background: white; }\n.eyebrow { font-size: .75rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }\nli { margin-block: 8px; }\na { display: inline-flex; margin-top: 12px; padding: 11px 16px; border-radius: 10px; background: #111; color: white; text-decoration: none; font-weight: 750; }",
                ],
            ],
            [
                'slug' => 'go',
                'name' => 'Go',
                'short_name' => 'Go',
                'category' => 'Compiled',
                'description' => 'Compile and run concise Go programs through the configured isolated execution service.',
                'editor_mode' => 'go',
                'execution_mode' => 'remote',
                'runner_language' => 'go',
                'runner_version' => '*',
                'main_file' => 'main.go',
                'colour' => '#00add8',
                'sort_order' => 80,
                'files' => [
                    'main.go' => "package main\n\nimport \"fmt\"\n\nfunc main() {\n\tmarks := []int{72, 84, 91}\n\ttotal := 0\n\tfor _, mark := range marks {\n\t\ttotal += mark\n\t}\n\taverage := float64(total) / float64(len(marks))\n\tfmt.Printf(\"Average mark: %.1f%%\\n\", average)\n}",
                ],
            ],
            [
                'slug' => 'c',
                'name' => 'C',
                'short_name' => 'C',
                'category' => 'Compiled',
                'description' => 'Learn variables, functions and memory-conscious programming in a remote compiler sandbox.',
                'editor_mode' => 'c',
                'execution_mode' => 'remote',
                'runner_language' => 'c',
                'runner_version' => '*',
                'main_file' => 'main.c',
                'colour' => '#5c6bc0',
                'sort_order' => 90,
                'files' => [
                    'main.c' => "#include <stdio.h>\n\nint main(void) {\n    int marks[] = {68, 74, 82, 91};\n    int count = sizeof(marks) / sizeof(marks[0]);\n    int total = 0;\n\n    for (int i = 0; i < count; i++) {\n        total += marks[i];\n    }\n\n    printf(\"Average mark: %.1f%%\\n\", (double) total / count);\n    return 0;\n}",
                ],
            ],
            [
                'slug' => 'cpp',
                'name' => 'C++',
                'short_name' => 'C++',
                'category' => 'Compiled',
                'description' => 'Use modern C++ collections, loops and functions in an isolated remote compiler sandbox.',
                'editor_mode' => 'cpp',
                'execution_mode' => 'remote',
                'runner_language' => 'c++',
                'runner_version' => '*',
                'main_file' => 'main.cpp',
                'colour' => '#00599c',
                'sort_order' => 100,
                'files' => [
                    'main.cpp' => "#include <iostream>\n#include <numeric>\n#include <vector>\n\nint main() {\n    const std::vector<int> marks{78, 85, 92};\n    const int total = std::accumulate(marks.begin(), marks.end(), 0);\n    const double average = static_cast<double>(total) / marks.size();\n\n    std::cout << \"Average mark: \" << average << \"%\\n\";\n    return 0;\n}",
                ],
            ],
        ];
    }


    public static function all(bool $includeGuided = true): array
    {
        $languages = [];
        if (class_exists('Database') && Database::tableExists('programming_languages')) {
            foreach (Database::fetchAll('SELECT * FROM programming_languages WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
                $row['files'] = self::decodeFiles((string) ($row['starter_files_json'] ?? ''));
                $languages[] = $row;
            }
        } else {
            $languages = self::definitions();
        }
        return $includeGuided ? [self::guided(), ...$languages] : $languages;
    }

    public static function find(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        if ($slug === 'mwanacode') return self::guided();
        foreach (self::all(false) as $language) {
            if (($language['slug'] ?? '') === $slug) return $language;
        }
        return null;
    }

    public static function workspace(array $language): array
    {
        $files = $language['files'] ?? self::decodeFiles((string) ($language['starter_files_json'] ?? ''));
        $workspace = [];
        foreach ((array) $files as $name => $content) {
            $clean = self::cleanFileName((string) $name);
            if ($clean !== '') $workspace[$clean] = (string) $content;
        }
        if (!$workspace) $workspace[(string) ($language['main_file'] ?? 'main.txt')] = '';
        return $workspace;
    }

    public static function normalizeWorkspace(array $files, array $language): array
    {
        $normalised = [];
        foreach ($files as $name => $content) {
            $clean = self::cleanFileName((string) $name);
            if ($clean === '' || mb_strlen($clean) > 120) continue;
            $normalised[$clean] = mb_substr((string) $content, 0, 60000);
            if (count($normalised) >= 12) break;
        }
        if (!$normalised) return self::workspace($language);
        $main = (string) ($language['main_file'] ?? '');
        if ($main !== '' && !array_key_exists($main, $normalised)) {
            $normalised = [$main => '', ...$normalised];
        }
        return $normalised;
    }

    private static function decodeFiles(string $json): array
    {
        if ($json === '') return [];
        $files = json_decode($json, true);
        return is_array($files) ? $files : [];
    }

    private static function cleanFileName(string $name): string
    {
        $name = str_replace('\\', '/', trim($name));
        $parts = array_values(array_filter(explode('/', $name), static fn (string $part): bool => $part !== '' && $part !== '.' && $part !== '..'));
        $clean = implode('/', $parts);
        return preg_match('/^[A-Za-z0-9._\-\/]+$/', $clean) ? $clean : '';
    }

    public static function guided(): array
    {
        return [
            'slug' => 'mwanacode',
            'name' => 'MwanaCode',
            'short_name' => 'Mwana',
            'category' => 'Guided beginner mode',
            'description' => 'A controlled beginner language for variables, decisions, loops and turtle drawing.',
            'editor_mode' => 'mwanacode',
            'execution_mode' => 'guided',
            'runner_language' => null,
            'runner_version' => null,
            'main_file' => 'main.mwana',
            'colour' => '#6c5ce7',
            'sort_order' => 0,
            'files' => [
                'main.mwana' => "SAY \"Welcome to CodeMwana\"\nSET goal = \"Build useful ideas\"\nSAY goal",
            ],
        ];
    }
}
