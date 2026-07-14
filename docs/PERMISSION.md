# PERMISSION

> Role & Permission Matrix for KJA Event Manager

---

# Purpose

Dokumen ini mendefinisikan hak akses setiap Role pada KJA Event Manager.

Semua perubahan Role dan Permission wajib didokumentasikan di file ini.

---

# Permission Principles

* Least Privilege
* Role Based Access Control (RBAC)
* Deny by Default
* Every Feature Requires Permission
* Never Hardcode Role

---

# Current Roles

| Role                | Description                                    |
| ------------------- | ---------------------------------------------- |
| Super Admin         | Mengelola seluruh sistem dan konfigurasi       |
| Admin               | Mengelola seluruh Event                        |
| Ketua Event         | Mengelola event yang menjadi tanggung jawabnya |
| Sekretariat         | Mengelola data peserta dan administrasi        |
| PJ Divisi           | Monitoring divisi masing-masing                |
| Operator Registrasi | Registrasi peserta                             |
| Operator Scan       | Scan QR dan absensi                            |
| Juri                | Input nilai perlombaan                         |
| Peserta             | Melihat data pribadi                           |
| Viewer              | Dashboard tanpa hak edit                       |

---

# Permission Matrix

| Feature            | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Registrasi | Scan | Juri | Peserta | Viewer |
| ------------------ | :---------: | :---: | :---: | :---------: | :-------: | :--------: | :--: | :--: | :-----: | :----: |
| Dashboard          |      ✅      |   ✅   |   ✅   |      ✅      |     ✅     |      ❌     |   ❌  |   ❌  |    ❌    |    ✅   |
| Live Monitoring    |      ✅      |   ✅   |   ✅   |      ✅      |     ✅     |      ❌     |   ❌  |   ❌  |    ❌    |    ✅   |
| Master Data        |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| Import Excel       |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| Person             |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    👁   |    ❌   |
| Registration       |      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |      ✅     |   ❌  |   ❌  |    ❌    |    ❌   |
| Attendance         |      ✅      |   ✅   |   ✅   |      ✅      |     ✅     |      ❌     |   ✅  |   ❌  |    👁   |    ❌   |
| Attendance Manual  |      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |      ❌     |   ✅  |   ❌  |    ❌    |    ❌   |
| Attendance Session |      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| QR Generator       |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| Print QR           |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| Print ID Card      |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| Permission Letter  |      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    👁   |    ❌   |
| Export Excel       |      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| Export PDF         |      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| Scoring            |      ✅      |   ✅   |   ✅   |      ❌      |     ❌     |      ❌     |   ❌  |   ✅  |    👁   |   👁   |
| Competition        |      ✅      |   ✅   |   ✅   |      ❌      |     ❌     |      ❌     |   ❌  |   ✅  |    👁   |   👁   |
| Certificate        |      ✅      |   ✅   |   ✅   |      ❌      |     ❌     |      ❌     |   ❌  |   ❌  |    👁   |   👁   |
| User Management    |      ✅      |   ❌   |   ❌   |      ❌      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| Role Management    |      ✅      |   ❌   |   ❌   |      ❌      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |
| System Settings    |      ✅      |   ❌   |   ❌   |      ❌      |     ❌     |      ❌     |   ❌  |   ❌  |    ❌    |    ❌   |

---

# Future Role

Planned:

* Ketua Cabang
* Venue Manager
* Operator Venue
* Official
* Volunteer
* Medical Team
* Security Team
* Finance
* Documentation Team

---

# Permission Naming Convention

Gunakan format berikut:

```
person.view
person.create
person.update
person.delete

attendance.scan
attendance.manual
attendance.export

competition.score
competition.rank

certificate.generate

dashboard.view
dashboard.live
```

---

# Development Rules

* Jangan melakukan pengecekan role secara langsung (`role == 'admin'`).
* Gunakan Gate atau Policy Laravel.
* Semua menu harus disembunyikan jika user tidak memiliki permission.
* Semua endpoint harus memvalidasi permission, meskipun menu tidak tampil.
* Setiap fitur baru wajib menambahkan permission baru sebelum implementasi.

---

# Long Term Goal

Role dan Permission harus sepenuhnya dinamis sehingga organisasi dapat membuat Role sendiri tanpa mengubah source code.
