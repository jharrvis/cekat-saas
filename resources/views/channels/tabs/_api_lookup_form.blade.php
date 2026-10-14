{{-- Form tambah/edit API Lookup. $lookup = null untuk tambah baru. --}}
@php
    $isEdit = isset($lookup) && $lookup;
    $formId = $isEdit ? 'lookup-edit-' . $lookup->id : 'lookup-new';
    $oldParams = old('params', $isEdit ? ($lookup->params ?? []) : [['name' => '', 'label' => '', 'required' => false, 'description' => '', 'example' => '']]);
    $oldHeaders = old('headers', $isEdit ? ($lookup->headers ?? []) : []);
    $oldMappingRaw = old('mapping_rows', null);
    if ($oldMappingRaw === null) {
        $oldMappingRaw = [];
        foreach (($isEdit ? ($lookup->response_mapping ?? []) : []) as $f => $p) {
            $oldMappingRaw[] = ['field' => $f, 'path' => $p];
        }
    }
    $oldMapping = $oldMappingRaw;
    $input = 'rounded-md border border-input bg-background px-3 py-2 text-sm w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';
@endphp
<form action="{{ $isEdit ? route('channels.api-lookups.update', ['channel' => $chatbot->id, 'lookup' => $lookup->id]) : route('channels.api-lookups.store', ['channel' => $chatbot->id]) }}" method="POST" class="grid gap-4 rounded-lg border bg-muted/30 p-4">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
            <label class="text-sm font-medium">Key <span class="text-red-500">*</span></label>
            <input type="text" name="key" value="{{ old('key', $lookup->key ?? '') }}" {{ $isEdit ? 'readonly' : '' }} placeholder="cari_perkara"
                class="{{ $input }} font-mono {{ $isEdit ? 'opacity-60' : '' }}">
            @if ($isEdit) <p class="text-xs text-muted-foreground">Key tidak dapat diubah karena dipakai AI di prompt.</p> @endif
        </div>
        <div class="grid gap-2">
            <label class="text-sm font-medium">Nama <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $lookup->name ?? '') }}" placeholder="Cari Perkara PA Salatiga" class="{{ $input }}">
        </div>
    </div>

    <div class="grid gap-2">
        <label class="text-sm font-medium">Deskripsi (kapan AI boleh memakai lookup ini)</label>
        <textarea name="description" rows="2" placeholder="Dipakai saat user menanyakan informasi perkara Pengadilan Agama Salatiga berdasarkan nomor perkara." class="{{ $input }}">{{ old('description', $lookup->description ?? '') }}</textarea>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
            <label class="text-sm font-medium">URL Template <span class="text-red-500">*</span></label>
            <input type="text" name="url_template" value="{{ old('url_template', $lookup->url_template ?? '') }}" placeholder="https://api.pa-salatiga.go.id/list_perkara.php?search={nomor_perkara}" class="{{ $input }} font-mono text-xs">
            <p class="text-xs text-muted-foreground">Gunakan <code>{nama_param}</code> untuk parameter. Hanya skema http/https.</p>
        </div>
        <div class="grid gap-2">
            <label class="text-sm font-medium">Metode</label>
            <input type="hidden" name="method" value="GET">
            <p class="{{ $input }} bg-muted font-mono">GET <span class="text-xs text-muted-foreground">— Fase 1 hanya mendukung GET (baca data).</span></p>
        </div>
    </div>

    {{-- Parameter dinamis --}}
    <div class="grid gap-2">
        <label class="text-sm font-medium">Parameter</label>
        <div id="{{ $formId }}-params" class="grid gap-2">
            @foreach ($oldParams as $i => $p)
            <div class="grid gap-2 rounded-md border p-3 md:grid-cols-[1fr_1fr_auto] lookup-param-row">
                <input type="text" name="params[{{ $i }}][name]" value="{{ $p['name'] ?? '' }}" placeholder="nama_param (a-z, 0-9, _)" class="{{ $input }} font-mono text-xs">
                <input type="text" name="params[{{ $i }}][label]" value="{{ $p['label'] ?? '' }}" placeholder="Label tampil" class="{{ $input }} text-xs">
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="params[{{ $i }}][required]" value="1" {{ ! empty($p['required']) ? 'checked' : '' }}> Wajib</label>
                    <button type="button" onclick="this.closest('.lookup-param-row').remove()" class="text-xs text-red-500 hover:underline">Hapus</button>
                </div>
                <input type="text" name="params[{{ $i }}][description]" value="{{ $p['description'] ?? '' }}" placeholder="Deskripsi untuk AI" class="{{ $input }} text-xs md:col-span-2">
                <input type="text" name="params[{{ $i }}][example]" value="{{ $p['example'] ?? '' }}" placeholder="Contoh nilai" class="{{ $input }} text-xs md:col-span-1">
            </div>
            @endforeach
        </div>
        <button type="button" onclick="addLookupParamRow('{{ $formId }}-params')" class="text-xs text-primary hover:underline w-fit">+ Tambah parameter</button>
    </div>

    {{-- Auth --}}
    <div class="grid gap-4 md:grid-cols-3">
        <div class="grid gap-2">
            <label class="text-sm font-medium">Autentikasi</label>
            <select name="auth_type" onchange="toggleLookupAuth(this)" class="{{ $input }}">
                @foreach (['none' => 'Tanpa auth', 'bearer' => 'Bearer token', 'api_key_header' => 'API key via header', 'api_key_query' => 'API key via query'] as $val => $label)
                <option value="{{ $val }}" {{ old('auth_type', $lookup->auth_type ?? 'none') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-2 lookup-auth-secret" style="{{ in_array(old('auth_type', $lookup->auth_type ?? 'none'), ['bearer', 'api_key_header', 'api_key_query']) ? '' : 'display:none' }}">
            <label class="text-sm font-medium">Secret / Token</label>
            <input type="password" name="auth_secret" value="" placeholder="{{ $isEdit ? 'Kosongkan untuk tidak mengubah' : 'Token rahasia' }}" class="{{ $input }} font-mono text-xs">
        </div>
        <div class="grid gap-2 lookup-auth-config" style="{{ old('auth_type', $lookup->auth_type ?? 'none') === 'api_key_header' ? '' : 'display:none' }}" data-auth-for="api_key_header">
            <label class="text-sm font-medium">Nama header</label>
            <input type="text" name="auth_config[header_name]" value="{{ old('auth_config.header_name', ($lookup->auth_config['header_name'] ?? 'X-API-Key')) }}" class="{{ $input }} font-mono text-xs">
        </div>
        <div class="grid gap-2 lookup-auth-config" style="{{ old('auth_type', $lookup->auth_type ?? 'none') === 'api_key_query' ? '' : 'display:none' }}" data-auth-for="api_key_query">
            <label class="text-sm font-medium">Nama parameter query</label>
            <input type="text" name="auth_config[param_name]" value="{{ old('auth_config.param_name', ($lookup->auth_config['param_name'] ?? 'api_key')) }}" class="{{ $input }} font-mono text-xs">
        </div>
    </div>

    {{-- Header tambahan dinamis --}}
    <div class="grid gap-2">
        <label class="text-sm font-medium">Header tambahan</label>
        <div id="{{ $formId }}-headers" class="grid gap-2">
            @foreach ($oldHeaders as $i => $h)
            <div class="grid gap-2 md:grid-cols-[1fr_1fr_auto] lookup-header-row">
                <input type="text" name="headers[{{ $i }}][name]" value="{{ $h['name'] ?? '' }}" placeholder="X-Custom-Header" class="{{ $input }} font-mono text-xs">
                <input type="text" name="headers[{{ $i }}][value]" value="{{ $h['value'] ?? '' }}" placeholder="nilai" class="{{ $input }} font-mono text-xs">
                <button type="button" onclick="this.closest('.lookup-header-row').remove()" class="text-xs text-red-500 hover:underline w-fit">Hapus</button>
            </div>
            @endforeach
        </div>
        <button type="button" onclick="addLookupHeaderRow('{{ $formId }}-headers')" class="text-xs text-primary hover:underline w-fit">+ Tambah header</button>
    </div>

    {{-- Mapping respons --}}
    <div class="grid gap-2">
        <label class="text-sm font-medium">Pemetaan respons (field → dot-path JSON)</label>
        <div id="{{ $formId }}-mapping" class="grid gap-2">
            @php $mi = 0; @endphp
            @foreach ($oldMapping as $row)
            <div class="grid gap-2 md:grid-cols-[1fr_1fr_auto] lookup-mapping-row">
                <input type="text" name="mapping_rows[{{ $mi }}][field]" value="{{ $row['field'] ?? '' }}" placeholder="nama field (mis. status)" class="{{ $input }} font-mono text-xs">
                <input type="text" name="mapping_rows[{{ $mi }}][path]" value="{{ $row['path'] ?? '' }}" placeholder="data.0.status_perkara" class="{{ $input }} font-mono text-xs">
                <button type="button" onclick="this.closest('.lookup-mapping-row').remove()" class="text-xs text-red-500 hover:underline w-fit">Hapus</button>
            </div>
            @php $mi++; @endphp
            @endforeach
        </div>
        <p class="text-xs text-muted-foreground">Contoh: field <code>status</code> → path <code>data.0.status_perkara</code>. Indeks array: <code>data.0.field</code>.</p>
        <button type="button" onclick="addLookupMappingRow('{{ $formId }}-mapping')" class="text-xs text-primary hover:underline w-fit">+ Tambah mapping</button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
            <label class="text-sm font-medium">Path deteksi hasil kosong (dot-path)</label>
            <input type="text" name="empty_path" value="{{ old('empty_path', $lookup->empty_path ?? '') }}" placeholder="data" class="{{ $input }} font-mono text-xs">
            <p class="text-xs text-muted-foreground">Bila path ini kosong, balasan memakai "Pesan hasil kosong".</p>
        </div>
        <div class="grid gap-4 grid-cols-2">
            <div class="grid gap-2">
                <label class="text-sm font-medium">Timeout (detik)</label>
                <input type="number" name="timeout_seconds" min="3" max="30" value="{{ old('timeout_seconds', $lookup->timeout_seconds ?? 8) }}" class="{{ $input }}">
            </div>
            <div class="grid gap-2">
                <label class="text-sm font-medium">Cache (detik, 0 = mati)</label>
                <input type="number" name="cache_ttl_seconds" min="0" max="86400" value="{{ old('cache_ttl_seconds', $lookup->cache_ttl_seconds ?? 0) }}" class="{{ $input }}">
            </div>
        </div>
    </div>

    <div class="grid gap-2">
        <label class="text-sm font-medium">Template balasan (gunakan {field} dari mapping)</label>
        <textarea name="reply_template" rows="3" placeholder="Perkara {nomor_perkara} ({klasifikasi_perkara}), terdaftar {tanggal_register}. Status: {status_perkara}." class="{{ $input }} text-xs">{{ old('reply_template', $lookup->reply_template ?? '') }}</textarea>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
            <label class="text-sm font-medium">Pesan bila hasil kosong</label>
            <input type="text" name="empty_message" value="{{ old('empty_message', $lookup->empty_message ?? '') }}" placeholder="Data tidak ditemukan." class="{{ $input }} text-xs">
        </div>
        <div class="grid gap-2">
            <label class="text-sm font-medium">Pesan bila API error</label>
            <input type="text" name="error_message" value="{{ old('error_message', $lookup->error_message ?? '') }}" placeholder="Layanan pencarian sedang tidak dapat dihubungi." class="{{ $input }} text-xs">
        </div>
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $lookup->is_active ?? true) ? 'checked' : '' }}> Aktif</label>
        <div class="flex gap-2">
            @if ($isEdit)
            <button type="button" onclick="toggleLookupForm({{ $lookup->id }})" class="h-9 px-4 rounded-md border text-sm">Batal</button>
            @endif
            <button type="submit" class="bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 rounded-md font-medium text-sm">{{ $isEdit ? 'Simpan' : 'Tambah Lookup' }}</button>
        </div>
    </div>
</form>
