# SECURITY

> Security Standard for KJA Event Manager

---

# Purpose

Dokumen ini mendefinisikan standar keamanan yang wajib diterapkan selama pengembangan KJA Event Manager.

Semua developer, AI Assistant, dan kontributor wajib mengikuti aturan dalam dokumen ini.

---

# Security Philosophy

KJA Event Manager mengikuti prinsip:

* Secure by Default
* Least Privilege
* Defense in Depth
* Never Trust User Input
* Audit Everything

Keamanan merupakan bagian dari desain sistem, bukan fitur tambahan.

---

# Authentication

## Current

* Email
* Password

## Future

* Google Login
* OTP
* Passkey
* SSO
* Multi Organization Login

---

# Authorization

KJA Event Manager menggunakan **Role Based Access Control (RBAC)**.

Semua hak akses dikelola melalui:

* Role
* Permission
* Gate
* Policy

> Lihat **PERMISSION.md** untuk detail Role & Permission Matrix.

Tidak diperbolehkan:

* Hardcode Role
* Hardcode Permission
* Menampilkan menu tanpa validasi permission

---

# Attendance Security

Attendance merupakan fitur paling kritikal.

QR Code **TIDAK BOLEH** berisi:

* NIP
* Universal ID
* Database ID
* Email
* Nomor HP

QR hanya boleh berisi:

* Attendance Code

Contoh:

```text
KEM-7F92QKX
```

Attendance Code harus:

* Random
* Unique
* Tidak dapat ditebak
* Dapat diregenerate

QR lama harus dapat dinonaktifkan apabila kartu hilang.

---

# Password Policy

Minimal:

* 8 karakter

Disarankan:

* 12+ karakter

Password wajib:

* Hash menggunakan Laravel Hash
* Tidak boleh disimpan plain text
* Tidak boleh dikirim melalui API Response

---

# Session Security

Menggunakan Laravel Session.

Future:

* Remember Device
* Device Management
* Session Monitoring
* Remote Logout

---

# Input Validation

Seluruh request wajib divalidasi.

Gunakan:

* Form Request
* Livewire Validation

Tidak boleh mempercayai input dari user.

Validasi minimal:

* Required
* Type
* Length
* Exists
* Unique
* Authorization

---

# SQL Injection Protection

Gunakan:

* Eloquent
* Query Builder

Hindari:

* Raw Query
* String Concatenation SQL

Raw Query hanya diperbolehkan apabila benar-benar diperlukan.

---

# XSS Protection

Gunakan Blade Template.

Escape seluruh output.

Hindari:

* Raw HTML
* Unescaped Content

---

# CSRF Protection

Semua Form wajib menggunakan CSRF Protection bawaan Laravel.

Tidak boleh dinonaktifkan.

---

# File Upload Security

Allowed:

* PDF
* Excel
* Image

Semua upload wajib:

* Validasi MIME Type
* Validasi ukuran
* Rename menggunakan UUID
* Disimpan di Storage

Jangan pernah menggunakan nama file asli sebagai nama penyimpanan.

---

# Export Security

Semua Export harus dicatat.

Minimal mencatat:

* User
* Event
* Jenis Export
* Waktu

Future:

Export Approval untuk data sensitif.

---

# Audit Log

Minimal aktivitas berikut harus dicatat:

* Login
* Logout
* Registrasi
* Scan QR
* Manual Attendance
* Edit Data
* Delete Data
* Import
* Export
* Generate QR
* Print
* Generate Certificate

Audit Log tidak boleh dapat diubah oleh user biasa.

---

# Sensitive Data

Data berikut dianggap sensitif:

* Password
* Attendance Code
* Parent Phone
* Email
* Authentication Token
* API Key

Data sensitif tidak boleh:

* Ditampilkan tanpa permission
* Masuk ke log
* Dikirim ke client apabila tidak diperlukan

---

# Storage Security

Database hanya menyimpan metadata file.

File besar disimpan di:

* Nextcloud
* TrueNAS

Storage harus memiliki backup rutin.

---

# Backup Policy

Minimal:

* Database: 1x sehari
* Storage: 1x sehari
* Configuration: setiap perubahan besar

Backup harus dapat direstore.

---

# API Security (Future)

Semua API akan menggunakan:

* API Key
* Access Token
* Rate Limiting
* OAuth
* HTTPS Only

---

# Infrastructure Security

Development:

* Mini PC
* Proxmox

Production:

* VPS / Dedicated Server

Server wajib:

* Firewall aktif
* HTTPS
* Backup
* Monitoring
* Automatic Update (Security Patch)

---

# Logging & Monitoring

Minimal monitoring:

* Error Log
* Login Failed
* Storage Usage
* CPU
* RAM
* Database

Future:

* Health Check Dashboard
* Alert Notification

---

# AI Development Rules

AI MUST NOT:

* Disable Validation
* Disable Authorization
* Store Plain Password
* Expose Attendance Code
* Expose API Key
* Remove Security Middleware

AI MUST ALWAYS:

* Add Validation
* Follow Laravel Best Practice
* Use Eloquent
* Escape Output
* Keep Audit Logging
* Reuse Existing Security Components

---

# Security Checklist

Before Release:

* Password Hashed
* Authorization Checked
* Validation Complete
* CSRF Enabled
* Export Logged
* Audit Enabled
* Backup Configured
* HTTPS Enabled
* APP_KEY Configured
* APP_DEBUG Disabled
* .env Not Committed

---

# Future Security Roadmap

Sprint 1

* Attendance Code
* Internal QR
* Audit Export

Sprint 2

* QR Regeneration
* Operator Scan Token

Sprint 3

* Login Notification
* Device Management

Sprint 4

* Two Factor Authentication
* Offline Scan Encryption
* Attendance Code Rotation
* IP Restriction
* Geo Restriction
