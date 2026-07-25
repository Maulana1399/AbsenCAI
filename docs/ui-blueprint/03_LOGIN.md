# 03 — LOGIN BLUEPRINT

> Modern, minimal, universal.
> Benchmark: Clerk, Linear, Stripe, Vercel.

---

## 1. UX BENCHMARK REFERENCE

| Aplikasi | Pola UX | Yang Diadopsi |
|----------|---------|---------------|
| Clerk | Centered card + social login + clean | Layout centered card |
| Linear | Minimal, dark, magic link option | Clean form, no clutter |
| Stripe | Centered, brand logo + form | Brand consistency |
| Vercel | Clean split: brand panel + form | Optional split layout |
| GitHub | Centered card, form, link to signup | Standard auth pattern |

### Pola Universal Login Modern:

1. **Centered card** — Fokus penuh pada satu tugas
2. **Brand logo** — Pengingat visual aplikasi
3. **Email + Password** — Standard, predictable
4. **Social login** — Opsional, mengurangi friction
5. **Link to register** — Discoverability
6. **Forgot password** — Recovery path
7. **No distractions** — Tidak ada navbar, sidebar, atau iklan

---

## 2. WIREFRAME — DESKTOP (1440×900)

```
+------------------------------------------------------------------+
|                                                                  |
|                                                                  |
|                                                                  |
|                     ┌────────────────────────────┐               |
|                     │                            │               |
|                     │      ● KJA                 │               |
|                     │      Event Manager         │               |
|                     │                            │               |
|                     │    ──────────────────      │               |
|                     │                            │               |
|                     │    Masuk ke Akun Anda      │               |
|                     │                            │               |
|                     │    ┌──────────────────┐    │               |
|                     │    │ Email            │    │               |
|                     │    │                  │    │               |
|                     │    └──────────────────┘    │               |
|                     │                            │               |
|                     │    ┌──────────────────┐    │               |
|                     │    │ Password         │    │               |
|                     │    │ ••••••••••       │    │               |
|                     │    └──────────────────┘    │               |
|                     │                            │               |
|                     │    [ ] Ingat Saya          │               |
|                     │                            │               |
|                     │    [Masuk]                 │               |
|                     │                            │               |
|                     │    ──────────────────      │               |
|                     │                            │               |
|                     │    Lupa password?          │               |
|                     │                            │               |
|                     │    Belum punya akun?        │               |
|                     │    Daftar                  │               |
|                     │                            │               |
|                     └────────────────────────────┘               |
|                                                                  |
|                                                                  |
|                                                                  |
|                                                                  |
|                         © 2026 KJA Techno                        |
+------------------------------------------------------------------+
```

---

## 3. WIREFRAME — SPLIT LAYOUT (ALTERNATIVE)

```
+---------------------------------------+--------------------------+
|                                       |                          |
|  BRAND PANEL                          |  LOGIN FORM              |
|                                       |                          |
|  ┌─────────────────────────────────┐  |  ┌────────────────────┐  |
|  │                                 │  |  │                    │  |
|  │      ● KJA                      │  |  │  ● KJA Event       │  |
|  │      Event Manager              │  |  │  Manager           │  |
|  │                                 │  |  │                    │  |
|  │   Satu Platform untuk           │  |  │  Masuk ke Akun     │  |
|  │   Semua Event Organisasi        │  |  │                    │  |
|  │                                 │  |  │  ┌──────────────┐  │  |
|  │   [Ilustrasi Abstract           │  |  │  │ Email        │  │  |
|  │    Geometric Background         │  |  │  │              │  │  |
|  │    Blue Gradient]               │  |  │  └──────────────┘  │  |
|  │                                 │  |  │                    │  |
|  │                                 │  |  │  ┌──────────────┐  │  |
|  │   ✦ 10+ organisasi             │  |  │  │ Password     │  │  |
|  │   ✦ 5.000+ peserta             │  |  │  │ ••••••••••   │  │  |
|  │   ✦ 50+ event                  │  |  │  └──────────────┘  │  |
|  │                                 │  |  │                    │  |
|  └─────────────────────────────────┘  |  │  [Masuk]          │  |
|                                       |  │                    │  |
|                                       |  │  Lupa password?    │  |
|                                       |  │                    │  |
|                                       |  │  Belum punya akun? │  |
|                                       |  │  Daftar            │  |
|                                       |  │                    │  |
|                                       |  └────────────────────┘  |
|                                       |                          |
+---------------------------------------+--------------------------+
```

---

## 4. WIREFRAME — MOBILE (375×812)

