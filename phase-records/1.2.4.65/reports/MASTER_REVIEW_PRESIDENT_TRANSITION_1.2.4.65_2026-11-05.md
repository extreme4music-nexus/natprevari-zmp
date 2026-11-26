# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Scoped Master Review — President transition — ZMP Competitions 1.2.4.65

Date: 2026-11-05
Scope: Administrator appointment route, scheduled-successor predecessor chain, and related role/state invariants only. This is not whole-phase acceptance.

## Evidence reconciled

1. **President role denial — PASS.** The live local President #26 session was denied the Administrator-only mandate page; read-only before/after mandate inventory matched. No HTTP status is claimed because none was captured. Evidence: `TEST_ENGINEER_PRESIDENT_TRANSITION_ROLE_DENIAL_1.2.4.65_2026-11-05.md`.
2. **Initial scheduled appointment route — scoped PASS with predecessor-chain FAIL.** The actual Admin UI/admin-post submission created a scheduled successor and one appointment audit event, kept the appointee a Subscriber and preserved existing active Presidents/mandates. It incorrectly linked the new mandate to #24/#26 rather than the nearest prior scheduled #60/#111. Preserve the initial report and failure; it is superseded only for that chain defect by the corrected regression. Evidence: `TEST_ENGINEER_PRESIDENT_TRANSITION_SCHEDULED_ROUTE_1.2.4.65_2026-11-05.md`.
3. **Debugger diagnosis — confirmed source defect.** `ZMP_President_Mandate::appoint_locked()` selected prior mandates only in `active`, `expired`, or `replaced` status, omitting an earlier valid non-overlapping `scheduled` appointment. The stored predecessor is used by later handover/archive authorization. Debugger exact source SHA before fix: `DD941ADFD7EFE48AA339BE2938CAB409F195321679EE01A5CD58CA5E0E903EDE`. Activation/access impact is source-derived; no activation test was run here.
4. **Backend fix and Code Review — PASS.** Candidate `includes/class-zmp-president-mandate.php` SHA-256 `5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40`. The changed query includes the nearest prior eligible scheduled appointment, while excluding revoked/cancelled appointments and scheduled terms expired before activation. PHP lint and source/install hash parity PASS. Independent exact-hash Code Reviewer returned PASS with no findings. The durable exact-hash review is recorded in `reports/CODE_REVIEW_PRESIDENT_TRANSITION_PREDECESSOR_FIX_1.2.4.65_2026-11-05.md`; it documents the already-completed review, with no additional review or test.
5. **Corrected actual route regression — PASS.** After the exact-hash Code Review, the real Admin UI/admin-post route created scheduled mandate #69 for synthetic Subscriber #126, 2030-12-04–2034-12-03. It linked to predecessor #60/#111; exactly one `appointed` event #19 was recorded; the appointee remained Subscriber; #26 remained President; and pre-existing mandate rows #23/#24/#60 remained unchanged. Exact test rows/user were removed, baseline restored, and `wp db check` PASS. Evidence: `TEST_ENGINEER_PRESIDENT_TRANSITION_PREDECESSOR_REGRESSION_1.2.4.65_2026-11-05.md`.
6. **UI Reviewer evidence — NOT RUN, preserved.** The reviewer could not capture the transition page after bounded active-tab attempts; `get_window_state` timed out. No page content, viewport, screenshot or accessibility tree was returned. The actual Test Engineer route report records the visible success notice/scheduled row/audit entry, but it is not represented as an independent UI Reviewer verdict. Reports: `UI_REVIEW_PRESIDENT_TRANSITION_1.2.4.65_2026-11-05.md`, `UI_REVIEW_PRESIDENT_TRANSITION_FOLLOWUP_1.2.4.65_2026-11-05.md`, and `UI_REVIEW_PRESIDENT_TRANSITION_ACTIVE_ADMIN_CAPTURE_1.2.4.65_2026-11-05.md`.

## Verdict

**SCOPED MASTER PASS — backend President scheduled-successor transition route and predecessor-chain behavior.** The actual route, exact-hash source review, corrected predecessor link, role/state invariants, cleanup, and DB integrity are supported by independent evidence. The source change was backend-only; the actual route was exercised through the rendered Admin UI. The standalone UI Reviewer capture remains NOT RUN and is not claimed as PASS.

This verdict does not close the separate `.65` UI review gate, whole-phase Master review, package/provenance, GitHub synchronization, Actions, Auditor, or any later roadmap point. Preserve existing PASS evidence; do not rerun the transition route or previously passed lifecycle/archive/photo/facsimile checks absent changed code/configuration or specific evidence invalidity. No facsimile/certificate records were accessed in this transition review.

## Next action

Reconcile this scoped PASS into current `.65` status records. Continue only with the next open roadmap criterion after the transition point; keep the uncompleted standalone UI review explicitly visible for the whole-phase gate. Do not mark `.65` ready for Auditor.
