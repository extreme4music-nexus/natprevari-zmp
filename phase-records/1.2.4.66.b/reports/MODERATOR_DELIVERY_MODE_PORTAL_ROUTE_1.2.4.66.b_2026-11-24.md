## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Moderator delivery-mode portal and route — 1.2.4.66.b — 2026-11-24

## Local runtime result
**PASS 11/11** on the synthetic `.66.b` WordPress site `http://127.0.0.1:8098` (database `zmp65_subject_test`, prefix `z66s_`). The logged-in test user retained Teacher, Subscriber, Moderator, Commission, Testator and Organizer roles.

The rendered competition portal exposed the **Модератори** tab and its **Начин на одржување** workspace tab. The selected year, category, regional level and region were present in the form. For regional music categories, the rendered choices were Physical and Online; Hybrid was absent.

Two actual authenticated loopback POSTs saved different settings for different scopes: School Choir / Skopje / Online and School Orchestra / Polog / Physical. Readback kept both records isolated by season, category, phase and region and showed the selected setting in the panel. A direct tampered Hybrid request was rejected with the expected `regional_music_modes_only` redirect and did not change the saved Online value. Both accepted decisions created moderator audit rows.

A temporary network guard blocked non-loopback HTTP. No unexpected outbound marker was recorded. The local test guard, synthetic user/session, year, mappings, stages, regional-event and audit rows, and shortcode page were removed after the run. `wp db check` passed for the shared local synthetic database.

## Source/runtime evidence
The five source/runtime pairs used by this check matched exactly: `class-zmp-moderator-operations.php` (`2696213036F3B9741A6CF8A6B37680706AB0B7374E0700C0FD591CF1C5A53BEE`), `class-zmp-shortcodes.php` (`688602F70FB2A4B9F9E88D14DA1F347B9F7F1CEBB04F2991184CEB5AC18121DD`), `class-zmp-competition-structure.php` (`CBF6B4D504FC9149B9E38B13C8BC02D7C51E3CB0D6FCF53D3C665E96B6E6C33F`), `class-zmp-category-registrations.php` (`7B4774416BE708B59A78A7256E1758AF18A07A6F98C736C244D8C6CB6944D699`), and `class-zmp-regions.php` (`3557AD3BFEC2F701627CB546B16650F3F08C3924AFFBA54B48A3A43450830222`). PHP lint passed for all five.

Fixture: `tests/moderator-delivery-mode-ui-route.php`; SHA-256 `29092552D459C33BC209680110F63FE2DB0F529847F6E44E601BA19A90A9ECFE`.

## Visual-review boundary
The Windows browser capture was **NOT RUN**: Computer Use stopped before interacting because it could not confidently identify Chrome's current URL. The PASS above is rendered-shortcode plus authenticated local HTTP/runtime evidence, not a screenshot or independent visual/browser verdict. No production URL was opened or changed.