```
+----------------------------------+
|                                  |
|                                  |
|      ● KJA                       |
|      Event Manager               |
|                                  |
|    ──────────────────             |
|                                  |
|    Masuk ke Akun Anda            |
|                                  |
|    ┌──────────────────────────┐  |
|    │ Email                    │  |
|    │                          │  |
|    └──────────────────────────┘  |
|                                  |
|    ┌──────────────────────────┐  |
|    │ Password                 │  |
|    │ ••••••••••               │  |
|    └──────────────────────────┘  |
|                                  |
|    [ ] Ingat Saya               |
|                                  |
|    [Masuk]                       |
|                                  |
|    ──────────────────             |
|                                  |
|    Lupa password?                |
|                                  |
|    Belum punya akun?             |
|    Daftar                       |
|                                  |
|    ──────────────────             |
|                                  |
|    © 2026 KJA Techno             |
+----------------------------------+
```

---

## 5. CONTENT STRATEGY

| Element | Copy | Notes |
|---------|------|-------|
| Title | Masuk ke Akun Anda | Standard, universal |
| Email label | Email | Bukan "Alamat Email" — lebih pendek |
| Password label | Password | Bukan "Kata Sandi" — lebih pendek, familiar |
| Remember | Ingat Saya | Standard |
| Submit | Masuk | Action-oriented |
| Forgot | Lupa password? | Link, bukan tombol |
| Register | Belum punya akun? Daftar | "Daftar" adalah link |
| Brand tagline | Satu Platform untuk Semua Event Organisasi | Hanya di split layout |

---

## 6. STATES

### 6.1 Default State

```
┌────────────────────────────┐
│      ● KJA                 │
│      Event Manager         │
│                            │
│    Masuk ke Akun Anda      │
│                            │
│    ┌──────────────────┐    │
│    │ Email            │    │
│    │                  │    │
│    └──────────────────┘    │
│                            │
│    ┌──────────────────┐    │
│    │ Password         │    │
│    │                  │    │
│    └──────────────────┘    │
│                            │
│    [Masuk]                 │
└────────────────────────────┘
```

### 6.2 Validation Error

```
┌────────────────────────────┐
│      ● KJA                 │
│      Event Manager         │
│                            │
│    Masuk ke Akun Anda      │
│                            │
│    ┌──────────────────┐    │
│    │ Email            │    │
│    │                  │    │
│    └──────────────────┘    │
│    ⚠ Email harus diisi.   │
│                            │
│    ┌──────────────────┐    │
│    │ Password         │    │
│    │ ••••••••••       │    │
│    └──────────────────┘    │
│                            │
│    [Masuk]                 │
└────────────────────────────┘
```

### 6.3 Server Error

```
┌────────────────────────────┐
│      ● KJA                 │
│      Event Manager         │
│                            │
│    Masuk ke Akun Anda      │
│                            │
│    ┌──────────────────────┐│
│    │ ✗ Email atau        ││
│    │   password salah.   ││
│    │   Coba lagi.        ││
│    └──────────────────────┘│
│                            │
│    ┌──────────────────┐    │
│    │ Email            │    │
│    │                  │    │
│    └──────────────────┘    │
│                            │
│    ┌──────────────────┐    │
│    │ Password         │    │
│    │                  │    │
│    └──────────────────┘    │
│                            │
│    [Masuk]                 │
└────────────────────────────┘
```

### 6.4 Loading State

```
┌────────────────────────────┐
│      ● KJA                 │
│      Event Manager         │
│                            │
│    Masuk ke Akun Anda      │
│                            │
│    ┌──────────────────┐    │
│    │ Email            │    │
│    │ user@mail.com    │    │
│    └──────────────────┘    │
│                            │
│    ┌──────────────────┐    │
│    │ Password         │    │
│    │ ••••••••••       │    │
│    └──────────────────┘    │
│                            │
│    [◌ Memproses...]       │  ← Button loading state
└────────────────────────────┘
```

---

## 7. VISUAL GUIDELINES

| Element | Specification |
|---------|---------------|
| Card | `max-w-md`, centered, `rounded-xl`, `shadow-sm` |
| Card background | White (light) / `zinc-900` (dark) |
| Logo position | Center, above form |
| Form fields | Full width, stacked vertical |
| Submit button | Full width, `variant="primary"` |
| Background (outside card) | `zinc-50` (light) / `zinc-950` (dark) |
| Split brand panel | Blue gradient (`blue-50` → `blue-100`) |
| Error alert | Red border + icon, muncul di atas form |
| Link color | `text-blue-600` |
| Social login | Future — belum diimplementasikan |

---

## 8. RULES

| Rule | Detail |
|------|--------|
| Jangan gunakan background masjid, pemandangan, atau foto | Latar polos atau subtle gradient |
| Jangan gunakan branding CAI | Logo harus "KJA Event Manager", bukan "AbsenCAI" atau "CAI" |
| Jangan tampilkan illustration figur manusia | Gunakan abstract geometric atau gradient |
| Jangan gunakan multi-column form | Login harus satu kolom, stack vertical |
| Jangan tampilkan fitur atau navigasi lain | Fokus hanya pada login |
| Password field harus `type="password"` | Tampilkan toggle show/hide |
| Remember me = optional | Jangan aktifkan default untuk security |
| Submit button disabled selama loading | Cegah double submit |
| Redirect ke `/workspace` setelah sukses | Bukan `/dashboard` |
