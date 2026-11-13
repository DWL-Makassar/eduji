<?php

use Illuminate\Support\Facades\Route;
use Modules\School\Http\Controllers\Admin\AchievementController;
use Modules\School\Http\Controllers\Admin\AlumniController;
use Modules\School\Http\Controllers\Admin\ExtracurricularController;
use Modules\School\Http\Controllers\Admin\MajorController;
use Modules\School\Http\Controllers\Admin\RegistrantController;
use Modules\School\Http\Controllers\Admin\SpmbSettingsController;
use Modules\School\Http\Controllers\Admin\StaffController;
use Modules\School\Http\Controllers\Admin\UnitController;
use Modules\School\Models\Registrant;

// Prefix "admin", nama "admin.", middleware web + auth (ModuleServiceProvider). Kunci = prefix permission.
Route::resource('school-staff', StaffController::class)->except('show');
Route::resource('school-achievements', AchievementController::class)->except('show');
Route::resource('school-extracurriculars', ExtracurricularController::class)->except('show');
Route::resource('school-majors', MajorController::class)->except('show');
Route::resource('school-units', UnitController::class)->except('show');
Route::resource('school-alumni', AlumniController::class)->except('show');

// SPMB
Route::resource('school-registrants', RegistrantController::class)->only(['index', 'show', 'destroy'])->parameters(['school-registrants' => 'registrant']);
Route::put('school-registrants/{registrant}/status', [RegistrantController::class, 'updateStatus'])->name('school-registrants.status');
Route::get('school-registrants/{registrant}/documents/{type}', [RegistrantController::class, 'document'])
    ->whereIn('type', array_keys(Registrant::DOCUMENTS))->name('school-registrants.document');
Route::get('school-spmb', [SpmbSettingsController::class, 'edit'])->name('school-spmb.edit');
Route::put('school-spmb', [SpmbSettingsController::class, 'update'])->name('school-spmb.update');
