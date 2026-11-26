# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# President stacked-role dashboard access — 1.2.4.66.b

Date: 2026-11-25

## Owner context

The Owner says yesterday's checks passed on `127.0.0.1:8100`. Those PASS results are retained as Owner-reported evidence and are not replaced by this supplemental run. The prior runtime notes for this work session pointed to an already-running separate local clone at `http://localhost:8103`; no new server was started. The code change below was checked only against the 8103 clone and must not be presented as a rerun of the 8100 acceptance.

## Finding and fix

`ZMP_President_Dashboard` gated the dashboard, menu, and President-only admin scope on a role list containing exactly `zmp_president`. That rejected the required `subscriber + zmp_teacher + zmp_president` profile and any otherwise-approved stacked roles.

Changed the dashboard role guard to require `subscriber`, `zmp_teacher`, and `zmp_president`, while permitting only the established additional ZMP member roles (`zmp_moderator`, `zmp_commission`, `zmp_testator`, `zmp_organizer`). Administrator exclusion remains; dashboard visibility still requires an active mandate. Candidate and active local runtime hashes match for `includes/class-zmp-president-dashboard.php`: `c4b888a081f248f343819fd9d7ff3188b6a54e1c25b7d6ab982d70ade3cd6a9a`. PHP lint passed.

## Supplemental runtime evidence on 8103

`tests/president-dashboard-stacked-member-current-runtime.php` passed 7/7: a synthetic active President with Subscriber + Teacher + Moderator + Commission + Testator + Organizer was recognized, received the dashboard menu, and rendered the dashboard; a President without Teacher was rejected; a correctly stacked President without an active mandate received no dashboard/menu; Administrator was not treated as President. Exact cleanup passed. Fixture SHA-256: `363b3fdb99e43333300d5295b61cfd1a9ae15fbb1a01a480ce9e0fa6988b1c73`.

After the guard change, the existing stacked-President school-email service and admin-post regressions passed 8/8 and 10/10 when run on 8103 with WP-CLI user 1. Their first invocation without `--user=1` stopped at the test harness's admin preflight before any fixture writes; the corrected invocations passed and restored the fixture state.

## Limits

This is supplemental isolated-clone evidence tied to the current candidate/runtime hash. It is not a repeat or replacement of yesterday's 8100 checks, does not inspect production, and does not close broader phase/release gates.
