# Bug Fixing Report: Timer Admin Room Control

## Revisi: Transisi Operan Setelah Pause dan Latency Panel Admin

### 1. Timer tetap 30 detik setelah jawaban pembeli salah saat pause

#### Gejala

Ketika timer dipause pada fase `menjawab`, lalu admin menekan aksi jawaban pembeli salah, fase berubah menjadi `operan` tetapi waktu yang tampil masih melanjutkan sisa 30 detik fase menjawab.

#### Root Cause

Handler `BUY_WRONG` hanya mengubah `timer_phase` dan `timer_expires_at`. Status `is_paused` serta `paused_phase_remaining` tetap tersimpan. API memprioritaskan state paused, sehingga expiry operan yang baru diabaikan dan nilai pause fase menjawab yang lama tetap digunakan.

#### Perbaikan

### [app/Http/Controllers/Admin/RoomController.php](app/Http/Controllers/Admin/RoomController.php)

- `BUY_WRONG` sekarang menghitung ulang expiry global dari sisa waktu yang tersimpan.
- Timer diaktifkan kembali saat berpindah ke operan.
- Nilai pause dibersihkan.
- Expiry fase operan baru ditetapkan menjadi 10 detik.

### 2. Latency saat menekan tombol di panel admin

#### Root Cause

Form aksi sebelumnya menunggu redirect HTML setelah setiap POST. Alur tersebut menambah navigasi halaman dan membuat tombol terasa lambat, sekaligus memungkinkan klik berulang sebelum respons selesai.

#### Perbaikan

### [resources/views/admin/rooms/control.blade.php](resources/views/admin/rooms/control.blade.php)

- Aksi `BUY`, `BUY_CORRECT`, `BUY_WRONG`, `PASS_CORRECT`, `PASS_WRONG`, `CLOSE`, pause, resume, dan reset dikirim menggunakan `fetch`.
- Controller mengembalikan JSON untuk request asynchronous sehingga tidak perlu redirect perantara.
- Tombol langsung dinonaktifkan dan menampilkan status `Memproses...` untuk mencegah klik ganda.
- Panel dimuat ulang setelah server mengonfirmasi aksi agar kontrol dan state timer tetap konsisten.

### Validasi Revisi

```bash
php -l app/Http/Controllers/Admin/RoomController.php
php artisan view:clear
php artisan view:cache
```

JavaScript embedded pada panel admin juga berhasil diparse tanpa error.

## Perbaikan Glitch Countdown Operator

### Gejala

Pada operator panel, countdown dapat berubah dari `20` ke `19`, lalu kembali beberapa kali ke `20` sebelum melanjutkan hitungan.

### Root Cause

Operator melakukan polling API setiap 300 ms, sedangkan API mengembalikan sisa waktu dalam bilangan bulat yang dibulatkan ke bawah. Setelah countdown lokal turun ke `19`, respons polling berikutnya masih dapat berisi `20` karena belum melewati batas detik berikutnya. Nilai server tersebut selalu menimpa nilai lokal, sehingga tampilan terlihat mundur lalu maju kembali.

### Perbaikan

### [resources/views/operator/stage.blade.php](resources/views/operator/stage.blade.php)

- Menyimpan identitas soal dan fase timer yang sedang ditampilkan.
- Saat soal dan fase masih sama-sama berjalan, sinkronisasi menggunakan nilai terendah antara state lokal dan respons server.
- Respons polling yang lebih tua tidak dapat menaikkan countdown yang sudah turun.
- Sinkronisasi penuh tetap dilakukan untuk soal baru, pergantian fase, pause, resume, reset, dan timer yang selesai.

### Validasi

Kompilasi Blade berhasil dijalankan:

```bash
php artisan view:clear
php artisan view:cache
```

Hasil:

```text
Compiled views cleared successfully.
Blade templates cached successfully.
```

**Tanggal:** 2026-09-20  
**Scope:** Timer total, pause/resume, reset, dan tampilan suffix detik  
**Status:** Fixed dan tervalidasi

## Ringkasan Bug

1. Total timer soal selalu tampil `0`.
2. Tombol pause tidak benar-benar menghentikan timer.
3. Tampilan waktu dapat menjadi `7 ss`.
4. Tombol reset tidak mengembalikan timer.

## Root Cause

### 1. Field timer belum dapat diisi oleh model

Controller sudah mengirim field berikut melalui `$roomQuestion->update(...)`:

- `global_timer_expires_at`
- `is_paused`
- `paused_global_remaining`
- `paused_phase_remaining`

Namun field-field tersebut belum ada di `$fillable` model `RoomQuestion`. Akibatnya, Eloquent mengabaikan field tersebut saat mass assignment.

Dampaknya:

