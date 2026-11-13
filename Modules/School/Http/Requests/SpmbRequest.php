<?php

declare(strict_types=1);

namespace Modules\School\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Modules\School\Models\Registrant;

/** Formulir pendaftaran SPMB (publik, tanpa login). */
class SpmbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'nisn' => ['nullable', 'digits:10'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'after:1990-01-01', 'before:today'],
            'previous_school' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'regex:/^[0-9+()\-\s]{6,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'major_id' => ['nullable', Rule::exists('school_majors', 'id')->where('is_active', true)],
            'website' => ['nullable', 'max:0'], // honeypot: manusia tidak melihat field ini (sama seperti form kontak)
        ];

        foreach (Registrant::DOCUMENTS as $type => $required) {
            // Berkas dicek dari isinya (mime), bukan hanya ekstensi. Pas foto harus gambar karena tampil di kartu.
            $rules["documents.{$type}"] = [
                $required ? 'required' : 'nullable',
                $type === 'photo' ? File::types(['jpg', 'jpeg', 'png'])->max(1024) : File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(2048),
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return collect(Registrant::DOCUMENTS)->keys()
            ->mapWithKeys(fn (string $type) => ["documents.{$type}" => __("school::messages.spmb.documents.{$type}")])
            ->all() + collect(['full_name', 'nisn', 'gender', 'birth_place', 'birth_date', 'previous_school', 'address', 'parent_name', 'parent_phone', 'email', 'major_id'])
            ->mapWithKeys(fn (string $field) => [$field => __("school::messages.spmb.fields.{$field}")])
            ->all();
    }
}
