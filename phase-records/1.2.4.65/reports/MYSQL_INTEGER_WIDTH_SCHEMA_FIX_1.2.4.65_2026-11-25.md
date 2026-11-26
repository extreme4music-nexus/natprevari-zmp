# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# OWNER MANDATE — READ FIRST: THE OWNER-CONFIRMED 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. DO NOT REOPEN IT OR COUNT OLDER NOT RUN/FAIL RESULTS FOR THAT SAME SCOPE AS OPEN WITHOUT NEW CONTRADICTORY EVIDENCE.
# ROLE STACK — EVERY ZMP MEMBER MUST HAVE SUBSCRIBER. TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE BOTH TEACHER AND SUBSCRIBER.

# .65 MySQL integer metadata compatibility — 2026-11-25

## Root cause

The failing dependency chain is:

1. The school-engagement installer checks the type of zmp_mentor_schools.engagement_epoch.
2. School-profile readiness depends on the school-engagement schema.
3. School-email readiness depends on the school profile.
4. A school-engagement readiness failure therefore produces the profile and email schema_not_ready events seen in the supplied log.

The .65 validators compared integer type strings literally to bigint(20) unsigned. The supplied SQL dump defines these columns as BIGINT UNSIGNED (without a display-width number). MySQL 8.0.19 changed metadata output so integer display widths are omitted from SHOW CREATE / DESCRIBE output except for TINYINT(1). Width has no effect on integer storage capacity or range. Official reference: https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-19.html and https://dev.mysql.com/doc/refman/8.0/en/numeric-type-attributes.html.

This explains why the old check can reject a valid BIGINT UNSIGNED column on a modern MySQL server even when the SQL schema is otherwise correct. It also explains the prior local false negative: the test clone used MariaDB, whose metadata returned bigint(20) unsigned, and its relevant tables had been sanitized before the previous migration test.

## Code correction

Added ZMP_Database::normalize_schema_type() to remove only integer display-width syntax before comparing metadata. The normalizer retains the base integer type and signedness, and the expected and actual types both pass through it. Updated:
- mentor-school engagement compatibility;
- teacher school-profile compatibility;
- school-email request/event compatibility;
- President facsimile schema compatibility.

The earlier .65 fix that lets a confirmed stale schema marker bypass the five-minute retry cooldown is included in this package as well.

## Evidence

- The supplied dump's school engagement, profile, mentor, and email DDL uses widthless BIGINT UNSIGNED definitions.
- Local normalizer checks passed for bigint(20) unsigned, BIGINT UNSIGNED, int(10) unsigned, tinyint(1), and varchar(254); signedness is preserved.
- On the isolated dump-derived clone, Messenger, retention, mentor-school, school-profile, school-email, subject-proposal, and President-facsimile readiness checks returned true after the changes.
- Local PHP server at 127.0.0.1:8111 returned HTTP 200 after the updated files were installed in the isolated clone.
- The previous A/B test still applies: a stale version marker migrates despite a future retry deadline, while a current marker still respects that deadline.
- PHP syntax: 625/625 passed. ZIP contents: 755/755 verified against the phase source.
- The ZMP-Site_Latest snapshot was not booted because its wp-config points at the production MySQL host. No production connection was made. Its zmp-competitions.php SHA-256 is C0F9594F0FFC37D6C612C85AFF960EAC1EDA27A7B4CDC84A4DD09AF3B649FB8F, matching the pre-fix package; it does not contain the new code. This establishes only the snapshot contents, not which code is currently live.
- A newer SQL dump was not present in ZMP-SQL at the time of this test. The available dump remains zmp-database-20261125-162548.sql. The production server's exact version and live metadata output were therefore not directly queried.

## Package

- Install ZIP: ../../04_RELEASES/VERSIONS/1.2.4.65/ZIP/zmp-competitions-1.2.4.65-mysql-schema-type-fix-2026-11-25.zip
- ZIP SHA-256: CF02DEEAA5D51422C8ECAAF5FC344F9D88125124FF0FCC52D3D0319EE4382652
- Source manifest: ../../04_RELEASES/VERSIONS/1.2.4.65/HASHES/SOURCE_SHA256_1.2.4.65-mysql-schema-type-fix-2026-11-25.txt
- This closes the reproduced MySQL metadata portability defect in .65 code. The owner should install this package and retry the school save; if it still fails, the new SQL dump or detailed database metadata is needed to identify any additional schema drift.


## Follow-up — separate profile authorization warning

The owner later confirmed the affected profile account is both Administrator and Teacher. After schema readiness advanced, the next error was forbidden because the Teacher role-stack allow-list rejected the extra administrator role. That separate code defect is fixed and locally reproduced in ADMIN_TEACHER_SCHOOL_PROFILE_ROLE_FIX_1.2.4.65_2026-11-25.md. The newest school-profile-role-fix ZIP includes all schema and role fixes.
