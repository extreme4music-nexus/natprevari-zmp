# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger Trace — passkey removal loopback positives — 1.2.4.66.a

Date: 2026-11-05
Status: **INCONCLUSIVE — static trace only; product defect not confirmed**.

## Evidence examined

- Test Engineer's exact current-candidate failure is `reports/STEPUP_CONTEXT_TEST_ENGINEER_1.2.4.66.a.md` (current candidate round: Task3 round2 FAIL 2/18 on store SHA `88497a37c87c2a9961b4fc3980c535d30d536bf9df31f31cf27c9b04bee02d19`).
- Product sources traced: `includes/class-zmp-passkeys.php` SHA `a7fb96b373390daf83115606769b97f996b543c9a14b4342448d5ff026623b1e`; `admin/class-zmp-passkey-profile.php` SHA `57024d14d98b996937cd4d65efd5c397329462ea961a60c5555ac4e80bd1d7e1`; `includes/class-zmp-passkey-store.php` SHA `88497a37c87c2a9961b4fc3980c535d30d536bf9df31f31cf27c9b04bee02d19`.

## Trace

`ZMP_Passkey_Profile::handle_remove()` sends a 403 when `is_secure_request_context()` or private `valid_request()` fails; it sends a 400 if `ZMP_Passkeys::remove_credential()` fails; successful credential deletion returns JSON success. With the documented synthetic loopback request values (`http`, host localhost/127.0.0.1 with port 8101, matching Origin and Referer, server port 8101, peer 127.0.0.1), the static origin guard appears to allow both positive cases: configured local `WP_HOME` is accepted; loopback peer detection accepts IPv4 `127/8`. The schema-v2 change adds only a challenge-table context column and has no direct credential-removal query path.

The result does not isolate the failing gate: `valid_request()` still depends on current logged-in/read capability, POST/action and nonce; `remove_credential()` depends on session token, password, schema, ownership and delete result; the test also parses captured JSON. No runtime/debug command was run and no file/database/site was changed by the Debugger.

## Next diagnostic

Use the already assigned single bounded test-only diagnostic to record separate sanitized booleans and response/status for the origin check, user/session/capability, nonce, JSON success and credential retained/deleted. Do not modify product code unless that run identifies a product gate returning the wrong result.
