# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Direct school-email approval route — 1.2.4.65 — 2026-11-24

## Result
**PASS 10/10** against the local synthetic WordPress site http://127.0.0.1:8097 (zmp65_subject_test, prefix specific to this phase). The test used WP-CLI admin ID 1 and created temporary synthetic Teacher+Subscriber+Moderator+Commission+Testator+Organizer users. No production site, real address, mail delivery, YouTube or Google service was used.

The browser-equivalent `admin-post.php` request carried the active synthetic President's authentication cookies and request nonce. It returned the expected approved redirect without exposing the reserved .example.test address. The stacked President's queue withheld the address; approval recorded the expected actor, email hash, profile epoch and submitted/approved audit events; requester readback exposed status only. The real appointment service preserved all six pre-existing roles when adding President.

## Network boundary and cleanup
A temporary MU-plugin guard permitted only the current site's loopback host/port and blocked every other HTTP request. The known WordPress core update check at `api.wordpress.org/core/version-check/1.7/` was explicitly blocked by the guard; no unexpected external HTTP marker was recorded. The route still completed successfully. The guard file, marker, and newly created MU-plugin directory were removed. Synthetic users, auth session, mandate/audit, request/events, school/profile/mentor records, debug option and tracked table counts were restored to baseline.

## Evidence
Fixture: tests/school-email-stacked-president-admin-post.php; SHA-256 `4B2557C33713A3A6F21023F06828636A11EEA10CB3E1F9FA46A008C52191ECF4`. PHP lint passed. The 10/10 assertions include role preservation, appointment, pending-queue privacy, real authenticated POST redirect, no address disclosure, no unexpected outbound HTTP, approval persistence/audit, status privacy and exact cleanup.

This is direct local HTTP evidence for this route only; it does not close other phase gates or prove browser rendering, external mail delivery, or production data/schema behavior.
