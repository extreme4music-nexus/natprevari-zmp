# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Messenger limited-review request, admin review and notice acknowledgement — 1.2.4.66.b — 2026-11-24

## Result

**PASS 7/7** on the isolated local .66.b WordPress runtime. A synthetic Subscriber+Moderator requested review of one exact moderator-room thread through handle_request_admin_message_access(). The request stored pending status, thread/scope and the reason. Synthetic administrator account 1 opened only that thread through handle_admin_message_review_read(); the captured response returned 200 and included the exact reason and two synthetic messages, with the fixed notice that attachments are not displayed. The database recorded 
eviewed_by=1, 
eviewed_at, and one 	hread_reviewed audit row bound to the same thread. Exactly the two thread participants received scoped review notices. The requester acknowledged their own notice through handle_review_notice_ack().

All seven final assertions passed: request creation/scope, fixed read-only review response, reviewed state/actor, exact audit, two participant notices, requester acknowledgment, and exact baseline restoration with both synthetic accounts removed. Post-test wp db check passed. The actual handlers redirected/exited as expected under WP-CLI.

The first test harness revision captured the fixture array by value before the audit ID was assigned, so its final notice-read assertion falsely reported FAIL even though review, audit, notices and cleanup had passed. Correcting the capture to reference the updated test fixture produced the final 7/7 PASS. This was a harness assertion defect, not a plugin defect.

## Evidence

- Request/staging fixture: 	ests/role-messenger-review-request-stage.php — SHA-256 $stage.
- Review and notice acknowledgment fixture: 	ests/role-messenger-review-read-ack.php — SHA-256 $review.
- Product/runtime source includes/class-zmp-role-messenger.php matched the phase source — SHA-256 $src.
- The local HTTP guard continued blocking WordPress background HTTP; only hostnames were recorded, and no external request was permitted. Temporary fixture users, rows, audit, notices and private session file were removed.

## Scope

This verifies the limited Moderator-request → Administrator-review → participant-notice/ack path for one synthetic moderator conversation. It does not test the 200-message omission edge case, multi-region commission scopes, concurrent review requests, or user-interface/mobile acceptance. No product code, production website or production database was changed.