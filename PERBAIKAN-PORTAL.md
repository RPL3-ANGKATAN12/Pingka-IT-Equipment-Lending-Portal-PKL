# Catatan Perbaikan Portal Peminjaman IT

Perubahan yang diterapkan pada source code:

1. Route `/request` dan `/track/{token}` sudah memiliki handler yang berfungsi. Route request membuka form pada halaman utama, dan admin dapat membuka halaman tracking dari data loan.
2. Data publik loan menampilkan nama dan departemen sesuai kebutuhan portal, tetapi tetap menyembunyikan email, catatan, hostname, model, dan serial number. API item hanya mengirim field inventaris yang diperlukan. Endpoint login, pengajuan, dan tracking juga diberi rate limit.
3. Approval request sekarang berjalan dalam transaksi dengan penguncian request dan stok barang. Barang berstatus maintenance tidak dapat dipinjam.
4. Pengubahan target stok mempertahankan jumlah pinjaman aktif, serial number yang sudah pernah dikembalikan dapat dipakai lagi, dan validasi panjang field disamakan dengan skema database.
5. Alias middleware employee yang merujuk ke class yang tidak ada dihapus karena tidak dipakai route mana pun dan tabel pengguna hanya mendukung role admin.
6. Dependency frontend diperbarui dalam batas versi yang diizinkan; `npm audit` tidak lagi melaporkan kerentanan.
7. Form tambah dan edit item mendukung input beberapa Serial Number sekaligus (satu nomor per baris). Saat edit, serial yang sudah terdaftar mengisi slot Target Stock; hanya slot tersisa yang dapat diisi, dan serial lama tidak boleh dimasukkan ulang.
8. Admin dapat memilih beberapa request pending pada halaman Request lalu approve atau reject sekaligus. Approval yang stoknya tidak tersedia dilewati dan dilaporkan, sementara request lain yang masih valid tetap diproses.
9. Aksi bulk dilengkapi modal konfirmasi dan toolbar responsif yang menampilkan jumlah request terpilih sebelum perubahan diterapkan.

## Hasil verifikasi

- `php artisan test`: 11 test lulus (59 assertions), menggunakan SQLite in-memory dan menjalankan seluruh migration.
- `php artisan view:cache`: seluruh template Blade berhasil dikompilasi.
- `npm run build`: build Vite berhasil.
- `npm audit`: 0 vulnerability setelah pembaruan `package-lock.json`.

## Pengujian manual yang disarankan

1. Jalankan migration dan aplikasi lokal, lalu pastikan halaman Items, Loans, Request, dan Return dapat dibuka.
2. Ajukan peminjaman dari modal Request, lalu approve dari admin. Pastikan stok berkurang sekali; coba approve ulang dan pastikan stok tidak berubah.
3. Ubah target stok pada item yang sedang dipinjam. Pastikan stok tersedia tetap memperhitungkan jumlah loan aktif dan target di bawah jumlah loan aktif ditolak.
4. Uji item dengan Serial Number: pinjamkan, konfirmasi pengembalian, lalu gunakan kembali serial number yang sama.
5. Buka halaman `/loans` dan `/api/items` tanpa login. Pastikan nama dan departemen tampil pada `/loans`, sementara email, catatan, hostname, model, dan serial number tetap tidak ditampilkan.
6. Dari halaman admin Loans, buka tautan Track dan pastikan status loan tampil tanpa informasi pribadi peminjam.
7. Tambahkan item dengan pelacakan Serial Number aktif dan masukkan beberapa serial, hanya sebagian, atau tanpa serial. Pastikan jumlah tersedia sesuai serial yang sudah didaftarkan dan serial sisanya bisa ditambahkan kemudian.
8. Pilih beberapa request pending, lalu uji Approve Selected dan Reject Selected. Pastikan stok terpotong hanya untuk request yang berhasil di-approve dan tidak berubah untuk request yang ditolak atau dilewati.

Catatan perubahan yang telah ada sebelumnya:

1. Tombol Completed pada halaman Loans menggunakan route `admin.loans.update-status` dan method `PUT`, sesuai `routes/web.php`.
2. Stok barang tidak ditambah saat status pinjaman berubah menjadi `selesai`. Stok baru bertambah setelah pengembalian dikonfirmasi pada halaman Returned Items, sehingga stok tidak terhitung dua kali.
3. Saat pinjaman ditolak, stok yang sebelumnya dicadangkan dilepas kembali. Status `maintenance` tetap dipertahankan.
4. Pengaturan `Hostname Required` sudah ditangani pada model `Item`, `ItemController`, tampilan item, dan migration `add_requires_hostname_to_items_table`.

## Setelah menyalin project

1. Buat file `.env` dari `.env.example`, lalu sesuaikan koneksi database lokal. File `.env` tidak disertakan dalam ZIP demi keamanan.
2. Jalankan `composer install`.
3. Jalankan `npm ci` dan `npm run build` untuk memasang dependency frontend dan membuat aset produksi.
4. Pastikan database `portal_peminjaman` sudah dibuat dan konfigurasi `.env` benar.
5. Jalankan `php artisan migrate` agar semua perubahan skema (termasuk `requires_hostname`, Serial Number, dan `target_stock`) diterapkan. Jangan jalankan `migrate:fresh` pada database berisi data karena perintah itu menghapus tabel dan data.
6. Untuk produksi, pastikan `APP_ENV=production`, `APP_DEBUG=false`, dan cookie session hanya dikirim melalui HTTPS.
7. Jalankan `php artisan optimize:clear`, lalu `php artisan serve`.

Folder `vendor`, `node_modules`, `.git`, file `.env`, log, dan cache hasil runtime tidak disertakan agar ZIP lebih kecil. Source code aplikasi dan file dependency manifest tetap disertakan.
