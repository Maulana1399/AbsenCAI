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

## Competition Foundation (2026-08) — done / remaining

Done:
- [x] Format lomba (5 format) + status lomba di `competition_classes`
- [x] Teams event-scoped (`competition_teams` + `competition_team_members`)
- [x] Auto team formation (`CompetitionTeamFormationService`)
- [x] Team member management (`CompetitionTeamService`) + UI `competition.teams`

Remaining (documented in `docs/audit/COMPETITION-IMPLEMENTATION-AUDIT.md`):
- [ ] Match engine per-format (schedule entries menunjuk team untuk format team)
- [ ] Result type eksplisit (win/loss · score · time · ranking) di UI hasil
- [ ] `competition_team_id` di `competition_schedule_entries` untuk scheduling team
- [ ] Integrasi bracket team (advance winner team)
- [ ] Import Competition (IF-11)

---

## Sprint 4 (Rekomendasi — NOT STARTED)

- [ ] **Legacy retirement** — Eksekusi Phase 1–4 di `docs/LEGACY_RETIREMENT_PLAN.md` (fallback write izin/scan, dual-write peserta, read-path legacy, physical retirement)
- [ ] **Import Framework (IF series)** — IF-01 & IF-02 COMPLETE (audit+engine); lanjut IF-03+; lihat `docs/import-framework.md` & `docs/import-audit.md`
- [ ] **Roadmap V2** — Blueprint Event (fondasi Roadmap V2)
- [ ] **Scoring Engine** — generic scoring
- [ ] **Certificate Engine** — generate sertifikat otomatis

---

## Import Framework (IF series)

> IF-01 ✅, IF-02 ✅ (engine), **IF-03 ✅ (Desa)**, **IF-04 ✅ (Kelompok)**, **IF-05 ✅ (Regu)**, **IF-06 ✅ (Person)**, **IF-07 ✅ (Participation)**, **IF-08 ✅ (Pengajian)**, **IF-09 ✅ (Peserta)**, **IF-10 ✅ (Cleanup — STABLE v1.0)**.

- [x] **IF-02** — Bangun Import Engine: stage pipeline nyata, runner dispatch, DTO konsolidasi, exceptions, DI registry/coordinator, version guard, logging hook (+37 unit test)
- [x] **IF-03** — Migrasi Desa: adapter reusable, definition penuh, template generator, wizard + komponen reusable (+36 test)
- [x] **IF-04** — Migrasi Kelompok: metadata definition (`ImportDefinitionMetadata`), parameter engine (desa_id), wizard 5-langkah otomatis, template REFERENSI desa (+25 test)
- [x] **IF-05** — Migrasi Regu: collaborator nyata (normalisasi gender, duplicate unique name), `ImportWizardBase` reusable, template REFERENSI gender (+19 test)
- [x] **IF-06** — Migrasi Person (Design C): identity global, reuses `PersonDuplicateDetectionService`, normalisasi gender/date/spasi, template REFERENSI gender+desa (+21 test)
- [x] **IF-07** — Migrasi Participation (Design C): parameter event_id, lookup Person dulu, duplicate per event, commit via `ManualParticipantRegistrationService`, template REFERENSI event (+17 test)
- [x] **IF-08** — Migrasi Pengajian (behavior-preserving): wizard extends `ImportWizardBase`, service → orchestrator, `ImportCommit.metrics`, parser/validator/committer port persis, parity test (+18 test)
- [x] **IF-09** — Migrasi Peserta (final legacy): real collaborators, committer port `model()` via `RegistrationService`, hapus `Excel::import`/`PesertaImport`/manual coordinator/`executeImport`/`ImportPeserta` vestigial (+10 test)
- [x] **IF-10** — Final Lock & Cleanup: hapus orphan (`ImportResult`/`ImportMetrics`/`TemplateVersion`, dir kosong, helper test mati); docs sinkron — **framework v1.0 STABLE**
- [ ] **IF-11** — Template Engine lanjutan (REFERENSI dropdown) + retire `public/templates/*`
- [ ] **IF-10** — Import Activity Log + gate/ability audit (`manage-import`)
- [ ] **IF-11** — Import Competition (cabang & kelas)
- [ ] **IF-12** — Import Venue
- [ ] **IF-13** — Import Schedule
- [ ] **IF-14** — Import Committee (Event Role / Committee Assignment)
- [ ] **IF-15** — Import Activity / Rundown
- [ ] **IF-16** — Import Attendance
- [ ] **IF-17** — Import Access Grant
- [ ] **IF-18** — Regression penuh + parity audit seluruh import vs golden standard

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
