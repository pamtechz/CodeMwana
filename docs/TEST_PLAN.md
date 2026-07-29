# CodeMwana test plan

| ID | Scenario | Expected result |
|---|---|---|
| T01 | Open home page at mobile and desktop widths | Layout fits without horizontal scrolling; main call-to-action is visible |
| T02 | Register with valid learner information | User is stored, signed in and redirected to dashboard |
| T03 | Register with duplicate email or username | Validation error appears and no duplicate record is created |
| T04 | Sign in with incorrect password | Generic authentication error appears |
| T05 | Open a lesson | Progress row is created or changed to in-progress |
| T06 | Submit a quiz below 60% | Attempt is stored; lesson stays in-progress; feedback is shown |
| T07 | Submit a quiz at or above 60% | Lesson becomes completed; best score and points update |
| T08 | Run valid SAY, SET, REPEAT and IF commands | Correct console output appears |
| T09 | Run an unknown MwanaCode command | Safe line-numbered error appears; arbitrary code does not run |
| T10 | Draw a square with turtle commands | Drawing appears in the canvas tab |
| T11 | Save and reopen a code project | Project persists across logout/login sessions |
| T12 | Access teacher page as learner | HTTP 403 response is returned |
| T13 | Access admin page as teacher | HTTP 403 response is returned |
| T14 | Submit a POST request without CSRF token | Request is rejected with session-expired response |
| T15 | Enter HTML in project title | Output is escaped and no script executes |
