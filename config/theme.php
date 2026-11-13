<?php

/*
| Preset tema: token warna utama, font, dan sudut (komponen memakai `primary-*` dari Tailwind @theme, lihat resources/css/app.css).
| Tema aktif disimpan di setting `website.theme`; token ditulis sebagai CSS variable di <head> (tanpa JS, tanpa flash).
| Menambah tema = tambah satu entri di sini + label di lang/<bahasa>/website.php (`themes.<kunci>`).
| Teks putih di atas `primary-600` harus lolos kontras WCAG AA (≥ 4.5:1).
*/
return [
    // Hijau seperti tampilan Formal sebagai bawaan.
    'default' => 'islamic',

    'themes' => [
        'default' => [
            '50' => '#eef2ff', '100' => '#e0e7ff', '200' => '#c7d2fe', '300' => '#a5b4fc', '400' => '#818cf8',
            '500' => '#6366f1', '600' => '#4f46e5', '700' => '#4338ca', '800' => '#3730a3', '900' => '#312e81',
        ],
        'education' => [
            '50' => '#eff6ff', '100' => '#dbeafe', '200' => '#bfdbfe', '300' => '#93c5fd', '400' => '#60a5fa',
            '500' => '#3b82f6', '600' => '#2563eb', '700' => '#1d4ed8', '800' => '#1e40af', '900' => '#1e3a8a',
        ],
        'corporate' => [
            '50' => '#f8fafc', '100' => '#f1f5f9', '200' => '#e2e8f0', '300' => '#cbd5e1', '400' => '#94a3b8',
            '500' => '#64748b', '600' => '#475569', '700' => '#334155', '800' => '#1e293b', '900' => '#0f172a',
        ],
        'institution' => [
            '50' => '#fff1f2', '100' => '#ffe4e6', '200' => '#fecdd3', '300' => '#fda4af', '400' => '#fb7185',
            '500' => '#f43f5e', '600' => '#e11d48', '700' => '#be123c', '800' => '#9f1239', '900' => '#881337',
        ],
        // 600 digeser lebih gelap dari skala Tailwind aslinya agar teks putih lolos AA.
        'islamic' => [
            '50' => '#f0fdf4', '100' => '#dcfce7', '200' => '#bbf7d0', '300' => '#86efac', '400' => '#4ade80',
            '500' => '#22c55e', '600' => '#15803d', '700' => '#166534', '800' => '#14532d', '900' => '#052e16',
        ],
        'vocational' => [
            '50' => '#fff7ed', '100' => '#ffedd5', '200' => '#fed7aa', '300' => '#fdba74', '400' => '#fb923c',
            '500' => '#f97316', '600' => '#c2410c', '700' => '#9a3412', '800' => '#7c2d12', '900' => '#431407',
        ],
        'playful' => [
            '50' => '#fdf2f8', '100' => '#fce7f3', '200' => '#fbcfe8', '300' => '#f9a8d4', '400' => '#f472b6',
            '500' => '#ec4899', '600' => '#db2777', '700' => '#be185d', '800' => '#9d174d', '900' => '#831843',
        ],
    ],

    /*
    | Font situs (setting `website.font`). `google` = parameter family Google Fonts; null = font sistem (tanpa unduhan).
    */
    'fonts' => [
        'system' => ['stack' => "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif", 'google' => null],
        'jakarta' => ['stack' => "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif", 'google' => 'Plus+Jakarta+Sans:wght@400;500;600;700'],
        'poppins' => ['stack' => "'Poppins', ui-sans-serif, system-ui, sans-serif", 'google' => 'Poppins:wght@400;500;600;700'],
        'nunito' => ['stack' => "'Nunito', ui-sans-serif, system-ui, sans-serif", 'google' => 'Nunito:wght@400;600;700;800'],
        'lora' => ['stack' => "'Lora', Georgia, 'Times New Roman', serif", 'google' => 'Lora:wght@400;500;600;700'],
    ],

    /*
    | Desain beranda (setting `website.design`): susunan visual seksi situs publik. one … ten = Desain 1–10.
    | Komponennya di resources/js/components/site/sections (Desain 1) dan sections/<kunci>/ (desain lain, ditemukan otomatis).
    | Menambah desain = entri di sini + label di lang/<bahasa>/website.php (`designs.<kunci>`) + folder komponennya.
    */
    'designs' => ['one' => []],

    /* Sudut tombol/input (control) dan kartu (card), setting `website.radius`. */
    'radii' => [
        'sharp' => ['control' => '0.125rem', 'card' => '0.25rem'],
        'default' => ['control' => '0.5rem', 'card' => '0.75rem'],
        'round' => ['control' => '0.875rem', 'card' => '1.5rem'],
    ],
];
