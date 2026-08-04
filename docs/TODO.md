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
- [ ] **Roadmap V2** — Blueprint Event (fondasi Roadmap V2)
- [ ] **Scoring Engine** — generic scoring
- [ ] **Certificate Engine** — generate sertifikat otomatis

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
