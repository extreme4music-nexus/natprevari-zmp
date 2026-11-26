# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Active synthetic-site source parity check — 1.2.4.66.a

Date: 2026-11-11
Scope: Read-only SHA-256 comparison between the current candidate and the running synthetic site at `/home/tonic/zmp-local-sandbox/phase66a-test/site`. No credentials, request contents, database, browser state, or service state were accessed or changed.

| File | Candidate SHA-256 | Running site SHA-256 | Result |
|---|---|---|---|
| `includes/class-zmp-passkeys.php` | `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE` | `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE` | MATCH |
| `admin/class-zmp-passkey-profile.php` | `705C7DDA64DB3307DB34C06D1A5134AEA76464DF78CAB77BD3406E5A6A5DDD6D` | `705C7DDA64DB3307DB34C06D1A5134AEA76464DF78CAB77BD3406E5A6A5DDD6D` | MATCH |
| `assets/js/zmp-passkeys.js` | `B8B2BD2F352797671A3BC92FCF3887D3C2A77CFF2104BAFE69EAB47DC685D4FB` | `B8B2BD2F352797671A3BC92FCF3887D3C2A77CFF2104BAFE69EAB47DC685D4FB` | MATCH |

## Finding

The three directly relevant server/profile/browser source files match byte-for-byte between the `.66.a` candidate and the running synthetic site. A stale copy of these files is therefore not supported as the explanation for the reported registration failure. Runtime cause remains **UNRESOLVED / NOT REPRODUCED** because no enrollment-options request was observed. The previously reported user-facing error wording may have been paraphrased or originated in browser/runtime behavior; this check does not identify its source.

## Safe next observation

In the already logged-in Edge tab, the Owner may enter the synthetic account's current password locally and click enrollment once. Capture only HTTP status, the fixed diagnostic category/header, and presence of the matching fixed log marker. Do not collect request/response bodies, password, nonce, cookies, attestation, or credential data. No enrollment retry was initiated by this check.

## Diagnostic configuration check
The active synthetic site's wp-config.php defines ZMP_PASSKEY_DIAGNOSTICS as 	rue (boolean only recorded; no other configuration values inspected or copied). Therefore, when the enrollment options request reaches the plugin handler, its fixed diagnostic header/category and privacy-safe marker are enabled. This does not establish that a request was submitted or that a 403 originated in the plugin.
