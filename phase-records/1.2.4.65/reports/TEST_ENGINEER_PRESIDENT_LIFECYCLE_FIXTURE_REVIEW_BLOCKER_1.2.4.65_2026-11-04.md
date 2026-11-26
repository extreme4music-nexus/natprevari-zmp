## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — President lifecycle fixture acceptance checkpoint

Date: 2026-11-04

## Selected open scenario

Reviewed existing tests/phase65-president-lifecycle.php as a possible one-run service-level verification for mandate transition, certificate key rotation/revocation, and non-President denial. The existing HTTP route report confirms this integrated behavior is still incomplete and specifically says its service-level replacement-key evidence does not establish browser-bound admin-post behavior.

## Candidate checks

- Candidate fixture SHA-256: 7765827D2E908A73BC807B8A1A86EFF12F47B6B04A45DFC978FC3476041032B0.
- PHP lint: PASS, no syntax errors.
- Independent exact-hash Code Review: FAIL P2 for cleanup safety. The fixture calls ZMP_President_Governance::admin_upload_document() twice, which creates the deterministic zmp-president-documents-<sitehash> private directory. Its finally block cleans document rows/files but does not capture, remove, or verify that directory. The reviewer identified the calls at fixture lines 145–156 and 180–186, cleanup at 383–414, and directory creation in includes/class-zmp-president-governance.php lines 477–479.
- Other reviewed safety observations: generated users and numeric row IDs are scoped; Ed25519 keys are generated in memory; reviewer found no mail/network calls. The P2 is test-fixture cleanup only and does not establish a product defect.

## Runtime result

NOT RUN / BLOCKED before environment preflight. Exact-hash review did not pass, so the candidate was not staged or executed. No WordPress database, user, route, file, service, or port was accessed or modified by this check. The authorized existing .65 synthetic clone remains untouched.

No replacement candidate was created in this bounded task. A separate cleanup correction and exact-hash review would be required before any runtime attempt.

## Separately authorized cleanup remediation — 2026-11-04

The original FAIL/P2 hash and finding above remain unchanged. In a separate bounded follow-up, only tests/phase65-president-lifecycle.php was updated to snapshot the matching governance-document directory recursively (relative entries and file hashes) when it exists before the test. Existing contents are compared after cleanup and never deleted. If the directory was absent at baseline, cleanup attempts rmdir only after the fixture's tracked document files have been removed and the directory is empty; absence is verified. A failed or unsafe baseline prevents the scenario from running.

- Corrected fixture SHA-256: 1DFE592186F152952AD4658A7A4A29C8623B3AF9319B1C669B40D866E081C605.
- PHP lint: PASS.
- Independent exact-hash Code Review: PASS. Report SHA-256 894783DDDACC8E9F4D7CB39709F815F390FFFB5BF8DCF6E984E5F9694CFCBD8B.
- Runtime/preflight: NOT RUN. The single bounded read-only WSL preflight batch returned Wsl/E_ACCESSDENIED for the authorized disposable site before any WP-CLI command ran. Database identity/integrity, plugin inventory, live service state, and installed/source hashes therefore remain unverified for this attempt. No fixture was staged and no database, site file, user, route, service, or port was changed. No retry was made.

This test-only cleanup remediation is reviewed but functionally unverified. It does not establish a product defect or close the lifecycle acceptance gate.

## Direct-mount preflight follow-up — 2026-11-04

The Owner-designated direct mount `/mnt/z/Documents/Natprevari_ZMP` was used in a single read-only preflight batch against the existing disposable site `/home/tonic/zmp-local-sandbox/phase65-test/site`. WSL returned `Wsl/E_ACCESSDENIED` before the Bash command began. No WP-CLI command ran; site root/URL, database identity/prefix/integrity, plugin/user inventory, process/port state, and installed/source hashes could not be verified. The fixture was not staged and no site/database/service state changed. No retry was made.

Final status for this candidate remains **review PASS / runtime NOT RUN / preflight BLOCKED**. The cleanup correction is not behaviorally verified and does not close the President lifecycle acceptance gate.

## Owner-approved elevated preflight follow-up — 2026-11-04

Using the Owner-confirmed absolute native PowerShell launcher with WSL working directory `/mnt/z/Documents/Natprevari_ZMP`, the single read-only preflight against `/home/tonic/zmp-local-sandbox/phase65-test/site` again returned `Wsl/E_ACCESSDENIED` before Bash began. Consequently none of the requested root/DB/plugin/user/service/source-hash or integrity checks ran. The reviewed fixture was not staged and no site, database, filesystem, or service state changed. Per the bounded-attempt instruction, no further invocation was made.

Candidate disposition remains **exact-hash review PASS / preflight BLOCKED / runtime NOT RUN**.

## Owner-elevated synthetic-site preflight — 2026-11-04

The newly authorized elevated `exec_command` path successfully reached the existing disposable site. Read-only preflight passed:

