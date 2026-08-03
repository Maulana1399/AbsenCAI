# ROLE MATRIX

> Complete role & permission audit based on actual code implementation.

> ⚠️ **EVOLUSI — PERMISSION ENGINE (Design C).** Sejak Sprint 2, ability event-scoped di-resolve dari `User → Person → EventCommitteeAssignment → EventRole.permissions`, bukan dari `users.role`. `users.role` hanya menentukan hak platform (SuperAdmin/Admin). Matriks di bawah ini adalah **default EventRole template + hak platform**, bukan lagi tabel lookup runtime untuk role event. Lihat `docs/CHANGELOG.md` (Unreleased → Permission Engine).

---

## 1. Role Definitions

| Role | Code | Description | Access Level |
|------|------|-------------|-------------|
| Super Admin | `super_admin` | Full system access | Global (platform) |
| Admin | `admin` | Manage all events | Global (platform) |
| Ketua Event | `ketua_event` | Event-scoped (via EventRole assignment) | Event-scoped |
| Sekretariat | `sekretariat` | Admin + secretariat duties | Event-scoped (via EventRole) |
| PJ Divisi | `pj_divisi` | Attendance monitoring only | Event-scoped (via EventRole) |
| Operator Registrasi | `operator_registrasi` | Participant registration | Event-scoped (via EventRole) |
| Operator Scan | `operator_scan` | QR scan + attendance | Event-scoped (via EventRole) |
| Juri | `juri` | Scoring / submit result (V2) | Event-scoped (via EventRole) |
| Viewer | `viewer` | Read-only reports + dashboard | Event-scoped (via EventRole) |

> Role non-platform (ketua_event, sekretariat, pj_divisi, operator_registrasi, operator_scan, juri, viewer) **tidak otomatis** mendapat ability dari `users.role`. Mereka harus memiliki `EventCommitteeAssignment` + `EventRole` aktif di event tsb.

---

## 2. Permission Matrix

| Ability | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Operator Registrasi | Operator Scan | Juri | Viewer |
|---------|:-----------:|:-----:|:-----:|:-----------:|:---------:|:------------------:|:-------------:|:----:|:------:|
| `view-dashboard` | ✅ | ✅ | ✅* | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| `view-master-data` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-master-data` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-events` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-registration` | ✅ | ✅ | ✅* | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| `manage-participants` | ✅ | ✅ | ✅* | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-attendance` | ✅ | ✅ | ✅* | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| `manage-sessions` | ✅ | ✅ | ✅* | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-qr-labels` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-secretariat` | ✅ | ✅ | ✅* | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-import` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `view-reports` | ✅ | ✅ | ✅* | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| `manage-pengajian` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `view-activity-log` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-users` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-matches` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-officials` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `submit-result` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |

*Ketua Event & role event lainnya: akses event-scoped hanya saat memiliki `EventCommitteeAssignment` + `EventRole` aktif dengan ability tersebut (Permission Engine).
Catatan: `view-master-data` / `manage-master-data` — hanya SuperAdmin sejak Permission Engine.

---

## 3. Menu Visibility by Role

### CAI Navigation

| Menu Item | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Operator Registrasi | Operator Scan | Juri | Viewer |
|-----------|:-----------:|:-----:|:-----:|:-----------:|:---------:|:------------------:|:-------------:|:----:|:------:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Scan Absensi | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| Sesi Absensi | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Registrasi | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Peserta CAI | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Laporan | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| QR & Label | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Surat Izin | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Activity Log | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Kelola Event | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Master Data | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| User Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

### Pengajian Navigation

| Menu Item | Super Admin | Admin | Ketua | Sekretariat | Others |
|-----------|:-----------:|:-----:|:-----:|:-----------:|:------:|
| Regional Report | ✅ | ✅ | ✅* | ✅ | ❌ (viewer ✅) |
| Peserta (Daftar) | ✅ | ✅ | ❌ | ✅ | ❌ |
| Peserta (Import) | ✅ | ✅ | ❌ | ✅ | ❌ |
| Operasional Desa | ✅ | ✅ | ❌ | ✅ | ❌ |
| Kelola Event | ✅ | ✅ | ❌ | ❌ | ❌ |

