# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — `.66.b` jury step-up negative-path evidence

Date: 2026-11-13 (Europe/Skopje)

## Scope

The Owner-confirmed successful passkey/finalization interaction is preserved as PASS and was not repeated. This report covers a no-proof finalization attempt against the existing synthetic `.66.b` record and documents whether stale/altered-context cases could be safely exercised in this run. No product source was changed.

## Environment and source identity

- Isolated site: `/home/tonic/zmp-local-sandbox/phase66b-test/site`; DB `zmp66b_isolated`, prefix `z66a_`; only active plugin `zmp-competitions-66b` version `1.2.4.66.b`.
- The candidate and runtime SHA-256 matched for the tested code: entrypoint `7AC6FC4C10E1EFBBBB43B0A9E0F90684495374C1F00F34D3F1F551D54504DFDF`; jury handler `972F845A4ABE62606557841FADEFFB8AAA6F6F1F8B73CC07C7CB293343ED8D12`; passkey service `25E64679A2C5CF9D6E41C1E18064EE3515DAFDF240457B317A56B352824E1B6C`; step-up JS `E42B402ACB3A1B9C3B89AFF5CDDDFF048A7B216D68B7EA0310FE44B7619DB0F9`.
- Before the request, the exact scoped synthetic result was one row: season 1/category 2/entry 1/municipal, final `100.00`, `confirmed`, actor 116. The scoped `result_confirmed` audit count was 1.

## No-proof finalization — PASS

- Invoked the actual `ZMP_Category_Jury::handle_post()` path under WP-CLI with synthetic user 116, a valid jury nonce, the exact existing entry scope, and empty step-up challenge/assertion fields. No authenticator prompt or credential value was used.
- Observed redirect status: `step_up_required`.
- After the attempt, the same scoped result remained `100.00`, `confirmed`, actor 116; `result_confirmed` audit count remained 1. No result/audit mutation occurred.
- WP-CLI emitted its expected warning that a redirect was attempted from CLI; its redirect hook captured the expected application status. The application outcome was still the explicit `step_up_required` guard.
- The one-off PHP script was placed under the task sandbox's temporary directory and then removed; removal was confirmed.

## Stale/altered-context/replay cases — NOT RUN

- A follow-up isolated challenge lifecycle invocation was rejected by the command execution policy before it ran. It created no file and made no database changes. Consequently no stale/altered-context/replay result is claimed.
- To exercise those cases without a passkey prompt, the next bounded setup is a synthetic throwaway WP session in this isolated site, a challenge created through `ZMP_Passkeys::step_up_options()` for the synthetic entry, then verify attempts with one changed payload binding and one expired/consumed challenge. Capture only generic rejection and scoped result/audit counts; never print challenge IDs, options, assertions, cookies or tokens. Delete only the exact challenge row created for the case and verify cleanup.

## Data safety and scope

- The Owner-confirmed result row and its audit event were preserved. No credential, passkey challenge value, rationale text, personal data, external website, email or production resource was used.
- This report does not establish administrator/other-role coverage, wrong-region/category, stale assignment, valid cryptographic assertion replay, full route inventory, whole-phase acceptance, package, GitHub synchronization, or Auditor outcome.

## Follow-up after handler correction
- The no-proof runtime case above was executed before the qualification-normalizer source change and is retained at that older hash; the result/audit row remained unchanged in that attempt. It does not count as validation of the current handler hash.
- Current runtime re-execution of no-proof and malformed qualification values: NOT RUN due to command execution refusal before code execution. The existing Owner-confirmed successful passkey flow was not repeated.
