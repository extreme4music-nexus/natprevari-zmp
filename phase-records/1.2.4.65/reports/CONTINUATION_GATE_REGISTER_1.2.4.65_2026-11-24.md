## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# `.65` continuation gate addendum — 2026-11-24

This addendum records only new work performed after the historical 2026-11-05 gate ledger. It does not overwrite or reinterpret that ledger's historical results.

| Gate | Status | Evidence | Scope |
|---|---|---|---|
| `messenger_legacy_scoped_threads_index_migration` | **PASS** | `MESSENGER_LEGACY_SCOPED_THREADS_REPAIR_1.2.4.65_2026-11-24.md`; fixed source/runtime SHA-256 `BABFE20C900A4421DFD5154939EEE84BEB9824A80FF3B56639B8E9FC202BC655` | Legacy index replacement, retained synthetic row, unique-index fail-closed behavior, recovery, repeat installation, exact fixture cleanup, and database integrity. |

The overall `.65` phase remains **IN PROGRESS / NOT READY**. The addendum closes no other historical FAIL, BLOCKED, or NOT RUN gate. It makes no release, ZIP, GitHub, Actions, or Auditor claim.
| `teacher_subject_submit_stacked_role_http_route` | **PASS** | Per-phase `TEACHER_SUBJECT_ROUTE_STACKED_ADMIN_POST_*.md` and exact fixture hashes in `tests/subject-register-stacked-teacher-admin-post.php` | Synthetic direct POST, stacked teacher/member roles, proposal/event persistence, no mail/outbound marker, exact cleanup on `.65`, `.66`, `.66.a`, `.66.b`. Scoped gate only. |
| `school_email_stacked_president_http_approval` | **PASS** | Per-phase `SCHOOL_EMAIL_STACKED_PRESIDENT_ADMIN_POST_*.md` and shared fixture hash `4B2557C33713A3A6F21023F06828636A11EEA10CB3E1F9FA46A008C52191ECF4` | Authenticated loopback `admin-post` approval, stacked President role retention, privacy/audit checks, blocked core update request, exact cleanup on all four snapshots. Scoped gate only. |