---

## 4. Middleware Protection

> Routes operasional kini berada di prefix `events/{event}/...` (dengan `resolve.active-event`). Path non-prefix hanyalah redirect shim.

| Route | Middleware | Ability |
|-------|-----------|---------|
| `/dashboard` (platform) | `auth, verified` | — (landing platform) |
| `/events/{event}/dashboard` | `auth, verified, can:view-dashboard` | `view-dashboard` |
| `/events/{event}/registrasi` | `auth, verified, can:manage-registration` | `manage-registration` |
| `/events/{event}/registrasi/ulang` | `auth, verified, can:manage-registration` | `manage-registration` |
| `/events/{event}/database` | `auth, verified, can:manage-participants` | `manage-participants` |
| `/desa` | `auth, verified, can:view-master-data` | `view-master-data` |
| `/kelompok` | `auth, verified, can:view-master-data` | `view-master-data` |
| `/regu` | `auth, verified` | (no gate) |
| `/events/{event}/sesi-absensi` | `auth, verified, can:manage-sessions` | `manage-sessions` |
| `/events/{event}/rekap-peserta` | `auth, verified, can:view-reports` | `view-reports` |
| `/events/{event}/rekap-absensi` | `auth, verified, can:view-reports` | `view-reports` |
| `/events/{event}/qr-label` | `auth, verified, can:manage-qr-labels` | `manage-qr-labels` |
| `/events/{event}/absensi` | `auth, verified, can:manage-attendance` | `manage-attendance` |
| `/events/{event}/surat-izin` | `auth, verified, can:manage-secretariat` | `manage-secretariat` |
| `/events/{event}/activity-log` | `auth, verified, can:view-activity-log` | `view-activity-log` |
| `/events` | `auth, verified, can:manage-events` | `manage-events` |
| `/users` | `auth, verified, can:manage-users` | `manage-users` |
| `/master-data` | `auth, verified, can:view-master-data` | `view-master-data` |
| `/person` | `auth, verified, can:view-master-data` | `view-master-data` |
| `/koreksi-data` | `auth, verified, can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/admin/access` | `auth, verified, can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/admin/manual-entry` | `auth, verified, can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/admin/import-massal` | `auth, verified, can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/report` | `auth, verified, can:view-reports` | `view-reports` |
| `/import/peserta` | `auth, verified, can:manage-participants` | `manage-participants` |
| `/import/regu` | `auth, verified, can:manage-participants` | `manage-participants` |
| `/import/desa` | `auth, verified, can:manage-master-data` | `manage-master-data` |
| `/import/kelompok` | `auth, verified, can:manage-master-data` | `manage-master-data` |

---

## 5. Event-Scoped Authorization (KetuaEvent → Permission Engine)

KetuaEvent (dan semua role event non-platform) dapat mengakses event hanya jika di-assign via `EventCommitteeAssignment` + `EventRole` aktif:

```
User (role non-platform)
  → User.person_id → Person
  → EventCommitteeAssignment → Event
  → EventRole.permissions (JSON) berisi ability
  = access granted
```

**14 event abilities** di-resolve via `EventPermissionService`:
- view-dashboard, manage-registration, manage-participants, manage-attendance
- manage-sessions, manage-qr-labels, manage-secretariat, manage-import
- view-reports, manage-pengajian, view-activity-log
- manage-matches, manage-officials, submit-result (Competition)

**Enforcement points:**
1. `EventSwitcher::switchTo()` — throws AuthorizationException untuk unassigned events
2. `EventAccessService::canAccess()` — hanya true untuk platform user
3. `EventPermissionService::allows()` — dipakai oleh seluruh Gate event-scoped di `AppServiceProvider`

> Default template EventRole: lihat `app/Support/EventRolePermissionDefaults.php`.

---

## 6. Super Admin Bypass

All gates include a `Gate::before()` callback that returns `true` for Super Admin:
```php
Gate::before(function ($user) {
    return $user->hasRole(Role::SuperAdmin) ? true : null;
});
```

---

## 7. Livewire Mutation Authorization

Each Livewire mutation method gates with `Gate::authorize('{ability}')` before any database write. See `PERMISSION.md` for per-component details.
