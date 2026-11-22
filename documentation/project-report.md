# CampusConnect Pro — project report

Three portals (Student, Club, Admin) on one PHP 8 / MySQL application with role-based access control checked server-side on every page, PDO prepared statements, CSRF tokens, escaped output, password_hash, session regeneration, login lockout and validated uploads.

## Implemented
Authentication and roles; student registration with email verification; club accounts created by admin; club profile, members, executive positions with posting permission; post drafts, preview, submit, admin approve/reject with mandatory reason, resubmission, approval history, re-approval after editing live posts; public feed with likes, comments (add/edit/delete/report), saves, search/filter; events with approval, registration, waiting list, deadline, department eligibility, cancellation; digital tickets with QR; QR attendance scanner with duplicate prevention; attendance CSV; certificates with QR verification; in-app notifications; optional PHPMailer email; notices and emergency announcements; venues with conflict detection; admin moderation, analytics charts, activity logs, settings.

## Added in v2
Interactive monthly event calendar (student), event feedback and star ratings (students rate attended events; clubs see anonymous ratings and comments), in-app 48-hour event reminders on the student dashboard, live list filtering, client-side validation, password strength meter, character counters, redesigned UI, demo data seeder.

## Not implemented (listed honestly)
Club gallery, club announcements page, volunteer management, sponsorship management, club budget tracking, lost and found, campus map, multi-image posts (table exists), OAuth mailbox verification (reminder emails; in-app "starting soon" alerts exist), dark/light mode is client-side only. These can be added as later phases on the same schema.

## Design decisions
- Club members are records managed by the club account, not separate logins; the club logs in with its official email.
- "Approved" and "Published" are the same moment for posts: approving publishes.
- Editing a live post sends it back to Pending and removes it from the feed until re-approved (simple and safe).
