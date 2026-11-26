# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Server lifecycle — 1.2.4.66.a

Date: 2026-11-05 (Europe/Skopje)

| PID | Port/socket | Purpose | Owner phase | State |
|---|---|---|---|---|
| 286676 | /home/tonic/zmp-local-sandbox/phase66a-test/run/mariadb.sock (network disabled) | Isolated database zmp66a_isolated | 1.2.4.66.a | Running; WP-CLI DB check succeeded |
| 286942 | 127.0.0.1:8101 | Synthetic WordPress web runtime | 1.2.4.66.a | Running; HTTP 200 |

Site: /home/tonic/zmp-local-sandbox/phase66a-test/site; WordPress 7.1.3; DB zmp66a_isolated, prefix z66a_; plugin inventory only active zmp-competitions 1.2.4.66.a. Web SAPI is PHP 8.5.4 cli-server with JSON/OpenSSL/Sodium/mbstring. There is one synthetic administrator account with an .invalid email; no real email was sent. The test credential is local-only, mode 0600, and excluded from reports/source/packages.

The site was created from WordPress core only; no production/participant DB, uploads, config, or third-party plugin was copied. .65 site/database/services and the persistent clone were not used or modified for the phase setup.

After required phase evidence is complete, stop only owned PIDs 286942 and 286676 and verify port/socket release. Preserve the synthetic site/database until evidence and release checks finish. Never stop the persistent clone or .65 services.
