# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Stacked Teacher subject proposal route — 1.2.4.66.a — 2026-11-24

## Result
**PASS 8/8** against the local synthetic WordPress snapshot http://127.0.0.1:8100, database zmp65_subject_test, prefix phase-specific synthetic prefix. The site contained only its synthetic administrator before and after the run. A synthetic Teacher+Subscriber with Moderator, Commission, Testator and Organizer roles submitted the first subject proposal through the actual `admin-post.php` route.

The request returned HTTP 302 with the allowlisted zmp_subject_status=submitted; the pending proposal and event actor/timestamp were persisted and read back. The school-profile engagement service and proposal schema preflight passed. Diagnostic-option contents and tracked database rows matched their exact baselines after cleanup, and no synthetic user or session remained.

## Network boundary
The fixture permitted only the current loopback admin-post URL, blocked any mail or non-loopback HTTP attempt, and recorded no outbound marker. A separate temporary URL observer on the .65 run also recorded no non-loopback target. Its files and newly created MU-plugin directory were removed. No mail was delivered and no external service was accessed.

## Evidence and history
Fixture: tests/subject-register-stacked-teacher-admin-post.php; SHA-256 `135A1FF0443BE9D23F4F6DC543BEE7239804CE53DABB014CC9B8B41161A2EE04`. PHP lint passed. This current scoped retest addresses the earlier unclassified outbound-guard result for this route. Historical R10–R13 reports and gate snapshots remain unchanged; this evidence does not close unrelated phase gates, YouTube/Drive integration, or UI review.
