## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Активниот локален runtime е усогласен со .66.b — 2026-11-25

## Наод и дејство
Активниот локален WordPress на 127.0.0.1:8103 го вчитува plugin директориумот wp-content/plugins/zmp-competitions-66b. Hash споредба со тековниот кандидат откри 14 различни PHP-файлови, покрај еден кандидатски source во одделната папка temporary-plugin/. За да ги проверувам тековните .66.b текови, ги синхронизирав 14-те различни source фајлови во активната локална plugin папка. Пред синхронизацијата направив копии во /home/tonic/zmp-local-sandbox/phase66b-test/tmp/runtime-pre-candidate-sync-20261125.

Синхронизирани фајлови:
- zmp-competitions.php
- admin/class-zmp-president-mandate-admin.php
- includes/class-zmp-president-mandate.php
- includes/class-zmp-mentor-school-profile.php
- includes/class-zmp-mentor.php
- includes/class-zmp-school-email-access.php
- includes/class-zmp-support.php
- includes/class-zmp-school-email-access-ui.php
- includes/class-zmp-president-governance.php
- includes/class-zmp-teacher-register.php
- includes/class-zmp-president-subject-approvals.php
- includes/class-zmp-mentor-subjects.php
- includes/class-zmp-mentor-subject-proposals.php
- includes/class-zmp-president-facsimile-certificates.php

## Проверка по синхронизацијата
Hash споредбата на 69 PHP/CSS/JS изворни фајлови во plugin директориумот покажа 69/69 совпаѓање со кандидатот; останати разлики: 0. Кандидатската папка temporary-plugin/zmp-entry-youtube-upload содржи еден фајл што не е во активниот plugin директориум. Овој извештај не заклучува дали тој одделен кандидатски додаток треба да биде посебен дел од пакетот; неговата намена/пакетирање останува за проверка пред release.

Не е стартуван нов сервер, не е активиран друг plugin, не е менувана база при самата синхронизација и production не е допрен. Локалните резервни копии остануваат во tmp додека продолжуваат фазните проверки.
