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

# Design — ZMP Competitions 1.2.4.65

Status: **IN PROGRESS / UNRELEASED**  
Parent: frozen and Auditor-accepted `.64`, GitHub `version/1.2.4.64` at `b66e70ecdc78010001c270c329298e2f2cbbc920`.

## Phase objective

Integrate and privacy-review the President, school register and official-email workflows from `.47`–`.64`, preserve authorized election/document behavior, and complete the `.65` roadmap acceptance before packaging.

## Required behavior

1. Verify the integrated lifecycle from appointment and mandate through election documents, signature/certificate, facsimile use/revocation, President transition, multi-school profile, register and official-email request.
2. Verify role isolation, direct URL/AJAX/REST authorization, CSRF/nonces, signatures/hashes, key rotation/loss, secret-safe logs, document access, consent/retention and archive behavior. Do not claim legal approval without evidence from the Board and legally authorized reviewer.
3. Add an opt-in President profile-photo setting for the Home tab. Default is OFF. During a documented transition, both currently recorded Presidents may appear; each image appears only when that President opted in. Respect opt-out and loss of eligibility immediately; expose no private upload URL or image metadata.
4. Diagnose and fix the owner-provided `schema_not_ready` reports only in an isolated synthetic clone. Their origin/version is unverified. Reproduce profile, AJAX and admin-post paths with ready and deliberately incomplete schemas; preserve fail-closed behavior and clean the disposable database after checks.
5. Diagnose and fix the owner-reported approved-audio URL checker issue in a local synthetic environment. Separate WordPress action authorization/nonce failures from the remote server-side probe result. Keep outbound checks bounded and SSRF-safe. A probe 403 must not be represented as proof that a URL is broken; a true remote 403/other unavailable result remains visible and must not silently become automated success.
6. Update the dashboard What's New panel to cover `.63`, `.64` and `.65`; update `.65` What's New and Debugging documents with implemented behavior and evidence. `.65` remains marked unreleased until its gates pass.

## Evidence boundaries

- `.63` and `.64` release artifacts remain frozen. Their records may serve as historical evidence and parent baselines only.
- Owner-provided `schema_not_ready` logs and URL-check screenshots are reports, not proof of root cause or origin.
- The supplied audio URL is not available in the screenshots; never access production or attempt a production URL.
- Production/Legal approval and live-site claims require separate owner-supplied evidence; do not infer them from code or synthetic tests.
