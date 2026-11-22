# Database design

Single MySQL database `campusconnect` (InnoDB, utf8mb4), all relations enforced by foreign keys.

| Table | Purpose |
|---|---|
| users | One login row per student, club account and admin (`role`), hashed password, lockout counters |
| students | Student profile (1:1 with users) |
| clubs | Club profile (1:1 with a `club` user), official email |
| club_emails | History of official-email verification / change requests |
| club_members | Members and executive positions; `can_post` = authorised to author posts/events |
| posts | Club posts; status draft → pending → published / rejected → archived |
| post_images | Extra images (reserved for multi-image posts) |
| post_approvals | Full approval history (submitted, resubmitted, approved, rejected, archived) with reason and internal note |
| post_comments / post_likes / post_saves / post_reports | Feed interaction and moderation |
| events | Club events; status draft/pending/approved/rejected/cancelled. "Registration open / ongoing / completed" is derived from dates |
| event_approvals | Event approval history |
| event_registrations | One row per student per event; status registered / waitlist / cancelled |
| event_tickets | Unique ticket code per active registration (QR payload) |
| attendance | One scan per ticket (UNIQUE ticket_id blocks duplicates) |
| certificates | Unique certificate code, one per student per event |
| notifications | In-app notifications |
| notices | Admin notices, including emergency announcements |
| venues | Venue list used for suggestions and conflict view |
| email_verifications | Hashed one-time tokens for student email verification |
| activity_logs | Audit trail |
| settings | Key/value system settings |

Key constraints: `users(email, role)` unique, `students.student_code` unique, `event_registrations(event_id, user_id)` unique, `attendance.ticket_id` unique, `certificates(event_id, user_id)` unique, `post_likes(post_id, user_id)` primary key.
