## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — first approved subject catalog route candidate — 1.2.4.65

## Current candidate status
- Candidate: `tests/phase65-subject-first-catalog-active-president-route.php`
- SHA-256: `8C885E6CBB867909790032070DE036A56A554E8B8D21A3C384BB7C03E5EDBEB0`
- PHP lint: **PASS** (`No syntax errors detected`).
- Independent exact-hash Code Review: **PASS** for current hash 8C885E6CBB867909790032070DE036A56A554E8B8D21A3C384BB7C03E5EDBEB0. Report: `reports/CODE_REVIEW_SUBJECT_FIRST_CATALOG_ACTIVE_PRESIDENT_R5_1.2.4.65_2026-11-05.md`, SHA-256 `DD662948F01C4321933B6E80EF93085FEE9C588DDF7B962F4844E92A3489CA30`. Historical R1–R4 findings/results remain preserved and apply only to earlier hashes.
- Runtime: **NOT RUN**. Per Engineering Lead instruction, do not run until current exact-hash Code Review is PASS and the Lead confirms.
- No source files, local database rows, users, profiles, pages, sessions, or options were changed while preparing this candidate. No temporary candidate copy was staged.

## Exact route scope
Candidate is guarded to DB `zmp65_isolated`, prefix `z65_`, loopback site/admin-post `127.0.0.1:8094`, candidate-only active plugin inventory, compatible proposal schema, exact hashes of six relevant product route/service classes, empty proposal/event/catalog/alias tables, and the single explicitly pinned, pre-validated synthetic President ID 25. The candidate checks an in-memory composite identity fingerprint, exact sole President role, `.invalid` email domain, Synthetic display label, and exactly one active mandate; it never enumerates or selects among multiple President accounts.

One synthetic Teacher session proposes a unique synthetic subject through the actual `admin-post_zmp_submit_subject_proposal` route. The candidate verifies the exact pending row and submission audit. It then authenticates the pre-existing exact synthetic active President and submits `approve_new` through the actual President decision admin-post route. It verifies the approved proposal, exact active canonical catalog row, exact `approved_new` audit actor/payload, and a repeated same decision fails without a second catalog row or audit event. Route response body/location are checked for synthetic login, email and phrase disclosure. WordPress mail is intercepted and counted; all HTTP outside the exact loopback admin-post POST is blocked and counted.

The candidate snapshots exact rows for users/usermeta, posts/postmeta, options, proposal/event/catalog/alias tables, and President mandate/audit tables. It restores only the two diagnostic option rows the candidate permits the routes to change, removes only rows proven to belong to its unique synthetic phrase and actors after validating the entire proposal/event/canonical set, uses one transaction with rollback on any cleanup row failure, destroys only its newly-created session tokens, deletes only the exact synthetic Teacher after safe feature-row cleanup, restores request/cookie/current-user context, and requires the normalized row-snapshot hashes to match the pre-run baseline afterward. A failed or ambiguous route response is never retried; bounded late-commit readback occurs only in cleanup after ambiguous transport.

## Historical candidate and review note
The prior test-only candidates `phase65-subject-register-admin-post.php` and its clone are unchanged and will not be rerun. This candidate contains no MU-plugin trace marker, filesystem trace guard, or marker cleanup. The initial draft and subsequent review-failed candidate hashes were superseded before runtime; their findings were corrected in the current candidate. The prior test-only candidate/source reports remain unchanged.




## Fresh runtime preflight — 2026-11-05 — BLOCKED before staging
Engineering Lead confirmed one run only after the candidate's exact-hash review PASS. Fresh read-only checks found the expected existing processes (PHP 145525, MariaDB 144183) and listening loopback port 8094; DB check PASS; version `1.2.4.65`; sole active plugin `zmp-competitions`; and the proposal, event, catalog and alias tables each had zero rows. Candidate workspace hash matched `8C885E6CBB867909790032070DE036A56A554E8B8D21A3C384BB7C03E5EDBEB0`.

