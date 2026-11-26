# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent Code Review — `.65` Schema Performance

Date: 2026-11-03  
Scope: Read-only review of the `.65` schema-performance correction and the previously reported President-photo and manual-audio-review fixes.

## Review chronology

1. **Initial review — FAIL, three P2 findings.** At source hashes `zmp-competitions.php` `B561C0BA…`, `class-zmp-approved-audio-tools.php` `9179D4EA…`, and `class-zmp-president-public-display.php` `C6F798DB…`, the review found: (a) an administrator editing another President’s profile could change photo consent; (b) manual audio approval did not persist/show reviewer identity; and (c) ordinary `init` forced uncached schema metadata checks.

2. **First targeted re-review — FAIL, one P2 remains.** At `zmp-competitions.php` `45A8E5A2…`, President display `7966462C…`, audio tools `86CEBE88…`, and school-schema support hashes `0DD1E942…`, `48D8F48B…`, `559E8D29…`, consent changes were correctly restricted to the President and reviewer identity/checklist/note/time/URL-hash requirements were enforced. The entrypoint still called `retention_audit_schema_is_compatible(true)` during ordinary `init` (`zmp-competitions.php:386`), forcing uncached metadata reads.

3. **Second targeted re-review — FAIL, same P2 remains.** The entrypoint at `291D0E76…` removed the forced scan from its own normal-init condition. Messenger at `9870739D…` still registered `initialize_retention()` on every `init`; its path always acquired the lock and called `schema_is_compatible(true)` (`class-zmp-role-messenger.php:477–507`), so ordinary requests continued to scan metadata.

4. **Final targeted re-review — PASS.** At the exact final reviewed hashes—Messenger `38A7418CCC3DC1BEE6EC25EACE837214935CFA46B134D352C3A47F8D51607FC2`, entrypoint `291D0E7601B1DA01CDC96FE12774FB632E2638169E8526DDC3E9864D4B124A96`, President display `7966462C232A0D7B387487B9E29BEEAA3FA271400AD68E6454A7F6B55CD4A759`, and audio tools `86CEBE88A84A298C72EC188DB1EF84BA61F407CECA96F3D12853A82C6BBA62F6`—ordinary retention initialization returns before lock/metadata checks when schema marker, retry state, retention policy/version, and cron are current (`class-zmp-role-messenger.php:497–521`). Incomplete initialization and migration/retry retain the locked fresh checks and idempotent backfill (`:524–555`; `zmp-competitions.php:386–419`). No TTL-based periodic metadata scan was found. The two previously fixed consent/reviewer findings remain resolved at the same President/audio hashes.

## Execution limits

This was a source-only, read-only review. No product files were edited; no tests or database checks were run.
