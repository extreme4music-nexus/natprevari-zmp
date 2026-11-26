# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Messenger legacy scoped_threads repair — 1.2.4.66.a — 2026-11-24

## Finding and repair
The source comparison found that .65 and .66.b contained the legacy index preflight, while .66 and .66.a still had the pre-fix installer. The old five-column non-unique scoped_threads definition conflicts with the six-column definition that adds 
egion_key; dbDelta() then tries to add the key under the same name and MariaDB reports duplicate_key.

The .66 and .66.a Messenger source now includes the guarded helper from the fixed .65 snapshot: it checks the exact prefixed messages table and ordered index metadata, replaces only a verified obsolete non-unique scoped_threads index, logs bounded diagnostics, and fails closed for incomplete/ambiguous metadata, an unexpected unique index, or a failed drop. The helper runs before the message-table dbDelta() call. Source SHA-256: `5CE8A617291C0CFDE349B819DA61275965148973E43683EED6062A71DBE8E832`; local runtime copy hash matches exactly.

## Local synthetic results
**PASS 7/7** on http://127.0.0.1:8100, local DB zmp65_subject_test, prefix phase-specific synthetic prefix. The fixture reproduced the exact legacy index, inserted one synthetic message row, and verified:

- The installer replaced the obsolete index without the duplicate-key error.
- The synthetic row, subject and 
egion_key survived migration.
- An unexpected unique scoped_threads index was rejected and left untouched.
- Restoring the expected index allowed recovery and a repeated install.
- The Messenger schema compatibility check passed.
- Exact row, index and schema-control/debug option cleanup passed.
- wp db check passed for all tables in the shared local synthetic database.

Fixture: tests/messenger-legacy-index-migration.php; SHA-256 `57EC93BA96D4DF0BEF424A8D60E3FAF1E0AEEAA8C2241BF3F8CE3B09F0A51C85`. PHP lint passed. The test ran only on the disposable loopback site; no production DB, message, account or external service was accessed.

## Scope
This closes the scoped legacy index migration defect for this phase snapshot. It does not mean the historical production database was migrated or that unrelated gates in this phase are complete. The fixed root PROJECT_ROADMAP.md was not changed.
