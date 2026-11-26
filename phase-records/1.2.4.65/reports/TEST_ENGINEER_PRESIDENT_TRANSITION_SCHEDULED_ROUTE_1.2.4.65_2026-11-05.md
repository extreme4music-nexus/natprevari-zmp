# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Test Engineer — President scheduled-successor admin-post route — ZMP 1.2.4.65

Date: 2026-11-05
Scope: One actual scheduled appointment submission through the authenticated Administrator UI/admin-post path, plus exact cleanup. This is not a whole President-transition PASS.

## Existing PASS evidence preserved
- No prior PASS work was repeated: President lifecycle service fixture 23/23, archive access 15/15, President photo transition 12/12, and their associated UI checks remain as previously reported.
- The authorized existing `.65` local site was used: `http://127.0.0.1:8094`, WSL `/home/tonic/zmp-local-sandbox/phase65-test/site`, DB `zmp65_isolated`, prefix `z65_`. No site/database/server was created or stopped.
- Existing active tab `2090200110` was confirmed as `Synthetic Transition Administrator` by its rendered admin toolbar/menu. No credentials were entered and no tab was created.

## Route execution and results
- A new synthetic Subscriber `zmp65_transition_successor_20261105` (temporary user ID `125`) was created for this run. The form selected #125, start `2030-12-04`, and synthetic decision text explicitly marked as test-only. The source `ZMP_President_Mandate::term_end_date()` computes `start + P4Y - 1 day`; rendered end date was `2034-12-03`.
- The real UI form was submitted once. WordPress returned to the mandate page with `zmp_mandate_notice=scheduled`; the rendered success notice said the future mandate was recorded and access begins on its start date. The scheduled appointment and one appointment-history row were visibly rendered.
- **PASS — scheduled appointment route and invariants:** newly created row #68 was `scheduled`, user #125, dates `2030-12-04`–`2034-12-03`; exactly one audit row #18 (`appointed`, actor Administrator #124) recorded the same dates. User #125 remained `subscriber`; active President #26 remained `zmp_president`; active mandates #23/#24 were unchanged. No effective-date transition was forced.
- **FAIL / NEEDS DEBUGGER — predecessor chain:** the new row linked to predecessor mandate #24/user #26. Existing scheduled mandate #60/user #111 ends `2030-12-03`, immediately before the new term, but was not selected. In `includes/class-zmp-president-mandate.php::appoint_locked()` (source SHA-256 `DD941ADFD7EFE48AA339BE2938CAB409F195321679EE01A5CD58CA5E0E903EDE`), predecessor lookup selects prior rows only from `active`, `expired`, or `replaced`, excluding `scheduled`. Preserve this as a concrete possible transition-chain defect; do not silently call the chain PASS. No product code was changed.
- The role-denial UI result is separately documented as PASS in `TEST_ENGINEER_PRESIDENT_TRANSITION_ROLE_DENIAL_1.2.4.65_2026-11-05.md`; it is not repeated here.

## Exact cleanup and restoration
- Deleted only audit row #18 where the exact mandate/user/event/actor matched this run; deleted only scheduled mandate #68 with the exact user/date/status/predecessor/actor/decision fields above; deleted only synthetic user #125. Parent-owned temporary Administrator #124 and all other records were left untouched.
- Post-cleanup read-only query confirmed original mandate rows #23/#24/#60 and their fields are unchanged; mandate-audit table is empty as in the baseline; user #125 is absent; user #26 remains `zmp_president`.
- `wp db check --allow-root`: **PASS**, exit 0. No certificate/facsimile records were queried or changed. No active mandate, existing user, server, product source, test fixture, or `PROJECT_ROADMAP.md` was changed.

## Disposition
- Keep the route submission/state/audit/role invariant result as **PASS**; keep the predecessor-chain subcriterion **FAIL / NEEDS DEBUGGER** pending independent product diagnosis. No rerun requested. Whole `.65` remains IN PROGRESS; this does not authorize whole-phase Master/Auditor, packaging, or `.66`.
