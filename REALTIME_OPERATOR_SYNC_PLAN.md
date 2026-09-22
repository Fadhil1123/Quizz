# Plan Perbaikan Sinkronisasi Admin ke Operator

## Tujuan

Operator harus menerima perubahan dari admin secara otomatis tanpa refresh browser untuk aksi berikut:

- tampilkan soal
- pause timer
- resume timer
- reset timer
- beli soal dan perubahan fase
- jawaban benar/salah dan tutup soal
- selesaikan kuis

Scope plan ini hanya alur realtime admin -> operator melalui Laravel Reverb. Polling timer lokal, scoring, autentikasi, dan tampilan lain tidak diubah kecuali diperlukan agar alur realtime bekerja.

## Diagnosis Saat Ini

### Jalur yang sudah benar

1. Controller admin memanggil `broadcastRoomState($roomId)` setelah aksi database selesai.
2. `RoomStateUpdated` memakai `ShouldBroadcastNow`, sehingga queue worker bukan syarat utama untuk pengiriman event.
3. Server mengirim ke public channel `stage-room.{roomId}` dengan event `room.state.updated`.
4. Operator subscribe ke channel yang sama dan mendengarkan `.room.state.updated`.
5. State awal tetap tersedia melalui `/api/rooms/{id}/state`.

Artinya, mismatch nama channel atau event bukan kandidat utama.

### Masalah terkonfirmasi dari log

Log Laravel berisi kegagalan broadcast berikut:

```text
Failed to broadcast Reverb event: Pusher error: cURL error 7:
Failed to connect to localhost port 8080: Couldn't connect to server
```

Konfigurasi runtime Laravel juga membaca:

- broadcaster: `reverb`
- host: `127.0.0.1`
- port: `8080`
- app id: `100001`
- app key: `reverb_app_key_local`

Kesimpulannya: event admin memang dipanggil, tetapi pada kondisi log tersebut gagal dikirim karena server Reverb tidak sedang dapat dijangkau di `localhost:8080`. Refresh browser terlihat memperbarui halaman karena mengambil state langsung dari database, bukan karena broadcast berhasil.

### Masalah konfigurasi operator yang berisiko

1. Operator meng-hardcode WebSocket ke `127.0.0.1:8080`. Jika browser operator berada di komputer berbeda, alamat tersebut menunjuk ke komputer operator, bukan ke host aplikasi/Reverb.
2. Halaman operator memuat Pusher dan Laravel Echo dua kali.
3. CDN memakai `laravel-echo@1.15.3`, sedangkan dependency proyek memakai `laravel-echo@2.5.0`. Runtime browser dan dependency build tidak konsisten.
4. `.env` memiliki beberapa blok `BROADCAST_CONNECTION`, `REVERB_APP_*`, dan `VITE_REVERB_*` dengan nilai berbeda. Nilai terakhir menjadi efektif, sehingga mudah terjadi ketidaksinkronan key antara Laravel, Reverb, dan browser.
5. Browser log hanya menunjukkan percobaan subscribe dan `Script error`, tidak menunjukkan event `room.state.updated` diterima.

## Hipotesis Akar Masalah Berdasarkan Prioritas

1. **Reverb tidak berjalan atau mati.** Ini sudah didukung oleh error cURL ke port 8080.
2. **Host WebSocket salah untuk jaringan operator.** `127.0.0.1` hanya benar jika browser operator dan proses Reverb berada pada mesin yang sama.
3. **Inisialisasi Echo/Pusher ganda dan versi CDN yang berbeda.** Ini dapat membuat koneksi tidak stabil atau error JavaScript sebelum listener aktif.
4. **Konfigurasi environment tidak tunggal atau config cache belum diperbarui.** Ini dapat membuat app key, host, dan port yang dipakai pengirim berbeda dari yang dipakai penerima.

## Rencana Implementasi

### Tahap 1: Tetapkan satu konfigurasi Reverb

