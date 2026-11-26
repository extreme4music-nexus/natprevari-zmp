# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# President queue denial with complete WordPress auth cookies — .65 — 2026-11-24

## Result
**PASS 7/7** on the exact local .65 plugin source. A synthetic zmp_teacher + subscriber session was sent with both WordPress AUTH_COOKIE and LOGGED_IN_COOKIE.

## Route evidence
- Authenticated /wp-admin/profile.php positive control returned HTTP 200 and contained the synthetic Teacher display-name marker.
- Direct GET to the President-only subject queue returned HTTP 403 with no redirect. The response included no proposal marker or decision controls.
- A POST with a valid President-decision nonce, sent as the same Teacher, returned HTTP 403 with the generic permission denial and no redirect.
- Feature-table and user baselines were restored exactly; both diagnostic option records were restored exactly; the synthetic user/session were removed; the outbound HTTP guard was not triggered.

## Exact fixture and source
Fixture: 	ests/phase65-subject-president-route-denial-auth-cookie.php.
Fixture SHA-256: `C380E9E8A6271F99BBDC84E21DB3CBF1488D24969BA81BD84FDDF3BF478F76E1`.
Loaded source hashes verified by the fixture:
- class-zmp-mentor-subject-proposals.php: D35DDFDEFDA7BA24559E35E1E74E13F0F16838C05A4D8C40E7F4C17290CD1373
- class-zmp-president-subject-approvals.php: 1902E1903C435E0DBCA07E99E529F1D3513619DE4DDC2ACD3DD28D6936960608
- class-zmp-mentor-school-profile.php: 8B24FA50E47725B4E260D0593DF5E82E8C6A049CC61FD3A58B5D5C5BED531835

The fixture ran on http://127.0.0.1:8097, database zmp65_subject_test, prefix z65s_, and the fresh synthetic site. The previous R5 outcome remains preserved as history; its incomplete auth-cookie set caused a login redirect, so it had not established the direct queue response. This new run used the complete cookie set and observed the intended denial.

No production site, account, email, YouTube or external service was used. This PASS closes the direct Teacher queue denial and nonce-valid decision denial scope only; unrelated .65 gates remain open.
