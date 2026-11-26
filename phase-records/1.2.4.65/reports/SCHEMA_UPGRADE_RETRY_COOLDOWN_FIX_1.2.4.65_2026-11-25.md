# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# OWNER MANDATE — READ FIRST: THE OWNER-CONFIRMED 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. DO NOT REOPEN IT OR COUNT OLDER NOT RUN/FAIL RESULTS FOR THAT SAME SCOPE AS OPEN WITHOUT NEW CONTRADICTORY EVIDENCE.
# ROLE STACK — EVERY ZMP MEMBER MUST HAVE SUBSCRIBER. TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE BOTH TEACHER AND SUBSCRIBER.

# .65 automatic schema upgrade retry fix — 2026-11-25

## Finding

The ordinary WordPress init migration path in zmp-competitions.php applied schema_retry_allowed() even when zmp_db_version was readable and explicitly older than ZMP_SCHEMA_VERSION. A future retry deadline could therefore postpone a required plugin-upgrade migration. Plugin activation did not expose this condition because activation is an immediate retry path. This explains why a fresh activation of the earlier ZIP passed while an ordinary update/install over an existing pending retry could leave the old schema marker in place.

## Change

- Read the schema marker directly from the database and distinguish a confirmed stale marker from a failed marker read.
- Bypass the retry cooldown only when the stored marker is readable and differs from the current schema version.
- Preserve cooldown behavior for ordinary repeated retries when the schema marker is already current.
- Re-read the marker after acquiring the migration lock so a concurrent request that completed the upgrade is respected.
- A failed database read does not count as proof of a stale version and does not itself bypass the retry gate.
- No database schema definitions were changed in this fix; it corrects when the existing migration suite is allowed to run.

## Regression test — isolated local clone

Environment: /home/tonic/zmp-prodclone-debug-20261125/site, local database zmp_debug_prodclone_20261125, local server 127.0.0.1:8111. This is the sanitized clone imported from the exact user-provided dump zmp-database-20261125-162548.sql; no production site/database was accessed.

A/B reproduction on the same isolated clone, with zmp_db_version=1.2.4.53.2, retry pending, and a retry deadline five minutes in the future:

1. Pre-fix .65 package: normal GET /wp-login.php returned HTTP 200, but the marker stayed 1.2.4.53.2. The upgrade was blocked by cooldown.
2. Patched .65: the same normal request returned HTTP 200, advanced the marker to 1.2.4.65, and cleared both retry options. The upgrade ran immediately.
3. Control check with a current marker (1.2.4.65) and a future retry deadline: a normal request left the current marker and pending retry intact, confirming that ordinary retry throttling remains active. The temporary retry options were then removed.

After migration, direct schema readiness checks returned true for Messenger, retention audit, school profile, mentor schools, school email, and subject proposals. wp db check succeeded. Synthetic test accounts and the synthetic school profile remained in the isolated clone; no production data was used.

## Package and verification

- Install ZIP: ../../04_RELEASES/VERSIONS/1.2.4.65/ZIP/zmp-competitions-1.2.4.65-schema-retry-fix-2026-11-25.zip
- ZIP SHA-256: F89856C42D6A00170A750757B0F82235726FDEAE0C77ACE673D8ED533977EAFA
- Source manifest: ../../04_RELEASES/VERSIONS/1.2.4.65/HASHES/SOURCE_SHA256_1.2.4.65-schema-retry-fix-2026-11-25.txt
- Archive: 755/755 entries verified byte-identical after packaging.
- PHP lint: 625/625 files passed.
- Source file changed: zmp-competitions.php.
- Main PROJECT_ROADMAP.md was not modified.
- This result closes the reproduced .65 install/update cooldown defect only. It does not claim that the entire .65 phase or the production site has been independently verified.


## Follow-up — production engine metadata compatibility

The later owner-supplied logs exposed a second, independent defect. School-related schema validators required the literal metadata string bigint(20) unsigned, which is not returned by modern MySQL metadata output for ordinary BIGINT columns. The new portability fix and evidence are in MYSQL_INTEGER_WIDTH_SCHEMA_FIX_1.2.4.65_2026-11-25.md. The mysql-schema-type-fix ZIP supersedes the earlier retry-fix ZIP and contains both changes.
