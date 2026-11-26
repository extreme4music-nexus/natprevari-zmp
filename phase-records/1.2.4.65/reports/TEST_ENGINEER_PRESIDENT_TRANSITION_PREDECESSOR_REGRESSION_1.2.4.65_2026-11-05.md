# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Test Engineer — President scheduled-successor predecessor regression

**Status: PASS — targeted scheduled-route regression only**  
**Date:** 2026-11-05  
**Site/database:** existing local synthetic `.65` site `127.0.0.1:8094`, database `zmp65_isolated`  
**Code under test:** `includes/class-zmp-president-mandate.php`  
**Candidate SHA-256:** `5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40`

## Scope and preserved evidence

This run covers only the previously failing predecessor-chain behavior for an actual future scheduled President appointment through the authenticated WordPress admin UI/admin-post route. The exact-hash Code Reviewer PASS was received before this run. The installed plugin copy of the changed PHP file was synchronized and its SHA-256 matched the reviewed candidate before submitting the form.

Existing service, archive, photo, and other route/UI PASS evidence was not repeated. The active President #26, mandate #24, scheduled mandate #60, certificate/facsimile data, and all unrelated rows were left untouched. No certificate or facsimile records were read or changed.

## Route result

Using the already-open authenticated Synthetic Transition Administrator session, one synthetic Subscriber (#126) was selected in the real “Назначи претседател” form and submitted once with start date `2030-12-04`, the UI-calculated end date `2034-12-03`, and decision text `Synthetic Phase 65 predecessor-chain regression only; not an official decision.` The browser returned to the mandate page with the success notice that the future mandate was recorded. The UI displayed it under scheduled appointments and in the audit history.

Read-only database evidence for the new mandate showed:

| Check | Result |
|---|---|
| Mandate | #69, user #126, `scheduled`, `2030-12-04`–`2034-12-03` |
| Predecessor | mandate #60 / user #111 |
| Audit event | one `appointed` event #19 for mandate #69, actor #124 |
| Appointee role | Subscriber; no President role before the effective date |
| Current President | #26 remains the active President |
| Existing mandate rows | #23, #24, and #60 retained their prior users, states, dates, and links |

This corrects the prior regression’s predecessor-link failure: the new route-created row linked to the scheduled successor chain (#60/#111), not active mandate #24/#26.

## Cleanup and integrity

After collecting the route evidence, cleanup deleted only the exact test event #19, exact scheduled mandate #69 (matching user, status, dates, and predecessor IDs), and synthetic Subscriber #126. The temporary Administrator #124 was preserved. Post-cleanup queries confirmed #23/#24/#60 unchanged, #69 and event #19 absent, #126 absent, #26 still President, #111 still Subscriber, and #124 still Administrator. `wp db check --allow-root` reported `Success: Database checked.`

No application server was started or stopped. The existing local site remained available. The Owner’s pre-existing Chrome tab was reused and left open; no task tab was created.

## Evidence limits

This PASS applies only to the targeted future scheduled-successor admin-post route and predecessor chain after the reviewed PHP fix. It does not assert that the future appointment was activated by advancing time, does not replace the previously recorded lifecycle service evidence, and is not a whole-phase Master or Auditor verdict.
