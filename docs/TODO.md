# TODO

> Active task list. Completed items removed per documentation sync (2026-08-03) and Sprint 3.3 (2026-08-03).
>
> Sprint series: Sprint 1 ✅, Sprint 2 ✅, Sprint 3.1 ✅, Sprint 3.2 ✅, Sprint 3.3 ✅ — Sprint 4 NOT STARTED.
> Baseline: **1944+ passed / 4648+ assertions / 0 failures**.

---

## Immediate (Pre-UAT)

- [ ] **UAT** — User Acceptance Testing untuk Competition V1, Public Portal, Event Dashboard (checklist: `docs/UAT_CHECKLIST.md`)
- [ ] **UAT bug fixes** — Address any findings from manual testing
- [ ] **UAT sign-off** — Formal approval

---

## Sprint 4 (Rekomendasi — NOT STARTED)

- [ ] **Legacy retirement** — Eksekusi Phase 1–4 di `docs/LEGACY_RETIREMENT_PLAN.md` (fallback write izin/scan, dual-write peserta, read-path legacy, physical retirement)
- [ ] **Import Framework (IF series)** — IF-01 & IF-02 COMPLETE (audit+engine); lanjut IF-03+; lihat `docs/import-framework.md` & `docs/import-audit.md`
- [ ] **Roadmap V2** — Blueprint Event (fondasi Roadmap V2)
- [ ] **Scoring Engine** — generic scoring
- [ ] **Certificate Engine** — generate sertifikat otomatis

---

## Import Framework (IF series)

> IF-01 (audit & desain) ✅ COMPLETE. **IF-02 (engine infrastructure) ✅ COMPLETE 2026-08-06** — infra siap, belum dipakai modul.

- [x] **IF-02** — Bangun Import Engine: stage pipeline nyata, runner dispatch, DTO konsolidasi, exceptions, DI registry/coordinator, version guard, logging hook (+37 unit test)
- [ ] **IF-03** — Bangun Import Wizard reusable (5 langkah: Upload → Preview → Validation → Import → Result)
- [ ] **IF-03** — Bangun Import Wizard reusable (5 langkah: Upload → Preview → Validation → Import → Result)
- [ ] **IF-04** — Migrasi Pengajian Import ke framework (UX identik; parity test golden)
- [ ] **IF-05** — Migrasi Desa Import
- [ ] **IF-06** — Migrasi Kelompok Import
- [ ] **IF-07** — Migrasi Regu Import (pertahankan normalizer gender)
- [ ] **IF-08** — Migrasi Peserta Import
- [ ] **IF-09** — Template Engine standar (DATA/PETUNJUK/REFERENSI) + retire `public/templates/*`
- [ ] **IF-10** — Import Activity Log + gate/ability audit (`manage-import`)
- [ ] **IF-11** — Import Person
- [ ] **IF-12** — Import Competition (cabang & kelas)
- [ ] **IF-13** — Import Venue
- [ ] **IF-14** — Import Schedule
- [ ] **IF-15** — Import Committee (Event Role / Committee Assignment)
- [ ] **IF-16** — Import Activity / Rundown
- [ ] **IF-17** — Import Attendance
- [ ] **IF-18** — Import Access Grant
- [ ] **IF-19** — Regression penuh + parity audit seluruh import vs golden standard

---

## Post-UAT

- [ ] **CategoryDefinition CRUD UI** — Event-scoped category management (`category_definitions`; UI `Competition/Category` mengelola `competition_categories`, tabel berbeda)
- [ ] **Absensi table retirement** — Deferred: table masih ada untuk historical reads
- [ ] **Hapus migration NO-OP** — `2026_08_09_000001_make_legacy_mapping_fk_nullable` self-declares obsolete
- [ ] **Cleanup backfill/diagnostic tooling** — `AuditLegacyData`, `AttendanceParity`, `AttendanceDiagnose`, `SuratIzinBackfillService` (hanya dipakai untuk rekonsiliasi legacy)

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
