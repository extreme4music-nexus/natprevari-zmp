## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent Code Review — .65 multi-school directory school scope — 2026-11-05

## Initial finding
Initial exact-source review found a P1 privacy scope defect: get_employment_directory($viewer, 0) returned rows from all active schools although the .64 contract expects authorized school choices from list_directory_schools($viewer). The shortcode passed school ID 0. Original result: FAIL. The finding was recorded in PLAN_1.2.4.65.md before implementation.

## Re-review after remediation
Candidate: zmp-competitions-1.2.4.65/includes/class-zmp-mentor-school-profile.php
SHA-256: 8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B

PASS — exact-hash source review. The method now gets authorized active school IDs from list_directory_schools() for the exact current viewer. No allowed schools returns an empty result. An explicit unrelated or inactive ID returns school_not_owned; explicit allowed IDs are rechecked for active state. The all-schools path collects the current viewer's allowed active school keys and constrains the query to those keys, preserving multiple connected schools. Role checks remain ahead of query execution. Per-field *_visible projection is unchanged, and the query/result never contains official email. No remaining source finding in this assigned scope.

## Limits
Runtime verification of the changed directory query, unrelated-school denial, and directory HTML is NOT RUN: the exact previously used .64 shortcode path on the current .65 site returned the site's theme placeholder/home template, not a published directory fixture. No alternate route or automation bypass was attempted. Teacher profile save/reload was verified separately in the current .65 browser. This report is not a Test Engineer verdict or phase acceptance.
