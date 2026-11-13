<?php

declare(strict_types=1);

namespace Modules\School\Http\Controllers;

use App\Website\Seo\SeoBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Modules\School\Http\Requests\SpmbRequest;
use Modules\School\Models\Major;
use Modules\School\Models\Registrant;
use Modules\School\Spmb;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * SPMB publik: formulir pendaftaran, halaman selesai, cek status, dan kartu pendaftaran.
 * Kartu & pas foto hanya lewat URL bertanda tangan yang kedaluwarsa (tidak bisa ditebak dari id).
 */
class SpmbController
{
    public function __construct(private readonly Spmb $spmb, private readonly SeoBuilder $seo) {}

    public function index(): Response
    {
        return Inertia::render('School::site/Spmb', [
            'open' => $this->spmb->isOpen(),
            'year' => $this->spmb->academicYear(),
            'info' => $this->spmb->info(),
            'majors' => Major::query()->active()->ordered()->get(['id', 'name']),
            'labels' => __('school::messages.spmb.fields'),
            'genders' => __('school::messages.spmb.genders'),
            'documents' => collect(Registrant::DOCUMENTS)->map(fn (bool $required, string $type) => [
                'type' => $type, 'label' => __("school::messages.spmb.documents.{$type}"), 'required' => $required,
            ])->values(),
            'seo' => $this->seo->make(__('school::messages.spmb.title'))->toArray(),
        ]);
    }

    public function store(SpmbRequest $request): RedirectResponse
    {
        if (! $this->spmb->isOpen()) {
            return back()->with('error', __('school::messages.spmb.closed'));
        }

        $data = $request->safe()->except(['documents', 'website']);
        $folder = null;

        try {
            $registrant = DB::transaction(function () use ($request, $data, &$folder) {
                $registrant = Registrant::create($data + ['academic_year' => $this->spmb->academicYear(), 'status' => 'submitted']);
                $folder = $registrant->folder();

                $documents = [];
                foreach (array_keys(Registrant::DOCUMENTS) as $type) {
                    if ($file = $request->file("documents.{$type}")) {
                        $documents[$type] = $file->store($registrant->folder(), Registrant::DISK);
                    }
                }

                $registrant->forceFill([
                    'access_code' => Registrant::generateAccessCode(),
                    'documents' => $documents,
                    'number' => sprintf('%s-%04d', $this->spmb->numberPrefix(), $registrant->id),
                ])->save();

                return $registrant;
            });
        } catch (Throwable $e) {
            // Transaksi dibatalkan, tapi berkas yang sempat tersimpan tidak ikut; bersihkan agar tidak ada berkas yatim.
            if ($folder !== null) {
                Storage::disk(Registrant::DISK)->deleteDirectory($folder);
            }

            throw $e;
        }

        return to_route('school.spmb.done')->with('spmb_registrant', $registrant->id);
    }

    public function done(Request $request): Response|RedirectResponse
    {
        $registrant = Registrant::find($request->session()->get('spmb_registrant'));

        if ($registrant === null) {
            return to_route('school.spmb.status');
        }

        return Inertia::render('School::site/SpmbDone', [
            'number' => $registrant->number,
            'accessCode' => $registrant->access_code,
            'name' => $registrant->full_name,
            'cardUrl' => $this->cardUrl($registrant),
            'seo' => $this->seo->make(__('school::messages.spmb.title'))->toArray(),
        ]);
    }

    public function status(): Response
    {
        return Inertia::render('School::site/SpmbStatus', [
            'result' => null,
            'seo' => $this->seo->make(__('school::messages.spmb.status_title'))->toArray(),
        ]);
    }

    /**
     * Nomor pendaftaran + kode akses harus cocok. Pesan gagal sama untuk semua kasus (tidak membocorkan nomor yang ada),
     * dan setiap nomor dikunci 15 menit setelah 5 kali gagal, dari IP mana pun (selain batas per IP di route).
     */
    public function check(Request $request): Response|RedirectResponse
    {
        $input = $request->validate([
            'number' => ['required', 'string', 'max:32'],
            'access_code' => ['required', 'string', 'max:16'],
        ]);

        $number = strtoupper(trim($input['number']));
        $lock = 'spmb-status:'.$number;

        if (RateLimiter::tooManyAttempts($lock, 5)) {
            return back()->withErrors(['number' => __('school::messages.spmb.locked')])->withInput($request->only('number'));
        }

        $registrant = Registrant::query()->with('major:id,name')->where('number', $number)->first();
        $code = strtoupper(preg_replace('/\s+/', '', $input['access_code']));

        if ($registrant === null || ! is_string($registrant->access_code) || ! hash_equals($registrant->access_code, $code)) {
            RateLimiter::hit($lock, 15 * 60);

            return back()->withErrors(['number' => __('school::messages.spmb.not_found')])->withInput($request->only('number'));
        }

        RateLimiter::clear($lock);

        return Inertia::render('School::site/SpmbStatus', [
            'result' => [
                'number' => $registrant->number,
                'name' => $registrant->full_name,
                'major' => $registrant->major?->name,
                'status' => $registrant->status,
                'status_label' => __("school::messages.spmb.statuses.{$registrant->status}"),
                'note' => $registrant->note,
                'cardUrl' => $this->cardUrl($registrant),
            ],
            'seo' => $this->seo->make(__('school::messages.spmb.status_title'))->toArray(),
        ]);
    }

    public function card(Registrant $registrant): Response
    {
        $registrant->load('major:id,name');

        return Inertia::render('School::site/SpmbCard', [
            'labels' => __('school::messages.spmb.fields'),
            'registrant' => [
                'number' => $registrant->number,
                'academic_year' => $registrant->academic_year,
                'full_name' => $registrant->full_name,
                'nisn' => $registrant->nisn,
                'birth' => $registrant->birth_place.', '.$registrant->birth_date->translatedFormat('j F Y'),
                'previous_school' => $registrant->previous_school,
                'major' => $registrant->major?->name,
                'registered_at' => $registrant->created_at?->toIso8601String(),
                'photo' => $registrant->documentPath('photo') ? URL::temporarySignedRoute('school.spmb.photo', now()->addHour(), $registrant) : null,
            ],
            'seo' => $this->seo->make(__('school::messages.spmb.card_title'))->toArray(),
        ]);
    }

    public function photo(Registrant $registrant): StreamedResponse
    {
        $path = $registrant->documentPath('photo');
        abort_unless($path && Storage::disk(Registrant::DISK)->exists($path), 404);

        return Storage::disk(Registrant::DISK)->response($path);
    }

    private function cardUrl(Registrant $registrant): string
    {
        return URL::temporarySignedRoute('school.spmb.card', now()->addDay(), $registrant);
    }
}
