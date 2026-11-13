<?php

declare(strict_types=1);

return [
    'failed' => 'Identitas tersebut tidak cocok dengan data kami.',
    'password' => 'Kata sandi salah.',
    'public_password' => 'Sandi akun demo hanya bisa dipakai dari komputer sendiri (localhost). Untuk situs online, isi ADMIN_EMAIL dan ADMIN_PASSWORD sendiri di .env, jalankan php artisan db:seed --force, lalu masuk dengan akun itu.',
    'throttle' => 'Terlalu banyak upaya masuk. Silahkan coba lagi dalam :seconds detik.',
];
