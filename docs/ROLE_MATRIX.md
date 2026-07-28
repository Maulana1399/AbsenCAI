# ROLE MATRIX

> Complete role & permission audit based on actual code implementation.

---

## 1. Role Definitions

| Role | Code | Description | Access Level |
|------|------|-------------|-------------|
| Super Admin | `super_admin` | Full system access | Global |
| Admin | `admin` | Manage all events | Global |
| Ketua Event | `ketua_event` | Event-scoped (via assignment) | Event-scoped |
| Sekretariat | `sekretariat` | Admin + secretariat duties | Global |
| PJ Divisi | `pj_divisi` | Attendance monitoring only | Global |
| Operator Registrasi | `operator_registrasi` | Participant registration | Global |
| Operator Scan | `operator_scan` | QR scan + attendance | Global |
| Juri | `juri` | Scoring (V2 — Scoring Engine) | — |
| Viewer | `viewer` | Read-only reports + dashboard | Global |

---

## 2. Permission Matrix

| Ability | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Operator Registrasi | Operator Scan | Juri | Viewer |
|---------|:-----------:|:-----:|:-----:|:-----------:|:---------:|:------------------:|:-------------:|:----:|:------:|
| `view-dashboard` | ✅ | ✅ | ✅* | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| `view-master-data` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-master-data` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
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

*Ketua Event: event-scoped access only when has EventCommitteeAssignment for the specific event.

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

| Route | Middleware | Ability |
|-------|-----------|---------|
| `/dashboard` | `auth, verified, can:view-dashboard` | `view-dashboard` |
| `/registrasi` | `auth, verified, can:manage-registration` | `manage-registration` |
| `/registrasi/ulang` | `auth, verified, can:manage-registration` | `manage-registration` |
| `/database` | `auth, verified, can:manage-participants` | `manage-participants` |
| `/desa` | `auth, verified, can:view-master-data` | `view-master-data` |
| `/kelompok` | `auth, verified, can:view-master-data` | `view-master-data` |
| `/regu` | `auth, verified` | (no gate) |
| `/sesi-absensi` | `auth, verified, can:manage-sessions` | `manage-sessions` |
| `/rekap-peserta` | `auth, verified, can:view-reports` | `view-reports` |
| `/rekap-absensi` | `auth, verified, can:view-reports` | `view-reports` |
| `/qr-label` | `auth, verified, can:manage-qr-labels` | `manage-qr-labels` |
| `/absensi` | `auth, verified, can:manage-attendance` | `manage-attendance` |
| `/surat-izin` | `auth, verified, can:manage-secretariat` | `manage-secretariat` |
| `/activity-log` | `auth, verified, can:view-activity-log` | `view-activity-log` |
| `/events` | `auth, verified, can:manage-events` | `manage-events` |
| `/users` | `auth, verified, can:manage-users` | `manage-users` |
| `/master-data` | `auth, verified, can:view-master-data` | `view-master-data` |
| `/person` | `auth, verified, can:view-master-data` | `view-master-data` |
| `/koreksi-data` | `auth, verified, can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/admin/access` | `auth, verified, can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/admin/manual-entry` | `auth, verified, can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/admin/import-massal` | `auth, verified, can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/report` | `auth, verified, can:view-reports` | `view-reports` |
| `/import/peserta` | `auth, verified, can:manage-import` | `manage-import` |
| `/import/regu` | `auth, verified, can:manage-import` | `manage-import` |
| `/import/desa` | `auth, verified, can:manage-master-data` | `manage-master-data` |
| `/import/kelompok` | `auth, verified, can:manage-master-data` | `manage-master-data` |

---

## 5. Event-Scoped Authorization (KetuaEvent)

KetuaEvent can only access events they are assigned to via `EventCommitteeAssignment`:

```
User.role = ketua_event
  → User.person_id → Person
  → EventCommitteeAssignment → Event
  = access granted
```

**7 abilities are event-scoped for KetuaEvent:**
- view-dashboard, manage-registration, manage-participants, manage-attendance
- manage-sessions, manage-secretariat, view-reports

**Enforcement points:**
1. `EventSwitcher::switchTo()` — throws AuthorizationException for unassigned events
2. `EventAccessService::canAccess()` — used in Gate definitions
3. Gate closures in `AppServiceProvider` — per-ability, per-event checks

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
