# LAPORAN AUDIT DOKUMENTASI PROJECT

**Tanggal:** 2026-07-24
**Project:** KJA Event Manager
**Audit scope:** Seluruh file Markdown (.md) di repository

---

## 1. Ringkasan Kondisi Aktual

Berdasarkan audit source code aktual:

**Canonical architecture:**
```
Person → Participation → EventAttendance
```

**Sudah diretire:**
- `people.nip` — kolom dihapus (PGM.20 Phase 4B)
- `pesertas.nip` — kolom dihapus (PGM.20 Phase 4B)
- `pesertas.regu_id` — kolom dihapus (PGM.19 Sprint 8B)
- NIP generation — `legacyNextNip()` dihapus
- NIP fallback di attendance — dihapus
- NIP di Person/Participation — tidak ada
- Regu dual-write — dihentikan
- Absensi dual-write — dihentikan

**Masih aktif sebagai canonical:**
- `participant_number` — identitas human-readable
- `attendance_code` — identitas QR/attendance
- `Participation.regu_id` — regu event-scoped (CAI)
- `EventAttendance` — canonical attendance

**Framework:** Laravel 12 + Livewire 3 + Flux UI
**Database:** SQLite (dev/test), MariaDB (target production)
**PHP:** 8.3.32 (via /tmp/php wrapper)

---

## 2. Inventory Dokumentasi

Total file Markdown project: **59 file**

### Klasifikasi:

| Klasifikasi | Jumlah | Keterangan |
|:-----------|:------:|------------|
| **ACTIVE** | 15 | Dokumentasi utama yang masih relevan |
| **HISTORICAL** | 35 | PGM/sprint report, audit report — berguna sebagai history |
| **UPDATED** | 7 | Diperbarui dalam audit ini |
| **DUPLICATE** | 2 | `README.md` (root) dan `docs/README.md` |
| **OBSOLETE** | 0 | Tidak ada yang dihapus |

---

## 3. Dokumentasi yang Diubah

| File | Masalah | Perubahan |
|------|---------|-----------|
| `README.md` | Baseline test outdated, PGM.19-20 belum tercantum | Update test baseline ke 1574/3745/0, tambah PGM.19-20 |
| `docs/ARCHITECTURE.md` | Belum mencantumkan canonical architecture | Tambah canonical data flow + retired components |
| `docs/DATABASE.md` | `people.nip` masih tercantum sebagai kolom aktif, NIP masih disebut sebagai fallback | Hapus `nip` dari schema people, update identity rules, NIP retired |
| `docs/INDEX.md` | Status document list outdated untuk DATABASE & ARCHITECTURE | Update status menjadi "Updated" |
| `docs/FEATURE.md` | "Search by name or NIP" masih tercantum | Hapus "or NIP" |
| `docs/HANDOFF.md` | Korupsi file (0 bytes) akibat sed error | Buat ulang dengan informasi current architecture + NIP retirement |
| `docs/ai/CURRENT_STATE.md` | PGM.20 belum tercantum di status | Tambah PGM.20, update test baseline |

---

## 4. Dokumentasi Historical

Seluruh file PGM report dan audit report di `docs/ai/` dipertahankan sebagai historical record:

- `PGM19_SPRINT8B_PHYSICAL_REGU_RETIREMENT_REPORT.md`
- `PGM20_LEGACY_NIP_RETIREMENT_AUDIT.md`
- `PGM20_NIP_RUNTIME_CUTOVER_REPORT.md`
- `PGM20_PHASE4A_PHYSICAL_RETIREMENT_AUDIT.md`
- Dan seluruh PGM report lainnya di `docs/ai/`

File ini tidak diubah dan tetap valid sebagai bukti perjalanan implementasi.

---

## 5. Dokumentasi Tidak Relevan / Obsolete

Tidak ada file yang dihapus. Semua file historical dipertahankan.

Beberapa file memiliki informasi yang sebagian outdated tetapi tidak diubah karena:
- `docs/SPRINT3_MULTI_EVENT_AUDIT.md` — masih relevan sebagai arsitektur multi event
- `docs/DATABASE_V2.md` — masih relevan sebagai rencana transisi database
- `docs/PENGAJIAN_MVP_OPERATIONAL.md` — masih relevan untuk pengajian module

---

## 6. Konflik Dokumentasi vs Actual Implementation

