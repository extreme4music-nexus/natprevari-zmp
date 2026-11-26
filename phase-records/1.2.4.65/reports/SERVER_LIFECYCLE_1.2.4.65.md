## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# `.65` Server Lifecycle Record

## Pre-start inventory — 2026-11-03

- Windows listen ports 80, 3306, 3319, 8089 and 8094: free at the recorded check.
- WSL preexisting services: Apache PID 149 on `*:80`; MariaDB PID 360 on `127.0.0.1:3306`; separate `.58` MariaDB PID 8278 on private socket; `.60` MariaDB PID 32192 on `127.0.0.1:3319`.
- None of those listeners is owned by `.65`; none was changed or stopped. The active port 80/3306 services are not configured as the `.65` disposable site's isolated endpoints.
- `.65` disposable site: `/home/tonic/zmp-local-sandbox/phase65-test/site`, copied from approved WSL clone core + Raft theme, synthetic database `zmp65_isolated` with prefix `z65_`, only candidate ZMP plugin installed, no production content or copied config.

## Phase-owned service state — 2026-11-03 18:52 Europe/Skopje

| PID | Port/socket | Purpose | Owner |
|---|---|---|---|
| 144183 | `/home/tonic/zmp-local-sandbox/phase65-test/run/mariadb.sock` (network disabled; no TCP port) | synthetic `.65` WordPress DB | `.65` |
| 145525 | `127.0.0.1:8094` | disposable `.65` WordPress/PHP UI and route checks; loopback only | `.65` |

PID and listener were verified after start; PID file is `/home/tonic/zmp-local-sandbox/phase65-test/run/php-server.pid`, log `/home/tonic/zmp-local-sandbox/phase65-test/run/php-server.log`. At phase close, stop only PIDs 145525 and 144183 and confirm both processes are gone and the port/socket are released. Leave the persistent clone and preexisting unrelated services untouched.
