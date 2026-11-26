# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# `.66.b` privileged route negative runtime matrix — 2026-11-18

## Scope and method

Executed every plugin-owned runtime hook registered under `admin_post`, `wp_ajax`, and `wp_ajax_nopriv` (`*_zmp_*`) by dispatching the actual WordPress callbacks in isolated `.66.b` WP-CLI processes. Authenticated routes used synthetic Teacher 117 with no route nonce; `nopriv` routes used user 0. Empty resource IDs/tokens/files were supplied unless a route required a trigger field. A temporary MU-plugin guard blocked any SQL DML/DDL and recorded attempts; all WordPress HTTP and email transports were intercepted. No production site or external account was used. This is runtime callback authorization/invalid-input evidence, not browser transport proof or a positive-path test for every route.

## Results

- Runtime hooks exercised: 119 / 119; no missing callback result, timeout, or fatal PHP error.
- Final outcomes: 116 denial responses (role, nonce, invalid token/resource, or `-1`); 2 intended public President-display JSON reads; 1 handover handler attempted its forbidden redirect branch with missing nonce.
- SQL writes attempted: 0; email attempts: 0; WordPress HTTP attempts: 0; PHP warnings: 0 after the download guard fix.
- Both public President-display responses contained only `state`, `display_name`, `start_date`, `end_date`, and `presidents`; each President object contained only display name and dates. No email, phone, address, user ID, or photo URL appeared.
- Database integrity: `wp db check` PASS after the matrix.
- Candidate and active isolated runtime hashes match for `class-zmp-registration-requirements.php`: `0047A11E003DD39132A30B997E8AF2115CA103490E4EF607AE69B31378E9581E`; PHP lint PASS for both.

## Routes and observed outcome

| Hook | Outcome | SQL | Mail | HTTP |
|---|---|---:|---:|---:|
| `admin_post_nopriv_zmp_accept_committee_invite` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_accept_committee_invite` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_admin_message_review_read` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_admin_message_review_request` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_approve_circular_invitation` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_archive_competition` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_assign_competition_members` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_audio_tool_approve` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_audio_tool_archive` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_audio_tool_audit` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_audio_tool_reject` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_audio_tool_save` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_circular_pdf` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_decide_president_facsimile_handover` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_download_admin_facsimile` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_download_certificate` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_download_certificate_template` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_download_entry_document` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_download_knowledge_certificate` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_download_registration_form` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_download_year_archive` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_drive_callback` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_drive_connect` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_drive_disconnect` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_export_teacher_register` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_jury_print_roster` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_mark_notification_read` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_matrix_stream` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_migrate_drive_folders` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_migrate_legacy_storage` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_organizer_mark_read` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_organizer_message` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_passkey_registration_finish` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_passkey_registration_options` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_passkey_remove` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_president_governance_acknowledge` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_president_governance_archive` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_president_governance_download` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_president_governance_publish` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_president_governance_upload` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_president_handover_extend` | FORBIDDEN REDIRECT | 0 | 0 | 0 |
| `admin_post_zmp_president_public_display_save` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_publish_announcement` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_publish_draft` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_purge_category_videos` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_register_president_facsimile_certificate` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_remove_fee_exception` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_remove_school_contact` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_request_fee_invoice` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_reset_competition` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_revoke_president_facsimile_certificate` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_role_message` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_role_message_attachment` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_role_message_export` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_role_retention_deadline` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_role_retention_retry` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_role_review_notice_ack` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_save_admin_facsimile` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_save_circular_invitation` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_save_drive_folders` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_save_drive_settings` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_save_fee_exception` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_save_fee_payment_details` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_save_registration_requirements` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_save_school_contact` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_save_teacher_assignments` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_school_email_access_decide` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_school_email_access_disclose` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_school_email_request_submit` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_send_circular_invitation` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_stream_category_video` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_submit_invoice_batch` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_submit_president_facsimile` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_submit_subject_proposal` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_upload_knowledge_fee_proof` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_assign_regional_juror` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_callback` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_check` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_clear_uncertain_upload` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_connect` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_disconnect` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_finalize` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_save` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_unassign_regional_juror` | DENY | 0 | 0 | 0 |
| `admin_post_zmp_youtube_upload` | DENY | 0 | 0 | 0 |
| `wp_ajax_nopriv_zmp_document_token_info` | DENY | 0 | 0 | 0 |
| `wp_ajax_nopriv_zmp_document_token_upload` | DENY | 0 | 0 | 0 |
| `wp_ajax_nopriv_zmp_heartbeat` | DENY | 0 | 0 | 0 |
| `wp_ajax_nopriv_zmp_passkey_login_finish` | DENY | 0 | 0 | 0 |
| `wp_ajax_nopriv_zmp_passkey_login_options` | DENY | 0 | 0 | 0 |
| `wp_ajax_nopriv_zmp_president_public_display_get` | PUBLIC READ-ONLY | 0 | 0 | 0 |
| `wp_ajax_nopriv_zmp_save_answer` | DENY | 0 | 0 | 0 |
| `wp_ajax_nopriv_zmp_submit_exam` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_commission_close_phase` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_commission_decide_complaint` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_commission_finalize_competition` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_commission_notifications` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_disqualify_target` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_document_token_info` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_document_token_upload` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_heartbeat` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_jury_result_step_up_options` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_matrix_import_drive` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_matrix_picker_config` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_matrix_preview_url` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_matrix_search` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_matrix_upload` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_moderator_control_attempt` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_moderator_live_students` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_president_public_display_get` | PUBLIC READ-ONLY | 0 | 0 | 0 |
| `wp_ajax_zmp_president_subject_step_up_options` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_reply_incident` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_reset_student_test` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_save_answer` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_submit_complaint` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_submit_exam` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_submit_incident` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_test_db_connection` | DENY | 0 | 0 | 0 |
| `wp_ajax_zmp_testator_decide_complaint` | DENY | 0 | 0 | 0 |

## Defect corrected during the matrix

The `download_form()` and `download_entry_document()` callbacks read query parameters without a missing-key default. An invalid request with the key absent emitted PHP `Undefined array key` warnings before the nonce denial. Both now use `absint($_GET[... ] ?? 0)`, after which missing-key requests return the expected invalid-nonce denial with no warning. The edit is in `includes/class-zmp-registration-requirements.php` and was synchronized to the isolated runtime; candidate/runtime hashes match.

## Limits

The matrix checks unauthorized/invalid requests only. It does not substitute for positive-path checks for every privileged operation, nor does it close browser UI, Owner YouTube channel reconciliation, `.59` current-hash race assertion, historical `.65` attribution/provenance, or phase-wide independent review/release gates.

Reproducibility helpers are privileged-route-denial-harness.php and privileged-route-write-blocker.mu.php; both are test evidence only and must not be installed on a live site.