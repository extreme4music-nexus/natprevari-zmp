## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger — AJAX schema log source attribution (`.65`)

Date: 2026-11-04

## Findings

- School-profile saving in the `.65` candidate uses WordPress `personal_options_update` and `edit_user_profile_update` hooks in `includes/class-zmp-support.php`; no matching school-profile `wp_ajax_*` handler was found.
- School-email submit/decision and disclosure paths use `admin_post` hooks in `includes/class-zmp-school-email-access.php` and `includes/class-zmp-school-email-access-ui.php`; no matching school-email `wp_ajax_*` handler was found.
- A plausible explanation for the supplied `admin-ajax.php` rows is that WordPress executes `init` on admin-AJAX requests and the `.65` `zmp_competitions_init()` retry invokes the schema installers. Those installers can emit the reported schema messages when a prerequisite remains unavailable. This is a source-based hypothesis only; it does not identify which AJAX action initiated the request or prove the originating site/version ran `.65`.
- The supplied AJAX rows provide path and flow ID but no `form_action`/WordPress `action`. Exact action attribution is **UNCONFIRMED / NOT RUN**. No matching AJAX endpoint can be inferred from the path alone.

## Safe next evidence

If available, obtain only the sanitized same-flow `context.form_action` or the browser Network request's `action` value. Do not collect cookies, nonce, POST payload, or unrelated request data. No production access was performed.

