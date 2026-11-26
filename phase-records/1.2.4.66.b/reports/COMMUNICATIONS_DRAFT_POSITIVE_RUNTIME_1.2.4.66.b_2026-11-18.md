## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Communications draft creation — local synthetic runtime evidence

Date: 2026-11-18 (Europe/Skopje)
Scope: Positive moderator authorization for saving a draft announcement in the isolated `.66.b` WordPress clone. No announcement was published, no notification/email was sent, and no live site was accessed.

## Candidate and test

- Runtime: `/home/tonic/zmp-local-sandbox/phase66b-test/site`; synthetic database `zmp66b_isolated`, prefix `z66a_`.
- Test helper `communications-draft-positive.php`, SHA-256 `A12250960FB171259382C9F5B49182F41EB0274CC330E373542B110745E792A9`; PHP syntax PASS.
- Created a disposable synthetic Subscriber+Moderator, generated a valid `zmp_communications` nonce using WordPress's actual `_wpnonce` field, and invoked the registered product handler with draft status and a unique synthetic title.

## Result

PASS: moderator capability was recognized; exactly the expected draft record was created for the synthetic actor; no WordPress post or notification was created for the draft. Cleanup removed the draft and test user. Announcement, notification, and WordPress post counts matched their baselines after cleanup. `wp db check` passed.

The first harness attempt used a custom nonce field, while the form/handler expects `_wpnonce`; WordPress correctly rejected it before any write. The helper was corrected to use the real field, then passed. WP-CLI emitted its expected redirect warning when the handler completed; shutdown assertions confirmed the created draft and cleanup. This was a harness correction, not a product defect.

## Limits

This closes only moderator draft creation. Publishing an announcement, recipient selection/notifications, WordPress post synchronization, update of an existing draft, results-publication gate, and browser UI remain open. No email or external HTTP request was made.
