# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

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

# Test Plan — ZMP Competitions 1.2.4.65

All tests are **NOT RUN** at phase start. Use a phase-scoped disposable WordPress site derived from the persistent WSL clone, synthetic-only database, and only the `.65` candidate plugin.

| Area | Required scenarios | Acceptance |
|---|---|---|
| Migration | `.64`→`.65`, repeat install, retained rows, readiness markers | Expected additive schema, no lost/duplicate rows, integrity check clean |
| President life cycle | active mandate, expired/outgoing/new President, election documents, certificate/signature/facsimile use/revoke | Server-side authorization and exact active/transition state; no cross-role access |
| Home photo | default OFF; opt-in/out for each recorded President; two-President transition; eligibility loss | Only opted-in eligible records display; immediate removal; no private URL/metadata leakage |
| Register and email | multi-school assignments, subject/grade, search/export, request/decision/disclosure | Correct scope, privacy, audit trail; no email exposure except authorized request |
| Authorization/security | direct URL, AJAX, REST, CSRF/nonces, mixed/unapproved roles, key rotation/loss | All paths enforce same server-side role/resource checks; logs contain no secrets |
| Schema readiness | profile, AJAX-triggered retry, admin-post; fully ready vs missing table/column/type/nullability/index | Ready flows succeed; incomplete schema safely fails closed with actionable diagnostics; DB clean |
| Audio checker | action POST valid/invalid nonce/capability; outbound 2xx, fingerprint, redirect, 403, 404/5xx, timeout, DNS/network and unsafe/private URL | Separate local handler denial from remote probe result; real 403 visible, never auto-success; SSRF bounds intact |
| UI | dashboard What's New `.63/.64/.65`, President preference/Home, audio status and admin action | Rendered UI correct, accessible labels/status, keyboard behavior, mobile layout |
| Regression | affected `.47`–`.64` integrations only where `.65` changes invalidate prior evidence | Preserve unchanged valid PASS; rerun only affected/missing checks |
| Cleanup | synthetic rows/files, disposable DB/site, process and port | integrity PASS; owned processes stopped; ports released; persistent clone untouched |
