<?php

declare(strict_types=1);

namespace App\Cms\Contact;

use App\Cms\Support\Column;
use App\Core\Support\Paginate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** Kotak masuk pesan kontak (admin): daftar, baca, hapus. */
class ContactMessageController
{
    public function index(Request $request): Response
    {
        Gate::authorize('contact.view');

        $messages = ContactMessage::query()->latest('id')->paginate(config('cms.per_page'))->withQueryString();

        return Inertia::render('admin/cms/Index', [
            'resource' => $this->meta() + ['rowAction' => 'show'],
            'columns' => [Column::text('name'), Column::text('subject'), Column::boolean('is_read'), Column::datetime('created_at')],
            'rows' => Paginate::shape($messages, fn (ContactMessage $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'subject' => $m->subject,
                'is_read' => $m->is_read,
                'created_at' => $m->created_at?->toIso8601String(),
                'can' => ['update' => true, 'delete' => Gate::allows('contact.delete')],
            ]),
            'filters' => ['q' => ''],
            'searchable' => false,
            'can' => ['create' => false],
        ]);
    }

    public function show(string $id): Response
    {
        Gate::authorize('contact.view');

        $message = ContactMessage::query()->findOrFail($id);

        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return Inertia::render('admin/cms/Show', [
            'resource' => $this->meta(),
            'recordId' => $message->id,
            'canDelete' => Gate::allows('contact.delete'),
            'details' => [
                ['label' => __('cms.fields.name'), 'value' => $message->name],
                ['label' => __('cms.fields.email'), 'value' => $message->email],
                ['label' => __('cms.fields.subject'), 'value' => $message->subject],
                ['label' => __('cms.fields.created_at'), 'value' => $message->created_at?->toIso8601String(), 'type' => 'datetime'],
                ['label' => __('cms.fields.message'), 'value' => $message->message, 'type' => 'multiline'],
            ],
        ]);
    }

    public function destroy(string $id): RedirectResponse
    {
        Gate::authorize('contact.delete');

        ContactMessage::query()->findOrFail($id)->delete();

        return to_route('admin.contact.index')->with('success', __('cms.messages.deleted', ['name' => __('cms.singular.contact')]));
    }

    /** @return array{key: string, title: string, singular: string, routeBase: string} */
    private function meta(): array
    {
        return [
            'key' => 'contact',
            'title' => __('cms.resources.contact'),
            'singular' => __('cms.singular.contact'),
            'routeBase' => 'admin.contact',
        ];
    }
}
