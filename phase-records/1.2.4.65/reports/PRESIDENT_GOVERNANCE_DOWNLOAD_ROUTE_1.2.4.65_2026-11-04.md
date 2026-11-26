## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# President governance-document download route — `.65`

Date: 2026-11-04

## Scope and result

The authenticated synthetic President download exposed a product defect in the pre-fix candidate. The candidate was corrected and independently Code Reviewed. The post-fix browser attempt reached the expected action URL, but Chrome returned `ERR_BLOCKED_BY_CLIENT`; response status, headers, and PDF bytes were therefore **not verified**. This route is not accepted as PASS yet.

## Reproducible finding and correction

- On the disposable synthetic `.65` site, President 2 (#26) opened the active-mandate document list and clicked document #21. Before the fix the request URL contained `document_id` and its nonce, but lacked `action`; WordPress returned the President scope-guard 403 page.
- Root cause: the download form used `method="get"`; its successful form fields replace the `action` query value supplied in `form_action_url()`. The form emitted document ID and nonce but no action field. The guard explicitly allowlists `zmp_president_governance_download`, and the corresponding authenticated hook is registered.
- The `.65` candidate now emits hidden `action=zmp_president_governance_download`, preserving the existing document ID and per-document nonce. Authorization and route policy were not broadened.
- Candidate SHA-256: `5AAF1D331DBB46D4ED2801ABDDE3F879229E4D69C7A53550B77F3A48765124DA`.
- PHP lint: PASS. Installed disposable-site copy matched the candidate hash and passed PHP lint. Independent Code Review: PASS for the exact candidate hash; see `reports/CODE_REVIEW_PRESIDENT_DOWNLOAD_FIX_1.2.4.65_2026-11-04.md`.
- After staging the matching source, the President form produced a URL containing `action=zmp_president_governance_download`; Chrome then displayed `ERR_BLOCKED_BY_CLIENT`. No claim is made about server response status or body after that point. No alternative browser route was attempted.

## Synthetic setup and cleanup

- Test target: `/home/tonic/zmp-local-sandbox/phase65-test/site`, DB `zmp65_isolated`, prefix `z65_`, loopback port 8094. Existing PHP/MariaDB services were left running.
- A 599-byte synthetic PDF was attached to mandate #24/user #26 using the application upload method; its original content hash was `7c093a23b6fc4daef01f3bd371a1cc1629825fa80390ff6b97954665802ca55b`. The encrypted copy was stored in the test sandbox outside the document root.
- The temporary President password hash was restored exactly; its backup and all task-created scripts/PDF were removed. The temporary encryption-key line was removed; `wp-config.php` hash returned to baseline `928f2a0c682928825db74fce3d304b86b579d81e2875ddaf49fdf5d4987cefcf`.
- Only document row #21 and its matching synthetic upload audit event #17 were removed. Post-cleanup governance document count and event #17 count were both zero; `wp db check` PASS. Task-created Chrome tabs 2090197915 and 2090197776 were closed. Existing local services and unrelated tabs were not stopped or changed.

## Acceptance status

This is a code fix with exact-hash review, not a route runtime PASS. The final authenticated download response and content hash remain unverified because the browser blocked the response. The wider President lifecycle/route work package and `.65` remain **IN PROGRESS / NOT READY FOR AUDITOR**. Preserve existing lifecycle service PASS 23/23 and archive-access PASS 15/15; do not rerun them.