1. Rapikan `.env` sehingga hanya ada satu blok `BROADCAST_CONNECTION` dan satu set `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, host, port, dan scheme.
2. Tentukan alamat yang dapat dijangkau oleh tiga pihak:
   - Laravel sebagai pengirim event
   - proses Reverb sebagai WebSocket server
   - browser operator sebagai client
3. Untuk satu komputer gunakan `127.0.0.1`; untuk komputer berbeda gunakan hostname atau IP LAN host aplikasi.
4. Samakan app id dan app key antara Laravel broadcast, konfigurasi Reverb, dan client operator.
5. Bersihkan/reload config Laravel setelah perubahan environment.

### Tahap 2: Pastikan transport Reverb hidup

1. Jalankan proses Reverb pada host aplikasi dengan `php artisan reverb:start`.
2. Pastikan port yang dipakai benar-benar listen dan tidak diblokir firewall.
3. Pastikan Laravel dapat mengirim POST broadcast ke endpoint Reverb tanpa error cURL.
4. Jangan mengubah queue sebagai solusi utama karena event saat ini memakai `ShouldBroadcastNow`.

### Tahap 3: Sederhanakan client operator

1. Hapus pemuatan CDN Pusher/Echo yang duplikat.
2. Pilih satu sumber Echo yang konsisten dengan dependency proyek, yaitu package lokal `laravel-echo` dan `pusher-js` melalui Vite, atau satu CDN yang versinya sengaja dikunci dan kompatibel. Jangan mencampur keduanya.
3. Ganti konfigurasi `wsHost` hardcode dengan nilai dari environment/config yang sesuai host Reverb sebenarnya.
4. Pertahankan kontrak yang sudah benar:
   - channel `stage-room.{roomId}`
   - event `.room.state.updated`
   - state pada `event.payload`
5. Tambahkan status koneksi/error yang mudah terlihat di console atau indikator internal agar kegagalan subscribe tidak menjadi `Script error` tanpa konteks.

### Tahap 4: Verifikasi alur end-to-end

Lakukan setelah Reverb aktif dan client operator sudah dimuat ulang:

1. Buka satu room pada admin dan operator.
2. Di console operator pastikan koneksi WebSocket berhasil dan subscription ke `stage-room.{roomId}` berhasil.
3. Pilih soal dari admin; operator harus berubah tanpa refresh.
4. Uji pause dan resume; indikator pause serta timer operator harus mengikuti.
5. Uji reset, BUY, BUY_WRONG, BUY_CORRECT/PASS_CORRECT, CLOSE, dan finish.
6. Untuk setiap aksi, pastikan:
   - tidak ada error broadcast di `storage/logs/laravel.log`
   - event `.room.state.updated` diterima browser
   - `event.payload` dirender
   - refresh manual tidak diperlukan
7. Uji juga ketika operator dibuka dari komputer berbeda pada jaringan yang sama.

## Checklist Penerimaan

- [ ] Tidak ada error `Failed to connect ... port 8080` saat admin melakukan aksi.
- [ ] Reverb berjalan stabil selama sesi kuis.
- [ ] Browser operator tersambung ke host Reverb yang benar, bukan otomatis ke localhost yang salah.
- [ ] Hanya ada satu inisialisasi Echo/Pusher pada halaman operator.
- [ ] Versi Echo yang berjalan di browser konsisten dengan dependency yang dipilih.
- [ ] Semua aksi admin dalam scope memperbarui operator tanpa refresh.
- [ ] State awal saat operator baru membuka halaman tetap tampil melalui endpoint API.
- [ ] Jika WebSocket terputus, kegagalan terlihat jelas di console/log dan tidak disamarkan sebagai halaman yang diam.

## File yang Kemungkinan Disentuh Saat Implementasi

- `resources/views/operator/stage.blade.php`
- `resources/js/echo.js`
- `resources/js/app.js`
- `.env` dan/atau `.env.example`
- `config/broadcasting.php` hanya jika konfigurasi yang disepakati memang memerlukannya

File event, route, dan controller tidak perlu diubah pada tahap awal karena kontrak broadcast di sana sudah sesuai. Perubahan pada backend baru dipertimbangkan jika verifikasi membuktikan event tetap gagal setelah Reverb dan client dikonfigurasi benar.

## Catatan Risiko

- Mengganti `127.0.0.1` dengan IP LAN tanpa mengatur firewall akan tetap membuat koneksi gagal.
- Mengubah key Reverb tanpa menyamakan semua pihak akan menghasilkan koneksi ditolak atau event tidak diterima.
- Menjalankan lebih dari satu proses Reverb pada port yang sama dapat menyebabkan proses kedua gagal start.
- Jangan mengandalkan refresh sebagai fallback realtime; refresh hanya boleh dipakai untuk mengambil state awal atau pemulihan setelah koneksi terputus.