| Dokumen | Isi | Actual | Status |
|---------|-----|--------|--------|
| DATABASE.md:37 | `people.nip` sebagai kolom | Kolom sudah dihapus | ✅ Fixed |
| DATABASE.md:47-53 | NIP sebagai legacy identifier dengan fallback | NIP sudah diretire total | ✅ Fixed |
| FEATURE.md:154 | Search by name or NIP | NIP search sudah dihapus | ✅ Fixed |
| HANDOFF.md:122-123 | Person memiliki nip, peserta auto-generate nip | NIP sudah diretire | ✅ Fixed |
| ARCHITECTURE.md | Belum ada canonical data flow | Ada canonical architecture | ✅ Fixed |

---

## 7. Actual Architecture

```
Person (master identity)
├── nama, jenis_kelamin, desa_id, kelompok_id, tanggal_lahir
│
└── Participation (event-scoped membership)
    ├── person_id, event_id, participant_number, attendance_code, regu_id
    │
    ├── EventAttendance (canonical attendance)
    │   └── participation_id, sesi_absensi_id, status, method
    │
    └── ActivityRegistration (optional)
```

**Retired components:**
- `pesertas.regu_id` — Sprint 8B
- `people.nip` — PGM.20
- `pesertas.nip` — PGM.20
- NIP canonical identity — PGM.20

---

## 8. Status Roadmap Aktual

| Area | Status |
|------|--------|
| PGM.12–PGM.17 (Pengajian Desa MVP) | ✅ COMPLETE |
| PGM.18 (Physical Mapping Cleanup) | ✅ COMPLETE |
| PGM.19 (Physical Regu Retirement) | ✅ COMPLETE |
| PGM.20 (Legacy NIP Retirement) | ✅ COMPLETE |
| S01–S04 Foundation | ✅ COMPLETE |
| S3.0–S3.10 Multi Event | ✅ COMPLETE |
| RBAC S1–S7 | ✅ COMPLETE |
| Person CRUD | ✅ COMPLETE |
| User Management | ✅ COMPLETE |
| **Venue CRUD** | 🔜 **Pending** |
| **CategoryDefinition CRUD** | 🔜 **Pending** |
| Competition Module | 🔮 Future |
| Commercial Platform | 🔮 Future |

---

## 9. Technical Debt / Legacy Remaining

| Komponen | Status | Runtime Impact |
|----------|--------|----------------|
| `peserta` table | Legacy compatibility | RegistrationService masih membuat peserta record |
| `Absensi` model + table | Historical only | Tidak ditulisi, hanya historical read |
| `IzinAbsensi` | Legacy + canonical | Masih ditulisi untuk legacy path |
| `legacy_nip` snapshot | Historical | Write-only, tidak dibaca runtime |
| `absensis.nip` column | NOT NULL | Masih ada, perlu dibuat nullable jika di-retire |

---

## 10. Next Recommended Work

**Prioritas 1: Venue CRUD**
- Model `Venue` dan migration sudah ada (S3.9C)
- Perlu Livewire component: Index, Create, Edit, Delete
- Event-scoped, perlu ActiveEventContext
- Tidak ada dependency ke PGM lain

**Prioritas 2: CategoryDefinition CRUD**
- Model `CategoryDefinition` dan migration sudah ada (S3.9B)
- Pola sama dengan Venue CRUD

**Prioritas 3 (Deferred):**
- Absensi table retirement — setelah tidak ada dependency read
- legacy_nip snapshot columns — bisa dihapus kapan saja

---

## 11. Verification

| Check | Result |
|-------|--------|
| Full test suite | **1574 passed, 3745 assertions, 0 failures** |
| Design C diagnostic | **problem_total = 0** |
| migrate:fresh | PASS |

---

## 12. Git Diff Summary

### File diubah:

| File | Perubahan |
|------|-----------|
| `README.md` | Update test baseline, tambah PGM.19-20 |
| `docs/ARCHITECTURE.md` | Tambah canonical architecture + retired components |
| `docs/DATABASE.md` | Hapus `people.nip`, update identity rules |
| `docs/INDEX.md` | Update document status |
| `docs/FEATURE.md` | Hapus "or NIP" dari search description |
| `docs/HANDOFF.md` | **Buat ulang** (korupsi file) |
| `docs/ai/CURRENT_STATE.md` | Update PGM.20 status, test baseline |

### File tidak diubah:

Seluruh PGM report, audit report, sprint report di `docs/ai/` dipertahankan sebagai historical record.

---

**Audit selesai.** Dokumentasi utama sudah sinkron dengan actual implementation. Siap untuk review sebelum lanjut ke implementasi berikutnya.
