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

# CURRENT STATUS — .65 — 2026-11-25

**OWNER-CONFIRMED PASS:** The Owner confirms that all `.65` checks reviewed/performed on 2026-11-24 passed. This is the current status for those checks. The 2026-11-05 gate-count/worklist snapshot is obsolete and has been moved outside the project; its old NOT RUN/FAIL counts are not current and must not be repeated as `.65` open items.

The 2026-11-24 work log records focused PASS evidence for role/President stacks (26/26), direct President queue denial (7/7), stacked President school-email approval (10/10), stacked Teacher subject route (8/8), and Messenger legacy-index migration (7/7). The supplied conversation record also records the populated school-profile render as 9/9 in `.65`.

Historical failed attempts remain historical evidence only; a later PASS for the same acceptance scope supersedes the old open/failed status. Do not reopen or repeat the `.65` checks above without a relevant code/evidence change.
## 2026-11-25 package delivery
A fresh WordPress install ZIP was generated from the current `.65` phase plugin source. Archive integrity is 755/755 byte-identical entries and PHP syntax is 625/625 PASS. The artifact hash and file manifest are recorded in `reports/RELEASE_PACKAGE_1.2.4.65_2026-11-25.md`. This packaging note does not widen the scope of focused runtime PASS results or modify the fixed main roadmap.

## 2026-11-25 supplied-dump reproduction
The exact user-provided SQL dump was imported and scrubbed in an isolated local DB. Current `.65` activation migrated the starting marker from `1.2.4.53.2` to `1.2.4.65`, cleared retry state, and made Messenger, school-profile, mentor-school, school-email, subject-proposal, and retention schema checks ready. Synthetic `subscriber + zmp_teacher` primary-school profile save/readback passed. This does not establish the exact production mismatch; the attached production excerpt lacks table/issue/expected/actual fields. Details: `reports/PRODUCTION_DUMP_SCHEMA_MIGRATION_DEBUG_1.2.4.65_2026-11-25.md`.


## 2026-11-25 — schema upgrade blocked by retry cooldown: FIXED / PASS

A controlled A/B regression on the isolated dump clone reproduced the update defect in the earlier .65 package: with a readable old schema marker and a future retry deadline, an ordinary WordPress request left the marker unchanged. The patched code bypasses cooldown only for a confirmed forward schema upgrade; the same request then migrated to 1.2.4.65 and cleared retry state. Six schema readiness checks passed and wp db check succeeded. This is a scoped fix, not a claim that all phase checks or production behavior are closed. Evidence: reports/SCHEMA_UPGRADE_RETRY_COOLDOWN_FIX_1.2.4.65_2026-11-25.md. The earlier dated ZIP remains preserved; the new schema-retry-fix ZIP is the package to install for this fix.


## 2026-11-25 — MySQL integer display-width mismatch: FIXED IN CODE / LOCAL PASS

The school-engagement validator expected bigint(20) unsigned literally, while the supplied SQL defines BIGINT UNSIGNED and modern MySQL omits integer display widths from metadata output. This made the mentor-school prerequisite fail; profile readiness depends on it and school-email readiness depends on profile readiness. The shared validator now normalizes only integer display widths while retaining signedness. Normalizer cases passed and seven local schema readiness checks passed. Production MySQL version/live metadata were not queried; the latest site snapshot points to production and was not booted. Evidence: reports/MYSQL_INTEGER_WIDTH_SCHEMA_FIX_1.2.4.65_2026-11-25.md. Use the mysql-schema-type-fix ZIP; it includes the earlier stale-marker retry fix.


## 2026-11-25 — administrator + Teacher school profile access: FIXED / LOCAL PASS

The new log shows schema migration/engagement creation followed by profile.php forbidden. Owner confirmed the account is WordPress administrator and ZMP Teacher. Local reproduction confirmed administrator + subscriber + zmp_teacher was rejected solely because administrator was missing from the teacher role-stack allow-list; subscriber + teacher without administrator worked. The allow-list now permits administrator only as an additive role and still requires subscriber + zmp_teacher. Missing linked mentor data now reports teacher_profile_required with a specific profile-page message. Local synthetic administrator test passed; ordinary Teacher+Subscriber retained one engagement and schema readiness remained true. Evidence: reports/ADMIN_TEACHER_SCHOOL_PROFILE_ROLE_FIX_1.2.4.65_2026-11-25.md. Package includes the MySQL type and retry-cooldown fixes.


## 2026-11-25 — Messenger retry notice follow-up: LOCAL PASS / PRODUCTION NOT CONFIRMED

On the isolated dump clone, Messenger and retention schema checks are true, the schema version is current, and no retry marker remains. A forced stale-marker + future-cooldown request migrated immediately and cleared retry state. A forced legacy scoped_threads index missing region_key was repaired by ZMP_Role_Messenger::install(); readiness then passed and wp db check succeeded. The owner reports the notice remains on production; no fresh SQL dump or post-retry Messenger detail event is available, and production was not accessed. Do not mark the live notice closed from local evidence. Details: reports/MESSENGER_RETRY_NOTICE_FOLLOWUP_1.2.4.65_2026-11-25.md.