- `global_timer_expires_at` tetap `NULL`, sehingga API menghitung total timer sebagai `0`.
- `is_paused` tetap `false`, sehingga pause tidak tersimpan.
- Nilai waktu yang dibekukan tidak tersimpan.
- Resume dan reset tidak dapat bekerja konsisten.

### 2. Cast timer belum lengkap

Kolom baru belum memiliki cast untuk datetime, boolean, dan integer. Hal ini membuat nilai timer lebih rentan diperlakukan sebagai tipe yang tidak konsisten ketika dibaca oleh controller/API.

### 3. Formatter frontend menambahkan suffix secara langsung

Script admin dan operator menambahkan `s` langsung ke nilai yang diterima. Formatter baru sekarang membersihkan suffix `s` dari input terlebih dahulu, mengubahnya menjadi angka, lalu menambahkan satu suffix `s`.

## Perubahan yang Dilakukan

### [app/Models/RoomQuestion.php](app/Models/RoomQuestion.php)

Ditambahkan ke `$fillable`:

```php
'global_timer_expires_at',
'is_paused',
'paused_global_remaining',
'paused_phase_remaining',
```

Ditambahkan ke `$casts`:

```php
'global_timer_expires_at' => 'datetime',
'is_paused' => 'boolean',
'paused_global_remaining' => 'integer',
'paused_phase_remaining' => 'integer',
```

Ini memperbaiki penyimpanan dan pembacaan state timer pada select, pause, resume, dan reset.

### [resources/views/admin/rooms/control.blade.php](resources/views/admin/rooms/control.blade.php)

Ditambahkan normalisasi timer melalui formatter yang:

- Menghapus suffix `s` jika input sudah memilikinya.
- Mengubah input menjadi angka valid.
- Mencegah nilai negatif atau `NaN`.
- Menampilkan tepat satu suffix `s`.

Formatter digunakan untuk countdown lokal dan sinkronisasi API.

### [resources/views/operator/stage.blade.php](resources/views/operator/stage.blade.php)

Diterapkan normalisasi yang sama pada stage display agar admin dan operator menampilkan nilai timer secara konsisten.

## Alur Setelah Fix

1. Saat soal dipilih, controller menyimpan expiry global dan expiry fase.
2. API menghitung sisa waktu global dan fase dari expiry tersebut.
3. Saat pause ditekan, model menyimpan status pause serta sisa detik.
4. Saat paused, API menggunakan nilai sisa yang dibekukan.
5. Saat resume ditekan, controller membuat expiry baru berdasarkan nilai pause.
6. Saat reset ditekan, controller mengembalikan fase ke `papar` dan timer ke 182 detik termasuk buffer aplikasi.
7. Frontend menampilkan nilai dengan satu suffix `s`.

## Validasi

### Kompilasi Blade

```bash
php artisan view:clear
php artisan view:cache
```

Hasil:

```text
Compiled views cleared successfully.
Blade templates cached successfully.
```

### Lint PHP

File timer yang diperiksa:

```bash
php -l app/Models/RoomQuestion.php
php -l app/Http/Controllers/Admin/RoomController.php
php -l app/Http/Controllers/Api/StateController.php
```

Semua menghasilkan `No syntax errors detected`.

### Model runtime

Runtime model terverifikasi mengenali seluruh field timer pada `$fillable` dan `$casts`.

### Migration

Migration timer sudah berstatus `Ran`:

```text
2026_09_20_075215_add_dual_timer_and_pause_to_room_questions
```

### Route

Route berikut tetap terdaftar:

- `admin.rooms.control`
- `admin.rooms.pause-timer`
- `admin.rooms.resume-timer`
- `admin.rooms.reset-timer`

### Test bawaan

Test bawaan feature gagal pada assertion lama yang mengharapkan status `200` untuk `/`, sedangkan aplikasi memang mengarahkan user yang belum login ke `/login` dengan status `302`. Kegagalan ini tidak terkait perubahan timer.

## Catatan File Lain

`vendor/bin/pint --dirty --format agent` dijalankan sesuai aturan project. Karena mode `--dirty` memformat seluruh file PHP yang sedang berubah, beberapa file PHP lain yang sudah memiliki perubahan sebelumnya ikut terkena formatting tanpa perubahan logika timer:

- `app/Http/Controllers/Admin/RoomController.php`
- `app/Http/Controllers/Api/StateController.php`
- `routes/web.php`
- migration timer

Tidak ada dependency baru atau migration baru yang dibuat untuk fix ini.

## Kesimpulan

Akar masalah timer adalah field dual timer dan pause yang belum terdaftar pada model `RoomQuestion`. Setelah field ditambahkan ke `$fillable` dan `$casts`, ditambah normalisasi formatter pada admin dan operator, timer total, pause/resume, reset, dan tampilan suffix detik sudah diperbaiki serta lolos validasi kompilasi dan lint.
