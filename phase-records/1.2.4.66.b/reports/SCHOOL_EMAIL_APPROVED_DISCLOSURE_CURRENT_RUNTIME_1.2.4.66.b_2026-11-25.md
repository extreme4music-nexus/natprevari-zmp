## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Училишна службена е-пошта: одобрено откривање на адресата — .66.b — 2026-11-25

## Околина
Постојниот изолиран WordPress 127.0.0.1:8103, база zmp66b_isolated, префикс z66a_. Source/runtime hash за includes/class-zmp-school-email-access.php е d8855bc141d5e1f854b751a10420eb90d04af9b6cbcd2f7a3e0da7664be796ce. Активниот синтетички мандат 58/116 беше зачуван.

## Резултат — 7/7 PASS
- Активниот President, привремено со стекот teacher/subscriber и дозволените дополнителни членски улоги, го одобри едно synthetic барање.
- Барачот со свој валиден request-bound nonce ја доби точната .example.test адреса; беше запишан еден disclosed audit-настан. Audit payload не ја содржеше адресата.
- Друг наставник со сопствен валиден nonce не ја доби адресата (not_approved за туѓо барање) и не додаде disclosed настан.
- Барачот со невалиден nonce беше одбиен со forbidden и не додаде настан.
- По промената на службената адреса по одобрувањето, старото одобрение беше invalidated; disclosure врати contact_unavailable и запиша contact_changed, без старата/новата адреса во audit payload.
- Барањето остана approved. Synthetic корисници, ментори, ангажмани, профили, request/events беа отстранети; стекот на улоги на активниот President и diagnostic option се вратија точно; mandate 58/116 остана непроменет; wp db check помина.
- Не се испрати е-пошта и не се отвори надворешна адреса.

Fixture: tests/school-email-approved-disclosure-current-runtime.php; final SHA-256 e0c46d39abf7dfca9ddae4aa74282c5e1302138f5fc71a047009be03aeeabe0a. Два претходни обида беа исклучиво harness-грешки: во првиот недостасуваше stacked role на синтетичкиот President; во вториот nonce беше создаден под President наместо под барачот. Сите тест-редови и привремени улоги беа исчистени/вратени во тие обиди; финалното коригирано извршување помина 7/7.

Локалниот HTTP marker е 412 бајти: 4 api.wordpress.org и 34 localhost записи. Existing MU guard ги одбива intercepted HTTP со WP_Error; не е забележан нов надворешен host.

Ова затвора само позитивно откривање за овластениот барач и некои негативни случаи. Submit_request негативни варијанти и визуелен UI остануваат отворени; променетата адреса/стара согласност е проверена и поништена.
