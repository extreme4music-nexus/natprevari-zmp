## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Test Engineer — multi-school directory scope — 1.2.4.65

## Current status — 2026-11-05
- **Evidence review:** PASS for the recorded bounded runtime result, exact source/fixture hashes, pre/post database integrity, fixture cleanup, and process/port preservation.
- **Recorded functional runtime:** PASS, 24/24 assertions, executed by the Engineering Lead via WP-CLI on the authorized local synthetic site. This report reviews that execution output; it does not claim a separate Test Engineer execution.
- **Independent Test Engineer runtime execution:** NOT RUN. Do not elevate this report to a separate independent execution verdict.
- **Scope:** the changed all-school directory authorization/privacy behavior and shortcode projection only. This does not close the full multi-school roadmap point or `.65` phase.

## Bound candidate
- Product source: `includes/class-zmp-mentor-school-profile.php`
- Product SHA-256: `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`
- Test-only fixture: `tests/phase65-multischool-directory-scope.php`
- Fixture SHA-256: `467C7C473252C2DB1ED04961A0360D7688FF4604E83B6EA049C1840CE8DA06FB`
- Independent source review: PASS on exact product hash, `reports/CODE_REVIEW_MULTISCHOOL_SCHOOL_SCOPE_1.2.4.65_2026-11-05.md`.
- Independent fixture review: PASS on exact fixture hash (latest review in the session record).

## Runtime evidence reviewed
- Authorized local site: `127.0.0.1:8094`; database `zmp65_isolated`, prefix `z65_`.
- Preflight: active plugin inventory, installed candidate hash, exact synthetic Teacher #127 and Subscriber #111 identity/role preconditions, ready profile schema and expected two school engagements were checked by the fixture. Pre-run `wp db check` PASS.
- Fixture output: `overall=PASS`; all 24 assertions PASS. Assertions covered authorized all-school scope, school-specific queries, omission of official email, independent per-school visible employment-term/retired fields, exclusion of disabled fields in actual shortcode HTML, denial of an unrelated active school and below-Teacher subscriber, exact restoration of diagnostic/schema options, and no `$wpdb` error.
- Expected denied-access diagnostics were sanitized `school_not_owned` and `forbidden`; no email send or network attempt occurred. Output did not include personal names, email values, or profile field values.
- Post-run `wp db check` PASS; staged fixture copy removed and absence verified. The existing PHP and MariaDB processes remained running and port 8094 remained listening; no server lifecycle action occurred.
- Installed product source hash remained `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B` after execution.

## Limits and remaining work
The test verifies a bounded synthetic fixture and shortcode callback, not browser rendering/responsiveness or the whole roadmap point. Teacher profile save/reload for separate school fields/privacy was separately observed in the current session and documented in `reports/MULTISCHOOL_PROFILE_ACCEPTANCE_1.2.4.65_2026-11-05.md`; President photo/consent PASS evidence remains preserved and was not repeated. Multi-school point still needs its remaining criterion reconciliation, cleanup of the exact synthetic Teacher if no longer needed, and independent Master review before it can be closed. Whole `.65` phase remains IN PROGRESS; no Auditor, ZIP, or GitHub synchronization is authorized by this scoped report.

## Historical fixture corrections
Earlier fixture hashes `9005928A...` and `2714D245...` were superseded before execution. The current fixture was tightened to stop on exact environment/account preconditions and use only the previously validated Subscriber #111, removing broad subscriber lookup. Only the bound `467C7C...` fixture hash was staged and run.

## Test Engineer execution — 2026-11-05
The Test Engineer independently performed one fresh bounded execution of the exact current fixture after preflight on the authorized local site. Fresh preflight: DB check PASS; active plugin inventory was only `zmp-competitions`; installed source hash matched `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`; version was `1.2.4.65`; the known synthetic account roles matched the fixture preconditions (`zmp_teacher`, `subscriber`). Exact synthetic identity and email-domain checks were performed by the fixture without emitting identity values. Fixture source and staged-copy hash both matched `467C7C473252C2DB1ED04961A0360D7688FF4604E83B6EA049C1840CE8DA06FB`.

**Result: PASS, 24/24 assertions.** All assertions enumerated in “Runtime evidence reviewed” passed. The only runtime warnings were the expected sanitized denials `school_not_owned` and `forbidden`; no personal names, email values, profile values, email send, or network attempt appeared. Fixture-reported exact restoration of diagnostic/schema options and `$wpdb` health both PASS. Post-run DB check PASS, installed source hash unchanged, staged fixture removed and absence verified. Existing PHP PID 145525 and MariaDB PID 144183 remained running; port 8094 remained listening. No product source, account, profile, school, or other database rows were changed by the test run.

This supersedes the earlier status above that said separate Test Engineer runtime was NOT RUN. The execution was conducted by the Test Engineer in this run; the earlier Engineering Lead execution remains separately recorded and was not used as a substitute.
