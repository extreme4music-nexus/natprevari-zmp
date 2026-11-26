# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# President passkey admin-post scope fix — 2026-11-12

## Finding
The active President-only admin scope ran on admin_init and returned HTTP 403 for any admin-post.php action outside its explicit allowlist. Passkey registration options, registration finish, and credential removal were registered handlers but absent from that allowlist. WordPress therefore could reject those requests before the passkey handler performed its own validation. This is consistent with the generic enrollment failure and the user's report that the same password works for ordinary profile login.

The observed Owner POST was not action-logged, so its association with enrollment remains UNCONFIRMED. The source path is confirmed; the exact historical request is not.

## Change
Added only ZMP_Passkey_Profile::OPTIONS_ACTION, FINISH_ACTION, and REMOVE_ACTION to the President admin-post.php allowlist. Existing passkey handler checks remain authoritative: current account, role/eligibility, request method, action-specific nonce, password verification, and credential ownership/removal validation. No login, other admin screen, DB, credential, or live-site behavior was changed by this patch.

## Verification
- PHP syntax: PASS, includes/class-zmp-president-dashboard.php.
- Focused policy invocation with minimal WordPress/class stubs: PASS — all three passkey actions return allow; an unlisted action returns deny.
- Browser enrollment after fix: NOT RUN. Requires a new Owner-initiated attempt in the existing Edge tab.
- Historical POST-to-enrollment correlation: NOT VERIFIED (action was not logged).
- Independent exact-hash review: PENDING.

## Candidate hashes
- includes/class-zmp-president-dashboard.php — SHA-256 EDECF02CDF7C79491AD1AE0A32010B60B4E5508C436E339933C4E07F06836C4A
- admin/class-zmp-passkey-profile.php — SHA-256 705C7DDA64DB3307DB34C06D1A5134AEA76464DF78CAB77BD3406E5E6A5DDD6D
- zmp-competitions.php — SHA-256 C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F
- assets/js/zmp-passkeys.js — SHA-256 B8B2BD2F352797671A3BC92FCF3887D3C2A77CFF2104BAFE69EAB47DC685D4FB

Phase .66.a remains IN PROGRESS / NOT_READY. No ZIP, GitHub synchronization, or Auditor acceptance is claimed.

## Independent exact-hash review — 2026-11-12
Verdict: PASS / no static findings. Reviewer verified the three entries stay limited to the explicit President admin-post allowlist, the load order resolves the passkey action constants before policy execution, and the passkey handler's nonce, session, active mandate, password and credential-ownership checks remain in force. Review report: reports/CODE_REVIEW_PRESIDENT_PASSKEY_ADMIN_POST_ALLOWLIST_1.2.4.66.a_2026-11-12.md, SHA-256 1F07F9951443A92159607C999E48A50A2303BD074D6ECD2345A1A00756621CEE. Review is static; browser/runtime enrollment remains NOT RUN.
