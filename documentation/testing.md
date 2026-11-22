# Testing checklist

Run these by hand after installing and tick every item before submission.

## Auth & security
- [ ] Student registers → verification link works → login works; unverified login is refused
- [ ] Wrong password 5 times locks the account for 15 minutes
- [ ] Student opening `/club/dashboard.php` or `/admin/dashboard.php` gets 403; logged-out users are redirected to login
- [ ] A form posted without the CSRF token is rejected
- [ ] Uploading a `.php` file renamed to `.jpg` is refused

## Club / approval workflow
- [ ] Club cannot create a post until an executive with "can post" exists
- [ ] Draft → Submit → status Pending; admin sees it; student feed does not
- [ ] Admin rejects with empty/short reason → blocked; with reason → club sees it, students and other clubs cannot
- [ ] Club edits rejected post and resubmits → Pending again, history shows "resubmitted"
- [ ] Admin approves → appears in feed; editing a live post sends it back to Pending and removes it from the feed
- [ ] Club A cannot open `club/create-post.php?id=<club B post id>` (404)

## Students
- [ ] Like / unlike, save / unsave, comment, edit own comment, delete own comment, report others' comment
- [ ] Search and filters on feed and events

## Events, tickets, attendance, certificates
- [ ] Event submit → admin approve → visible to students; venue/time conflict blocks submit
- [ ] Register creates ticket + QR; duplicate registration blocked; full event → waiting list; cancel promotes the next student
- [ ] Club scans QR (or types code): first scan records, second scan is blocked as duplicate; a ticket from another event is refused
- [ ] Attendance CSV downloads; "Issue certificates" creates certificates only for attendees
- [ ] `certificate.php` prints; `verify-certificate.php` confirms the code; `verify-ticket.php` works

## Admin
- [ ] Create/disable club, reset club password, approve/reject club email change
- [ ] Hide / delete reported comment; publish notice (emergency shows on student dashboard)
- [ ] Analytics charts load; activity log lists actions

## v2 features
- [ ] Student > Calendar shows approved events, month arrows work, registered events are highlighted; phone width shows a list
- [ ] Student can rate only events they attended; rating 1-5 saves; editing updates it
- [ ] Club > Event Feedback shows average and anonymous comments for its own events only
- [ ] Dashboard shows "Starting soon" for registered events within 48 hours
- [ ] Long tables get a filter box; register form shows password strength bar
- [ ] seed-demo.php refuses non-localhost requests

- [ ] Club cancels an approved event with a reason: every registered/waitlisted student gets a bell notification with the reason
- [ ] Opening that notification shows the event page with a red "cancelled" banner and no register button; My Registrations shows "Event cancelled"
- [ ] A student never sees how many people registered (event page shows only "Limited seats" or "Open to everyone")
