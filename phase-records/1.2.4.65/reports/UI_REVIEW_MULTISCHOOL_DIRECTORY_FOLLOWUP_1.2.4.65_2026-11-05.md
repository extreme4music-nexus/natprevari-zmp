# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Independent UI Review — Multi-School Teacher Employment Directory — 1.2.4.65 — 2026-11-05

## Verdict
**PASS within the populated-directory responsive, keyboard, semantic, and visual-privacy UI scope.** This is the independent UI Reviewer result. It does not cover exact anonymous database no-mutation, backend-only assertions outside the rendered UI, or whole-phase readiness.

## Environment and route
- Existing local synthetic WordPress only: `http://127.0.0.1:8094/?page_id=37`.
- Existing site: `/home/tonic/zmp-local-sandbox/phase65-test/site`, DB `zmp65_isolated`.
- Reviewer used a new task-owned Chrome tab and closed it after review. No new site/sandbox/server was created; no site data was changed.
- Authenticated role: synthetic `zmp_teacher` (account ID 130). No credentials recorded.

## Findings
- Teacher view rendered both synthetic school groups and their opted-in employment/status fields.
- Synthetic official email and private replacement details were not rendered.
- Desktop 1440×900 and mobile 390×844 both had no horizontal overflow.
- At mobile size, the menu opened and closed by keyboard. Tab focus advanced through toolbar, menu, social links, and footer.
- The accessibility tree exposed the directory's headings, lists, and definition lists.
- Temporary viewport override was reset after the review.

## Boundaries
This independent UI PASS supersedes the earlier login-blocked UI observation only for the criteria above. Keep `multischool_anonymous_exact_db_no_mutation` BLOCKED. A separate independent functional runtime verification is being recorded before changing the current integrated gate disposition. President photo/consent PASS evidence is unaffected.
