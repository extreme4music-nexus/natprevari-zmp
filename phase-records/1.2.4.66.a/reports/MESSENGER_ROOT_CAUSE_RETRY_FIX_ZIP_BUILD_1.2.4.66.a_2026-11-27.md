# Messenger retry root-cause fix ZIP build — 1.2.4.66.a — 2026-11-27

- ZIP: zmp-competitions-1.2.4.66.a-2026-11-27-messenger-root-cause-04.zip
- SHA-256: ab8acc203ba040f088c9ef75b70a066fb9779608af69881aec8db1dd96b34127
- Size: 7,043,241 bytes.
- Runtime source/archive count: 2,244/2,244; exact path parity PASS; archive integrity PASS.
- PHP syntax: 4/4 modified runtime files PASS.
- Existing local :8111 clone: future retry cooldown blocked and expired retry allowed (2/2 PASS); safe blocker event retained with school_profile_install (1/1 PASS). Temporary options and diagnostic event were restored.
- Owner live diagnostic: Messenger compatibility YES; shared marker 1.2.4.53.2 stale; retry pending. The precise failed global prerequisite is not known yet.
- Fixes: stale-marker requests now honor schema_retry_allowed(); global migration failures log the first exact blocker in schema_prerequisite_component.
- No new server was started. No production data was changed. Live cleanup remains unresolved until a fresh blocker event is collected and the marker successfully advances.

- GitHub readback: source and phase documentation synchronized to ersion/1.2.4.66.a, commit 2d102f95b3526ca24977d5f99d776fe2351e384c. The four changed PHP paths and report/manifest blobs were read back successfully. The binary root-cause ZIP is not present on the GitHub branch; use the local release-library ZIP linked above.
