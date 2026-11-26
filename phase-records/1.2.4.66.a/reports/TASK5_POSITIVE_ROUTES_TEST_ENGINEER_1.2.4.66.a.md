# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Task5 positive route fixture — Test Engineer — 2026-11-06

## Scope
One dedicated fixture was authored and run once on the authorized `.66.a` synthetic site to attempt the previously unreached actual admin-post decisions. It seeded the bounded setup (synthetic President/active mandate/session, in-memory P-256 credential, one teacher, three pending proposals, two canonical subjects) and stopped at the first failure. Original matrix and diagnostic fixtures were not modified or rerun.

Fixture `tests/phase66a-president-subject-step-up-positive-routes.php` SHA-256 `622197A685DEF5BBFBEE84116F43B2A906A1640372043DF057E391D9BD517E53`; PHP lint **PASS**.

## Outcome
- **FAIL** — Fixture output: `FAIL (2/2): Task5 positive route verification`; failed check `step-up options issued for approve_new`; stopped during `approve_new actual admin-post route` before posting to the route. Thus no actual decision route was reached.
- **NOT RUN** — approve_alias and reject actual routes, because the first options request failed and the fixture is stop-on-first-failure. No diagnosis or retry was attempted.
- **PASS** — Exact fixture cleanup output: `CLEANUP PASS`; post-run `wp db check --silent` succeeded; counts users/credentials/challenges/proposals/proposal-events/mandates/mandate-audit/subjects/aliases restored to `1/1/0/0/0/0/0/0/0`.
- **PASS** — Only `zmp-competitions` 1.2.4.66.a active; PHP 286942 and MariaDB 286676 preserved; all four reviewed candidate source/site files remained hash-identical.

## Status preservation
Original Task5 matrix remains **FAIL / unresolved**; its existing diagnosis remains unresolved. This focused fixture reproduces an `approve_new` options failure under its own bounded setup but does not identify cause and provides no actual route success evidence. Concurrent replay/interleaving remains **NOT RUN**. No UI/browser, product edit, package, GitHub or phase-acceptance claim.

## Affected re-verification on categorized passkeys candidate — 2026-11-06
- Exact candidate `includes/class-zmp-passkeys.php` source/site SHA-256 `F233CB371DCDEF3A05F447C3E5081F4B7F4DE453FE2BFBE2470B447847E68ED7`. Only the synthetic `.66.a` site with this exact file was used.
- Updated positive-route fixture `tests/phase66a-president-subject-step-up-positive-routes.php` SHA-256 `92F50726983CCFA304314573FE51149DBEFB8EC0405894FE9C45D38DECC76499`; PHP lint PASS. It prints only a fixed allowed WP_Error code or `UNEXPECTED_CODE`; no message/raw error data is read or printed.
- One run, stop-on-first-failure: **FAIL** at `approve_new` options; safe category `step_up_context_invalid`. No admin-post route was reached. approve_alias/reject **NOT RUN**. No retry or further diagnosis.
- Cleanup **PASS**; post-run DB check **PASS**; counts users/credentials/challenges/proposals/events/mandates/audit/subjects/aliases restored to `1/1/0/0/0/0/0/0/0`; only plugin, all four file parity, PHP286942 and MariaDB286676 preserved.
- This is affected re-verification, not a matrix rerun. Preserve original Task5 matrix FAIL and concurrent replay NOT RUN. The named code is a safe fixed category, not a root-cause conclusion; actual route behavior remains untested.
