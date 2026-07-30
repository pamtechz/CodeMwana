# CodeMwana functional test plan

| ID | Operation | Expected result |
|---|---|---|
| T01 | Install on an empty database | Schema, settings, curriculum and first administrator are created once |
| T02 | Reopen setup after installation | Installer reports that the platform is installed and prevents accidental reseeding |
| T03 | Register valid learner | Unique account is stored, signed in and redirected to learning paths |
| T04 | Register duplicate username or email | Clear validation appears; no duplicate record is created |
| T05 | Sign in with username and correct password | Session regenerates and the learner dashboard opens |
| T06 | Repeatedly submit incorrect password | Generic failure messages appear and temporary rate limiting activates |
| T07 | Disable account as administrator | User can no longer sign in; existing data remains |
| T08 | Reset password as administrator | New hash is stored and the user can sign in with the new password |
| T09 | Update platform setting | Public branding or operation changes immediately from database values |
| T10 | Create and publish learning path | Path appears on public and learner path pages |
| T11 | Create lesson with unsafe HTML attributes | Unsupported tags and event/style attributes are removed before storage |
| T12 | Create, edit and delete quiz question | Assessment inventory and learner quiz reflect the database changes |
| T13 | Enrol in a learning path | Enrolment is stored and path appears on the learner dashboard |
| T14 | Open lesson | Progress becomes in-progress and last-access time updates |
| T15 | Submit quiz below 60% | Attempt is stored, explanations appear and lesson remains in progress |
| T16 | Submit quiz at or above 60% | Lesson completes, best score and points update, badge rules run |
| T17 | Run valid MwanaCode | Correct console or turtle output appears without arbitrary script execution |
| T18 | Run unsupported command or excessive loop | Safe line-numbered error appears and execution stops |
| T19 | Save and update code project | Project persists and changed code creates a project-version record |
| T20 | Delete owned project | Project and its versions are removed only for the owning learner |
| T21 | Open teacher report | Real learner, completion and assessment data appears |
| T22 | Publish and delete announcement | Intended audience sees the notice and deletion removes it |
| T23 | Access teacher/admin page as learner | HTTP 403 access-denied page appears |
| T24 | Submit write request without CSRF token | Request is rejected with status 419 |
| T25 | Test 320 px, 768 px and desktop widths | No horizontal overflow; navigation and operations remain usable |
| T26 | Disconnect after loading static assets | Offline fallback appears for unavailable database pages |
