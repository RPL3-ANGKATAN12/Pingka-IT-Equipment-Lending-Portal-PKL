# Catatan Perbaikan Portal Peminjaman IT

Perubahan yang diterapkan pada source code:

1. Tombol Completed pada halaman Loans menggunakan route `admin.loans.update-status` dan method `PUT`, sesuai `routes/web.php`.
2. Stok barang tidak ditambah saat status pinjaman berubah menjadi `selesai`. Stok baru bertambah setelah pengembalian dikonfirmasi pada halaman Returned Items, sehingga stok tidak terhitung dua kali.
3. Saat pinjaman ditolak, stok yang sebelumnya dicadangkan dilepas kembali. Status `maintenance` tetap dipertahankan.
4. Pengaturan `Hostname Required` sudah ditangani pada model `Item`, `ItemController`, tampilan item, dan migration `add_requires_hostname_to_items_table`.

## Setelah menyalin project

1. Buat file `.env` dari `.env.example`, lalu sesuaikan koneksi database lokal. File `.env` tidak disertakan dalam ZIP demi keamanan.
2. Jalankan `composer install`.
3. Pastikan database `portal_peminjaman` sudah dibuat dan konfigurasi `.env` benar.
4. Jalankan `php artisan migrate` agar kolom `requires_hostname` tersedia. Jangan jalankan `migrate:fresh` pada database berisi data karena perintah itu menghapus tabel dan data.
5. Jalankan `php artisan optimize:clear`, lalu `php artisan serve`.

Folder `vendor`, `node_modules`, `.git`, file `.env`, log, dan cache hasil runtime tidak disertakan agar ZIP lebih kecil. Source code aplikasi dan file dependency manifest tetap disertakan.
