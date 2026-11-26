# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Local route harness review — `.66.b` — 2026-11-18

## Confirmed boundaries

- Synthetic baseline before/after review: announcements 0, notifications 0, WordPress posts 9. `wp db check` passed. Existing local WordPress users remained IDs 1, 116, 117 and 119 with their expected roles.
- The earlier President governance service-level helper is valid and repeatable; the separate CLI streaming-helper check is invalid because the production handler exits after streaming. The HTTP helper was not run until its loopback and cleanup behavior is made safe. Thus the governance HTTP route remains open.
- A proposed Communications draft helper was rejected before the handler ran because an overly broad baseline guard correctly detected the nine pre-existing WordPress posts. Two subsequent guard-edit attempts did not match; the helper was removed and was never executed against the route. No announcement/notification/post rows changed.

## Current status

This report records harness validation only, not product failures or PASS. The local isolated DB is clean. Positive authorization coverage for remaining Communications, Role Messenger, School-email, audio-admin, and governance download routes remains open. No production service was contacted.
