# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Read-only Review — Partial Passkey Schema Migration Failure Injection

**Date:** 2026-11-11  
**Verdict:** **A bounded child-database injection is feasible in principle; NOT RUN**  
**Scope:** Static review of current `includes/class-zmp-passkey-store.php` and recorded child-clone state. No database connection/access, mutation, test execution, server/browser operation, or source/test edit occurred.

## Exact source identity

`includes/class-zmp-passkey-store.php` was rehashed and matches SHA-256 `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`.

The reviewed path is `install_schema()` (`:17-79`), `schema_is_compatible()` (`:76-79`), and the structural checks (`:554-650`). Installation calls `dbDelta()` for credentials, then challenges; it validates both structures before updating the marker. A failed second table creation therefore should leave the first table, no second table, and no new marker; the final structural gate returns false. `schema_is_compatible()` should remain false because structure and/or marker is missing. Protected storage operations check compatibility before accessing these tables.

## Recommended isolated procedure (not executed)

Target only the previously designated child site `/home/tonic/zmp-local-sandbox/phase66a-schema-create-20261111/site` and DB `zmp66a_schema_create` with prefix `z66a_`. Preserve the existing MariaDB process (PID 3828); do not connect to or change the main clone DB `zmp66a_isolated`, Owner services, or any other database.

1. **Preflight:** read-only confirm the child’s absolute path, `WP_HOME`/`WP_SITEURL` at `127.0.0.1:8102`, DB identity/name/prefix, only the ZMP plugin active, and the two passkey tables exist with zero rows and marker `3`. Confirm no PHP server/listener for the child is running. Abort if any value or row count differs.
2. **Recovery point:** create a whole-child-database backup inside that child sandbox, with restrictive permissions, and verify it is readable and nonempty before any schema change. Keep its path and contents out of reports/Git. Do not treat a credential-row-only backup as sufficient.
3. **Prepare only the child:** using the child DB connection, remove only the two confirmed-empty passkey tables and the single `zmp_passkey_schema_version` option. Confirm absence and DB integrity before WordPress bootstrap. Do not alter shared plugin schema markers or unrelated rows.
4. **Install a temporary clone-only MU-plugin injector:** it must be stored only inside the child clone’s `wp-content/mu-plugins` (or a child-local temporary MU-plugin directory configured for this clone), never in phase source. During `plugins_loaded` priority 10, remove only `ZMP_Passkey_Store::install_schema` at priority 20 so automatic migration does not race the explicit attempt. Register a `$wpdb` `query` filter before that point. The filter must match only the one exact `CREATE TABLE` DDL for `z66a_zmp_passkey_challenges`, replace that query with a harmless no-op SQL statement, increment an in-memory interception counter, and leave all other SQL unchanged. No broad `CREATE`, `ALTER`, table-name-prefix, or all-query failure rule.
5. **One WP-CLI attempt:** after the bootstrap hook suppression, invoke `ZMP_Passkey_Store::install_schema()` exactly once from the CLI evaluation and emit only booleans/counts. Required observations: return `false`; interception count exactly `1`; `schema_is_compatible()` false; marker absent; credentials table present; challenges table absent; database integrity passes. A guarded representative storage or passkey options API should report unavailable/fail closed without adding rows. Do not print SQL errors, credential values, user data, cookies, or assertion material. If the filter misses, intercepts more than once, or any expected state differs, stop and restore immediately; label the injection inconclusive/failed rather than claiming a pass.
6. **Restore before normal bootstrap:** remove the temporary MU-plugin and restore the verified child backup using a database-only restore path that does not bootstrap WordPress. Then verify the marker is `3`, both expected tables and indexes are restored, compatibility/integrity pass, baseline and unrelated table checksums match the pre-test record, and no injector file remains. Do not run another WordPress bootstrap until the injector is removed and the backup is restored. Keep the child PHP server stopped and port 8102 free.

## Expected fail-closed result

On the exact source hash, the injected no-op should make `dbDelta()` leave the challenge table absent. The subsequent structural validator should fail and `install_schema()` should return `false` before marker update. With the marker absent and the challenge table missing, `schema_is_compatible()` should be false and guarded passkey operations should be unavailable. The credential table may have been created by the first successful `dbDelta()`; this partial additive table is expected only inside the backed-up child and must be removed by restoring the backup.

## Risks and limits

- This recommendation depends on WordPress `dbDelta()` issuing the challenge `CREATE TABLE` through the normal `$wpdb->query` filter. The injector must confirm exactly one match; an unmatched query means the failure was not injected. A no-op replacement avoids deliberate SQL errors but still must be restricted to the exact child table DDL.
- Suppressing the automatic hook is necessary to observe a single direct `install_schema()` return and prevent startup migration from repairing the test state before observation. Verify that only the named callback at priority 20 was removed and that suppression exists only in the temporary child injector.
- Partial DDL is not transactional. The credential table can persist after challenge creation fails; do not attempt an ad hoc second migration or manual marker change. Restore the whole child DB backup.
- The child contains other synthetic WordPress/ZMP state despite empty passkey tables. Backup and exact post-restore comparison are required. Any concurrent bootstrap could race and repair the partial state, so run no child web server/browser requests during the injection.
- Static review cannot prove the query filter behavior, actual `dbDelta()` SQL shape, recovery import, or resulting runtime status. This remains **NOT RUN** and provides no migration acceptance evidence.

