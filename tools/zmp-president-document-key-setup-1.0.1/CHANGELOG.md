ZMP key setup helper 1.0.1 - correction record

Date: 2026-11-26

## Trigger

The owner installed helper 1.0.0 and received the fixed safe-stop message that the expected wp-settings.php line was not found. In 1.0.0 the matcher required exactly one single-line require_once ABSPATH . 'wp-settings.php'; statement. No backup or wp-config.php write occurs before this check.

## Correction

Version 1.0.1 recognizes common single-line require_once, require, include_once, and include statements that reference wp-settings.php, including parenthesized forms and trailing comments. It still requires exactly one match and fails closed otherwise. It continues to install only ZMP_PRESIDENT_DOCUMENT_KEY; it is not a general wp-config.php editor.

## Verification

- PHP syntax lint: PASS.
- Matcher matrix: 6/6 PASS for canonical, parenthesized, require, include_once, inline-comment, and duplicate-statement cases. Duplicate matches correctly return a safe error.
- ZIP integrity: PASS.
- No production wp-config.php was read or changed in this correction.

## Installation

Deactivate and delete helper 1.0.0, then install 1.0.1. The 1.0.0 failure returned before the backup/write stages, so it did not change wp-config.php. Run the tool once. If the revised matcher still cannot identify the site's bootstrap statement, stop and have hosting support add the constant.
