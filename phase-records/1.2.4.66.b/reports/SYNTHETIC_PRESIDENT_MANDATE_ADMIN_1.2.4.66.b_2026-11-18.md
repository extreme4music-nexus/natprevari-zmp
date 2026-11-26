## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---

# President mandate positive admin-handler retest — `.66.b` — 2026-11-18

## Result

PASS for the current `.66.b` mandate service and administrator callbacks in the isolated synthetic WordPress database.

Candidate/runtime SHA-256 matched for `admin/class-zmp-president-mandate-admin.php` (`28F0B464263A4A69FA3D8E8DDCA85D53F12AF3BE9E7BF91C20B59EAC52BD7216`) and `includes/class-zmp-president-mandate.php` (`5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40`).

1. The actual appointment handler checked Administrator capability and nonce, accepted an exact four-year future term after the current synthetic mandate, wrote a scheduled mandate and one `appointed` audit event, and left the new account as `subscriber`.
2. The current mandate service created a second scheduled synthetic appointment; the actual scheduled-cancellation handler checked its mandate-scoped nonce, revoked that exact row, stored the submitted reason and Administrator actor, and wrote the matching `revoked` audit event. The account remained `subscriber`.

Both helpers observed and deleted only their generated user, mandate and audit rows. They report `exact_user_and_mandate_cleanup=true` / `exact_fixture_cleanup=true`. `wp db check` passed after both runs. WP-CLI printed its expected warning that the callback redirected/exited; shutdown assertions still passed. No production or external service was used. Reproducers: `synthetic-mandate-appointment-positive.php` and `synthetic-mandate-cancellation-positive.php`.

This closes the inherited positive appointment and scheduled-cancellation handler gaps for the current `.66.b` code. It is direct callback/runtime coverage, not a rendered admin-browser acceptance check.


## Active-mandate revocation role-removal branch — PASS — 2026-11-18

Extended the positive callback coverage to admin_post_zmp_president_mandate_revoke. A new synthetic President account and synthetic active mandate were created only in the isolated database; the existing active mandate and its account were untouched. The handler accepted the Administrator nonce/reason, changed the exact fixture to revoked, wrote the revoked audit event, removed zmp_president and restored subscriber. The test also verified the initial appointed and final revoked events. Shutdown cleanup deleted only the generated audit/mandate/user and reported exact_fixture_cleanup=true. The expected WP-CLI redirect warning accompanied the callback's redirect/exit. Database integrity passed. Reproducer: synthetic-mandate-revocation-active-positive.php.
