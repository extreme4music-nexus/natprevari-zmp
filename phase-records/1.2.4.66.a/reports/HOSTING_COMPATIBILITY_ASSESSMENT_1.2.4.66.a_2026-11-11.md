# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Hosting compatibility assessment - ZMP 1.2.4.66.a

Date: 2026-11-11 (Europe/Skopje)
Scope: Compare only Owner-provided hosting version/settings with current official WordPress/PHP/WebAuthn requirements and the exact vendored plugin dependency. No production access, production request, SSH, or host mutation.

## Owner-provided values

- WordPress 7.1.4
- PHP 8.3.36; SAPI cgi-fcgi
- MySQL-compatible server 8.0.41
- Owner-reported HTTPS for admin, Home URL, Site URL and FORCE_SSL_ADMIN

These remain Owner-reported production facts; they were not independently fetched from the live host.

## Source-bound requirements and comparison

- Current WordPress requirements recommend PHP 8.3 or greater, MySQL 8.0 or greater (or MariaDB 10.11 or greater), and HTTPS. The reported PHP 8.3.36 / MySQL 8.0.41 / HTTPS values meet those version/protocol requirements. Official requirement: https://wordpress.org/about/requirements/
- WordPress 7.1.4 was an official security release on 2026-10-28. Source: https://wordpress.org/news/2026/10/wordpress-7-1-4/
- PHP 8.3.36 is an official PHP release (2026-10-21). The PHP 8.3 branch is in security-fixes-only support through 2027-12-31; its active-support period ended 2025-12-31. The reported branch is therefore still security-supported on this assessment date. Sources: https://www.php.net/releases/8_3_36.php and https://www.php.net/supported-versions.php
- The vendored WebAuthn library is web-auth/webauthn-lib 5.3.9. Exact local lock metadata SHA-256 10782B6588428A61B300224FD0DEDD9BEBFD3B56CCDCE5FE262D7EE6928767F0 records PHP >=8.2, ext-json and ext-openssl requirements; the reported PHP version meets the PHP version floor. The extension requirements are library metadata, not a claim that the production host was queried.
- W3C WebAuthn Level 3 says user agents expose WebAuthn only in secure contexts and that relying parties must validate the expected origin at registration and assertion verification. Source: https://www.w3.org/TR/webauthn-3/
- The Owner-reported HTTPS settings align with those requirements. The Owner-provided browser self-test dated 2026-11-06 reported the WebAuthn API available in a secure context on the synthetic loopback site; that is local browser evidence only and does not establish a production authenticator ceremony.

## Result

**PASS - version-level compatibility assessment against Owner-provided inputs and official requirements.** No incompatible version floor was found. This is not production runtime validation and does not prove a successful WebAuthn enrollment/login. Those browser acceptance checks remain NOT RUN; the current same-tab enrollment attempt has not yet been observed. No additional uncheckable host checklist or production test was added.

## Scope and provenance

- Current candidate source manifest SHA-256: 5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B.
- Exact local dependency metadata file: vendor/webauthn/composer.lock (SHA above); no dependency was changed.
- Official WordPress 7.1.4 release confirms the version label; no live-site request was made.
- The phase's local functional, migration and rollback reports remain independently scoped; this assessment does not broaden their PASS claims.
