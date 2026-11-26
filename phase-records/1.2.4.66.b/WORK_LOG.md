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


## `.66.b` Owner-confirmed jury result and UI checkpoint — 2026-11-12 23:48 Europe/Skopje
- Owner explicitly confirms the passkey prompt appeared and the operation succeeded; five screenshots show the final result confirmed. This is PASS evidence for the browser step-up/finalization flow. Do not ask for PIN proof or repeat the completed ceremony.
- Read-only isolated database verification: one scoped confirmed result (jury average 92.50, final 100.00, first award, actor 116), one `result_confirmed` audit event; WordPress DB check passed with all tables OK.
- Owner accepts current Desktop UI: PASS. Mobile View remains NOT RUN because the provided captures show desktop. No mobile PASS inferred.
- `.66.b` remains IN PROGRESS / NOT_READY; broader phase cases, independent reviews, packaging, GitHub source-hash sync and Auditor gate remain open. `.66.a` PHP server on 8101 remains stopped; its MariaDB/data remain preserved. `.66.b` local PHP 8103 and its isolated MariaDB are retained for continued checks.

## 2026-11-25 — Category result notifications restricted to exact phase eligibility
A synthetic local runtime test found category result announcements were notifying all accounts with entries in the year/category, even where an entry was not qualified for the selected stage. Updated `ZMP_Communications::recipients()` to derive category-result recipients from the exact stage-eligible, non-disqualified entries after resolving one unambiguous season. The real direct-publish handler passed: one qualified synthetic recipient notified, one unqualified synthetic entry and a pre-existing draft entry not notified; linked published WordPress post; no email; exact cleanup; `wp db check` all tables OK. Source/runtime SHA-256 matched: `59F2593AB5F1D5FC534E8B5661CCCDBD7F1F785FA704E9895E4EE261504256F5`. Evidence: `reports/COMMUNICATIONS_CATEGORY_RESULTS_RECIPIENT_SCOPE_1.2.4.66.b_2026-11-25.md`. Category saved-draft route and broader Communications cases remain open; phase remains IN PROGRESS / NOT_READY.


## 2026-11-25 — Qualification normalization runtime gap closed
The historical Nov. 13 `NOT RUN` is superseded for the exact current handler hash by `reports/JURY_QUALIFICATION_NORMALIZATION_RUNTIME_1.2.4.66.b_2026-11-25.md`. Six normalization inputs passed, including rejecting `'on'`, `'2'`, and `'1'` at the terminal stage; the actual finalization POST rejected `'on'` before passkey/persistence, result and audit counts stayed unchanged, synthetic admin was removed, and DB integrity passed. No product source change in this retest. Full passkey ceremony, remaining route variants and whole-phase acceptance remain open.

## 2026-11-25 — Regional-only passkey registration options verified
The earlier regional-only enrollment `NOT RUN` is superseded for eligibility and registration-options issuance by `reports/REGIONAL_ONLY_PASSKEY_REGISTRATION_OPTIONS_1.2.4.66.b_2026-11-25.md`. A synthetic commission account with an exact active-season regional assignment and no ordinary assignment received valid local `localhost` registration options and an exact unconsumed challenge; no credential was created. Exact cleanup passed, including preserving/restoring pre-existing expired challenge rows, and database integrity passed. Browser authenticator ceremony and credential persistence remain NOT RUN; do not mark full enrollment complete.

## 2026-11-25 — Pending-document finalization guard verified
The historical pending-document direct-rejection `NOT RUN` is superseded for the current local handler by `reports/PENDING_DOCUMENT_FINALIZATION_GATE_1.2.4.66.b_2026-11-25.md`. Approved documents showed the finalization form; one pending review hid it. A direct HTTP finalize request with a valid synthetic signed step-up assertion redirected to `documents_not_approved` without result or audit mutation. Exact cleanup/restoration, PHP lint and database integrity passed. This is one bounded synthetic entry/phase only; broader finalization and route cases remain open.

## 2026-11-25 — Saved category-results draft publication verified
The saved-draft category-results recipient case now passes in the isolated local runtime. The actual `handle_publish_draft()` promoted a synthetic regional results draft only after the exact phase gate passed, notified only the qualified synthetic entry owner, linked one published WordPress post, attempted no email, and cleaned all fixture data exactly. `wp db check` passed. Evidence: `reports/COMMUNICATIONS_CATEGORY_RESULTS_DRAFT_RECIPIENT_SCOPE_1.2.4.66.b_2026-11-25.md`. This is one regional scope; general announcement audience rules and broader phase gates remain open.
