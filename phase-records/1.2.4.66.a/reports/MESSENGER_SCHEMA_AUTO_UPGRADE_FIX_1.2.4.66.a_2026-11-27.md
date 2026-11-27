# Messenger schema automatic-upgrade fix — 1.2.4.66.a — 2026-11-27

**SUPERSEDED ROOT-CAUSE CLAIM — 2026-11-27:** advancing ZMP_SCHEMA_VERSION and repairing the Messenger index corrected a real code issue but did not resolve the live warning. The owner diagnostic now shows Messenger schema compatible and the shared marker stale at 1.2.4.53.2. The global blocker remains unidentified; see MESSENGER_LIVE_WARNING_DIAGNOSTIC_1.2.4.66.a_2026-11-27.md.

## Finding

The plugin release version was 1.2.4.66.a, but ZMP_SCHEMA_VERSION remained 1.2.4.65. The normal upgrade dispatcher runs component migrations when the stored zmp_db_version differs from that constant (or when a persisted Messenger retry is pending). A site already marked 1.2.4.65 could therefore install .66.a without entering the migration dispatcher, leaving the old Messenger index in place and allowing the fail-closed Messenger warning to remain. This is a confirmed code path that can explain the reported warning; the production database itself was not accessed in this check.

The Messenger install path already contained a safe repair for the historical scoped_threads index that omitted region_key. Its preflight-failure branch in .66.a had also lost the explicit compatibility-cache invalidation present in .65.

## Changes

- Advanced ZMP_SCHEMA_VERSION to 1.2.4.66.a, so an existing .65 database runs the normal locked schema-upgrade path when .66.a is installed.
- Restored compatibility-cache invalidation if the Messenger index preflight refuses a migration.
- The migration retains the existing fail-closed behavior and replaces the obsolete index before dbDelta; it does not drop Messenger rows.

## Local verification

Target: the existing clone at http://localhost:8111 (zmp_debug_prodclone_20261125, prefix wp_yqqdt5_). No server was started.

- Seeded a legacy scoped_threads(scope_type,season_id,category_id,thread_id,id) index while the database marker was 1.2.4.65; an HTTP request returned 200.
- The upgrade changed scoped_threads to scope_type,season_id,category_id,region_key,thread_id,id.
- zmp_db_version, zmp_role_version, and the retention initialization marker advanced to 1.2.4.66.a.
- Fresh Messenger schema compatibility: PASS; retry state: CLEAR.
- Repeated the migration with a synthetic message present. Its region_key, subject, and body survived; the test row was then removed and the synthetic-row count returned to 0.
- Required role-stack matrix after migration: 68 checks PASS, synthetic-account cleanup PASS.
- wp db check: PASS.
- PHP lint for both changed source files: PASS.

## Boundary

This verifies the migration against the local clone only. It does not claim production has been fixed; the owner should install this ZIP and check the Messenger notice. If it persists, the fresh Debugging event and actual table/index state will identify a separate production-specific mismatch.

## ZIP

See the current-candidate block in the .66.a release manifest for the archive name, SHA-256, size, and package-integrity result.
