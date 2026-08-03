# TODO

> Active task list. Completed items removed per documentation sync (2026-08-03).
>
> Sprint series: Sprint 1 ✅, Sprint 2 ✅, Sprint 3.1 ✅, Sprint 3.2 ✅ — Sprint 3.3 & 4 NOT STARTED.
> Baseline: **1944 passed / 4648 assertions / 0 failures**.

---

## Immediate (Pre-UAT)

- [ ] **UAT** — User Acceptance Testing untuk Competition V1, Public Portal, Event Dashboard
- [ ] **UAT bug fixes** — Address any findings from manual testing
- [ ] **UAT sign-off** — Formal approval

---

## Sprint 3.3 (Rekomendasi — NOT STARTED)

- [ ] **Legacy read-path retirement** — Evaluasi flip `ATTENDANCE_LEGACY_WRITE` default ke `false` (`config/features.php`) dan pemutusan legacy fallback read (`LegacyParticipationResolver`, `AttendanceReadService`)
- [ ] **Platform Dashboard event-picker** — Selesaikan 3 placeholder TODO di `platform-dashboard.blade.php` (pilih event / dialog pemilihan event)
- [ ] **Hapus migration NO-OP** — `2026_08_09_000001_make_legacy_mapping_fk_nullable` self-declares obsolete
- [ ] **Cleanup backfill/diagnostic tooling** — `AuditLegacyData`, `AttendanceParity`, `AttendanceDiagnose`, `SuratIzinBackfillService` (hanya dipakai untuk rekonsiliasi legacy)

## Sprint 4 (Rekomendasi — NOT STARTED)

- [ ] **Roadmap V2** — Blueprint Event (fondasi Roadmap V2)
- [ ] **Scoring Engine** — generic scoring
- [ ] **Certificate Engine** — generate sertifikat otomatis

---

## Post-UAT

- [ ] **CategoryDefinition CRUD UI** — Event-scoped category management (`category_definitions`; UI `Competition/Category` mengelola `competition_categories`, tabel berbeda)
- [ ] **Absensi table retirement** — Deferred: table masih ada untuk historical reads

---

## Documentation

- [ ] **Ongoing sync** — Keep documentation synchronized with implementation

---

## Roadmap V2 (Planned — Remaining)

See `docs/VISION_V2.md` for full Roadmap V2 details.

- [ ] Blueprint Event
- [ ] Scoring Engine
- [ ] Certificate Engine
- [ ] Mobile App
- [ ] Public API
