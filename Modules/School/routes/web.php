<?php

use App\Website\Http\Middleware\ShareSiteData;
use Illuminate\Support\Facades\Route;
use Modules\School\Http\Controllers\AlumniFormController;
use Modules\School\Http\Controllers\SchoolPageController;
use Modules\School\Http\Controllers\SpmbController;

// Halaman daftar publik. `kind` lewat defaults() (tanpa parameter URI, jadi urutan argumen aman).
Route::middleware(ShareSiteData::class)->group(function () {
    Route::get('guru-staf', [SchoolPageController::class, 'index'])->defaults('kind', 'staff')->name('school.staff');
    Route::get('prestasi', [SchoolPageController::class, 'index'])->defaults('kind', 'achievements')->name('school.achievements');
    Route::get('ekstrakurikuler', [SchoolPageController::class, 'index'])->defaults('kind', 'extracurriculars')->name('school.extracurriculars');
    Route::get('jurusan', [SchoolPageController::class, 'index'])->defaults('kind', 'majors')->name('school.majors');
    Route::get('unit', [SchoolPageController::class, 'index'])->defaults('kind', 'units')->name('school.units');
    Route::get('alumni', [SchoolPageController::class, 'index'])->defaults('kind', 'alumni')->name('school.alumni');

    // Pendataan alumni: kiriman masuk nonaktif sampai disetujui admin.
    Route::get('alumni/daftar', [AlumniFormController::class, 'create'])->name('school.alumni.create');
    Route::post('alumni/daftar', [AlumniFormController::class, 'store'])->middleware('throttle:5,1,alumni')->name('school.alumni.store');

    // SPMB. Kirim formulir & cek status dibatasi per IP (spam, tebak nomor), kiriman juga maks. 20/hari/IP (±7 MB berkas
    // per kiriman; disk tidak boleh bisa dipenuhi). Setiap limiter punya awalan sendiri: fitur lain tidak menghabiskan jatahnya.
    // Kartu & foto hanya lewat URL bertanda tangan.
    Route::get('spmb', [SpmbController::class, 'index'])->name('school.spmb');
    Route::post('spmb', [SpmbController::class, 'store'])->middleware(['throttle:5,1,spmb', 'throttle:20,1440,spmb-day'])->name('school.spmb.store');
    Route::get('spmb/selesai', [SpmbController::class, 'done'])->name('school.spmb.done');
    Route::get('spmb/status', [SpmbController::class, 'status'])->name('school.spmb.status');
    Route::post('spmb/status', [SpmbController::class, 'check'])->middleware('throttle:10,1,spmb-status')->name('school.spmb.check');
    Route::get('spmb/kartu/{registrant}', [SpmbController::class, 'card'])->middleware('signed')->name('school.spmb.card');
    Route::get('spmb/foto/{registrant}', [SpmbController::class, 'photo'])->middleware('signed')->name('school.spmb.photo');
});
