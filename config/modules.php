<?php

return [
    /*
    | Modul yang aktif untuk proyek ini (nama folder di Modules/), mis. 'Company'.
    | Tambahkan di daftar ini (di-commit per proyek) atau lewat .env: MODULES=Company,Catalog.
    | Modules/Example adalah modul contoh/kerangka (lihat docs/modules.md); tidak aktif secara bawaan.
    | Tidak ada lisensi/gating: modul aktif jika terdaftar.
    */
    'enabled' => [
        ...array_filter(array_map('trim', explode(',', (string) env('MODULES', '')))),
    ],
];
