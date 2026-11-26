## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Communications results publication gate — local synthetic PASS

Date: 2026-11-25 (Europe/Skopje).
Scope: Fail-closed result publication in both direct-create and saved-draft handlers in the isolated `.66.b` WordPress/MariaDB clone.

The synthetic database had exactly one legacy competition for `2026/2027`, its `regional` phase status was `open`, and it had zero regional result rows. The test fixture refused to proceed if this prerequisite differed. Candidate/runtime `class-zmp-communications.php` SHA-256 matched at `2A3BB1074CE446863F76ED0669CBB89582651FF4FED996C9C0661C9D7081F260`. Fixture `tests/communications-results-gate-runtime.php` SHA-256 `EEE0C5C1A90B59D7756DB0295024F701820AC31DAC2219E8B1BC1F98D0346C28`; PHP syntax PASS.

## Direct creation

PASS: the synthetic Subscriber+Moderator attempted to publish a result announcement for the open regional phase. The handler redirected with `results_not_confirmed`; no announcement, notification, or linked WordPress post was written. No email was attempted. Exact counts for announcements, notifications, posts and users returned to baseline after removing the synthetic account; `wp db check` passed.

## Existing draft

PASS: the same handler family was exercised through `handle_publish_draft()` with an exact synthetic draft. It redirected with `results_not_confirmed`; the draft remained a draft with no `published_at` or linked post, and no notification was created. Exact cleanup restored all tracked counts and `wp db check` passed.

WP-CLI emitted the expected redirect warning for both handler exits. No product source changed. Logs: `/home/tonic/zmp-local-sandbox/phase66b-test/logs/communications-results-gate-20261125-new.txt` and `...-draft.txt`.

This proves the negative gate for the tested legacy year/phase scope. It does not prove positive publication after valid confirmed results, nor category-scoped result gates or browser presentation.
