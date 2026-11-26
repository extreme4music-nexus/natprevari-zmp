## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# President lifecycle service-level runtime — 1.2.4.65

Date: 2026-11-04  
Runtime executor: Engineering Lead; independent Test Engineer pre/postflight review requested  
Candidate: `tests/phase65-president-lifecycle.php`  
Verdict: **PASS 23 / 23 executed checks, 0 FAIL; 3 explicitly out-of-scope HTTP scenarios NOT RUN.** This is service-level evidence only and does not close the integrated HTTP/browser lifecycle work package.

## Exact candidate and runtime

- Disposable site: `/home/tonic/zmp-local-sandbox/phase65-test/site`
- Synthetic DB/prefix: `zmp65_isolated` / `z65_`
- Fixture SHA-256: `1DFE592186F152952AD4658A7A4A29C8623B3AF9319B1C669B40D866E081C605` (same exact reviewed candidate)
- Source hashes matched the preflight record: plugin entrypoint `291D0E7601B1DA01CDC96FE12774FB632E2638169E8526DDC3E9864D4B124A96`; President governance `BABF6A6310311EB97434ACAD7F41025BFD506822839797C91668B0ADE5790B8D`; President mandate `DD941ADF7EFE48AA339BE2938CAB409F195321679EE01A5CD58CA5E0E903EDE`; facsimile certificates `153B2E4A73214B9B40660F98FF6C217ADBE687417EE950B1889513C37DC3C587`; registration requirements `EA0F66C078118BB489CF17D5BC23524191E8D4C8C87AD2543B8837F0E0D6A138`.
- Preflight and postflight verified the only active plugin was ZMP `.65`, seven synthetic users, three baseline mandates, zero lifecycle audit/document/certificate/facsimile rows, and valid DB integrity. Postflight `wp db check --allow-root --skip-plugins` exited 0. Test Engineer independently confirmed the same post-run baseline and existing PHP PID 145525/port 8094 and MariaDB PID 144183/private socket.
- Invoked the unchanged fixture once with direct WP-CLI `wp eval`, defining a fresh random `ZMP_PRESIDENT_DOCUMENT_KEY` in that PHP process only. The random key was not persisted or printed. An earlier PowerShell quoting attempt failed before WSL launched (no site effects); it is not a product test result and was not repeated. No fixture copy was staged.

## Executed checks — all PASS

1. `active_president_reads_own_encrypted_document`
2. `current_outgoing_president_owns_document_access`
3. `active_concurrent_president_document_list_is_owner_scoped`
4. `active_concurrent_president_cannot_get_foreign_document_id`
5. `active_concurrent_president_cannot_decrypt_foreign_document`
6. `second_active_president_reads_own_document`
7. `second_active_president_list_is_owner_scoped`
8. `former_president_denied_after_mandate_ends`
9. `outgoing_president_reads_own_document_during_scheduled_overlap`
10. `outgoing_president_access_revoked_when_linked_successor_takes_effect`
11. `teacher_direct_document_id_denied`
12. `testator_direct_document_id_denied`
13. `anonymous_direct_document_id_denied`
14. `facsimile_path_hash_is_verified`
15. `incoming_active_president_certificate_status_available`
16. `invalid_certificate_signature_rejected`
17. `signed_certificate_binds_facsimile_and_verifies`
18. `certificate_key_rotation_supersedes_old_key`
19. `teacher_denied_certificate_registration_context`
20. `anonymous_denied_certificate_registration_context`
21. `admin_revocation_invalidates_current_certificate`
22. `governance_private_directory_preserved_or_removed`
23. `fixture_cleanup`

The script emitted one expected synthetic certificate-revocation warning; its synthetic identifiers are intentionally omitted here.

## Scenarios not covered by this fixture

- `http_admin_post_document_and_certificate_routes`: **NOT RUN** — service-level checks do not prove direct URL, nonce, or browser response behavior.
- `private_facsimile_download_route`: **NOT RUN** — not safely invocable by this service-level script.
- `key_loss_recovery`: **NOT RUN** — no private signing-key storage/recovery API exists in this plugin path; only invalid-signature rejection was exercised.

## Cleanup and phase disposition

- Fixture-reported cleanup PASS; independent postflight baseline and database integrity PASS. No production site, external network, or email was used. No application change remains from the fixture.
- No product source changed. This closes only the reviewed fixture's President governance service-level scenarios. The three HTTP/browser scenarios, authenticated subject-queue denial, profile privacy DOM gate, other register/email routes, independent UI review and whole-phase Master readiness remain separate open work.
- `.65` remains **IN PROGRESS / NOT READY FOR AUDITOR**. No ZIP, GitHub sync/readback, Actions review, or Auditor was started.
