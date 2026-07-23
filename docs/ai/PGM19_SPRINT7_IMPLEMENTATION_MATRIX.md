# Sprint 7 Implementation Matrix

Generated from source code verification. Matches Sprint 6 audit findings.

## A. Legacy-Only Reads (6 → 0 target)

| # | File | Line | Current | Target |
|---|------|------|---------|--------|
| L1 | GantiPeserta.php | 55 | `$peserta->regu?->regu` | `$participation->regu?->regu ?? $peserta->regu?->regu` |
| L2 | Ulang.php render | 145 | `peserta::with('regu')` search | `Participation::with(...)` search scoped to event |
| L3 | ulang.blade.php | 35 | `$peserta->regu->regu` | `$peserta->regu->regu ?? '-'` (mapped from Participation) |
| L4 | TambahPeserta.php | 172,184 | `legacyPesertaMapping.peserta.regu` | `participations.regu` (latest) |
| L5 | TambahPeserta.php | 191,200 | `legacyPesertaMapping.peserta.regu` | `participations.regu` (latest) |
| L6 | tambah-peserta.blade.php | 102,121 | `$result['regu']` | (mapped from controller) |

## B. Canonical-First Fallback Reads (10 → 10 hardened)

| # | File | Current | Target | Type |
|---|------|---------|--------|------|
| F1 | Database.php:61 | `$participation->regu ?? $legacyPeserta?->regu` | `$participation->regu` | Hardened |
| F2 | dashboard.blade.php:156 | `$participation->regu->regu ?? $legacy->regu->regu` | `$participation->regu->regu` | Hardened |
| F3 | RekapAbsensi.php:64 | `$participation->regu ?? $lp?->regu` | `$participation->regu` | Hardened |
| F4 | EditPeserta.php:74 | `$participation->regu_id ?? $legacyPeserta?->regu_id` | `$participation->regu_id` | Hardened |
| F5 | Ulang.php:91 | `$participation->regu_id ?? $legacyPeserta?->regu_id` | `$participation->regu_id` | Hardened |
| F6 | PesertaExport display | `$participation->regu?->regu ?? $peserta?->regu?->regu` | `$participation->regu?->regu` | Hardened |
| F7 | rekap-absensi.blade:75 | `$entry->participation->regu->regu ?? $lp->regu->regu` | `$entry->participation->regu->regu` | Hardened |
| F8 | rekap-absensi.blade:111 | same pattern | same | Hardened |
| F9 | rekap-absensi.blade:151 | `$peserta->regu->regu ?? '-'` (mapped) | No change needed | Skip |
| F10 | dashboard.blade:116 | `$entry->participation->regu->regu ?? $lp->regu->regu` | `$entry->participation->regu->regu` | Hardened |

## C. Dual-Write Paths (9 → 5 kept, 4 stopped)

After Phase 2 migration of legacy reads, the following dual-write locations are safe to stop:

| # | File | Line | Stop? | Reason |
|---|------|------|-------|--------|
| W1 | RegistrationService.php | 73 | YES | Canonical Participation.regu_id written at line 70 |
| W2 | RegistrationService.php | 187 | YES | Canonical Participation.regu_id written at line 193 |
| W3 | EditPeserta.php | 117 | YES | Canonical Participation.regu_id written at line 102 |
| W4 | Ulang.php | 126 | YES | Canonical Participation.regu_id written at line 113 |
| W5 | TambahPeserta.php | 274 | YES | Canonical Participation.regu_id written at line 270 |
| W6 | RegistrationService.php | 96 | KEEP | New peserta creation (line 96 is peserta write; line 114 is participation write). Both needed for new entity. |
| W7 | Import/PesertaImport.php | 50 | KEEP | Via RegistrationService.createParticipant (new entity creation) |
| W8 | SelfRegister.php | 138 | KEEP | Via RegistrationService.createParticipant (new entity creation) |
| W9 | CaiParticipantReplacement.php | 194,251 | MODIFY | Read-for-write from $peserta->regu_id → use $oldParticipation->regu_id ?? |

## D. PlacementService (2 callers → all event-scoped)

| # | File | Current | Target |
|---|------|---------|--------|
| P1 | TambahPeserta.php:54 | `autoPlacement($gender, $eventId)` ✅ | No change |
| P2 | SelfRegister.php:54 | `autoPlacement($gender, $eventId)` ✅ | No change |
| P3 | PesertaImport.php:42 | `autoPlacement($gender)` ❌ no eventId | `autoPlacement($gender, $eventId)` |

## E. Test Fixture Migration (100+ → targeted)

Target: Only update fixtures in Sprint 7 test file and regression tests.

## Implementation Order

1. Phase 2: Cutover GantiPeserta → CaiParticipantReplacement → Ulang → TambahPeserta
2. Phase 3: Harden fallbacks (simplify to canonical-only)
3. Phase 4: Stop dual-write in 5 locations
4. Phase 5: Fix PesertaImport PlacementService call
5. Phase 6: Add Sprint 7 tests
6. Phase 7: Zero-dependency audit
