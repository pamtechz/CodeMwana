<?php

declare(strict_types=1);

function seed_database(PDO $pdo, array $administrator): void
{
    $content = json_decode(<<<'JSON'
{
  "settings": {
    "site_name": "CodeMwana",
    "site_tagline": "Learn. Build. Shine.",
    "site_description": "A safe, database-backed programming learning platform designed for children and schools.",
    "hero_eyebrow": "Programming foundations for young creators",
    "hero_title": "Build real coding skills, one clear idea at a time.",
    "hero_text": "Learn algorithms, variables, decisions, loops, turtle graphics and web creation through guided lessons, practical challenges and immediate feedback.",
    "primary_action_text": "Create learner account",
    "secondary_action_text": "Explore learning paths",
    "support_email": "",
    "organisation_name": "",
    "registration_open": "1",
    "leaderboard_enabled": "1"
  },
  "features": [
    {
      "title": "Guided learning paths",
      "description": "Short, ordered lessons move from computational thinking to practical coding and web concepts.",
      "icon": "map",
      "sort_order": 1
    },
    {
      "title": "Safe code execution",
      "description": "MwanaCode interprets a limited educational command set without evaluating arbitrary browser scripts.",
      "icon": "shield-check",
      "sort_order": 2
    },
    {
      "title": "Progress that persists",
      "description": "Enrolments, lesson status, quiz scores, points, badges and projects remain available across sessions.",
      "icon": "chart",
      "sort_order": 3
    },
    {
      "title": "Creative coding canvas",
      "description": "Turtle graphics make loops, angles and debugging visible through drawings learners create themselves.",
      "icon": "palette",
      "sort_order": 4
    },
    {
      "title": "Teacher insight",
      "description": "Teachers can review engagement, difficult lessons, learner progress and assessment performance.",
      "icon": "users",
      "sort_order": 5
    },
    {
      "title": "Low-bandwidth design",
      "description": "The interface uses lightweight local assets and responsive layouts for phones, tablets and computers.",
      "icon": "wifi",
      "sort_order": 6
    }
  ],
  "courses": [
    {
      "title": "Computational Thinking",
      "slug": "computational-thinking",
      "short_description": "Learn how programmers break problems into clear, testable steps.",
      "description": "Build a strong foundation in sequencing, patterns, algorithms and debugging before writing longer programs.",
      "icon": "route",
      "colour": "#6C5CE7",
      "level": "Starter",
      "estimated_time": "1 hour 20 minutes",
      "audience": "Ages 9–16",
      "outcomes": "Explain what an algorithm is\nBreak a problem into smaller steps\nRecognise patterns and correct faulty instructions",
      "sort_order": 1
    },
    {
      "title": "MwanaCode Foundations",
      "slug": "mwanacode-foundations",
      "short_description": "Write safe text-based programs using commands, variables, decisions and loops.",
      "description": "MwanaCode uses a small, controlled command set so learners can focus on logic without unsafe browser execution.",
      "icon": "terminal",
      "colour": "#0F9D8A",
      "level": "Beginner",
      "estimated_time": "2 hours",
      "audience": "Ages 10–16",
      "outcomes": "Print useful output\nStore and update values\nMake decisions with IF\nRepeat instructions efficiently",
      "sort_order": 2
    },
    {
      "title": "Creative Turtle Coding",
      "slug": "creative-turtle-coding",
      "short_description": "Use code to draw lines, shapes and reusable geometric patterns.",
      "description": "Learners apply angles, repetition and planning while creating visible results in the Code Lab canvas.",
      "icon": "pen-tool",
      "colour": "#F59E0B",
      "level": "Beginner",
      "estimated_time": "1 hour 35 minutes",
      "audience": "Ages 9–16",
      "outcomes": "Control movement and turning\nDraw regular shapes\nUse loops to create patterns\nDebug visual programs",
      "sort_order": 3
    },
    {
      "title": "Web Creator Basics",
      "slug": "web-creator-basics",
      "short_description": "Understand how HTML, CSS and JavaScript work together to create web experiences.",
      "description": "Connect programming concepts to practical web development using semantic structure, visual styling and safe interaction.",
      "icon": "globe",
      "colour": "#2563EB",
      "level": "Beginner",
      "estimated_time": "1 hour 45 minutes",
      "audience": "Ages 11–16",
      "outcomes": "Describe the roles of HTML, CSS and JavaScript\nWrite semantic page structure\nApply reusable styles\nPlan an accessible web page",
      "sort_order": 4
    }
  ],
  "lessons": [
    {
      "course_slug": "computational-thinking",
      "sort_order": 1,
      "title": "Programs are precise instructions",
      "slug": "programs-are-precise-instructions",
      "summary": "Discover why computers need instructions that are clear and ordered.",
      "learning_objective": "Explain the relationship between a task, an instruction and a program.",
      "concepts": "sequence, precision, input, output",
      "vocabulary": "program, instruction, sequence, input, output",
      "challenge_text": "Write six exact steps that guide a classmate from the classroom door to a selected desk. Test the steps and revise anything ambiguous.",
      "starter_code": "SAY \"A program is a set of precise instructions.\"",
      "expected_output": "A program is a set of precise instructions.",
      "teacher_note": "Ask learners to act out ambiguous and precise instructions so the difference becomes visible.",
      "content_html": "<h2>Computers follow instructions</h2><p>A computer does not guess what a person intended. It follows the instructions it receives in the order provided. A <strong>program</strong> is an organised set of those instructions.</p><div class=\"learning-example\"><h3>Human task and computer task</h3><p>A person may understand “prepare the desk”. A computer needs smaller actions: identify the books, move each book, then confirm that the surface is clear.</p></div><h2>Input, process and output</h2><p>Input is the information a program receives. The process is what it does with that information. Output is the result it produces.</p>",
      "questions": [
        {
          "question": "Why must a computer program use precise instructions?",
          "option_a": "Computers can guess missing steps",
          "option_b": "Computers follow the instructions they are given",
          "option_c": "Computers prefer long sentences",
          "option_d": "Computers only understand pictures",
          "correct_option": "B",
          "explanation": "A computer follows defined instructions; it does not infer the writer’s unstated intention."
        },
        {
          "question": "Which order correctly describes a simple program flow?",
          "option_a": "Output, input, process",
          "option_b": "Process, output, input",
          "option_c": "Input, process, output",
          "option_d": "Input, output, process",
          "correct_option": "C",
          "explanation": "A program receives input, processes it, and then produces output."
        },
        {
          "question": "Which instruction is most precise?",
          "option_a": "Make the screen nice",
          "option_b": "Put it over there",
          "option_c": "Display the learner name at the top of the page",
          "option_d": "Do the usual thing",
          "correct_option": "C",
          "explanation": "It states exactly what should be displayed and where."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "computational-thinking",
      "sort_order": 2,
      "title": "Build an algorithm",
      "slug": "build-an-algorithm",
      "summary": "Turn a familiar activity into a reliable sequence of steps.",
      "learning_objective": "Create and test an algorithm for a real task.",
      "concepts": "algorithm, sequence, test, revise",
      "vocabulary": "algorithm, step, order, test case",
      "challenge_text": "Create an algorithm for calculating the total cost of three exercise books priced at ZMW 12 each. Include the input, calculation and output.",
      "starter_code": "SET price = 12\nSET quantity = 3\nSET total = price + price + price\nSAY total",
      "expected_output": "36",
      "teacher_note": "Encourage learners to test algorithms using a second set of values rather than only reading them.",
      "content_html": "<h2>What is an algorithm?</h2><p>An algorithm is a finite sequence of steps for solving a problem or completing a task. A useful algorithm has a clear starting point, a sensible order and a definite result.</p><div class=\"learning-example\"><h3>Calculating a total</h3><ol><li>Read the price of one item.</li><li>Read the quantity.</li><li>Multiply price by quantity.</li><li>Display the total.</li></ol></div><h2>Test before trusting</h2><p>Testing helps reveal missing steps and incorrect assumptions. Good programmers test normal values and unusual values.</p>",
      "questions": [
        {
          "question": "What is an algorithm?",
          "option_a": "A computer brand",
          "option_b": "A sequence of steps for solving a problem",
          "option_c": "A colour used in a website",
          "option_d": "A finished database",
          "correct_option": "B",
          "explanation": "An algorithm is an ordered method for completing a task or solving a problem."
        },
        {
          "question": "What should happen after an algorithm is written?",
          "option_a": "It should never change",
          "option_b": "It should be tested with suitable inputs",
          "option_c": "It should be hidden",
          "option_d": "It should be converted into a picture only",
          "correct_option": "B",
          "explanation": "Testing checks whether the steps produce the expected result."
        },
        {
          "question": "Which property makes an algorithm easier to follow?",
          "option_a": "Steps in a clear order",
          "option_b": "Missing inputs",
          "option_c": "Several unrelated results",
          "option_d": "Ambiguous words",
          "correct_option": "A",
          "explanation": "Ordered steps make the process reproducible."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "computational-thinking",
      "sort_order": 3,
      "title": "Find and fix a bug",
      "slug": "find-and-fix-a-bug",
      "summary": "Use evidence to locate an error and correct it without guessing.",
      "learning_objective": "Apply a simple debugging cycle to an incorrect sequence.",
      "concepts": "bug, debugging, trace, evidence",
      "vocabulary": "bug, debug, trace, expected result, actual result",
      "challenge_text": "The program should count from 1 to 3 but prints 1, 2, 2. Trace each step and explain the exact correction.",
      "starter_code": "SAY 1\nSAY 2\nSAY 2",
      "expected_output": "1\n2\n3",
      "teacher_note": "Model calm debugging language: describe what happened, what was expected and what changed.",
      "content_html": "<h2>Bugs are normal</h2><p>A bug is an error that causes a program to behave differently from the expected result. Debugging is the process of finding the cause and correcting it.</p><ol><li>State the expected result.</li><li>Observe the actual result.</li><li>Trace the instructions in order.</li><li>Change one relevant part.</li><li>Test again.</li></ol><div class=\"learning-example\"><h3>Use evidence</h3><p>Changing many lines at once makes it difficult to know which change fixed the problem. Small, tested changes produce clearer evidence.</p></div>",
      "questions": [
        {
          "question": "What is a bug in a program?",
          "option_a": "A type of keyboard",
          "option_b": "An error that causes incorrect behaviour",
          "option_c": "A saved project name",
          "option_d": "A lesson score",
          "correct_option": "B",
          "explanation": "A bug is a fault in the instructions or data that changes the program’s behaviour."
        },
        {
          "question": "What is the best first step in debugging?",
          "option_a": "Delete the whole program",
          "option_b": "State the expected and actual results",
          "option_c": "Change every instruction",
          "option_d": "Ignore the output",
          "correct_option": "B",
          "explanation": "A clear comparison helps narrow the search."
        },
        {
          "question": "Why should a programmer change one relevant part at a time?",
          "option_a": "To make the program longer",
          "option_b": "To identify which change affected the result",
          "option_c": "To avoid testing",
          "option_d": "To remove all output",
          "correct_option": "B",
          "explanation": "Controlled changes make cause and effect easier to identify."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "computational-thinking",
      "sort_order": 4,
      "title": "Patterns and decomposition",
      "slug": "patterns-and-decomposition",
      "summary": "Simplify a large problem by finding repeated parts and smaller tasks.",
      "learning_objective": "Use pattern recognition and decomposition to plan a solution.",
      "concepts": "decomposition, pattern, reusable step",
      "vocabulary": "decomposition, pattern, subproblem, reuse",
      "challenge_text": "Plan a school-results summary by splitting it into input, validation, calculation and report tasks.",
      "starter_code": "SAY \"Input marks\"\nSAY \"Validate marks\"\nSAY \"Calculate result\"\nSAY \"Display report\"",
      "expected_output": "Input marks\nValidate marks\nCalculate result\nDisplay report",
      "teacher_note": "Let groups compare different decompositions and justify why their boundaries are useful.",
      "content_html": "<h2>Break a problem down</h2><p>Decomposition means dividing a large problem into smaller parts that can be understood and tested separately.</p><h2>Look for patterns</h2><p>When several tasks share the same structure, one reusable solution may handle all of them. For example, validating marks from different subjects follows the same rules.</p><div class=\"learning-example\"><h3>School results system</h3><p>Separate the work into receiving marks, checking values, calculating totals, assigning grades and displaying a report.</p></div>",
      "questions": [
        {
          "question": "What does decomposition mean?",
          "option_a": "Combining every task into one step",
          "option_b": "Breaking a problem into smaller parts",
          "option_c": "Removing all data",
          "option_d": "Repeating an error",
          "correct_option": "B",
          "explanation": "Decomposition reduces complexity by creating manageable subproblems."
        },
        {
          "question": "Why is pattern recognition useful?",
          "option_a": "It reveals parts that can use a reusable solution",
          "option_b": "It prevents programs from receiving input",
          "option_c": "It changes passwords",
          "option_d": "It removes the need for testing",
          "correct_option": "A",
          "explanation": "Repeated structures can often share one tested method."
        },
        {
          "question": "Which is a sensible subproblem in a results system?",
          "option_a": "Validate that each mark is between 0 and 100",
          "option_b": "Choose an unrelated wallpaper",
          "option_c": "Rename the keyboard",
          "option_d": "Disconnect the database",
          "correct_option": "A",
          "explanation": "Validation is a distinct, testable responsibility within the larger system."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "mwanacode-foundations",
      "sort_order": 1,
      "title": "Say hello with output",
      "slug": "say-hello-with-output",
      "summary": "Create a first MwanaCode program and inspect its output.",
      "learning_objective": "Use SAY to display meaningful text and values.",
      "concepts": "output, command, string",
      "vocabulary": "SAY, output, text, string",
      "challenge_text": "Write a three-line welcome message for a school coding club.",
      "starter_code": "SAY \"Welcome to the coding club\"\nSAY \"Today we build with logic\"\nSAY \"Let us begin\"",
      "expected_output": "Welcome to the coding club\nToday we build with logic\nLet us begin",
      "teacher_note": "Ask learners to predict the output before running the program.",
      "content_html": "<h2>The SAY command</h2><p><code>SAY</code> sends text or a value to the output panel. Text must be placed inside quotation marks so the interpreter knows where it begins and ends.</p><pre><code>SAY \"Muli bwanji, coder!\"</code></pre><h2>Read output as evidence</h2><p>The output panel shows what the program actually did. Compare it with what you expected before changing the code.</p>",
      "questions": [
        {
          "question": "Which command displays output in MwanaCode?",
          "option_a": "MOVE",
          "option_b": "SAY",
          "option_c": "TURN",
          "option_d": "END",
          "correct_option": "B",
          "explanation": "SAY sends text or a value to the output panel."
        },
        {
          "question": "Why is text placed inside quotation marks?",
          "option_a": "To make it a number",
          "option_b": "To mark the beginning and end of the text",
          "option_c": "To repeat it",
          "option_d": "To hide it from the user",
          "correct_option": "B",
          "explanation": "Quotation marks identify a text value."
        },
        {
          "question": "What should a learner do before running a program?",
          "option_a": "Predict the expected output",
          "option_b": "Delete the code",
          "option_c": "Close the browser",
          "option_d": "Change the account role",
          "correct_option": "A",
          "explanation": "Prediction makes testing purposeful."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "mwanacode-foundations",
      "sort_order": 2,
      "title": "Store values in variables",
      "slug": "store-values-in-variables",
      "summary": "Give information a name so it can be reused and changed.",
      "learning_objective": "Create variables with SET and use them in output.",
      "concepts": "variable, assignment, value",
      "vocabulary": "SET, variable, value, assignment",
      "challenge_text": "Store a learner name and a score, then display both values.",
      "starter_code": "SET learner = \"Chanda\"\nSET score = 80\nSAY learner\nSAY score",
      "expected_output": "Chanda\n80",
      "teacher_note": "Use labelled containers or cards to demonstrate that a variable name refers to a current value.",
      "content_html": "<h2>Variables hold values</h2><p>A variable is a named place for information. <code>SET score = 80</code> stores the number 80 using the name <code>score</code>.</p><pre><code>SET subject = \"Computer Studies\"\nSAY subject</code></pre><h2>Choose clear names</h2><p><code>totalCost</code> communicates more meaning than <code>x</code>. Clear names reduce mistakes when programs grow.</p>",
      "questions": [
        {
          "question": "What is a variable?",
          "option_a": "A named place for storing a value",
          "option_b": "A drawing command",
          "option_c": "A type of database server",
          "option_d": "A quiz button",
          "correct_option": "A",
          "explanation": "Variables associate meaningful names with values."
        },
        {
          "question": "Which line stores the number 25 in a variable named books?",
          "option_a": "SAY books 25",
          "option_b": "SET books = 25",
          "option_c": "MOVE books",
          "option_d": "IF books",
          "correct_option": "B",
          "explanation": "SET assigns a value to a variable."
        },
        {
          "question": "Which variable name communicates its purpose most clearly?",
          "option_a": "x",
          "option_b": "thing",
          "option_c": "totalCost",
          "option_d": "a1",
          "correct_option": "C",
          "explanation": "A descriptive name helps readers understand the program."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "mwanacode-foundations",
      "sort_order": 3,
      "title": "Update numbers safely",
      "slug": "update-numbers-safely",
      "summary": "Change a stored number using ADD and inspect the new value.",
      "learning_objective": "Update numeric variables and reason about program state.",
      "concepts": "state, update, addition",
      "vocabulary": "ADD, state, current value",
      "challenge_text": "Start with 5 points, add 10, then add 15 and display the final points.",
      "starter_code": "SET points = 5\nADD points 10\nADD points 15\nSAY points",
      "expected_output": "30",
      "teacher_note": "Trace the value after every line instead of jumping directly to the final answer.",
      "content_html": "<h2>Program state changes</h2><p>The current values stored by a program are called its state. <code>ADD points 10</code> reads the current value, increases it and stores the result back in the same variable.</p><div class=\"learning-example\"><h3>Trace table</h3><p>After SET: 5. After the first ADD: 15. After the second ADD: 30.</p></div>",
      "questions": [
        {
          "question": "What does ADD points 10 do?",
          "option_a": "Replaces points with text",
          "option_b": "Increases the current points value by 10",
          "option_c": "Displays 10 without changing points",
          "option_d": "Deletes the variable",
          "correct_option": "B",
          "explanation": "ADD updates a numeric variable using its current value."
        },
        {
          "question": "If score starts at 40 and ADD score 5 runs twice, what is the final value?",
          "option_a": "40",
          "option_b": "45",
          "option_c": "50",
          "option_d": "55",
          "correct_option": "C",
          "explanation": "The value changes from 40 to 45 and then to 50."
        },
        {
          "question": "What does program state describe?",
          "option_a": "The current stored values",
          "option_b": "The screen size",
          "option_c": "The website domain",
          "option_d": "The keyboard language",
          "correct_option": "A",
          "explanation": "State is the information a program currently holds."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "mwanacode-foundations",
      "sort_order": 4,
      "title": "Make decisions with IF",
      "slug": "make-decisions-with-if",
      "summary": "Run different instructions when a condition is true or false.",
      "learning_objective": "Write an IF and ELSE decision using a comparison.",
      "concepts": "condition, branch, comparison",
      "vocabulary": "IF, ELSE, END, condition",
      "challenge_text": "Display “Pass” when a mark is at least 50; otherwise display “Keep practising”.",
      "starter_code": "SET mark = 68\nIF mark >= 50\n  SAY \"Pass\"\nELSE\n  SAY \"Keep practising\"\nEND",
      "expected_output": "Pass",
      "teacher_note": "Test both branches by changing the mark to values above and below the boundary.",
      "content_html": "<h2>Programs can choose</h2><p>An IF statement checks a condition. The first branch runs when the condition is true. The ELSE branch runs when it is false.</p><pre><code>IF mark &gt;= 50\n  SAY \"Pass\"\nELSE\n  SAY \"Keep practising\"\nEND</code></pre><h2>Boundaries matter</h2><p>Testing 49, 50 and 51 reveals whether the comparison handles the exact pass mark correctly.</p>",
      "questions": [
        {
          "question": "What does an IF statement evaluate?",
          "option_a": "A condition",
          "option_b": "A file name",
          "option_c": "A colour only",
          "option_d": "A password reset",
          "correct_option": "A",
          "explanation": "IF selects a branch based on whether a condition is true or false."
        },
        {
          "question": "Which mark should be used to test the exact pass boundary of 50?",
          "option_a": "0",
          "option_b": "49",
          "option_c": "50",
          "option_d": "100",
          "correct_option": "C",
          "explanation": "Testing the boundary itself checks whether >= is correct."
        },
        {
          "question": "When does ELSE run?",
          "option_a": "When the IF condition is false",
          "option_b": "Before IF",
          "option_c": "Every time",
          "option_d": "Only when a variable is text",
          "correct_option": "A",
          "explanation": "ELSE provides the alternative branch."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "mwanacode-foundations",
      "sort_order": 5,
      "title": "Repeat work with loops",
      "slug": "repeat-work-with-loops",
      "summary": "Replace repeated instructions with a controlled REPEAT block.",
      "learning_objective": "Use REPEAT and END to execute a block a known number of times.",
      "concepts": "loop, iteration, repetition",
      "vocabulary": "REPEAT, END, iteration",
      "challenge_text": "Display “Practice makes progress” four times using one SAY instruction.",
      "starter_code": "REPEAT 4\n  SAY \"Practice makes progress\"\nEND",
      "expected_output": "Practice makes progress\nPractice makes progress\nPractice makes progress\nPractice makes progress",
      "teacher_note": "Compare repeated code with loop-based code and discuss maintainability.",
      "content_html": "<h2>Loops reduce repetition</h2><p>A loop runs the same block more than once. MwanaCode uses <code>REPEAT number</code> and closes the block with <code>END</code>.</p><pre><code>REPEAT 3\n  SAY \"Code, test, improve\"\nEND</code></pre><h2>Control the number</h2><p>CodeMwana limits repetitions so a mistake cannot keep the browser busy indefinitely.</p>",
      "questions": [
        {
          "question": "What is one benefit of a loop?",
          "option_a": "It replaces repeated instructions with one controlled block",
          "option_b": "It removes all conditions",
          "option_c": "It stores passwords",
          "option_d": "It creates database tables",
          "correct_option": "A",
          "explanation": "Loops make repeated behaviour shorter and easier to update."
        },
        {
          "question": "How many times does REPEAT 4 run its block?",
          "option_a": "Once",
          "option_b": "Twice",
          "option_c": "Four times",
          "option_d": "Until the browser closes",
          "correct_option": "C",
          "explanation": "The repetition count is four."
        },
        {
          "question": "Which command closes a REPEAT block?",
          "option_a": "STOP",
          "option_b": "ELSE",
          "option_c": "END",
          "option_d": "SAY",
          "correct_option": "C",
          "explanation": "END marks the end of the repeated block."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "creative-turtle-coding",
      "sort_order": 1,
      "title": "Move the turtle",
      "slug": "move-the-turtle",
      "summary": "Draw a line by moving a virtual turtle with its pen down.",
      "learning_objective": "Use MOVE and understand direction and distance.",
      "concepts": "coordinate, distance, direction",
      "vocabulary": "MOVE, distance, turtle, canvas",
      "challenge_text": "Draw one horizontal line 160 units long.",
      "starter_code": "TURN 90\nMOVE 160",
      "expected_output": "A horizontal line",
      "teacher_note": "Relate the canvas to a coordinate plane without requiring formal algebra.",
      "content_html": "<h2>Movement creates a line</h2><p>The turtle begins near the centre of the canvas. When its pen is down, <code>MOVE 100</code> draws a line 100 units in its current direction.</p><h2>Direction is state</h2><p>The turtle remembers which way it faces. A later MOVE uses that current direction.</p>",
      "questions": [
        {
          "question": "What does MOVE 100 do when the pen is down?",
          "option_a": "Draws a line 100 units in the current direction",
          "option_b": "Changes the line colour",
          "option_c": "Repeats a message",
          "option_d": "Deletes the project",
          "correct_option": "A",
          "explanation": "MOVE changes the turtle position and draws between the old and new positions."
        },
        {
          "question": "What does the turtle remember between commands?",
          "option_a": "Its current position and direction",
          "option_b": "The learner password",
          "option_c": "The database name",
          "option_d": "The quiz explanation",
          "correct_option": "A",
          "explanation": "Position and direction determine the next movement."
        },
        {
          "question": "Where can the learner see turtle output?",
          "option_a": "In the drawing view",
          "option_b": "In the email field",
          "option_c": "In the user table only",
          "option_d": "In the footer",
          "correct_option": "A",
          "explanation": "Drawing commands appear on the canvas."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "creative-turtle-coding",
      "sort_order": 2,
      "title": "Turn using angles",
      "slug": "turn-using-angles",
      "summary": "Change direction using degrees and predict the next movement.",
      "learning_objective": "Use TURN with common angles to control direction.",
      "concepts": "angle, rotation, degrees",
      "vocabulary": "TURN, angle, degrees, clockwise",
      "challenge_text": "Create an L shape using two MOVE commands and one 90-degree turn.",
      "starter_code": "MOVE 120\nTURN 90\nMOVE 120",
      "expected_output": "An L-shaped line",
      "teacher_note": "Use a physical arrow or learner facing forward to model clockwise turns.",
      "content_html": "<h2>TURN changes direction</h2><p><code>TURN 90</code> rotates the turtle clockwise by 90 degrees. It does not draw a line until MOVE runs.</p><div class=\"learning-example\"><h3>Common turns</h3><p>90 degrees makes a quarter turn. 180 degrees faces the opposite direction. 360 degrees completes a full turn.</p></div>",
      "questions": [
        {
          "question": "What does TURN 90 change?",
          "option_a": "The turtle direction",
          "option_b": "The saved project title",
          "option_c": "The learner role",
          "option_d": "The quiz score",
          "correct_option": "A",
          "explanation": "TURN rotates the turtle without moving it."
        },
        {
          "question": "How many degrees are in a full turn?",
          "option_a": "90",
          "option_b": "180",
          "option_c": "270",
          "option_d": "360",
          "correct_option": "D",
          "explanation": "A complete rotation is 360 degrees."
        },
        {
          "question": "Which sequence creates two connected lines at a right angle?",
          "option_a": "MOVE 100, TURN 90, MOVE 100",
          "option_b": "SAY 90, SAY 100",
          "option_c": "TURN 360 only",
          "option_d": "PENUP only",
          "correct_option": "A",
          "explanation": "The turtle moves, turns a quarter turn and moves again."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "creative-turtle-coding",
      "sort_order": 3,
      "title": "Draw regular shapes",
      "slug": "draw-regular-shapes",
      "summary": "Combine equal sides and equal turns to produce a square and triangle.",
      "learning_objective": "Use geometric reasoning to code regular shapes.",
      "concepts": "regular shape, side, exterior angle",
      "vocabulary": "side, angle, square, triangle",
      "challenge_text": "Draw a square with sides of 100 units using REPEAT.",
      "starter_code": "REPEAT 4\n  MOVE 100\n  TURN 90\nEND",
      "expected_output": "A square",
      "teacher_note": "Ask learners to explain why four 90-degree turns return to the starting direction.",
      "content_html": "<h2>Regular shapes follow patterns</h2><p>A square has four equal sides. After each side, the turtle turns 90 degrees. Repeating the same two commands four times completes the shape.</p><pre><code>REPEAT 4\n  MOVE 100\n  TURN 90\nEND</code></pre><h2>Check closure</h2><p>A correct shape returns to its starting position and direction after all sides and turns.</p>",
      "questions": [
        {
          "question": "How many equal sides does a square have?",
          "option_a": "Three",
          "option_b": "Four",
          "option_c": "Five",
          "option_d": "Six",
          "correct_option": "B",
          "explanation": "A square has four equal sides."
        },
        {
          "question": "Which turn is used after each side of a square?",
          "option_a": "45 degrees",
          "option_b": "60 degrees",
          "option_c": "90 degrees",
          "option_d": "180 degrees",
          "correct_option": "C",
          "explanation": "Four 90-degree exterior turns complete 360 degrees."
        },
        {
          "question": "Why is REPEAT useful for drawing a square?",
          "option_a": "The side-and-turn pattern is repeated four times",
          "option_b": "It changes the database",
          "option_c": "It creates a user account",
          "option_d": "It hides the canvas",
          "correct_option": "A",
          "explanation": "The square is built from one repeated pattern."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "creative-turtle-coding",
      "sort_order": 4,
      "title": "Create a colour pattern",
      "slug": "create-a-colour-pattern",
      "summary": "Combine loops, turning and pen colour to create a deliberate visual design.",
      "learning_objective": "Plan and debug a repeated drawing pattern.",
      "concepts": "pattern, colour, nested reasoning",
      "vocabulary": "PEN, pattern, iteration, debug",
      "challenge_text": "Create two overlapping squares using different pen colours and a turn between them.",
      "starter_code": "PEN \"#6C5CE7\"\nREPEAT 4\n  MOVE 90\n  TURN 90\nEND\nTURN 45\nPEN \"#F59E0B\"\nREPEAT 4\n  MOVE 90\n  TURN 90\nEND",
      "expected_output": "Two overlapping squares in different colours",
      "teacher_note": "Emphasise that colour values are data and should be chosen for sufficient contrast.",
      "content_html": "<h2>Style is also data</h2><p>The <code>PEN</code> command changes the colour used by later drawing commands. The program still separates instructions, values and output.</p><h2>Plan before adding complexity</h2><p>Build one shape, test it, then add the turn and second shape. This incremental approach makes visual bugs easier to locate.</p>",
      "questions": [
        {
          "question": "What does PEN change?",
          "option_a": "The drawing colour",
          "option_b": "The turtle distance",
          "option_c": "The learner email",
          "option_d": "The course title",
          "correct_option": "A",
          "explanation": "PEN sets the colour used for later lines."
        },
        {
          "question": "What is a reliable way to build a complex drawing?",
          "option_a": "Build and test one part at a time",
          "option_b": "Write everything without running it",
          "option_c": "Change every line after an error",
          "option_d": "Avoid predicting the result",
          "correct_option": "A",
          "explanation": "Incremental testing isolates errors."
        },
        {
          "question": "Why is a turn added between two squares?",
          "option_a": "To change the orientation of the second square",
          "option_b": "To delete the first square",
          "option_c": "To save the project",
          "option_d": "To increase the quiz score",
          "correct_option": "A",
          "explanation": "The changed direction creates the overlapping pattern."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "web-creator-basics",
      "sort_order": 1,
      "title": "How the web works",
      "slug": "how-the-web-works",
      "summary": "Follow a request from a browser to a web server and back.",
      "learning_objective": "Describe the roles of a browser, server, URL and response.",
      "concepts": "client, server, request, response",
      "vocabulary": "browser, server, URL, request, response",
      "challenge_text": "Draw a labelled flow showing a browser requesting a CodeMwana lesson and the server returning HTML.",
      "starter_code": "SAY \"Browser sends request\"\nSAY \"Server prepares response\"\nSAY \"Browser displays page\"",
      "expected_output": "Browser sends request\nServer prepares response\nBrowser displays page",
      "teacher_note": "Use the running CodeMwana page as the concrete example rather than an abstract website.",
      "content_html": "<h2>Browser and server</h2><p>The browser is a client. It sends a request for a resource identified by a URL. A web server processes the request and sends a response.</p><h2>Dynamic pages</h2><p>CodeMwana uses PHP to read data from the database and build HTML for the current learner. This is why two accounts can see different progress.</p>",
      "questions": [
        {
          "question": "What sends a request for a web page?",
          "option_a": "The browser",
          "option_b": "The keyboard",
          "option_c": "The printer",
          "option_d": "The quiz badge",
          "correct_option": "A",
          "explanation": "A browser acts as a web client."
        },
        {
          "question": "What does a server return after processing a request?",
          "option_a": "A response",
          "option_b": "A school timetable automatically",
          "option_c": "A password in plain text",
          "option_d": "A mouse click",
          "correct_option": "A",
          "explanation": "The response contains the requested content or an error status."
        },
        {
          "question": "Why can two learners see different dashboards?",
          "option_a": "The server uses account-specific database data",
          "option_b": "HTML changes at random",
          "option_c": "The browser invents scores",
          "option_d": "The URL is always hidden",
          "correct_option": "A",
          "explanation": "Server-side code uses the signed-in user to retrieve relevant records."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "web-creator-basics",
      "sort_order": 2,
      "title": "Structure pages with HTML",
      "slug": "structure-pages-with-html",
      "summary": "Use semantic elements to describe the meaning of page content.",
      "learning_objective": "Select appropriate HTML elements for headings, navigation and main content.",
      "concepts": "semantic HTML, element, hierarchy",
      "vocabulary": "HTML, element, heading, navigation, main",
      "challenge_text": "Plan the semantic structure of a school coding club page using header, nav, main, section and footer.",
      "starter_code": "SAY \"header\"\nSAY \"navigation\"\nSAY \"main content\"\nSAY \"footer\"",
      "expected_output": "header\nnavigation\nmain content\nfooter",
      "teacher_note": "Discuss meaning before visual appearance; CSS should not be used to fake heading structure.",
      "content_html": "<h2>HTML gives content structure</h2><p>HTML elements describe what content means. A heading introduces a section, navigation contains major links, and main contains the page’s primary content.</p><div class=\"learning-example\"><h3>Semantic structure</h3><p>Use <code>&lt;header&gt;</code>, <code>&lt;nav&gt;</code>, <code>&lt;main&gt;</code>, <code>&lt;section&gt;</code> and <code>&lt;footer&gt;</code> according to their purpose.</p></div>",
      "questions": [
        {
          "question": "What is the main role of HTML?",
          "option_a": "Describe the structure and meaning of content",
          "option_b": "Store every password",
          "option_c": "Replace the database server",
          "option_d": "Draw turtle lines",
          "correct_option": "A",
          "explanation": "HTML provides semantic document structure."
        },
        {
          "question": "Which element should contain the primary content of a page?",
          "option_a": "main",
          "option_b": "title only",
          "option_c": "style",
          "option_d": "script",
          "correct_option": "A",
          "explanation": "The main element identifies the central content."
        },
        {
          "question": "Why are semantic elements valuable?",
          "option_a": "They improve structure, accessibility and maintainability",
          "option_b": "They make every page the same colour",
          "option_c": "They remove the need for content",
          "option_d": "They prevent all errors",
          "correct_option": "A",
          "explanation": "Meaningful structure helps browsers, assistive technology and developers."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "web-creator-basics",
      "sort_order": 3,
      "title": "Style consistently with CSS",
      "slug": "style-consistently-with-css",
      "summary": "Apply reusable visual rules without mixing design into every HTML element.",
      "learning_objective": "Explain selectors, properties and reusable design tokens.",
      "concepts": "selector, property, value, cascade",
      "vocabulary": "CSS, selector, property, value, custom property",
      "challenge_text": "Define a small design system for a coding club with spacing, type sizes and accessible colours.",
      "starter_code": "SET primaryColour = \"#6C5CE7\"\nSET cardRadius = 16\nSAY primaryColour\nSAY cardRadius",
      "expected_output": "#6C5CE7\n16",
      "teacher_note": "Use contrast and readability as design requirements, not decoration choices.",
      "content_html": "<h2>CSS controls presentation</h2><p>A CSS rule selects elements and assigns property values. Shared rules produce consistency and reduce repeated work.</p><pre><code>.button {\n  padding: 12px 18px;\n  border-radius: 12px;\n}</code></pre><h2>Use design tokens</h2><p>Custom properties can store colours, spacing and sizes in one place so the interface remains coherent.</p>",
      "questions": [
        {
          "question": "What does a CSS selector do?",
          "option_a": "Chooses which elements a rule applies to",
          "option_b": "Creates a database user",
          "option_c": "Runs a quiz",
          "option_d": "Sends an email",
          "correct_option": "A",
          "explanation": "Selectors target elements for styling."
        },
        {
          "question": "What is a benefit of reusable CSS classes?",
          "option_a": "Consistent styling with less duplication",
          "option_b": "Every element needs inline styles",
          "option_c": "They remove HTML meaning",
          "option_d": "They expose passwords",
          "correct_option": "A",
          "explanation": "Reusable rules improve maintainability and consistency."
        },
        {
          "question": "Which design concern is essential for text colours?",
          "option_a": "Readable contrast",
          "option_b": "Randomness",
          "option_c": "Maximum animation",
          "option_d": "Using as many colours as possible",
          "correct_option": "A",
          "explanation": "Sufficient contrast makes content readable for more users."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 15,
      "icon": "book-open"
    },
    {
      "course_slug": "web-creator-basics",
      "sort_order": 4,
      "title": "Add safe interaction",
      "slug": "add-safe-interaction",
      "summary": "Connect user actions to clear interface feedback using event handlers.",
      "learning_objective": "Describe events, handlers, validation and feedback.",
      "concepts": "event, handler, validation, feedback",
      "vocabulary": "event, handler, validation, state",
      "challenge_text": "Plan the behaviour of a Save Project button, including loading, success and error states.",
      "starter_code": "SAY \"Saving project\"\nSAY \"Project saved\"",
      "expected_output": "Saving project\nProject saved",
      "teacher_note": "Ask learners to identify what the user should see while an operation is running and after it fails.",
      "content_html": "<h2>Interaction needs feedback</h2><p>An event is something that happens, such as a button click or form submission. An event handler runs the appropriate response.</p><h2>Validate on both sides</h2><p>Browser validation helps users correct input quickly. Server validation remains necessary because requests can be changed before they reach the server.</p><div class=\"learning-example\"><h3>Save flow</h3><p>Disable the button while saving, show progress, confirm success, and preserve the learner’s work if an error occurs.</p></div>",
      "questions": [
        {
          "question": "What is an event handler?",
          "option_a": "Code that responds to an event",
          "option_b": "A database table name only",
          "option_c": "A CSS colour",
          "option_d": "A course badge",
          "correct_option": "A",
          "explanation": "Handlers define what should happen when an event occurs."
        },
        {
          "question": "Why should validation occur on the server as well as the browser?",
          "option_a": "Requests can be modified before reaching the server",
          "option_b": "The browser never validates",
          "option_c": "CSS requires it",
          "option_d": "It increases screen size",
          "correct_option": "A",
          "explanation": "Server-side validation protects data integrity and security."
        },
        {
          "question": "What is good feedback while a project is saving?",
          "option_a": "Show a progress state and prevent duplicate submissions",
          "option_b": "Do nothing",
          "option_c": "Clear the code immediately",
          "option_d": "Sign the learner out",
          "correct_option": "A",
          "explanation": "Visible progress and disabled repeated actions reduce uncertainty and duplicate operations."
        }
      ],
      "difficulty": "beginner",
      "duration_minutes": 18,
      "icon": "book-open"
    }
  ],
  "badges": [
    {
      "code": "first_path",
      "name": "Path Finder",
      "description": "Enrolled in a first learning path.",
      "icon": "route",
      "points": 10,
      "sort_order": 1
    },
    {
      "code": "first_lesson",
      "name": "First Step",
      "description": "Completed a first lesson quiz.",
      "icon": "footprints",
      "points": 15,
      "sort_order": 2
    },
    {
      "code": "five_lessons",
      "name": "Logic Builder",
      "description": "Completed five lessons.",
      "icon": "blocks",
      "points": 25,
      "sort_order": 3
    },
    {
      "code": "learning_runner",
      "name": "Learning Runner",
      "description": "Completed twelve lessons.",
      "icon": "rocket",
      "points": 50,
      "sort_order": 4
    },
    {
      "code": "first_project",
      "name": "Project Starter",
      "description": "Saved a first coding project.",
      "icon": "folder-code",
      "points": 20,
      "sort_order": 5
    },
    {
      "code": "project_builder",
      "name": "Project Builder",
      "description": "Created five coding projects.",
      "icon": "hammer",
      "points": 40,
      "sort_order": 6
    },
    {
      "code": "perfect_score",
      "name": "Quiz Master",
      "description": "Achieved 100 percent on a lesson quiz.",
      "icon": "trophy",
      "points": 30,
      "sort_order": 7
    }
  ]
}
JSON, true, 512, JSON_THROW_ON_ERROR);

    $content['settings']['site_name'] = trim($administrator['platform_name']);
    $content['settings']['organisation_name'] = trim($administrator['organisation']);
    $content['settings']['support_email'] = strtolower(trim($administrator['support_email']));
    $content['settings']['site_description'] = trim($administrator['platform_name']) . ' is a safe, database-backed programming learning platform for children and schools.';

    $pdo->beginTransaction();
    try {
        $settingStatement = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
        foreach ($content['settings'] as $key => $value) {
            $exists = $pdo->prepare('SELECT id FROM site_settings WHERE setting_key = ?');
            $exists->execute([$key]);
            if (!$exists->fetchColumn()) $settingStatement->execute([$key, $value]);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM home_features')->fetchColumn() === 0) {
            $statement = $pdo->prepare('INSERT INTO home_features (title, description, icon, sort_order, is_active) VALUES (?, ?, ?, ?, 1)');
            foreach ($content['features'] as $feature) $statement->execute([$feature['title'], $feature['description'], $feature['icon'], $feature['sort_order']]);
        }

        $courseIds = [];
        $courseInsert = $pdo->prepare('INSERT INTO courses (title, slug, short_description, description, icon, colour, level, estimated_time, audience, outcomes, sort_order, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
        foreach ($content['courses'] as $course) {
            $find = $pdo->prepare('SELECT id FROM courses WHERE slug = ?');
            $find->execute([$course['slug']]);
            $id = $find->fetchColumn();
            if (!$id) {
                $courseInsert->execute([$course['title'], $course['slug'], $course['short_description'], $course['description'], $course['icon'], $course['colour'], $course['level'], $course['estimated_time'], $course['audience'], $course['outcomes'], $course['sort_order']]);
                $id = $pdo->lastInsertId();
            }
            $courseIds[$course['slug']] = (int) $id;
        }

        $lessonInsert = $pdo->prepare('INSERT INTO lessons (course_id, title, slug, summary, learning_objective, concepts, vocabulary, content_html, challenge_text, starter_code, expected_output, teacher_note, icon, difficulty, duration_minutes, sort_order, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
        $questionInsert = $pdo->prepare('INSERT INTO quiz_questions (lesson_id, question, option_a, option_b, option_c, option_d, correct_option, explanation, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($content['lessons'] as $lesson) {
            $find = $pdo->prepare('SELECT id FROM lessons WHERE slug = ?');
            $find->execute([$lesson['slug']]);
            $lessonId = $find->fetchColumn();
            if (!$lessonId) {
                $lessonInsert->execute([$courseIds[$lesson['course_slug']], $lesson['title'], $lesson['slug'], $lesson['summary'], $lesson['learning_objective'], $lesson['concepts'], $lesson['vocabulary'], $lesson['content_html'], $lesson['challenge_text'], $lesson['starter_code'], $lesson['expected_output'], $lesson['teacher_note'], $lesson['icon'], $lesson['difficulty'], $lesson['duration_minutes'], $lesson['sort_order']]);
                $lessonId = (int) $pdo->lastInsertId();
                foreach ($lesson['questions'] as $index => $question) {
                    $questionInsert->execute([$lessonId, $question['question'], $question['option_a'], $question['option_b'], $question['option_c'], $question['option_d'], $question['correct_option'], $question['explanation'], $index + 1]);
                }
            }
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM badges')->fetchColumn() === 0) {
            $statement = $pdo->prepare('INSERT INTO badges (code, name, description, icon, points, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($content['badges'] as $badge) $statement->execute([$badge['code'], $badge['name'], $badge['description'], $badge['icon'], $badge['points'], $badge['sort_order']]);
        }

        $adminEmail = strtolower(trim($administrator['email']));
        $findAdmin = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $findAdmin->execute([$adminEmail]);
        $adminId = $findAdmin->fetchColumn();
        if (!$adminId) {
            $statement = $pdo->prepare('INSERT INTO users (name, username, email, password, role, school_name, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $statement->execute([trim($administrator['name']), strtolower(trim($administrator['username'])), $adminEmail, password_hash($administrator['password'], PASSWORD_DEFAULT), 'admin', trim($administrator['organisation']), 'active']);
            $adminId = (int) $pdo->lastInsertId();
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn() === 0) {
            $statement = $pdo->prepare('INSERT INTO announcements (author_id, title, body, audience, status, is_pinned, published_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
            $statement->execute([$adminId, 'Welcome to CodeMwana', 'Start with Computational Thinking, enrol in a learning path, complete each challenge and save your own programs in the Code Lab.', 'all', 'published', 1]);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM schema_meta')->fetchColumn() === 0) {
            $pdo->prepare('INSERT INTO schema_meta (schema_version) VALUES (?)')->execute(['2.0.0']);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}
