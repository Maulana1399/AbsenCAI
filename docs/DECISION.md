# Architectural Decision Record (ADR)

Semua keputusan besar proyek dicatat di sini.

Status:

- Proposed
- Accepted
- Deprecated
- Rejected

---

# ADR-001

## Title

Rename Project to KJA Event Manager

## Date

2026-07-14

## Status

Accepted

## Context

AbsenCAI berkembang menjadi sistem yang tidak hanya menangani absensi tetapi juga Event Management.

## Decision

Menggunakan nama KJA Event Manager sebagai nama produk.

AbsenCAI menjadi modul pertama.

## Consequence

- Branding lebih luas
- Mendukung Multi Event
- Mudah dikembangkan menjadi produk komersial

---

# ADR-002

## Title

Person sebagai entitas utama

## Date

2026-07-14

## Status

Accepted

## Context

Peserta dapat mengikuti banyak event.

Satu orang juga dapat menjadi:

- Peserta
- Panitia
- Juri
- Official

## Decision

Database menggunakan Person sebagai master.

Setiap event hanya membuat Participation.

## Consequence

Tidak perlu registrasi ulang.

---

# ADR-003

## Title

Attendance Code menggantikan QR berbasis NIP

## Status

Accepted

## Context

QR berbasis NIP mudah ditebak dan sulit diganti ketika kartu hilang.

## Decision

Setiap Participation memiliki Attendance Code unik.

QR hanya berisi Attendance Code.

## Consequence

- QR dapat diregenerate
- QR lama dapat dinonaktifkan
- Lebih aman

---

# ADR-004

## Title

No Code Before Design

## Status

Accepted

## Decision

Semua fitur wajib melalui:

Idea

↓

Discussion

↓

Documentation

↓

Database Design

↓

Todo

↓

Sprint

↓

Development

---

# ADR-005

## Title

Development Priority

## Status

Accepted

Prioritas pengembangan:

1. CAI Operational
2. Stabilitas
3. Refactor
4. Multi Event
5. Competition
6. Commercial

---

# ADR-006

## Title

Storage Strategy

## Status

Accepted

## Decision

File besar tidak disimpan di Laravel.

Laravel hanya menyimpan metadata.

Storage menggunakan Nextcloud / TrueNAS.

---

# ADR-007

## Title

Commercial Strategy

## Status

Accepted

Produk dijual satu kali.

Fitur tambahan dijual sebagai Add-on Module.

---

# ADR-008

## Title

Database Strategy

## Status

Proposed

SQLite digunakan selama fase CAI.

Setelah stabil akan migrasi ke MariaDB/PostgreSQL.