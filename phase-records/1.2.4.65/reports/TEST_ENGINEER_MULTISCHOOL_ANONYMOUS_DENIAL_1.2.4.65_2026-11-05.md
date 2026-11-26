# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — anonymous multi-school access denial — 1.2.4.65

## Bound candidate and preflight
- Fixture: `tests/phase65-multischool-anonymous-denial.php`
- Fixture SHA-256: `F7412092F0BDC869F2D8E1964E321827BF5593A4CC040CD62A25D14B792126D3` (workspace and staged copy matched)
- Product source hashes matched both workspace and installed candidate:
  - Profile service: `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`
  - Directory renderer: `255A9673C082F3472DCDEF676DC80E8F63C70AF68B14CD2AC56919F936FD7042`
- Local site/database: `127.0.0.1:8094` / `zmp65_isolated`, prefix `z65_`; WP versioned plugin `1.2.4.65` was the only active plugin. Pre-run `wp db check` PASS.
- Synthetic user identity values were not emitted. Fixture preconditions verify database and installed source hashes; the script does not create users or profile/page data.

## Test Engineer execution
**Product behavior: PASS, 6/6 assertions.** One execution through `wp eval-file` verified: anonymous WordPress context, forbidden anonymous directory service, forbidden anonymous profile service, login notice from the actual shortcode renderer, no school/employment fields in anonymous shortcode HTML, and restoration of the original WordPress user context. The script emitted no profile values, email values, or send attempts. Only two sanitized expected `forbidden` diagnostics appeared.

## State and cleanup
- Post-run `wp db check`: PASS.
- Installed profile-service and directory-renderer hashes remained unchanged.
- Staged fixture removed; absence verified.
- Existing PHP PID 145525 and MariaDB PID 144183 remained running; port 8094 remained listening.
- No explicit user, profile, page or option values were printed or intentionally edited by the fixture.

**Data-mutation verification: BLOCKED / inconclusive.** A full database-export checksum taken immediately before execution was `ab10d0c0762e85b129d05e5baaf09af80b3f87398dfe6a4b6ab0d6e0b0b81981`; the post-run checksum was `a256910b3c3387d7183a3006f9344151236b50bdaefd003887ce6eccb1c6bfc7`. They differ, so this run cannot establish that no database option (including diagnostic state) changed. Database integrity still passes. There was no pre-run database snapshot to identify or safely restore any changed value; no speculative cleanup was attempted. The product behavior assertions passed, but the no-mutation boundary remains unverified and should be reconciled from the synthetic database's existing diagnostic state before the multi-school criterion is closed.

No phase acceptance, ZIP, or Auditor conclusion is made by this scoped test report.
