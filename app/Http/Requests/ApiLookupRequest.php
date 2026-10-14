<?php

namespace App\Http\Requests;

use App\Models\WidgetApiLookup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi definisi API Lookup + normalisasi baris dinamis
 * (parameter, header, mapping) menjadi array siap simpan.
 */
class ApiLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi channel ditangani di controller via Gate.
    }

    public function rules(): array
    {
        $channelId = $this->route('channel');
        $lookupId = $this->route('lookup');

        return [
            'key' => [
                'required', 'alpha_dash', 'max:64',
                Rule::unique('widget_api_lookups', 'key')
                    ->where('widget_id', $channelId)
                    ->ignore($lookupId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'method' => ['required', Rule::in(WidgetApiLookup::METHODS)],
            'url_template' => ['required', 'string', 'max:2000'],
            'params' => ['nullable', 'array'],
            'params.*.name' => ['required_with:params', 'alpha_dash', 'max:64'],
            'params.*.label' => ['nullable', 'string', 'max:120'],
            'params.*.required' => ['nullable', 'boolean'],
            'params.*.description' => ['nullable', 'string', 'max:500'],
            'params.*.example' => ['nullable', 'string', 'max:255'],
            'headers' => ['nullable', 'array'],
            'headers.*.name' => ['required_with:headers', 'string', 'max:120'],
            'headers.*.value' => ['nullable', 'string', 'max:2000'],
            'auth_type' => ['required', Rule::in(WidgetApiLookup::AUTH_TYPES)],
            'auth_secret' => ['nullable', 'string', 'max:2000'],
            'auth_config.header_name' => ['required_if:auth_type,api_key_header', 'string', 'max:120'],
            'auth_config.param_name' => ['required_if:auth_type,api_key_query', 'alpha_dash', 'max:64'],
            'timeout_seconds' => ['required', 'integer', 'min:3', 'max:30'],
            'mapping_rows' => ['nullable', 'array'],
            'mapping_rows.*.field' => ['required_with:mapping_rows', 'alpha_dash', 'max:64'],
            'mapping_rows.*.path' => ['required_with:mapping_rows', 'string', 'max:255'],
            'empty_path' => ['nullable', 'string', 'max:255'],
            'reply_template' => ['nullable', 'string', 'max:2000'],
            'empty_message' => ['nullable', 'string', 'max:500'],
            'error_message' => ['nullable', 'string', 'max:500'],
            'cache_ttl_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.unique' => 'Key sudah dipakai lookup lain di channel ini.',
            'key.alpha_dash' => 'Key hanya boleh huruf, angka, strip, dan underscore.',
            'method.in' => 'Fase 1 hanya mendukung metode GET.',
            'auth_config.header_name.required_if' => 'Nama header wajib diisi untuk auth API key via header.',
            'auth_config.param_name.required_if' => 'Nama parameter wajib diisi untuk auth API key via query.',
        ];
    }

    /**
     * Validasi silang: setiap {placeholder} di URL harus terdefinisi
     * di params, dan URL harus ber-skema http/https.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $template = (string) $this->input('url_template', '');
            $paramNames = collect($this->input('params', []))
                ->map(fn ($p) => $p['name'] ?? null)
                ->filter()
                ->all();

            preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $template, $m);
            foreach ($m[1] as $placeholder) {
                if (! in_array($placeholder, $paramNames, true)) {
                    $validator->errors()->add(
                        'url_template',
                        'Placeholder "{'.$placeholder.'}" tidak terdefinisi di daftar parameter.'
                    );
                }
            }

            // Skema harus http/https (tanpa placeholder agar bisa divalidasi)
            $probe = preg_replace('/\{[a-zA-Z0-9_]+\}/', 'x', $template);
            $scheme = strtolower((string) parse_url($probe, PHP_URL_SCHEME));
            if (! in_array($scheme, ['http', 'https'], true)) {
                $validator->errors()->add('url_template', 'URL harus ber-skema http atau https.');
            }
        });
    }

    /**
     * Normalisasi input menjadi array siap create/update model.
     * Baris dinamis yang kosong dibuang; secret yang dikosongkan
     * saat edit mempertahankan nilai lama.
     */
    public function normalized(?WidgetApiLookup $existing = null): array
    {
        $data = $this->validated();

        $data['params'] = collect($data['params'] ?? [])
            ->filter(fn ($p) => ! empty($p['name']))
            ->map(fn ($p) => [
                'name' => $p['name'],
                'label' => $p['label'] ?? $p['name'],
                'required' => ! empty($p['required']),
                'description' => $p['description'] ?? '',
                'example' => $p['example'] ?? '',
            ])
            ->values()
            ->all();

        $data['headers'] = collect($data['headers'] ?? [])
            ->filter(fn ($h) => ! empty($h['name']))
            ->map(fn ($h) => [
                'name' => $h['name'],
                'value' => $h['value'] ?? '',
            ])
            ->values()
            ->all();

        // Mapping: buang baris dengan field/path kosong; key = nama field.
        $mapping = [];
        foreach ($data['mapping_rows'] ?? [] as $row) {
            $field = trim((string) ($row['field'] ?? ''));
            $path = trim((string) ($row['path'] ?? ''));
            if ($field !== '' && $path !== '') {
                $mapping[$field] = $path;
            }
        }
        $data['response_mapping'] = $mapping;
        unset($data['mapping_rows']);

        $data['auth_config'] = $data['auth_config'] ?? [];
        $data['is_active'] = ! empty($data['is_active']);

        // Secret: kosong saat edit = pertahankan yang lama.
        $needsSecret = in_array($data['auth_type'], ['bearer', 'api_key_header', 'api_key_query'], true);
        if (empty($data['auth_secret'])) {
            if ($existing && $needsSecret) {
                unset($data['auth_secret']);
            } else {
                $data['auth_secret'] = null;
            }
        }
        if (! $needsSecret) {
            $data['auth_secret'] = null;
            $data['auth_config'] = [];
        }

        unset($data['_token'], $data['_method']);

        return $data;
    }
}
