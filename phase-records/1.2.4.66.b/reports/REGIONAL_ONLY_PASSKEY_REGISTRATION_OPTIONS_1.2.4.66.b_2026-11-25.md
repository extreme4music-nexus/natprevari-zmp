## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Regional-only passkey registration options — local synthetic PASS

Date: 2026-11-25 (Europe/Skopje).
Scope: Exercise current regional-only commission eligibility through the real ZMP_Passkeys::registration_options() method in the retained isolated .66.b WordPress runtime. No browser authenticator ceremony, credential enrollment, production site, or external service was used.

Candidate/runtime includes/class-zmp-passkeys.php SHA-256: $src (matched before execution). Fixture 	ests/regional-only-passkey-registration-options-runtime.php SHA-256: $test.

A disposable synthetic zmp_commission account had exactly one active-season, exact-region jury assignment and zero ordinary category-level assignments. With the configured local origin and correct synthetic password, current eligibility returned true and the product registration-options method succeeded. The generated options used the local RP ID localhost, a synthetic user handle, and a matching registration challenge row bound to the synthetic user and session hash; expiry and unconsumed state were confirmed. No credential row was created.

Cleanup removed the exact regional assignment, challenge, account, session and account metadata. The passkey-options method normally deletes expired challenge rows before issuance; the local database had old expired rows, so the fixture preserved and restored those rows exactly. The post-test expired-row set matched its original contents, tracked totals returned to baseline, and wp db check reported every table OK. PHP lint passed. Runtime output: all flags 	rue in 
eports/REGIONAL_ONLY_PASSKEY_REGISTRATION_OPTIONS_RUNTIME_2026-11-25.txt.

Run command: wp --path=/home/tonic/zmp-local-sandbox/phase66b-test/site --allow-root eval-file /home/tonic/zmp-local-sandbox/phase66b-test/tmp/regional-only-passkey-registration-options-runtime.php.

This closes regional-only enrollment eligibility and issuance of registration options for one synthetic account. Browser authenticator ceremony and credential persistence remain NOT RUN; overall .66.b remains IN PROGRESS / NOT_READY.
