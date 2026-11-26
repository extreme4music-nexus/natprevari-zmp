## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# 🚨 OWNER POLICY — WORDPRESS MEMBER ROLE STACKING

## REQUIRED RULE — READ BEFORE CONTINUING ANY PHASE

**EVERY ZMP MEMBER ACCOUNT MUST HAVE THE WORDPRESS `subscriber` ROLE.**

**`zmp_teacher` IS OPTIONAL FOR A MEMBER, BUT ANY MEMBER MAY ALSO HAVE IT. IT MUST WORK TOGETHER WITH `subscriber` AND WITH OTHER VALID ZMP ROLES WITHOUT BREAKING THE PLUGIN.**

- `subscriber` is the required baseline membership role.
- `zmp_teacher` is additive, not a replacement for `subscriber`, and is not required for every member.
- Members may also hold any authorized combination of `zmp_moderator`, `zmp_commission`, `zmp_testator`, `zmp_organizer`, and `zmp_president`. Role assignment/removal must preserve the other existing roles.
- The President must always retain both `subscriber` and `zmp_teacher`, in addition to `zmp_president` and any other authorized roles.
- An external Commission person who is not a ZMP member may lack `subscriber` and `zmp_teacher`; if that person is a ZMP member, `subscriber` is required and `zmp_teacher` remains optional.
- Teacher-only functions (school profile, teacher directory, teacher subject proposals, and other explicitly teacher-scoped features) require `zmp_teacher`. Missing `zmp_teacher` must not break unrelated plugin functions for a valid Subscriber member.
- Presence of `zmp_teacher` must not make a valid role stack fail, hide unrelated plugin features, replace roles, or crash the plugin. Authorization must check the capability/role needed for each operation rather than requiring an exact one-role array.
- Test fixtures for a ZMP member must include `subscriber`. Teacher-positive fixtures must include both `subscriber` and `zmp_teacher`; negative Teacher-scope fixtures may omit `zmp_teacher` while retaining `subscriber`.

**THIS OWNER POLICY, RECORDED 2026-11-25, SUPERSEDES ANY CONFLICTING HISTORICAL WORDING IN ROADMAPS, PLANS, REPORTS, TEST NOTES, OR PHASE COPIES. HISTORICAL PASS/FAIL RESULTS KEEP THEIR ORIGINAL SCOPE AND MUST NOT BE EXPANDED TO COVER ROLE COMBINATIONS THEY DID NOT TEST.**

---

# Plan — ZMP 1.2.4.66.b

Status: IN PROGRESS / NOT READY. `.66.a` Auditor assessment is NOT ACCEPTED; `.66.b` is separate forward work and does not close `.66.a`.

## Approved phase scope
Enforce authorization on privileged routes: role, active mandate, category, region/stage and exact resource; apply the same protection to UI, direct URL, AJAX and REST where relevant; bind step-up to exact action/resource/request context; preserve ordinary reads and competition flows.

## Carried-forward scope — current status reconciled 2026-11-25
- President subject-proposal browser attempt: FAIL, submission did not complete; exact failure stage unknown.
- Teacher profile form render + nonce-backed handler save/readback: **PASS 10/10** on the current `.66.b` source; actual browser-driven submission remains NOT RUN. See `tests/phase66b-president-teacher-stacked-profile-self-setup.php` and the 2026-11-25 inherited-fix report.
- Partial-schema fail-closed injection: **PASS 4/4** on 2026-11-25; the older NOT RUN checkpoint is historical.
- Whole-phase/Master review incomplete; Auditor NOT ACCEPTED.
- `.65` GitHub source/branch provenance gap remains recorded.
- Owner-confirmed passkey enrollment and sign-in: PASS; do not repeat without relevant change.

## First Owner-selected privileged action: jury final-result confirmation
The Owner selected “finalizing a result” as the first `.66.b` step-up action.

### Acceptance contract
- Eligible commission assignee for the exact entry/phase or Administrator must complete fresh passkey step-up; a passkey never grants authority.
- Revalidate active season/category, exact entry/phase, assignment and result state before writing.
- Bind the challenge to user, session, origin, `zmp_category_jury` nonce, exact season/category/entry/phase and normalized score/qualification/factual-basis/rationale payload.
- Challenges are one-use and time-limited. Missing, invalid, canceled, expired, replayed, wrong-user, altered-context/payload, missing-assignment and database-failure cases must not create a final result or protected audit/announcement mutation.
- Active assigned commission members and administrators can self-enroll passkeys; registration options and finish enforce the same eligibility.
- Verify actual browser behavior, accessible status/keyboard behavior and responsive UI.

## Current implementation checkpoint
- Source implemented in `includes/class-zmp-category-jury.php`, `includes/class-zmp-passkeys.php`, and `assets/js/zmp-president-subject-stepup.js`.
- PHP lint and JS syntax check: PASS.
- Functional runtime/browser checks: NOT RUN; no `.66.b` source deployed to the active `.66.a` site.
- UI review, independent functional test, code review, whole-phase reconciliation and Auditor: NOT RUN.
- Package/GitHub sync: NOT RUN.

## Next step
Use a phase-isolated synthetic WordPress environment to test positive assigned-commission/admin behavior, denied/no-proof and altered-payload/replay cases, and confirm no mutation on denial. Then review actual desktop/mobile UI and continue the remaining phase route matrix. Do not package or claim `.66.b` acceptance until required evidence exists.


## Owner-directed Drive video flow — 2026-11-25

Owner-directed design: replace the teacher-facing YouTube upload/link flow with plugin-managed upload to restricted ZMP Google Drive. The teacher sees the selected filename and size; client and server validate the actual video; upload begins only after “Поднеси пријава”. The form stays in place with animated staged progress and no refresh until Drive upload, object/metadata verification, and jury playback authorization all pass. The entry remains a draft on any failure and supports an exact retry.

The jury sees a small thumbnail card, not a Drive URL or file ID. Clicking it opens playback through the authenticated ZMP stream after exact commission assignment checks. Reduce file size with a quality-preserving validated compression profile before upload; keep the original privately until success. Preserve category slot rules and physical/online/combined requiredness. The full design and acceptance checklist are in reports/DRIVE_VIDEO_SUBMISSION_AND_JURY_THUMBNAIL_DESIGN_1.2.4.66.b_2026-11-25.md.

Status: DOCUMENTED / NOT IMPLEMENTED OR TESTED. Existing Drive OAuth, resumable upload, private media storage, and authenticated player are the starting mechanism; the current upload handler redirects and the jury view has no thumbnail card. Do not count the design note as a runtime PASS. The main PROJECT_ROADMAP.md remains unchanged.
