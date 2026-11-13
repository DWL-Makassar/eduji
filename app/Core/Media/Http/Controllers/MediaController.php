<?php

declare(strict_types=1);

namespace App\Core\Media\Http\Controllers;

use App\Core\Media\Http\Requests\StoreMediaRequest;
use App\Core\Media\Http\Resources\MediaAssetResource;
use App\Core\Media\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Endpoint JSON untuk pustaka media & MediaPicker. */
class MediaController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('media.manage'), 403);

        $search = trim((string) $request->query('q'));

        $assets = MediaAsset::query()
            ->with('media')
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(24);

        return MediaAssetResource::collection($assets);
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $upload = $request->file('file');

        $asset = MediaAsset::create([
            'title' => $request->input('title') ?: pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME),
            'uploaded_by' => $request->user()->id,
        ]);

        try {
            $asset->addMedia($upload)
                ->usingFileName($this->safeFileName($upload->getClientOriginalName()))
                ->withCustomProperties(['alt' => $request->input('alt')])
                ->toMediaCollection(MediaAsset::COLLECTION);
        } catch (Throwable $e) {
            // Mis. gambar rusak yang lolos pemeriksaan tipe tetapi gagal dibuatkan thumbnail. Jangan tinggalkan entri
            // pustaka tanpa berkas (delete() ikut menghapus berkas yang sempat tersimpan).
            report($e);
            $asset->delete();

            throw ValidationException::withMessages(['file' => __('media.unreadable')]);
        }

        return MediaAssetResource::make($asset->refresh())->response()->setStatusCode(201);
    }

    public function destroy(Request $request, MediaAsset $media): JsonResponse
    {
        Gate::allowIf($request->user()->can('media.manage'));

        $media->delete();

        return response()->json(status: 204);
    }

    /** Nama unik + ekstensi tervalidasi; nama asli pengguna tidak dipakai di path. */
    private function safeFileName(string $original): string
    {
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        return bin2hex(random_bytes(8)).'.'.$extension;
    }
}
