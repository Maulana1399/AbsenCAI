# 10 — INFORMATION ARCHITECTURE BLUEPRINT

> Complete information architecture, sitemap, URL structure, and data hierarchy.
> Benchmark: Linear, Supabase, Stripe.

---

## 1. SITEMAP — FULL STRUCTURE

```
Landing Page
├── /                          → Landing Page (public)

Auth
├── /login                     → Login
├── /register                  → Register
├── /forgot-password           → Forgot Password
├── /reset-password/{token}    → Reset Password

App (after login)
├── /workspace                 → Workspace (default redirect after login)
│
├── /events                    → Events Overview (list all events)
│   └── /events/{eventId}      → Event Home
│       ├── /events/{eventId}/absensi        → Module: Absensi
│       ├── /events/{eventId}/peserta         → Module: Manajemen Peserta
│       ├── /events/{eventId}/qrcode          → Module: Cetak QR & Label
│       ├── /events/{eventId}/akses           → Module: Akses Desa
│       ├── /events/{eventId}/laporan         → Module: Laporan Regional
│       └── /events/{eventId}/pengaturan      → Module: Pengaturan Event
│
├── /people                    → People Database
│   ├── /people                → List semua person
│   └── /people/{personId}     → Detail person (modal/redirect)
│
├── /analytics                 → Analytics Dashboard
│
└── /admin                     → Administration
    ├── /admin/events          → Event Management
    ├── /admin/users           → User Management
    ├── /admin/desa            → Master Data: Desa
    ├── /admin/kelompok        → Master Data: Kelompok
    └── /admin/settings        → Application Settings
```

---

## 2. URL DESIGN PRINCIPLES

### 2.1 URL Convention

| Aturan | Contoh |
|--------|--------|
| Lowercase | `/workspace` bukan `/Workspace` |
| Kebab-case | `/forgot-password` bukan `/forgotPassword` |
| No trailing slash | `/events` bukan `/events/` |
| Resource-based | `/events/{id}` bukan `/event-detail?id={id}` |
| Nested resource | `/events/{id}/absensi` bukan `/absensi?event_id={id}` |
| No file extension | `/people` bukan `/people.php` |

### 2.2 URL Hierarchy

```
Domain         / Area          / Resource    / ID        / Module
https://kja.org / events       /             / {eventId} / absensi
https://kja.org / admin        / users       /
https://kja.org / people       /
https://kja.org / workspace
```

---

## 3. PAGE TITLE HIERARCHY

| Halaman | Title Tag | H1 | Breadcrumb Label |
|---------|-----------|----|------------------|
| Landing | KJA Event Manager — Kelola Event Lebih Mudah | KJA Event Manager | — |
| Login | Masuk — KJA Event Manager | Masuk | — |
| Register | Daftar — KJA Event Manager | Daftar | — |
| Workspace | Beranda — KJA Event Manager | Selamat Datang, {Nama} | Beranda |
| Events | Event — KJA Event Manager | Event | Beranda > Event |
| Event Home | {Nama Event} — KJA Event Manager | {Nama Event} | Beranda > Event > {Nama} |
| Absensi | Absensi — {Nama Event} | Absensi | Beranda > Event > {Nama} > Absensi |
| Peserta | Peserta — {Nama Event} | Manajemen Peserta | Beranda > Event > {Nama} > Peserta |
| People | Orang — KJA Event Manager | Database Person | Beranda > Orang |
| Analytics | Analitik — KJA Event Manager | Analitik & Laporan | Beranda > Analitik |
| Admin | Pengaturan — KJA Event Manager | Administrasi | Beranda > Pengaturan |

---

## 4. DATA HIERARCHY

### 4.1 Entity Relationship (Simplified)

```
Organization (1)
  ├── Users (N) [manager/s assigned to org]
  │
  ├── Desa (N) [master data]
  │   └── Kelompok (N) [within desa]
  │
  ├── Events (N)
  │   ├── Event Modules (N) [absensi, peserta, etc]
  │   ├── Event Roles (N) [operator, juri, etc]
  │   │   └── Users (N) [assigned to role in event]
  │   └── Event Registrations (N)
  │       └── Person (1)
  │           └── Desa (1)
  │               └── Kelompok (1)
  │
  └── People (N) [global person database]
      ├── Personal Info (name, contact)
      ├── Desa (1)
      ├── Kelompok (1)
      └── Event Registrations (N) [cross-event history]
```

### 4.2 Data Ownership

| Entity | Owner | CRUD Scope |
|--------|-------|------------|
| Organization | Super Admin | Global |
| Users | Admin / Super Admin | Global |
| Desa | Admin / Super Admin | Global |
| Kelompok | Admin / Super Admin | Global |
| Events | Admin / Ketua Event | Global or own |
| Event Modules | System-defined | Based on event type |
| Event Registrations | Ketua Event / Operator | Per event |
| People | All roles (read), Admin/Sekre (write) | Global |
| Attendance Records | Operator Scan | Per event session |
| Analytics Data | Read-only for all roles | Based on role scope |

---

## 5. NAVIGATION HIERARCHY

### 5.1 Sidebar (Desktop)

```
Level 0     Level 1          Level 2
─────────────────────────────────────
Workspace
  └─ Quick actions (in-page)

Events
  └─ Event 1
  └─ Event 2
  └─ Event 3

People

Analytics

Administration
  ├─ Master Data
  │   ├─ Desa
  │   └─ Kelompok
  ├─ Users
  └─ Settings
```

