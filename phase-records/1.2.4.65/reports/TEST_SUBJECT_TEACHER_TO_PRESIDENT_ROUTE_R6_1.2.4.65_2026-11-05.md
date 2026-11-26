# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer runtime attempt — subject Teacher to active President route (R6)

**Result: BLOCKED** — the exact reviewed fixture stopped at its diagnostics-option baseline guard, before reporting route assertions. This is not a functional PASS or FAIL for the route behavior.

## Scope and candidate

- Authorized target: one fresh synthetic WordPress site under `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, database `zmp65_subject_test`, loopback port `8097`.
- Existing active `.65` site: `/home/tonic/zmp-local-sandbox/phase65-test/site`, database `zmp65_isolated`, PHP PID `145525` on `127.0.0.1:8094`.
- Fixture: `tests/phase65-subject-register-admin-post.php` from `zmp-competitions-1.2.4.65`.
- Fixture SHA-256 before staging and at execution: `06911F1CACD1683D582269DAA360E632945515B873518CC930BACC788C01888C`.
- The `.65` candidate plugin source was copied from the version folder to the disposable site's sole plugin directory. No product source was edited.

## Procedure and outcome

1. Confirmed the assigned site and temp paths were absent, port `8097` was unbound, active site DB was `zmp65_isolated`, and the active PHP service was PID `145525` on port `8094`.
2. Captured normalized full export hash of the active DB before the attempt: `b0a08f61824049d80c7f0a350ca9f4d00fbcf317e1227928420b0497ba6064c`.
3. Staged WordPress core from the active local synthetic install, copied only the `.65` plugin source, configured the isolated test DB/prefix and loopback URL, performed a fresh WP install, and activated only `zmp-competitions`. WP-CLI confirmed database `zmp65_subject_test` and active plugin `zmp-competitions`.
4. Started the task-owned PHP server as PID `280980` on `127.0.0.1:8097` and invoked exactly once:

   `wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6 eval-file /mnt/z/Documents/Natprevari_ZMP/zmp-competitions-1.2.4.65/tests/phase65-subject-register-admin-post.php`

   Exit code: `1`. Fixture output: `BLOCKED: exact route fixture requires an absent diagnostics option baseline.` No route assertion PASS/FAIL output was produced. WordPress emitted repeated `wp_is_block_theme` early-call notices during WP-CLI bootstrap; these did not prevent fresh install or plugin activation and are not evidence of route behavior.
5. Stopped only task PHP PID `280980`; verified `8097` unbound. Dropped only `zmp65_subject_test`, then removed only the task-owned `site-subject-r6` and `tmp/subject-route-r6` paths. Verified those paths absent and the DB absent.
6. Recomputed the normalized full active DB export hash: `b0a08f61824049d80c7f0a350ca9f4d00fbcf317e1227928420b0497ba6064c` (unchanged). Verified active PHP PID `145525` still listens on `8094`. The persistent production-clone sandbox was not accessed.

## Gate disposition

`subject_teacher_to_active_president_route_runtime`: **BLOCKED**. The reviewed fixture's explicit baseline guard prevents route execution on this fresh install. This bounded attempt is complete; no replacement fixture, baseline seeding, or retry was made. Preserve the prior R3 FAIL and all President photo/consent PASS evidence unchanged. The gate ledger was not edited.
