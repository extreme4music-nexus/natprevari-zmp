# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Code Review — Subject Route Denial R3 Fixture — 2026-11-04

## Review result: PASS — exact candidate only

- Candidate: `tests/phase65-subject-president-route-denial-r3-clone.php`
- Exact SHA-256: `5FD2DBB4C28CF0EF6A91A0F334F69CA82008C43C841AFA8FB1CDAB7C5796A332`
- Candidate report reviewed: `reports/TEST_ENGINEER_SUBJECT_ROUTE_DENIAL_R3_CANDIDATE_1.2.4.65_2026-11-04.md`
- Candidate report SHA-256: `61C354C8929AD96AF4FB09B01ABB6A4B2D09AA1E984AE37261ADC030940512CC`
- PHP lint independently verified: **PASS** (`No syntax errors detected`).
- Runtime: **NOT RUN**; no site/database access was made for this review.

## Review findings

No actionable findings for this test-only candidate.

The positive control sends the same `$headers` array, containing the generated Teacher's `LOGGED_IN_COOKIE` and local referer, to `/wp-admin/profile.php` (lines 132–144). It requires HTTP 200 and the unique synthetic display-name marker in the returned profile HTML. Because the test creates that user with only the `zmp_teacher` role, a successful control establishes that this exact cookie is recognized for the intended synthetic Teacher before interpreting the queue denial. The profile body and marker stay in memory and are not printed or persisted.

The disposable environment bindings are retained: exact `ABSPATH`, DB name, prefix, version, loopback host/port, and hashes of the two loaded subject services (lines 7–29). The HTTP pre-request filter permits only the profile, queue, and decision paths on HTTP loopback port 8100 (lines 97–101). Redirects are disabled for the route requests.

For the queue GET, the fixture preserves the strict HTTP 403 requirement and the no-subject/no-decision-control assertions (lines 146–164). It reports only the numeric response status and a bounded Location path class selected from `none`, `login`, `profile`, `admin`, or `other` (lines 148–159). It does not output the Location URL, query string, domain, cookies, nonce, or response body. Thus a 302 can be diagnosed without following it or exposing redirect data. The valid-nonce decision POST still requires 403 with no redirect and a generic denial; proposal/event and catalog/alias state assertions remain (lines 166–185).

The reviewed cleanup remains constrained to exact generated identities and expected pending-submission state, restores the synthetic session/cookie and diagnostic options, and verifies the captured user and subject-table baselines (lines 187–258, 294–308). Unexpected rows make cleanup fail rather than triggering a broad delete.

## Residual scope

- This PASS is static review and lint only; runtime for the R3 hash remains **NOT RUN**. The candidate report correctly preserves the R1/R2 FAILs and does not claim acceptance.
- The scenario verifies Teacher denial for the President queue and decision route. It does not establish rendered UI behavior, wrong-role proposal submission behavior, or unrelated AJAX/REST coverage.
- The in-process request filter constrains this fixture's WordPress HTTP calls; it is not independent process-level egress enforcement.

Proceed to at most one runtime attempt only after fresh approved preflight and exact staged-hash verification. Record the positive-control and queue statuses plus the allowlisted Location class, keep the 403 assertion strict, and report cleanup and post-run database integrity separately.
