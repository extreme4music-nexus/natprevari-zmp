# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Synthetic matrix catalog upload — .66.b — 2026-11-18

## Result

PASS for the current local upload path. The exact candidate/runtime SHA-256 of includes/class-zmp-matrix-catalog.php matched: 97681421B5712CF16A97DB4E4B83C8487D4DDC40431AF7A151C23CF749DBFEC5.

A generated one-second silent MP3 (4,412 bytes) was sent as a real multipart upload to the isolated local WordPress admin-ajax endpoint, authenticated as synthetic local Administrator 1. The actual zmp_matrix_upload handler accepted the nonce, file upload, metadata and audio MIME, called the current Drive-backed service, and returned JSON success with matrix row id 3. The row stored the expected title, synthetic Drive ID, audio/mpeg, exact size/hash and active status.

Google OAuth and Drive were replaced for this loopback-only request by a temporary MU mock. It returned only a synthetic access token, resumable-upload URL and synthetic file ID; the mock returned an error for every other HTTP request, so no network call reached Google or any external host. Four expected requests were captured: two token refreshes, upload initialization, and one byte transfer.

## Cleanup and limits

The exact synthetic matrix row was deleted and verified absent. The temporary administrator session token was destroyed; temporary MU plugins, nonce/auth helpers, MP3 and response logs were removed. The original matrix table count was 2 and returned to 2. WP-CLI database integrity check passed. No Drive setting or product source changed.

This closes current-candidate local upload-handler and mocked-transport coverage. It does not establish real Drive OAuth/permissions, real Drive playback, or mobile UI acceptance. Historical .48 live-Drive and production checks remain correctly NOT RUN.


## Matrix playback/readback route — PASS — 2026-11-18

A second synthetic fixture exercised the authenticated local admin-post stream handler with a loopback-only Google Drive download mock. The handler returned HTTP 200 with Content-Type audio/mpeg, Content-Length 4412, and Accept-Ranges. The downloaded response SHA-256 exactly matched the synthetic source MP3: b952ff4dee65b48d4f85e7be04b1160c4e5a0f9b4ddafad98906fd6bc127359b. Mock transport intercepted both token refresh and the Drive file read; every other HTTP request was blocked. The exact matrix row and temporary Administrator session were removed, restoring the table to its prior 2 rows; wp db check passed. This verifies current-code local upload plus authenticated playback/readback with mocked Drive. It does not claim live Google Drive playback.