### 5.2 Event Home Navigation (In-Page Module Grid)

```
Event Home
  ├─ Absensi
  ├─ Peserta
  ├─ QR & Label
  ├─ Akses Desa
  ├─ Laporan
  └─ Pengaturan Event
```

### 5.3 Administration Navigation (In-Page Sub-Nav)

```
Administration
  ├─ Event Management
  ├─ User Management
  ├─ Desa
  └─ Kelompok
```

---

## 6. CONTENT TYPES

| Type | Description | Example Pages |
|------|-------------|---------------|
| Dashboard | Overview, summary, timeline | Workspace, Event Home |
| List | Collection of items | Events, People, Desa |
| Detail | Single item view | Person Detail (modal) |
| Form | Data input | Login, Register, Create Event |
| Analytics | Charts, tables, KPIs | Analytics |
| Settings | Configuration | Administration |
| Scan | Camera + QR reader | Absensi |
| Public | Marketing pages | Landing Page |

---

## 7. DATA FLOW — CUSTOMER JOURNEY

```
                     ┌─────────────────┐
                     │   LANDING PAGE  │
                     │   (public)      │
                     └────────┬────────┘
                              │ Login
                              v
                     ┌─────────────────┐
                     │    LOGIN        │
                     └────────┬────────┘
                              │ Authenticate
                              v
                     ┌─────────────────┐
                     │   WORKSPACE     │ ← Default landing after login
                     │   (all events)  │
                     └────────┬────────┘
                              │
              ┌───────────────┼───────────────┐
              │               │               │
              v               v               v
     ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
     │  EVENT HOME  │ │   PEOPLE     │ │  ANALYTICS   │
     │  (per event) │ │ (database)   │ │ (reports)    │
     └───────┬──────┘ └──────────────┘ └──────────────┘
             │
    ┌────────┼────────┐
    │        │        │
    v        v        v
 ┌──────┐ ┌──────┐ ┌──────┐
 │Absen │ │Peserta│ │Cetak │
 │      │ │       │ │QR    │
 └──────┘ └──────┘ └──────┘
```

---

## 8. PERMISSION MATRIX (PAGE ACCESS)

| Page | Super Admin | Admin | Sekre | Ketua Event | PJ Divisi | Operator Scan | Operator Reg | Juri | Viewer |
|------|-------------|-------|-------|-------------|-----------|---------------|--------------|------|--------|
| Landing | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Login | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Auth pages | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Workspace | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Events List | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Event Home (own) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Event Home (other) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Absensi | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Peserta | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| QR & Label | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Akses Desa | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Laporan | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| People List | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| People Create | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Analytics | ✅ | ✅ | ✅ | ✅ (own) | ✅ (own) | ❌ | ❌ | ❌ | ✅ (read) |
| Admin — Events | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Admin — Users | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Admin — Desa | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Admin — Kelompok | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Admin — Settings | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 9. CROSS-REFERENCE: PAGE vs MODULE

| Module | Tersedia di |
|--------|-------------|
| Absensi | Event Home (dalam event) |
| Manajemen Peserta | Event Home (dalam event) |
| Cetak QR & Label | Event Home (dalam event) |
| Akses Desa | Event Home (dalam event) |
| Laporan Regional | Event Home (dalam event) |
| Pengaturan Event | Event Home (dalam event) |
| Database Person | People (global) |
| Analytics & Laporan | Analytics (cross-event) |
| Event Management | Administration |
| User Management | Administration |
| Master Data Desa | Administration |
| Master Data Kelompok | Administration |

---

## 10. SEARCH & FILTER ARCHITECTURE

### 10.1 Global Search (Future)

| Search Scope | Results |
|--------------|---------|
| People | Person name, Desa |
| Events | Event name, year, type |
| Modules | Module name |
| Settings | Setting name |

### 10.2 Page-Level Filters

| Page | Filters |
|------|---------|
| Events List | Status, Type, Year |
| People | Name (search), Desa, Kelompok |
| Analytics | Date range, Event, Module |
| Event Absensi | Date, Sesi, Status (hadir/belum) |
| Event Peserta | Desa, Kelompok, Status |
| Admin Events | Status, Type |
| Admin Users | Role, Status |

---

## 11. REDIRECT MAP

| From | To | Condition |
|------|----|-----------|
| `/` | `/workspace` | Authenticated |
| `/` | `/` (Landing) | Guest |
| `/login` | `/workspace` | Already authenticated |
| `/workspace` | `/login` | Not authenticated |
| Any `/events/{id}/...` | `/events` | Event not found / no access |
| Any `/admin/...` | `/workspace` | Not admin |
| Any app page | `/login` | Session expired |

---

## 12. RULES

| Rule | Detail |
|------|--------|
| URL structure harus RESTful | Resource-based, nested, kebab-case |
| Setiap halaman harus memiliki title tag unik | Format: `{Page Title} — KJA Event Manager` |
| Breadcrumb max 4 level | Root → Area → Resource → Module |
| Event modules hanya ada di dalam konteks event | `/events/{id}/module` |
| Admin area terpisah dari app area | `/admin/*` prefix |
| Halaman yang membutuhkan role tertentu harus return 403 | Bukan 404, bukan redirect ke login |
| Setiap entity harus memiliki canonical route | Satu URL, satu resource |
| Global search bersifat future | Infrastructure boleh disiapkan, UI tidak wajib |
