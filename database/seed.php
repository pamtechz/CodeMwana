<?php

declare(strict_types=1);

function seed_database(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    if ($count > 0) return;

    $pdo->beginTransaction();
    try {
        $course = $pdo->prepare('INSERT INTO courses (title, slug, short_description, description, icon, colour, level, estimated_time, sort_order, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
        $courses = [
            ['Think Like a Programmer','think-like-a-programmer','Learn algorithms, decomposition and debugging.','Build the thinking skills behind every program.','🧠','#6546d7','Foundation','1 hour',1],
            ['MwanaCode Basics','mwanacode-basics','Practise variables, decisions, loops and turtle drawing.','Write safe beginner programs using the MwanaCode language.','💻','#ef8c45','Beginner','2 hours',2],
            ['Web Creator Starter','web-creator-starter','Discover HTML, CSS and JavaScript.','Understand how structure, presentation and behaviour work together.','🌐','#278d69','Beginner web','1 hour',3],
        ];
        $courseIds = [];
        foreach ($courses as $row) { $course->execute($row); $courseIds[$row[1]] = (int) $pdo->lastInsertId(); }

        $lesson = $pdo->prepare('INSERT INTO lessons (course_id,title,slug,summary,learning_objective,concepts,vocabulary,content_html,challenge_text,starter_code,expected_output,icon,difficulty,duration_minutes,sort_order,is_published) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)');
        $lessons = [
            ['think-like-a-programmer','Algorithm Adventures','algorithm-adventures','Turn an everyday task into exact steps.','Describe an algorithm as ordered instructions.','Algorithms, sequence','algorithm, instruction, sequence','<h2>Computers need exact steps</h2><p>An <strong>algorithm</strong> is an ordered set of instructions. A good algorithm has a clear beginning, clear actions and a clear end.</p><h2>Example</h2><ol><li>Ask for a name.</li><li>Store the name.</li><li>Display a greeting.</li></ol>','Display three steps for preparing for school.','SAY "1. Pack books"\nSAY "2. Wear uniform"\nSAY "3. Leave for school"','Three numbered steps','🧭','beginner',15,1],
            ['think-like-a-programmer','Debugging Detective','debugging-detective','Find and correct programming mistakes.','Apply a step-by-step debugging process.','Debugging, testing','bug, debug, test','<h2>A bug is a mistake, not a defeat</h2><p>Debugging means finding, understanding and correcting an error. Read the message, locate the problem, change one thing and test again.</p>','Replace the unsupported SHOW command with SAY.','SHOW "I found the bug!"','I found the bug!','🐞','beginner',15,2],
            ['mwanacode-basics','Variables: Labelled Boxes','variables-labelled-boxes','Store values for later use.','Create and display variables with SET and SAY.','Variables, values','variable, value, assign','<h2>Variables store named values</h2><p>Use <code>SET name = value</code>. Put text inside quotation marks.</p><pre><code>SET learner = "Chanda"\nSAY learner</code></pre>','Store a learner name and number of books.','SET learner = "Mwamba"\nSET books = 4\nSAY learner\nSAY books','A name and number','📦','beginner',18,1],
            ['mwanacode-basics','Decisions with IF','decisions-with-if','Make a program choose an action.','Use IF, ELSE and comparison operators.','Conditions, comparisons','condition, boolean, comparison','<h2>Programs can choose</h2><pre><code>SET score = 7\nIF score >= 5\n  SAY "You passed"\nELSE\n  SAY "Try again"\nEND</code></pre>','Display Junior group below age 13, otherwise Senior group.','SET age = 12\nIF age < 13\n  SAY "Junior group"\nELSE\n  SAY "Senior group"\nEND','Junior group','🔀','beginner',20,2],
            ['mwanacode-basics','Loops Save Work','loops-save-work','Repeat instructions clearly.','Use REPEAT and END.','Loops, iteration','loop, repeat, iteration','<h2>Loops repeat commands</h2><pre><code>REPEAT 4\n  SAY "Clap"\nEND</code></pre><p>MwanaCode limits repetitions to keep programs safe.</p>','Use one loop to display a message five times.','REPEAT 5\n  SAY "Coding grows with practice"\nEND','Five lines','🔁','beginner',18,3],
            ['mwanacode-basics','Draw with a Turtle','draw-with-a-turtle','Create shapes using movement and turns.','Use MOVE, TURN and PEN.','Coordinates, angles, loops','turtle, angle, coordinate','<h2>Move and turn</h2><p>A square has four equal sides and four 90-degree turns.</p><pre><code>REPEAT 4\n  MOVE 100\n  TURN 90\nEND</code></pre>','Draw a coloured square.','CLEAR\nPEN "purple"\nREPEAT 4\n  MOVE 100\n  TURN 90\nEND','A purple square','🎨','beginner',22,4],
            ['web-creator-starter','HTML Gives Structure','html-gives-structure','Learn semantic page structure.','Identify common HTML elements.','HTML, semantics','element, tag, heading','<h2>HTML gives meaning</h2><p>HTML describes headings, paragraphs, links, images and sections. Semantic structure improves accessibility.</p>','List the HTML elements used for a heading, paragraph and link.','SAY "h1: heading"\nSAY "p: paragraph"\nSAY "a: link"','Three element descriptions','🏗️','beginner',18,1],
            ['web-creator-starter','CSS Controls Presentation','css-controls-presentation','Understand selectors, properties and values.','Explain how CSS styles HTML.','CSS, selectors','selector, property, value','<h2>CSS controls appearance</h2><pre><code>h1 { color: purple; }</code></pre><p>The selector chooses an element; the property and value describe its appearance.</p>','Display the selector, property and value in the example.','SAY "Selector: h1"\nSAY "Property: color"\nSAY "Value: purple"','Three CSS parts','🎨','beginner',18,2],
            ['web-creator-starter','JavaScript Adds Behaviour','javascript-adds-behaviour','Respond to clicks and input changes.','Describe browser events and handlers.','JavaScript, events','event, handler, DOM','<h2>JavaScript responds to events</h2><p>An event handler runs when something happens, such as a click. CodeMwana uses a controlled interpreter instead of evaluating unrestricted learner code.</p>','Display three examples of browser events.','SAY "click"\nSAY "input"\nSAY "submit"','Three events','⚡','intermediate',20,3],
        ];
        $lessonIds = [];
        foreach ($lessons as $row) {
            $courseSlug = array_shift($row);
            $lesson->execute(array_merge([$courseIds[$courseSlug]], $row));
            $lessonIds[$row[1]] = (int) $pdo->lastInsertId();
        }

        $question = $pdo->prepare('INSERT INTO quiz_questions (lesson_id,question,option_a,option_b,option_c,option_d,correct_option,explanation,sort_order) VALUES (?,?,?,?,?,?,?,?,?)');
        $quiz = [
            'algorithm-adventures' => [['What is an algorithm?','An ordered set of instructions','A colour scheme','A password','A picture','A','An algorithm is a clear sequence of instructions.']],
            'debugging-detective' => [['What is debugging?','Finding and correcting errors','Deleting every file','Ignoring messages','Changing colours','A','Debugging is a systematic error-finding process.']],
            'variables-labelled-boxes' => [['What does a variable do?','Stores a named value','Ends a loop','Creates a domain','Deletes output','A','A variable associates a name with a value.']],
            'decisions-with-if' => [['What result does a condition produce?','True or false','Only text','A website','A password','A','A condition evaluates to true or false.']],
            'loops-save-work' => [['Why use a loop?','To repeat a block clearly','To remove variables','To hide errors','To end HTML','A','Loops make repetition clear and reduce duplicated code.']],
            'draw-with-a-turtle' => [['How many degrees are in a full turn?','90','180','270','360','D','A full rotation is 360 degrees.']],
            'html-gives-structure' => [['What is HTML mainly for?','Page structure and meaning','Database passwords','Server backups','Image compression','A','HTML describes document structure and semantics.']],
            'css-controls-presentation' => [['What does CSS control?','Presentation and layout','User passwords','Database tables','Domain ownership','A','CSS controls visual presentation.']],
            'javascript-adds-behaviour' => [['What is a browser event?','An action such as a click','A CSS property','A database row','A file extension','A','Events describe actions a program can respond to.']],
        ];
        foreach ($quiz as $slug => $items) foreach ($items as $i => $q) $question->execute(array_merge([$lessonIds[$slug]], $q, [$i + 1]));

        $badge = $pdo->prepare('INSERT INTO badges (code,name,description,icon,points) VALUES (?,?,?,?,?)');
        foreach ([['FIRST_STEP','First Steps','Complete one lesson.','🌱',10],['CODE_EXPLORER','Code Explorer','Save your first project.','💻',10],['QUIZ_STAR','Quiz Star','Earn 100% on a quiz.','⭐',15],['LESSON_MASTER','Lesson Master','Complete five lessons.','🏆',25]] as $row) $badge->execute($row);

        $user = $pdo->prepare('INSERT INTO users (name,username,email,password,role,age_group,points,streak_days,status,last_login_at) VALUES (?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)');
        foreach ([['Demo Learner','learner','learner@codemwana.test','Learn@123','learner','11-13',145,3],['Teacher Account','teacher','teacher@codemwana.test','Teacher@123','teacher',null,0,0],['Administrator','admin','admin@codemwana.test','Admin@123','admin',null,0,0]] as $row) {
            $row[3] = password_hash($row[3], PASSWORD_DEFAULT);
            $row[] = 'active';
            $user->execute($row);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
