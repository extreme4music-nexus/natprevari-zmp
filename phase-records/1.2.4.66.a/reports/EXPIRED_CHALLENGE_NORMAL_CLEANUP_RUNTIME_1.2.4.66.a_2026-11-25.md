# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# Normal expired-challenge cleanup timing — .66.a — 2026-11-25

## Result

**PASS — ordinary challenge expiration cleanup runs before a new challenge is issued.** The test used empty copied passkey tables under a unique temporary table prefix in the already-running local clone database at `:8111`; the live server and primary passkey data were not changed.

## Procedure and observations

- Confirmed the primary database already had challenge rows, so the first direct-table preflight aborted without modifying them. To avoid touching existing state, copied only the two passkey table definitions to temporary names and switched the WP-CLI process to that scratch prefix.
- Seeded two synthetic registration challenge rows: one expired by two seconds, one expiring in about 60 seconds. Their challenge identifiers were hashes; no raw challenge value was written or reported.
- Called the actual `ZMP_Passkey_Store::create_challenge()` method. Its normal cleanup removed the expired row before issuance, preserved the still-valid row, and issued a new challenge.
- Verified the new row's remaining TTL was 295–300 seconds, then deleted the two known synthetic rows and dropped only the temporary tables.

| Check | Result |
|---|---|
| Expired row removed before issuance | PASS |
| Not-yet-expired row preserved | PASS |
| New challenge issued | PASS |
| New challenge TTL within 5-minute window | PASS |
| Temporary rows/tables cleaned | PASS |
| Primary clone challenge-row count unchanged | PASS |
| Primary clone database integrity | PASS (`wp db check`) |

No production access, browser authenticator ceremony, source modification, or server start/stop occurred. This closes original `.66.a` open-scope item 4 only; cancellation/unavailable-authenticator behavior is separately open.
