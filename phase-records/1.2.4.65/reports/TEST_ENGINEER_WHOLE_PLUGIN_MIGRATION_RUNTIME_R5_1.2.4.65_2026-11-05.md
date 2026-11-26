# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — whole-plugin migration runtime R5 — 1.2.4.65

Date: 2026-11-05  
Scenario: fresh synthetic `.64→.65` whole-plugin migration, retention, schema readiness and repeat activation  
Result: **PASS — 27/27 assertions; cleanup and DB integrity PASS**

## Exact candidate and independent review

- Fixture: `tests/phase65-whole-plugin-migration-retained-rows.php`
- Exact SHA-256: `A95933304FD893711A1BA3627C464AACF2FFCB75651C039E500CD17BB0FBE160`.
- PHP lint: **PASS**.
- Independent Code Review: **PASS** for that exact SHA; `CODE_REVIEW_WHOLE_PLUGIN_MIGRATION_FIXTURE_R5_1.2.4.65_2026-11-05.md`.
- The `.64` source manifest hash is `F54EF4E09ED09A29A3B718DB2BDC1EBBF6E0E9C1FBF5B633EE2AB99081B3CFF0` (753 files); `.65` is `40E01B235B16511DE4D26560D1A6670DB1392BF5F167393BF074E9771A19B7CB` (755 files). Every source file was checked against its manifest before staging.

## Runtime and evidence

- Created a fresh disposable WordPress site from WordPress core only at `/home/tonic/zmp-local-sandbox/phase65-test/site-migration`; created isolated synthetic database `zmp65_migration_synthetic` with prefix `z65m_`. No production data or uploads, third-party plugins, prior site content, or site test guard files were copied.
- Reused existing MariaDB PID `144183` and PHP PID `145525` on ports `3306` and `8094`; no service was started or stopped.
- Exact `.64` package activated with both schema markers at `1.2.4.64`; `wp db check` passed. Fixture `prepare` seeded six scenario rows and wrote only hash/column inventory metadata to its task temp manifest.
- Replaced the task site's plugin with the exact `.65` package. The `.65` migration ran through normal WordPress bootstrap; both schema markers advanced to `1.2.4.65`; `wp db check` passed.
- Fixture `verify` returned exit 0 and **27/27 PASS**. Every baseline plugin table row fingerprint was retained at least at its original multiplicity, all six seeded content hashes and all five unique scenario markers passed, schema/readiness checks passed, and the database error state was empty.
- Repeat activation did not change the complete plugin-table inventory digest: `fc17ab46f17eebbae4e91837d95b015bd3d837ae2c9fb2ac3d4f8a231c8eaeaa` before and after. All six seeded record hashes remained unchanged after repeat activation.
- Structured per-table counts explain the R4 finding as additive rows, not missing baseline rows: `zmp_phase_status` 4→8 (+4), `zmp_seasons` 0→2 (+2), `zmp_season_categories` 0→10 (+10), and `zmp_season_stages` 0→40 (+40). These reflect default structure created for the pre-existing and fixture competition records. The R5 comparator checks baseline row retention/multiplicity and reports current row deltas without treating valid additions as loss.

## Staging diagnostics

- Two pre-seed staging attempts stopped before creating the synthetic database because the source manifest was assumed to be in a version folder and its CRLF paths were not normalized. The partial task-owned site/temp paths were checked and removed; database absence was verified.
- A clean `.64` install then stopped fixture `prepare` before seeding because its two expected source-manifest copies had not yet been placed in task temp. No migration ran during that stop. The exact root manifests were verified and copied to the task temp; `prepare` then passed. These staging events did not alter product source or the active `.65` site/database.

## Cleanup and lifecycle

- Fixture cleanup returned **PASS**: each test-owned competition, mentor, school, student, registration row and synthetic user was removed; no scenario markers remained; hash-only manifest was removed.
- Post-cleanup database integrity check passed; schema markers remained `1.2.4.65`.
- Dropped only `zmp65_migration_synthetic`; removed only the task-created `site-migration` and `tmp/phase65-whole-plugin-migration` after resolved-path and no-symlink checks. Verified task site, DB and temp are absent.
- MariaDB PID `144183`, PHP PID `145525`, and ports `3306`/`8094` remained active and unchanged.

## Gate disposition

`whole_plugin_migration_retained_rows`: **PASS** for the exact `.64→.65` synthetic migration evidence above. R4's earlier executed FAIL remains preserved as historical evidence; R5 resolves its missing-count ambiguity with explicit baseline/current counts and retention fingerprints. This result does not change other `.65` gates or establish whole-phase readiness.