# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — whole-plugin migration runtime R4 — 1.2.4.65

Date: 2026-11-05
Scenario: `.64→.65` whole-plugin migration, retained rows, readiness and repeat activation
Result: **FAIL — whole-plugin row inventory assertion; cleanup PASS**

## Candidate and review

- Fixture: `tests/phase65-whole-plugin-migration-retained-rows.php`
- Exact SHA-256 verified in Windows workspace and WSL before staging: `AC93CAB62B0867BF25FF888FBDF917979653D88521169E76E2B05B8C2C660257`.
- Independent Code Review exact-hash PASS: `CODE_REVIEW_WHOLE_PLUGIN_MIGRATION_FIXTURE_R4_1.2.4.65_2026-11-05.md`; PHP lint PASS.
- Source manifest digests verified: `.64` `F54EF4E09ED09A29A3B718DB2BDC1EBBF6E0E9C1FBF5B633EE2AB99081B3CFF0`; `.65` `40E01B235B16511DE4D26560D1A6670DB1392BF5F167393BF074E9771A19B7CB`.

## Environment and migration

- Created a disposable synthetic WordPress site `/home/tonic/zmp-local-sandbox/phase65-test/site-migration` from WordPress core and the exact manifest-listed `.64` ZMP package (755 shipped files). No production data, third-party plugins, uploads, or source-clone database were copied.
- Reused existing MariaDB PID `144183` and PHP PID `145525` on port `8094`; no service was started or stopped.
- Created isolated database `zmp65_migration_synthetic`, prefix `z65m_`. Clean WordPress install, `.64` plugin activation, schema markers `1.2.4.64`, and DB integrity check passed.
- Fixture `prepare` passed on `.64`: six synthetic records were seeded and a hash-only manifest created (SHA-256 `e77cd379f1611838c30f87de920c302333ce6696fabd19e0e815e8610472eaf0`).
- Replaced the task site's plugin with the exact manifest-listed `.65` package (755 files). WP-CLI bootstrap advanced both schema markers to `1.2.4.65`; `wp db check` passed.

## Verification result

Fixture `verify` returned exit 1 with **26/27 assertions PASS**. The following all passed:

- Six seeded record content hashes and unique synthetic markers.
- `.65` schema/version readiness markers, including subject catalog query readiness.
- Repeat activation produced identical full plugin-table inventory digests: `d6e5a210c64936a1355781a627592fce6da9c2de48e297737ba2df298d3ec454` before and after.
- All six seeded records remained unchanged after repeat activation; `$wpdb->last_error` was empty.

The single FAIL was `retained_all_baseline_plugin_rows`, which reported row-count changes in four baseline tables:

- `z65m_zmp_phase_status`
- `z65m_zmp_season_categories`
- `z65m_zmp_season_stages`
- `z65m_zmp_seasons`

The fixture's verifier only emitted the affected table names, not before/after counts. Its authorized cleanup then removed the manifest. Therefore exact table count deltas cannot be recovered from this run; no values or row IDs were retained. Also, because the comparator stopped at count mismatch for these tables, this run does not prove whether any of their baseline rows were lost. Treat this as an executed fixture **FAIL** with cause and direction unresolved, not as a migration PASS or proven product defect. Source inspection is needed to determine whether `.65` intentionally creates rows in these tables and whether the comparator should allow those additions while still checking every baseline identity/content.

## Cleanup and integrity

- Fixture cleanup returned **PASS**: one each of the seeded competition, mentor, school, student and registration rows were deleted; the synthetic user and all markers were absent; the manifest was removed.
- Post-cleanup DB check passed. Version markers remained `1.2.4.65`. The base competition count returned to one; mentor, school, student and registration counts returned to zero.
- Dropped only `zmp65_migration_synthetic`; removed only task-created `site-migration` and `tmp/phase65-whole-plugin-migration` after path and symlink checks.
- Verified task site/database/temp absent. Parent sandbox and original `/site` are intact.
- Existing MariaDB PID `144183`, PHP PID `145525`, and ports 3306/8094 remain active and unchanged.

## Gate disposition

`whole_plugin_migration_retained_rows` remains **FAIL / OPEN**. The required whole-table retained-row claim is not accepted. No product source, frozen `.64` artifact, active `.65` site/database, persistent clone, ZIP, or GitHub artifact was changed.
