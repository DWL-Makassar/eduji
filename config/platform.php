<?php

return [
    /*
    | Bahasa UI yang tersedia (kode => label). Default & fallback diatur di config/app.php
    | (APP_LOCALE / APP_FALLBACK_LOCALE). Menambah bahasa = tambah entri di sini
    | + berkas lang/<kode>/ + resources/js/locales/<kode>.json.
    */
    'locales' => [
        'id' => 'Bahasa Indonesia',
        'en' => 'English',
    ],

    /*
    | Akun super admin awal untuk `php artisan db:seed`. Tidak ada installer:
    | isi ADMIN_* di .env lalu jalankan seeder.
    */
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'super_admin_role' => 'super-admin',

    /*
    | Proxy/CDN yang dipercaya meneruskan header X-Forwarded-* (Cloudflare, Nginx sebagai reverse proxy, load balancer).
    | Kosong = tidak ada proxy. Diisi daftar IP/CIDR dipisah koma, atau "*" (semua; hanya bila server tidak bisa dijangkau
    | langsung dari internet). Tanpa ini, di belakang proxy yang mengubah HTTPS menjadi HTTP, URL kanonis, sitemap, dan HSTS
    | tetap memakai http://. Dibaca lewat config (bukan env() langsung) agar tetap bekerja setelah `config:cache`.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    /*
    | Mode demo (situs contoh untuk calon pembeli). Bila aktif: kredensial admin (ADMIN_EMAIL/ADMIN_PASSWORD) ditampilkan
    | di halaman login, banner "situs demo" tampil, aksi di `blocked` ditolak (pengguna, role, pengaturan situs, reset password),
    | dan `php artisan demo:reset` (dijadwalkan tiap malam) mengosongkan database & berkas lalu mengisi ulang dengan `seeder`.
    | JANGAN aktifkan di situs sungguhan: demo:reset menghapus semua data.
    */
    'demo' => [
        'enabled' => (bool) env('DEMO_MODE', false),
        'seeder' => env('DEMO_SEEDER', 'Database\\Seeders\\DemoSeeder'),
        // Pengguna/role/pengaturan/reset password: bisa mengunci pengunjung lain. Formulir publik & unggah media: semua pengunjung
        // demo memakai akun admin yang sama, jadi data yang dikirim akan terbaca orang lain dan berkas akan di-host di domain demo.
        'blocked' => [
            'admin.users.*', 'admin.roles.*', 'admin.settings.update', 'password.email', 'password.store',
            'contact.store', 'school.spmb.store', 'school.alumni.store', 'admin.media.store',
        ],
    ],
];
