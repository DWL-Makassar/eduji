# EduJi: Website Sekolah (Edisi Gratis)

Website sekolah lengkap yang bisa disusun sendiri tanpa coding: beranda, berita, pengumuman, agenda, galeri, unduhan,
kontak, data guru & staf, prestasi, ekstrakurikuler, jurusan, alumni, dan pendaftaran siswa baru (SPMB) online.

Edisi gratis memakai desain beranda **Formal (Desain 1)** dan aktif untuk **1 domain sekolah** dengan kunci lisensi gratis.
Paket berbayar (11 desain beranda, source code, atau dipasangkan sampai online): <https://eduji.pages.dev>

## Coba di komputer sendiri

Butuh PHP 8.2+ (mis. [Laragon](https://laragon.org) atau XAMPP) dan MySQL 8.

```bash
cp .env.example .env          # Windows: copy .env.example .env
# isi DB_DATABASE, DB_USERNAME, DB_PASSWORD di .env (buat dulu database kosongnya)
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka <http://127.0.0.1:8000/login> dan masuk dengan **akun demo**:

| Email | Sandi |
|-------|-------|
| `admin@sekolah.test` | `eduji-demo` |

Akun demo **hanya bisa dipakai dari komputer sendiri (localhost)**. Di komputer sendiri panel admin juga terbuka tanpa kunci lisensi.

## Pasang di hosting sekolah

Ikuti **PANDUAN-INSTALASI.txt**. Sebelum menjalankan `php artisan migrate --seed`, ganti `ADMIN_EMAIL` dan `ADMIN_PASSWORD`
di `.env` dengan email & sandi kuat milik sekolah: sandi demo ditolak di domain online.

## Ketentuan

Boleh dipakai untuk satu website sekolah pada domain yang terdaftar. Tidak boleh dijual ulang, dibagikan ulang,
atau diubah untuk melewati kunci lisensi.
