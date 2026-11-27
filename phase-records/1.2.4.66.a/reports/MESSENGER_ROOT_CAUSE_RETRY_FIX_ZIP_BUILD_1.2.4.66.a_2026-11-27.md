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
