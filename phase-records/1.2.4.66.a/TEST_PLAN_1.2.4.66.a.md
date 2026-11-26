# 🚨 CURRENT OWNER STATUS — 2026-11-26 — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE; ANY MEMBER MAY ALSO HAVE IT WITHOUT BREAKING OTHER ROLES OR PLUGIN FEATURES. THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`.**

**CURRENT .66.a CHECKLIST: ITEMS 1–7 PASS. FRESH ZIP, PHASE HASH CHECK, RESTRICTIVE LICENSE, AND GITHUB SYNC/READBACK ARE COMPLETE. OWNER INSTALLATION/TEST OF THE ZIP IS THE NEXT USER-SIDE STEP. NEWER SAME-SCOPE PASS EVIDENCE SUPERSEDES EVERY OLDER NOT RUN/FAIL/OPEN STATEMENT BELOW. AUTHORITATIVE STATUS: `reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`.**

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

# Test Plan — ZMP Competitions 1.2.4.66.a

Status: **IN PROGRESS**. Test only actual plugin behavior in a disposable local WordPress site with synthetic accounts and data.

## Setup gates
- Confirm absolute disposable-site path, synthetic-only DB identity/content and ZMP-only active plugin inventory.
- Record current WSL processes/ports; reuse a suitable phase server or create one only with PID/port/purpose ownership recorded.
- Record local PHP/WordPress versions, required crypto extensions and localhost/HTTPS origin; do not infer production compatibility.
- Review exact source/fixture hashes before staging. No secrets, keys, raw challenges, assertions, credentials or PII in output.

## Acceptance behaviors
1. Registration binds a verified passkey to the current authenticated owner with exact `zmp_president` role and active mandate; unauthorized or stale enrollment is denied.
2. The credential owner can remove their credential after current-password reauthentication and a dedicated nonce, including after role/mandate loss; another account cannot remove it.
3. Login validates ceremony, challenge, RP ID/origin, user verification and signature, then establishes a session only for the bound user.
4. Step-up proof is short-lived, one-use, bound to the intended action/resource and cannot be replayed or reused for another operation.
5. The protected route still independently checks WordPress authentication, role/capability, active mandate, resource/assignment and nonce before writing.
6. Invalid, expired, replayed, cross-user and unavailable-authenticator cases fail closed without protected data writes or authorization change.
7. Recovery/rollback is documented and tested without touching the persistent production-clone or unrelated `.65` state.
8. UI, if changed, is rendered and exercised at desktop/mobile with keyboard operation and clear no-authenticator/error states.

## Status
Historical pre-implementation storage red check: `tests/phase66a-passkey-store.php` failed because `ZMP_Passkey_Store` was absent; no DB changes occurred. Current storage behavior has since passed 65 focused assertions. Task 3 registration and management have passed 49 focused assertions after review-fix round 1; browser ceremony and UI acceptance remain pending. Do not test the runner/harness as a general subject.

## Registration scope decision — 2026-11-05
- Enrollment is limited to the current account owner with exact `zmp_president` role and an active mandate, after checking the account's current password. Both options and finish recheck the role and mandate.
- Removal is limited to the current credential owner and requires current-password reauthentication and a dedicated nonce; it remains available after demotion or mandate loss.
- The passkey is bound to the immutable WordPress user ID. Role/capability/active-mandate authority is evaluated from current WordPress state on each protected route; registration never grants a role.
- Registration options are short-lived and bound to the exact user and current WordPress session. Owner-reported HTTPS and endpoint self-tests are recorded in the phase report.
