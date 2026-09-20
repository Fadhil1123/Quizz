# Error Report: Admin Room Control

**Tanggal pemeriksaan:** 2026-09-20  
**Area:** Admin panel, halaman control room  
**Status:** Root cause teridentifikasi  
**Perubahan source aplikasi:** Tidak ada

## Ringkasan

Saat halaman control room dibuka, Laravel gagal mengompilasi Blade view dan melempar:

```text
Illuminate\Contracts\View\ViewCompilationException
Malformed @foreach statement.
```

Error terjadi pada tahap kompilasi Blade, sebelum HTML halaman selesai dirender. Karena itu, masalah ini bukan disebabkan oleh data room, relasi model, query database, atau route.

## Bukti Diagnosis

### 1. Laravel log

`storage/logs/laravel.log` mencatat:

```text
Malformed @foreach statement.
Illuminate\Contracts\View\ViewCompilationException
```

Stack trace mengarah ke:

```text
resources/views/admin/rooms/control.blade.php
vendor/laravel/framework/src/Illuminate/View/Compilers/Concerns/CompilesLoops.php:106
```

### 2. Reproduksi kompilasi

Perintah berikut dijalankan tanpa mengubah source aplikasi:

```bash
php artisan view:cache
```

Hasilnya:

```text
Malformed @foreach statement.
BladeCompiler::compileForeach("($room->teams as$team)")
```

Compiler Laravel menggunakan pola yang membutuhkan spasi di sekitar keyword `as`. Ekspresi `as$team` tidak memenuhi format tersebut.

## Root Cause

Pada [resources/views/admin/rooms/control.blade.php](resources/views/admin/rooms/control.blade.php), terdapat tiga directive `@foreach` dengan spasi yang hilang setelah `as`:

| Baris | Bentuk saat ini | Masalah |
|---:|---|---|
| 98 | `@foreach($room->teams as$team)` | Tidak ada spasi antara `as` dan `$team` |
| 143 | `@foreach($room->teams as$team)` | Tidak ada spasi antara `as` dan `$team` |
| 212 | `@foreach($room->roomQuestions as$rq)` | Tidak ada spasi antara `as` dan `$rq` |

Format Blade yang valid harus memisahkan collection, keyword `as`, dan variable loop dengan spasi.

## Dampak

- Halaman `/admin/rooms/{id}/control` tidak dapat dirender.
- Semua proses di dalam view berhenti sebelum browser menerima halaman.
- Error muncul sebagai `ViewCompilationException`, bukan sebagai error runtime dari data room.
- Perintah `php artisan view:cache` juga gagal selama syntax tersebut belum diperbaiki.

## Cara Mengatasi

Perbaiki tiga directive tersebut dengan menambahkan spasi setelah `as`:

```blade
@foreach($room->teams as $team)
```

```blade
@foreach($room->roomQuestions as $rq)
```

Setelah perubahan dilakukan, bersihkan cache view lalu kompilasi ulang:

```bash
php artisan view:clear
php artisan view:cache
```

Validasi tambahan:

1. Buka kembali halaman control room sebagai admin.
2. Pastikan halaman berhasil dirender.
3. Pastikan daftar team dan daftar room question tampil.
4. Periksa `storage/logs/laravel.log` bila masih terjadi error.

## Catatan

Log browser juga memiliki error historis `Unexpected token 'p' ... is not valid JSON` pada polling endpoint. Error tersebut berbeda dari penyebab halaman gagal dibuka. Prioritas pertama adalah memperbaiki syntax `@foreach`, kemudian endpoint polling dapat diperiksa kembali bila error JSON masih muncul setelah halaman berhasil dirender.

## Kesimpulan

Error disebabkan oleh typo syntax Blade pada tiga `@foreach`: penggunaan `as$team` dan `as$rq` tanpa spasi. Menambahkan spasi sesuai format `@foreach($collection as $item)` merupakan perbaikan langsung dan cukup untuk mengatasi `ViewCompilationException` ini.
