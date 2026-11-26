## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Saved category-result draft recipient scope — local synthetic PASS

Date: 2026-11-25 (Europe/Skopje).
Scope: Exercise the actual saved-draft publication handler for a category-scoped regional result release in the retained isolated .66.b WordPress/MariaDB clone. No production site, external mail, or YouTube was contacted.

Candidate/runtime includes/class-zmp-communications.php SHA-256: $src (matched before execution). Fixture 	ests/communications-category-results-draft-recipients-runtime.php SHA-256: $test.

The fixture created a synthetic Moderator, two synthetic Subscriber entry owners, a category result scope, a closed municipal predecessor with one qualified and one unqualified entry, and a closed regional stage with the qualified entry's complete confirmed result. It then created a synthetic category-results draft and called ZMP_Communications::handle_publish_draft().

PASS: the existing draft became published; exactly the regional-stage-qualified synthetic entry owner received an unread notification; the unqualified owner was excluded; the notice linked to a published WordPress post; no mail was attempted; all synthetic announcements, notices, posts, users, entries, results and closures were removed; tracked counts returned to baseline; and wp db check reported all tables OK. PHP lint passed. The direct-publish counterpart is separately recorded in COMMUNICATIONS_CATEGORY_RESULTS_RECIPIENT_SCOPE_1.2.4.66.b_2026-11-25.md.

Run command: wp --path=/home/tonic/zmp-local-sandbox/phase66b-test/site --allow-root eval-file /home/tonic/zmp-local-sandbox/phase66b-test/tmp/communications-category-results-draft-recipients-runtime.php.

This closes the saved category-results draft path for one synthetic regional scope only. General announcement audience semantics, broader communications tests and overall .66.b acceptance remain open. No product source change was needed in this test. Phase remains IN PROGRESS / NOT_READY.
