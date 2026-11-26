## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Local Chrome task-tab workflow and recovery — `.65`

Date: 2026-11-04  
Purpose: repeatable operator handoff for the next President lifecycle browser acceptance. This records the successful local-only workflow; it does not grant authority beyond the project's existing Chrome/WSL authorization.

## Verified workflow

1. Start with `await cua.getState()` and inspect the current browser inventory. In this session, desktop Chrome was browser ID `7`; a separate Chrome profile was ID `5`. Re-read the inventory on recovery instead of assuming that IDs remain stable.
2. Create a task-owned local tab using the browser ID from the fresh inventory and omit unsupported visibility options:

   ```js
   let tab = await cua.createBrowserTab("7", "http://127.0.0.1:8094/wp-login.php");
   ```

   This exact call succeeded and created tab `2090197924`. Passing `{ visible: true }` previously returned `Capability is not available: visibility`; do not pass that option. The successful default call is the supported path; no alternate browser surface is needed.
3. If resuming an already-open blank task tab, bind by its listed tab ID and the exact desktop browser ID:

   ```js
   let tab = await cua.getTab("<task-tab-id>", { browser: "7" });
   ```

   The task-tab ID `2090197920` was successfully bound this way. `browser: "chrome"` selected a different Chrome profile (ID `5`) and returned “Tab not found in browser 5”; use the exact inventory ID for the intended task tab.
4. Navigate only to the approved local synthetic site with `tab.goto(...)`. A `chrome://newtab/` binding returns metadata only; use the documented `goto` method to open the local URL. After navigation or any interaction, call `tab.getAXState()` and derive fresh control indexes from that current state. Never reuse indexes after page changes.
5. Use only the synthetic account/credential permissions already recorded in project policy. Do not expose credentials, private signing keys, private URLs, or personal values in command output, browser screenshots, logs, reports or artifacts. Keep test-side private key material off the site.
6. At review completion, close the exact task-owned tab and verify it is absent:

   ```js
   await tab.close();
   await cua.listTabs({ browser: "7" });
   ```

   The two Lead-created/review task tabs (`2090197920` and `2090197924`) were closed; readback listed only the pre-existing tabs. Do not close or navigate unrelated/Owner tabs. Preserve the phase PHP/MariaDB services.

## Synthetic login and recovery

- The Owner authorizes generating, setting/resetting and entering a temporary synthetic username/password only on the disposable local `.65` WordPress site. The credential must stay in memory and must not be saved by Chrome or recorded in tool output, reports, logs, screenshots, shell history, GitHub or ZIP.
- This credential authorization is standing; do not ask the Owner for the same permission again. At every recovery, inspect fresh Chrome inventory and use the exact profile hosting the local ZMP site. The Test Engineer's 2026-11-04 follow-up used profile ID `4`, while the local ZMP tabs were in profile ID `7`; its login page therefore did not establish that the intended profile/session or approved synthetic credential flow was unavailable. Root's task tab `2090197949` in profile `7` also presented login and is retained for the required credential handoff.
- Before entering a newly set password, verify it against that exact local site with `wp_check_password` through WP-CLI. If the result is not a match, correct the WP-CLI update once before opening/submitting the browser form; do not repeatedly submit guesses. A verified synthetic account login successfully rendered `/wp-admin/profile.php` in this session.
- Preserve the original synthetic password hash in memory. After the review, use the WordPress local sign-out control in the owned tab, restore the exact original hash, verify byte equality and run `wp db check`. Remove only the session created by the task; do not log out other sessions or reset unrelated accounts. Then close and verify only the owned tab.
- Current session verified this workflow at browser ID `7`, task tab `2090197932`. It was closed after sign-out, exact hash restoration and DB check. These IDs are historical examples; use fresh inventory and current IDs on each handoff.

## Handoff boundary

- Owner explicitly authorizes generating and entering temporary synthetic usernames/passwords only on this project-managed local disposable WordPress site. Use a task-owned synthetic account; keep credential values in memory, never save them in Chrome, and never place them in reports, logs, screenshots, shell history or artifacts. Restore the original synthetic credential/session state after the check and verify cleanup.
- For certificate key-loss acceptance, create a fresh Ed25519 keypair outside WordPress and submit only the required public key and signed registration data through the authenticated local application route. Never transmit or persist the private key on the site. See `reports/PRESIDENT_CERTIFICATE_KEY_LOSS_ACCEPTANCE_1.2.4.65_2026-11-04.md`.

- The previous President governance download attempt is **not runtime PASS**: Chrome returned `ERR_BLOCKED_BY_CLIENT`. Its response/body must not be inspected by switching to `chrome://downloads` or another surface; preserve the exact limitation in `PRESIDENT_GOVERNANCE_DOWNLOAD_ROUTE_1.2.4.65_2026-11-04.md`.
- The next browser test is the distinct fresh-key President certificate replacement route. Use a new task-owned Chrome tab as above; do not reuse or take over a pre-existing tab. Do not repeat the governance download click or old route fixtures.
- **File chooser boundary:** the documented `waitForEvent("filechooser")` then `setFiles(path)` path was attempted once from the authenticated local certificate UI. Chrome refused with a request to enable ChatGPT extension **Allow access to file URLs**. Do not try another upload surface or change extension permissions. Resume only after the Owner enables that exact setting for this local synthetic task; verify selected-file state before submitting the application form.
- File chooser boundary: the documented `waitForEvent("filechooser")` then `setFiles(path)` path was attempted once from the authenticated local certificate UI. Chrome refused with a request to enable ChatGPT extension **Allow access to file URLs**. Do not try another upload surface or change extension permissions. Resume only after the Owner enables that exact setting for this local synthetic task; verify selected-file state before submitting the application form.
- If tab creation fails even without an unsupported option, record the exact tool response and ask the Owner per `AGENTS.md`; do not infer a general denial, retry in loops, or substitute another automation surface.

## Verified basis

The local login page loaded successfully; the synthetic President session reached `/wp-admin/profile.php`; the privacy review completed without submitting the profile; the original synthetic password hash was restored, the task-created session row removed and verified absent, and `wp db check` passed. No source/test files or user profile fields were changed. This note records browser handoff mechanics only.
