<?php

declare(strict_types=1);

namespace App\Website\Sections;

use App\Cms\Downloads\Download;
use App\Cms\Support\Field;
use App\Core\Settings\SettingsStore;
use App\Website\Http\Presenter;

/**
 * Section isi bebas untuk website sekolah/lembaga: isinya diketik admin di pengaturan blok
 * (kecuali "Unduhan terbaru" yang mengambil data CMS). Resolver mengembalikan null bila belum ada isi.
 */
final class ContentSections
{
    /** Ikon yang bisa dipilih untuk kartu info (nama = kunci di resources/js/components/ui/AppIcon.vue). */
    public const ICONS = ['star', 'shield', 'users', 'calendar', 'newspaper', 'megaphone', 'image', 'file', 'download', 'mail', 'home', 'layers'];

    public static function register(SectionRegistry $sections): void
    {
        // Field dibuat lewat closure (dijalankan per permintaan) agar labelnya mengikuti bahasa admin.
        $title = fn () => Field::text('title', label: 'website.fields.title');
        $image = fn (string $name = 'image') => Field::media($name, label: 'website.fields.image');
        $button = fn () => [Field::text('button_label', label: 'website.fields.button_label'), Field::url('button_url', label: 'website.fields.button_url')];
        $sides = ['default' => 'website.variants.image_left', 'right' => 'website.variants.image_right'];

        $sections->register('welcome', 'website.sections.welcome', fn (array $s) => filled($s['body']), 15, fn () => [
            $title(),
            $image('photo'),
            Field::text('name', label: 'website.fields.name'),
            Field::text('role', label: 'website.fields.role'),
            Field::richtext('body', required: true, label: 'website.fields.body'),
        ], $sides, 'users', automatic: false);

        $sections->register('image_text', 'website.sections.image_text', fn (array $s) => filled($s['title']) || filled($s['body']), 16, fn () => [
            $title(),
            Field::richtext('body', label: 'website.fields.body'),
            $image(),
            ...$button(),
        ], $sides, 'image', automatic: false);

        $sections->register('stats', 'website.sections.stats', fn (array $s) => $s['items'] !== [], 17, fn () => [
            $title(),
            Field::repeater('items', [
                Field::text('value', required: true, label: 'website.fields.value'),
                Field::text('label', required: true, label: 'website.fields.label'),
            ], 'website.fields.stat', 'website.fields.items'),
        ], ['default' => 'website.variants.cards', 'band' => 'website.variants.band'], 'layers', automatic: false);

        $sections->register('cards', 'website.sections.cards', fn (array $s) => $s['items'] !== [], 25, fn () => [
            $title(),
            Field::repeater('items', [
                Field::select('icon', array_combine(self::ICONS, array_map(fn (string $i) => "website.icons.{$i}", self::ICONS)), label: 'website.fields.icon'),
                Field::text('title', required: true, label: 'website.fields.title'),
                Field::textarea('text', rows: 2, label: 'website.fields.text'),
                Field::url('url', label: 'website.fields.link'),
            ], 'website.fields.card', 'website.fields.items'),
        ], ['default' => 'website.variants.cards', 'list' => 'website.variants.list'], 'star', automatic: false);

        $sections->register('downloads', 'website.sections.downloads', fn (array $s) => Download::active()->with('file.media')->latest('id')
            ->limit((int) ($s['limit'] ?? 5))->get()->map(Presenter::download(...))->all(), 65, fn () => [
                Field::text('title', label: 'website.fields.title', help: 'website.fields.title_help'),
                Field::number('limit', label: 'website.fields.limit'),
            ], icon: 'download');

        $sections->register('logos', 'website.sections.logos', fn (array $s) => $s['items'] !== [], 75, fn () => [
            $title(),
            Field::repeater('items', [
                Field::media('image', required: true, label: 'website.fields.image'),
                Field::text('name', label: 'website.fields.name'),
                Field::url('url', label: 'website.fields.link'),
            ], 'website.fields.logo', 'website.fields.items'),
        ], icon: 'image', automatic: false);

        $sections->register('faq', 'website.sections.faq', fn (array $s) => $s['items'] !== [], 85, fn () => [
            $title(),
            Field::repeater('items', [
                Field::text('question', required: true, label: 'website.fields.question'),
                Field::textarea('answer', required: true, rows: 3, label: 'website.fields.answer'),
            ], 'website.fields.question', 'website.fields.items'),
        ], icon: 'list', automatic: false);

        $sections->register('video', 'website.sections.video', fn (array $s) => self::youtubeEmbed($s['url']), 86, fn () => [
            $title(),
            Field::url('url', required: true, label: 'website.fields.video_url', help: 'website.fields.video_help'),
            Field::text('caption', label: 'website.fields.caption'),
        ], icon: 'image', automatic: false);

        $sections->register('map', 'website.sections.map', fn (array $s) => self::contact($s), 95, fn () => [
            $title(),
            Field::textarea('address', rows: 2, label: 'website.fields.address'),
            Field::text('phone', label: 'website.fields.phone'),
            Field::text('email', label: 'website.fields.email'),
            Field::text('map_query', label: 'website.fields.map_query', help: 'website.fields.map_help'),
        ], ['default' => 'website.variants.side', 'stacked' => 'website.variants.stacked'], 'mail');
    }

    /** URL embed (youtube-nocookie) dari tautan YouTube biasa/pendek/shorts; null bila bukan YouTube. */
    public static function youtubeEmbed(?string $url): ?string
    {
        $found = preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~', (string) $url, $m);

        return $found ? "https://www.youtube-nocookie.com/embed/{$m[1]}" : null;
    }

    /**
     * Kontak dari pengaturan blok, jatuh ke Pengaturan situs (alamat/telepon/email) bila kosong.
     *
     * @param  array<string, mixed>  $s
     * @return array{address: ?string, phone: ?string, email: ?string, map_url: ?string}|null
     */
    private static function contact(array $s): ?array
    {
        $general = app(SettingsStore::class);
        $value = fn (string $key) => ($s[$key] ?? null) ?: ($general->get('general', $key) ?: null);
        $data = ['address' => $value('address'), 'phone' => $value('phone'), 'email' => $value('email')];
        $query = $s['map_query'] ?: $data['address'];

        $data['map_url'] = $query ? 'https://maps.google.com/maps?output=embed&q='.rawurlencode($query) : null;

        return array_filter($data) === [] ? null : $data;
    }
}
