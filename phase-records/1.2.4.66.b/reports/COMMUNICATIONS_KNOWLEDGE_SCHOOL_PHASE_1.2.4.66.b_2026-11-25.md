# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Category results: knowledge-test school phase — .66.b local repair and runtime proof

Date: 2026-11-25 (Europe/Skopje).
Environment: retained isolated WordPress at `http://localhost:8103`, database `zmp66b_isolated`, prefix `z66a_`. Existing server only; no new server was started.

## Defect found

The active season/category configuration had the `school` stage enabled for the `knowledge_test` category (category 1). The Communications form exposed both that category and the school-phase option. A synthetic Moderator with a complete confirmed synthetic school result attempted publication through the real `ZMP_Communications::handle_publish()` handler. Before the fix, `ZMP_Category_Jury::stage_eligible_entries()` returned `false` because its allowed-phase list and enabled-stage queries omitted `school`; the confirmed-results gate denied publication. The run wrote no announcement, notification, or WordPress post and cleaned the synthetic users, entry, result, and closure exactly.

The initial observed runtime JSON was:
`ZMP_COMMUNICATIONS_KNOWLEDGE_SCHOOL={"phase":"school","category":"knowledge_test","eligible_only_synthetic_entry":false,"confirmed_gate":false,"published_announcement":false,"exact_phase_recipient_only":false,"linked_public_post":false,"email_not_sent":true,"exact_cleanup":true,"all_assertions_pass":false}`.

## Repair

Updated the `.66.b` source and the already-running local installation:
- `class-zmp-category-jury.php`: accept school as a stage; include enabled school stages in stage resolution and jury score-form rendering.
- `class-zmp-passkeys.php`: accept school as a bound jury-result finalization context.
- `class-zmp-mentor-category-results.php`: accept and render the school-phase filter.

All three files pass PHP lint. Candidate and active-runtime SHA-256 values match:
- Jury: `b4729465793cd039c7619d2e3e6df100dbdae280936353809b8e88fb44889b91`
- Passkeys: `caed7fab8795a8d502252f0505dd5bad5a34c66ec6f393050b8cfaf65f2c79a5`
- Teacher results: `758f574c87a3def976497b80a33fb0182f76e10077b0d380e3fafdf1192895df`

## Verification after repair

The same actual publication route was rerun with synthetic Moderator, Subscriber owner, school-stage entry, closed school stage, and confirmed result. PASS: only the exact eligible synthetic owner was notified in the in-site notification table; a published WordPress post was linked; no email was sent; and every tracked row and user count returned to its pre-test value.

`ZMP_COMMUNICATIONS_KNOWLEDGE_SCHOOL={"phase":"school","category":"knowledge_test","eligible_only_synthetic_entry":true,"confirmed_gate":true,"published_announcement":true,"exact_phase_recipient_only":true,"linked_public_post":true,"email_not_sent":true,"exact_cleanup":true,"all_assertions_pass":true}`.

A separate read-only render used a temporary synthetic Subscriber+Teacher and confirmed the school filter was present and selected, the passkey step-up context accepts the school phase, and the configured school stage returns the expected empty array after cleanup. The synthetic Teacher was removed exactly. Result: `ZMP_SCHOOL_PHASE_READBACK={"teacher_role_loaded":true,"teacher_capability":true,"school_filter_rendered_and_selected":true,"school_jury_stepup_context_allowed":true,"school_stage_empty_fixture_returns_array":true,"exact_cleanup":true,"read_only_business_data":true,"all_assertions_pass":true}`.

Two regressions passed after the change:
- Regional category-result publication still selected only the qualified regional participant, created its linked post, sent no email, and restored all tracked data. The legacy fixture's unused `database_check=false` field was removed so it no longer advertises an unrun check; the independent database check is recorded below.
- Jury qualification normalization and actual POST denial/no-write assertions passed.

`wp db check` passed for every table; the local home page remained HTTP 200. The final local WordPress user list contains only baseline accounts (IDs 1, 116, 117, 119); no synthetic test account remains.

Fixtures and SHA-256:
- `tests/communications-knowledge-school-phase-runtime.php`: `1f2a251842531c98d12da34fdb10f3f4ca380c00458d5720f8081d874c08c14a`
- `tests/school-phase-readback-runtime.php`: `99a251a5272b0b76e0bb27836a232cccfd6c3280044082a67537acbac02fe69d`
- `tests/communications-category-results-recipients-runtime.php`: `65f81792684eea61deef5ddcfc3c4e535c9174a00af1073932f730658f3259a7`
- Captured final outputs: `reports/COMMUNICATIONS_KNOWLEDGE_SCHOOL_PHASE_RUNTIME_2026-11-25.txt`.

No production site, external mail service, audio service, or YouTube was contacted. No real competition row was changed.

## Still open

This closes the category-scoped school-phase result gate/publication and render contract locally. A full browser authenticator/passkey ceremony and persistence test specifically for school-phase finalization remains NOT RUN. Other role/phase combinations, broader browser routes, and the remaining `.66.b` review/release gates remain open; this does not mark the phase complete.
