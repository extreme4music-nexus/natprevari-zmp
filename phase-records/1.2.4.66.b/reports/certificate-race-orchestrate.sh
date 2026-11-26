#!/usr/bin/env bash
set -u
D=/home/tonic/zmp-local-sandbox/phase66b-test/tmp/certificate-race-
SITE=/home/tonic/zmp-local-sandbox/phase66b-test/site
rm -f "${D}pdf-paused" "${D}pdf-release" "${D}worker-result.json" "${D}revoke-result.json"
wp --path="$SITE" --allow-root eval-file "${D}worker.php" >"${D}worker2.log" 2>&1 & W=$!
for i in $(seq 1 200); do [ -f "${D}pdf-paused" ] && break; kill -0 "$W" 2>/dev/null || break; sleep .05; done
if [ ! -f "${D}pdf-paused" ]; then cat "${D}worker2.log"; exit 11; fi
COUNT_DURING=$(wp --path="$SITE" --allow-root db query --skip-column-names "SELECT COUNT(*) FROM z66a_zmp_certificate_issuances WHERE result_type='category' AND result_id=1")
wp --path="$SITE" --allow-root eval-file "${D}revoke.php" >"${D}revoke2.log" 2>&1 & R=$!
sleep .25
COUNT_BEFORE_RELEASE=$(wp --path="$SITE" --allow-root db query --skip-column-names "SELECT COUNT(*) FROM z66a_zmp_certificate_issuances WHERE result_type='category' AND result_id=1")
touch "${D}pdf-release"
wait "$W"; WSTATUS=$?
wait "$R"; RSTATUS=$?
echo "during=$COUNT_DURING before_release=$COUNT_BEFORE_RELEASE worker_status=$WSTATUS revoke_status=$RSTATUS"
cat "${D}worker2.log"
cat "${D}revoke2.log"
