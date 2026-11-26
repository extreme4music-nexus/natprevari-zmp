## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Test Engineer Runtime Check — Multi-School Teacher Directory — 1.2.4.65 — 2026-11-05

## Scope
Independent Test Engineer check of the populated synthetic Teacher profile state on the Owner-approved existing `.65` local WordPress site. No product files or WordPress data were changed by this check. This report does not replace the separate UI Reviewer decision or the scoped service PASSes.

## Environment
- URL: `http://127.0.0.1:8094`
- WSL site: `/home/tonic/zmp-local-sandbox/phase65-test/site`
- Database: `zmp65_isolated`, prefix `z65_`
- Active plugin inventory: only `zmp-competitions` (verified in parent checkpoint before this task); no site, sandbox, or server was created.
- Synthetic Teacher user ID: 130. No credentials were printed or recorded.

## Checks and outcomes

| Check | Result | Evidence |
|---|---|---|
| Existing site reachable through WP-CLI | PASS | `wp option get siteurl` returned `http://127.0.0.1:8094`; plugin list contained only `zmp-competitions`. |
| Saved two-school Teacher state | PASS (read-only state) | Read-only query on `z65_zmp_mentor_schools` returned 2 active schools for user 130's mentor record. |
| Saved per-school employment/status state | PASS (read-only state) | Read-only query on `z65_zmp_mentor_school_profiles` returned 2 rows, 2 populated employment terms, 2 populated workloads, and 1 active replacement value. |
| Synthetic private contact field exists | PASS (read-only state) | Aggregate query found one non-empty synthetic official-email value across those rows; value intentionally not selected or recorded. |
| Authenticated rendered Teacher UI / save-reload interaction | BLOCKED | One task-owned Chrome tab creation attempt timed out in the CUA service with `Timed out waiting for Statsig values`; fresh inventory showed no newly created tab. No existing tab was claimed or modified. No credential was entered. |
| Post-test database integrity check | NOT RUN | No DB writes occurred during this check. A separate earlier attempted combined cleanup/check command was rejected before execution; this report does not claim integrity PASS. |

## Read-only query summary
On the existing site, `wp db query` executed these aggregates for user ID 130's mentor record: active school count; profile-row count; counts of populated `employment_term` and `workload`; count of active `replacement`; count of non-empty `official_email`. Result: `2 / 2 / 2 / 2 / 1 / 1`. No names, emails, passwords, or tokens were emitted.

## Disposition
The saved synthetic backend state is present and internally consistent with two school profiles. This bounded Test Engineer run could not independently exercise the authenticated browser flow because the task-owned-tab creation timed out. Therefore it is **not** a Test Engineer UI PASS. The populated-directory acceptance needs the separately recorded independent UI Reviewer result and/or a successful Test Engineer browser interaction; do not upgrade the ledger based on this DB-only evidence. No cleanup, database mutation, process shutdown, or sandbox creation occurred.

## Follow-up retry — authenticated Teacher UI — 2026-11-05
This addendum supersedes the earlier BLOCKED browser-interaction status for the authenticated Teacher portion only. The original CUA tab-creation attempt timed out; after Owner explicitly freed Chrome, a fresh task-owned Chrome tab was successfully opened (tab 2090200226) on the same local site. No existing Owner tab was claimed or changed.

- Route: `http://127.0.0.1:8094/wp-admin/profile.php` as authenticated synthetic `zmp_teacher` user ID 130.
- Read-only profile reload showed both saved school engagements: `Синтетичко училиште 65 А` (Центар) and `Синтетичко училиште 65 Б` (Карпош), with server-derived Скопски region. The employment/workload values and visibility choices remained present after navigation/reload. School B retained its valid synthetic replacement status and required dates. No profile form was submitted or modified during this follow-up.
- Route `http://127.0.0.1:8094/?page_id=37` rendered the authenticated directory after reload. It showed separate entries for both schools with their opted-in employment, workload, retirement and replacement status fields. The school B colleague/date details and both official-email values were absent from the directory text. The privacy explanation states that only chosen fields are shown and official emails are omitted.
- The toolbar identified the session as Synthetic Multi-School Teacher. No data mutation occurred.

**Follow-up outcome:** authenticated Teacher profile persistence/render and populated directory/privacy behavior **PASS** for this scoped browser check. The initial tab-creation attempt remains recorded as BLOCKED at that earlier attempt; the successful follow-up resolves that blocker for the authenticated UI flow. This does not claim a post-run database-integrity PASS, anonymous no-mutation PASS, or phase-wide acceptance. The parent Lead is running a separate anonymous GET with DB-export pre/post hashes; no anonymous request was made in this follow-up. Mobile/keyboard outcomes remain those of the separate UI Reviewer report.

## Follow-up — exact anonymous no-mutation check — 2026-11-05
A bounded independent anonymous browser request was bracketed by normalized full synthetic database export hashes on the existing site. No export file was created; the dump streamed only to `sha256sum`.

- **Route/request:** one direct GET to `http://127.0.0.1:8094/?page_id=37` in a new task-owned Edge tab (tab 913911697), with no authenticated WordPress toolbar/session. No other anonymous page request was made in this check.
- **Rendered result:** the page showed `Најавете се со наставнички профил за да ги видите споделените податоци.` The accessibility tree contained no school name, employment/status field, official email, or private replacement detail. No logged-in toolbar/user menu was present. HTTP status was not separately instrumented; the rendered page content was observed.
- **Normalized full DB export before GET:** `71991bf0e876e1d9d138a71b2388a5dca56a13744306361c5f57528d3ee84a08`
- **Normalized full DB export after GET:** `71991bf0e876e1d9d138a71b2388a5dca56a13744306361c5f57528d3ee84a08`
- **Result:** identical pre/post hashes; **PASS for no database mutation by this exact anonymous GET**. This check did not reset/restore data or touch the Teacher profile.
- **Cleanup:** the task-owned Edge tab was closed. No site/sandbox/server was created or stopped.

This exact pre/post export check addresses the formerly missing database preimage evidence for the one anonymous directory GET. The separate route/UI availability checks remain scoped to their own evidence.
