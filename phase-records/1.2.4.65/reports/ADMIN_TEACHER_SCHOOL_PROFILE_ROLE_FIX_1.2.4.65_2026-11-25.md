## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# OWNER MANDATE — READ FIRST: THE OWNER-CONFIRMED 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. DO NOT REOPEN IT OR COUNT OLDER NOT RUN/FAIL RESULTS FOR THAT SAME SCOPE AS OPEN WITHOUT NEW CONTRADICTORY EVIDENCE.
# ROLE STACK — EVERY ZMP MEMBER MUST HAVE SUBSCRIBER. TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE BOTH TEACHER AND SUBSCRIBER.

# .65 administrator + teacher school-profile access — 2026-11-25

## Finding from the new log

The database/schema failure has moved forward: the owner-provided log shows a school engagement created at plugin-install.php, followed later by a profile.php operation with error_code=forbidden. The latter is an authorization failure, not schema_not_ready.

The owner confirmed that the affected account is both the WordPress administrator and a ZMP Teacher. The profile role validator required zmp_teacher and subscriber, but rejected any extra role not listed in its allow-list. administrator was absent, so administrator + subscriber + zmp_teacher failed even though the same subscriber + zmp_teacher account without administrator worked.

The engagement-created event contains a school_id and engagement_epoch, but no WordPress user ID; it proves the migration/backfill created an engagement row, not that the currently logged-in administrator is linked to a zmp_mentors row.

## Code correction

- Added administrator as an allowed additive WordPress role in the Teacher role-stack check. It does not grant Teacher access by itself: both zmp_teacher and subscriber remain required.
- Kept owner-only access: the logged-in account can manage only its own profile.
- Split a missing linked mentor record out of the generic forbidden condition. It now returns teacher_profile_required.
- The profile UI displays a specific safe message telling the user to check Subscriber + Teacher roles and the linked teacher record.

## Local regression evidence

On the isolated, sanitized clone:
- Synthetic administrator + subscriber + zmp_teacher: is_teacher=true and can_manage_own_teacher_profile=true.
- The same administrator + subscriber account without zmp_teacher: is_teacher=false.
- When the synthetic administrator has the correct role stack but no linked mentor row, get_engagements returns teacher_profile_required and the profile page displays the specific missing-link message.
- The ordinary synthetic subscriber + zmp_teacher teacher account continues to load one engagement; schema readiness remains true.
- No production account or database was read or changed.

The user separately confirmed that a new synthetic subscriber + teacher account without administrator works. That matches the local result and isolates the extra administrator role as the difference.

## Package

- Install ZIP: ../../04_RELEASES/VERSIONS/1.2.4.65/ZIP/zmp-competitions-1.2.4.65-school-profile-role-fix-2026-11-25.zip
- ZIP SHA-256: 0488CAC70A43BADCAFD27B51ED305B9D02FDDF0D2AF084771A83345E6AEDB2B5
- Source manifest: ../../04_RELEASES/VERSIONS/1.2.4.65/HASHES/SOURCE_SHA256_1.2.4.65-school-profile-role-fix-2026-11-25.txt
- PHP lint: 625/625 passed.
- Archive: 755/755 entries verified against phase source.
- This package includes the earlier MySQL integer-width compatibility and stale-schema retry-cooldown fixes.

This closes the reproduced administrator+Teacher role-stack denial. If the owner account still gets teacher_profile_required after installing the package, its user account needs a linked zmp_mentors record; the new UI message distinguishes that data-link issue from permissions or schema readiness.