- Absolute site root `/home/tonic/zmp-local-sandbox/phase65-test/site`; URL `http://127.0.0.1:8094`; database `zmp65_isolated`; prefix `z65_`; `wp db check` reported all tables OK.
- Full plugin inventory contained only active `zmp-competitions` 1.2.4.65. All seven listed users used `@example.invalid`; query found 0 non-synthetic email domains and 0 pre-existing `zmp65life_` fixture users.
- Baseline: 3 President mandates; 0 mandate-audit, governance-document, certificate, certificate-event, and facsimile rows.
- Corrected fixture SHA-256 matched `1DFE592186F152952AD4658A7A4A29C8623B3AF9319B1C669B40D866E081C605`. Installed files matched phase source byte-for-byte: entrypoint `291D0E7601B1DA01CDC96FE12774FB632E2638169E8526DDC3E9864D4B124A96`; governance `BABF6A6310311EB97434ACAD7F41025BFD506822839797C91668B0ADE5790B8D`; mandate `DD941ADF7EFE48AA339BE2938CAB409F195321679EE01A5CD58CA5E0E903EDE`; facsimile certificates `153B2E4A73214B9B40660F98FF6C217ADBE687417EE950B1889513C37DC3C587`; registration requirements `EA0F66C078118BB489CF17D5BC23524191E8D4C8C87AD2543B8837F0E0D6A138`.
- MariaDB PID 144183/private socket and PHP PID 145525/loopback port 8094 were present. No service was started, stopped, or restarted.
- Sodium was present, but the disposable site's President document encryption key was absent; the fixture needs a temporary synthetic key to exercise encrypted document paths. No key value was generated or written during this attempt.

## Runtime invocation result

**NOT RUN.** The elevated runtime wrapper failed at its initial PHP command for reading the existing wp-config hash (`PHP Parse error: unexpected token backslash`). This occurred before the cleanup trap, temporary-key setup, fixture staging, or `wp eval-file`; therefore the fixture body never executed and no product behavior result was produced. No configuration, database, file, user, service, or port was changed by the attempt. No retry was made.

Status for this candidate: **exact-hash review PASS / read-only preflight PASS / runtime NOT RUN due command-wrapper syntax failure**.

## Engineering Lead's one-time runtime and Test Engineer reconciliation — 2026-11-04

After the wrapper failure above, the Engineering Lead executed the exact reviewed fixture once through a direct WP-CLI process invocation with a random ephemeral President-document key defined only in that PHP process. The exact candidate remained SHA-256 `1DFE592186F152952AD4658A7A4A29C8623B3AF9319B1C669B40D866E081C605`; no source or fixture bytes changed.

**Runtime reported by the Lead: 23 PASS, 0 FAIL.** The fixture's passing service-level assertions are:

1. `active_president_reads_own_encrypted_document` — PASS.
2. `current_outgoing_president_owns_document_access` — PASS.
3. `active_concurrent_president_document_list_is_owner_scoped` — PASS.
4. `active_concurrent_president_cannot_get_foreign_document_id` — PASS.
5. `active_concurrent_president_cannot_decrypt_foreign_document` — PASS.
6. `second_active_president_reads_own_document` — PASS.
7. `second_active_president_list_is_owner_scoped` — PASS.
8. `former_president_denied_after_mandate_ends` — PASS.
9. `outgoing_president_reads_own_document_during_scheduled_overlap` — PASS.
10. `outgoing_president_access_revoked_when_linked_successor_takes_effect` — PASS.
11. `teacher_direct_document_id_denied` — PASS.
12. `testator_direct_document_id_denied` — PASS.
13. `anonymous_direct_document_id_denied` — PASS.
14. `facsimile_path_hash_is_verified` — PASS.
15. `incoming_active_president_certificate_status_available` — PASS.
16. `invalid_certificate_signature_rejected` — PASS.
17. `signed_certificate_binds_facsimile_and_verifies` — PASS.
18. `certificate_key_rotation_supersedes_old_key` — PASS.
19. `teacher_denied_certificate_registration_context` — PASS.
20. `anonymous_denied_certificate_registration_context` — PASS.
21. `admin_revocation_invalidates_current_certificate` — PASS.
22. `governance_private_directory_preserved_or_removed` — PASS.
23. `fixture_cleanup` — PASS.

Explicit fixture limitations remain NOT RUN: `http_admin_post_document_and_certificate_routes`, `private_facsimile_download_route`, and `key_loss_recovery`. This service-level run does not prove HTTP/browser-bound nonce/session handling, direct URL denial, private facsimile download authorization, or actual signing-key recovery. It closes only the listed mandate/document access, transition, certificate binding/rotation/revocation service-level subcriteria.

**Independent post-run Test Engineer read-only reconciliation:** WSL directly accessed the authorized disposable site from `C:\\Windows\\Temp`; database integrity passed. Site identity was `/home/tonic/zmp-local-sandbox/phase65-test/site`, URL `http://127.0.0.1:8094`, DB `zmp65_isolated`, prefix `z65_`. Only plugin `zmp-competitions` 1.2.4.65 was active. Seven accounts remained and all were synthetic roles; no non-synthetic email domain or `zmp65life_` fixture account remained. Lifecycle tables returned to the observed baseline of 3 mandates and 0 mandate-audit, document, certificate, certificate-event, and facsimile rows. MariaDB PID 144183/private socket and PHP PID 145525/port 8094 were present. The fixture hash remained exact and installed entrypoint/governance/mandate/certificate/registration sources still byte-matched the phase candidate. No service was stopped or restarted. These independent cleanup checks do not upgrade the three NOT RUN route/recovery cases.

This later Lead-run result supersedes the earlier runtime-NOT-RUN status for the corrected hash only. The original candidate `7765827D2E908A73BC807B8A1A86EFF12F47B6B04A45DFC978FC3476041032B0` FAIL/P2 remains preserved. No test was repeated.

The newly specified absolute-native-PowerShell launch method was then used for one read-only preflight invocation with the same `/mnt/z/Documents/Natprevari_ZMP` direct mount. It also returned `Wsl/E_ACCESSDENIED` before Bash began. No WP-CLI, site, or filesystem operation ran; no fixture staging or runtime occurred. No further attempt was made.