**Stop conditions:** five of the six installed candidate route-source hashes did not match the reviewed workspace hashes (`class-zmp-mentor.php`, President approvals, proposal service, subject service and debug service); only mandate source matched. The read-only synthetic eligibility count returned **2** active synthetic Presidents, under the prior dynamic-selection candidate. That candidate was revised to pin the pre-validated President ID 25 without touching President ID 26 or selecting dynamically. Per Lead instruction, the prior execution stopped before fixture staging. No browser/HTTP route, WP-CLI eval fixture, database write, option/session/user mutation, email/network request, or server lifecycle action occurred. The test file was not staged; no cleanup was needed. Do not retry or weaken the hash/account guards. The revised candidate still requires fresh independent exact-hash review and Lead approval; source-copy/runtime remain unperformed.

## Revised source alignment proposal — pending Lead approval
The prior fresh preflight found the active installed plugin differed from the reviewed workspace in five source dependencies; the President mandate source already matched. Before runtime, the proposed bounded step is to copy only those five reviewed files from the `.65` workspace candidate into the verified active plugin directory on the existing site, without restarting or creating a server: `class-zmp-mentor.php`, `class-zmp-president-subject-approvals.php`, `class-zmp-mentor-subject-proposals.php`, `class-zmp-mentor-subjects.php`, and `class-zmp-debug.php`. Then verify all six candidate dependency hashes (including unchanged mandate class) are equal between workspace and installed plugin. No source copy, stage or runtime has been performed; wait for the Lead to approve after reading the revised candidate/review.



## Single authorized runtime attempt — 2026-11-05 — blocked, cleanup command refused
After Lead approval, the five authorized reviewed source files were copied into the existing active `.65` plugin's `includes` directory and individually hash-verified. A fresh preflight confirmed all six candidate dependencies and the entrypoint exactly matched workspace; DB check PASS; ZMP `.65` was the only active plugin; proposal/event/catalog/alias row counts were all zero; and the pinned synthetic President ID 25 identity, role and active mandate guards passed. Existing PHP/MariaDB processes and port 8094 were present.

The exact reviewed fixture hash `8C885E6CBB867909790032070DE036A56A554E8B8D21A3C384BB7C03E5EDBEB0` was staged under the authorized `phase65-test/tmp/first-catalog-route/` directory; staged hash matched. One `wp eval-file` invocation returned `BLOCKED: proposal_schema_not_ready` before the candidate's row snapshots, temporary Teacher/session creation, route POSTs, or candidate-managed data mutations. No retry was made.

The required exact-path removal plus postflight command was rejected by tool policy before process launch (`CreateProcess Rejected … blocked by policy`). Per no-retry instruction, no alternate cleanup mechanism was attempted. Thus the staged fixture is expected to remain at `/home/tonic/zmp-local-sandbox/phase65-test/tmp/first-catalog-route/phase65-subject-first-catalog-active-president-route.php`; its post-refusal existence was not rechecked. Post-run DB check, row/options/session snapshot verification, and post-run process/hash verification remain **NOT RUN**. No route or email/network attempt occurred; no service was started or stopped. The product/runtime gate remains **BLOCKED / NOT RUN**, and this report does not claim cleanup or acceptance PASS.

### Lead post-refusal observation — staged candidate file remains — 2026-11-05
Lead read-only inspection confirmed the exact staged fixture still exists at `/home/tonic/zmp-local-sandbox/phase65-test/tmp/first-catalog-route/phase65-subject-first-catalog-active-president-route.php` with SHA-256 `8C885E6CBB867909790032070DE036A56A554E8B8D21A3C384BB7C03E5EDBEB0`. A PowerShell `Remove-Item -LiteralPath` for only that exact file and its empty task directory was also rejected before execution with `CreateProcess ... blocked by policy`. No alternate removal tool/method will be attempted; user was asked to remove that exact temp fixture when available. Route behavior remains NOT RUN because execution stopped at `proposal_schema_not_ready`; postflight remains NOT RUN.

### Lead post-run integrity-only check
After the tool refused exact staged-file removal, Lead ran a separate read-only `wp db check` on the same existing `.65` database; it returned **PASS**. This verifies table integrity only; exact row/option/session postflight was not run, and the staged test file remains. No route acceptance or cleanup PASS is implied.
