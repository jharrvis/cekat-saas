{{-- API Lookup — chatbot mencari data dari API eksternal. Disisipkan di tab Webhook. --}}
<div class="bg-card rounded-xl border p-6 mb-6">
    <h3 class="text-lg font-semibold mb-2">API Lookup — Pencarian Data Eksternal</h3>
    <p class="text-muted-foreground text-sm mb-6">
        Daftarkan API yang boleh ditanya chatbot saat percakapan (mis. pencarian nomor perkara).
        AI akan otomatis memanggil lookup yang sesuai ketika user memberikan parameter yang dibutuhkan,
        lalu menjawab berdasarkan hasil API.
    </p>

    @php $lookups = $chatbot->apiLookups()->orderBy('name')->get(); @endphp

    @if ($lookups->isNotEmpty())
    <div class="grid gap-3 mb-6">
        @foreach ($lookups as $lookup)
        <div class="rounded-lg border p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium truncate">{{ $lookup->name }}
                        <code class="ml-1 rounded bg-muted px-1.5 py-0.5 text-xs font-mono">{{ $lookup->key }}</code>
                        @if ($lookup->is_active)
                        <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Aktif</span>
                        @else
                        <span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">Nonaktif</span>
                        @endif
                    </p>
                    <p class="mt-1 truncate font-mono text-xs text-muted-foreground">GET {{ $lookup->url_template }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="toggleLookupPanel('test', {{ $lookup->id }})"
                        class="h-8 px-3 rounded-md border text-xs font-medium hover:bg-muted">Uji</button>
                    <button type="button" onclick="toggleLookupPanel('form', {{ $lookup->id }})"
                        class="h-8 px-3 rounded-md border text-xs font-medium hover:bg-muted">Edit</button>
                    <button type="button" onclick="armLookupDelete(this)"
                        data-delete-url="{{ route('channels.api-lookups.destroy', ['channel' => $chatbot->id, 'lookup' => $lookup->id]) }}"
                        data-csrf="{{ csrf_token() }}"
                        class="h-8 px-3 rounded-md border border-red-200 text-xs font-medium text-red-600 hover:bg-red-50">Hapus</button>
                </div>
            </div>

            {{-- Panel Uji --}}
            <div id="lookup-test-{{ $lookup->id }}" class="hidden mt-4 rounded-lg border bg-muted/30 p-4">
                <p class="mb-3 text-sm font-medium">Uji lookup dengan parameter contoh</p>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach (($lookup->params ?? []) as $p)
                    <div class="grid gap-1">
                        <label class="text-xs font-medium">{{ $p['label'] ?? $p['name'] }} @if(!empty($p['required']))<span class="text-red-500">*</span>@endif</label>
                        <input type="text" data-test-param="{{ $p['name'] }}" value="{{ $p['example'] ?? '' }}" placeholder="{{ $p['name'] }}"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm font-mono">
                    </div>
                    @endforeach
                </div>
                <button type="button" onclick="runLookupTest({{ $lookup->id }})"
                    data-test-url="{{ route('channels.api-lookups.test', ['channel' => $chatbot->id, 'lookup' => $lookup->id]) }}"
                    data-csrf="{{ csrf_token() }}"
                    class="mt-3 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 rounded-md font-medium text-sm">Jalankan Tes</button>
                <div id="lookup-test-result-{{ $lookup->id }}" class="mt-3"></div>
            </div>

            {{-- Panel Edit --}}
            <div id="lookup-form-{{ $lookup->id }}" class="hidden mt-4">
                @include('channels.tabs._api_lookup_form', ['lookup' => $lookup, 'chatbot' => $chatbot])
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="text-sm text-muted-foreground mb-6">Belum ada API Lookup. Tambahkan satu untuk mulai — contoh: pencarian nomor perkara ke API pengadilan.</p>
    @endif

    <button type="button" onclick="toggleLookupPanel('form', 'new')"
        class="h-10 px-4 rounded-md border font-medium text-sm hover:bg-muted">+ Tambah Lookup</button>
    <div id="lookup-form-new" class="hidden mt-4">
        @include('channels.tabs._api_lookup_form', ['lookup' => null, 'chatbot' => $chatbot])
    </div>
</div>

<script>
function toggleLookupPanel(kind, id) {
    const el = document.getElementById('lookup-' + kind + '-' + id);
    if (el) el.classList.toggle('hidden');
}

function addLookupParamRow(containerId) {
    const c = document.getElementById(containerId);
    const i = c.querySelectorAll('.lookup-param-row').length;
    const div = document.createElement('div');
    div.className = 'grid gap-2 rounded-md border p-3 md:grid-cols-[1fr_1fr_auto] lookup-param-row';
    div.innerHTML =
        '<input type="text" name="params[' + i + '][name]" placeholder="nama_param (a-z, 0-9, _)" class="rounded-md border border-input bg-background px-3 py-2 text-sm w-full font-mono text-xs">' +
        '<input type="text" name="params[' + i + '][label]" placeholder="Label tampil" class="rounded-md border border-input bg-background px-3 py-2 text-sm w-full text-xs">' +
        '<div class="flex items-center gap-3">' +
        '<label class="flex items-center gap-1 text-xs"><input type="checkbox" name="params[' + i + '][required]" value="1"> Wajib</label>' +
        '<button type="button" onclick="this.closest(\'.lookup-param-row\').remove()" class="text-xs text-red-500 hover:underline">Hapus</button></div>' +
        '<input type="text" name="params[' + i + '][description]" placeholder="Deskripsi untuk AI" class="rounded-md border border-input bg-background px-3 py-2 text-sm w-full text-xs md:col-span-2">' +
        '<input type="text" name="params[' + i + '][example]" placeholder="Contoh nilai" class="rounded-md border border-input bg-background px-3 py-2 text-sm w-full text-xs">';
    c.appendChild(div);
}

function addLookupHeaderRow(containerId) {
    const c = document.getElementById(containerId);
    const i = c.querySelectorAll('.lookup-header-row').length;
    const div = document.createElement('div');
    div.className = 'grid gap-2 md:grid-cols-[1fr_1fr_auto] lookup-header-row';
    div.innerHTML =
        '<input type="text" name="headers[' + i + '][name]" placeholder="X-Custom-Header" class="rounded-md border border-input bg-background px-3 py-2 text-sm w-full font-mono text-xs">' +
        '<input type="text" name="headers[' + i + '][value]" placeholder="nilai" class="rounded-md border border-input bg-background px-3 py-2 text-sm w-full font-mono text-xs">' +
        '<button type="button" onclick="this.closest(\'.lookup-header-row\').remove()" class="text-xs text-red-500 hover:underline w-fit">Hapus</button>';
    c.appendChild(div);
}

function addLookupMappingRow(containerId) {
    const c = document.getElementById(containerId);
    const i = c.querySelectorAll('.lookup-mapping-row').length;
    const div = document.createElement('div');
    div.className = 'grid gap-2 md:grid-cols-[1fr_1fr_auto] lookup-mapping-row';
    div.innerHTML =
        '<input type="text" name="mapping_rows[' + i + '][field]" placeholder="nama field (mis. status)" class="rounded-md border border-input bg-background px-3 py-2 text-sm w-full font-mono text-xs">' +
        '<input type="text" name="mapping_rows[' + i + '][path]" placeholder="data.0.status_perkara" class="rounded-md border border-input bg-background px-3 py-2 text-sm w-full font-mono text-xs">' +
        '<button type="button" onclick="this.closest(\'.lookup-mapping-row\').remove()" class="text-xs text-red-500 hover:underline w-fit">Hapus</button>';
    c.appendChild(div);
}

function toggleLookupAuth(select) {
    const form = select.closest('form');
    const type = select.value;
    form.querySelectorAll('.lookup-auth-secret').forEach(el => {
        el.style.display = (type === 'bearer' || type === 'api_key_header' || type === 'api_key_query') ? '' : 'none';
    });
    form.querySelectorAll('.lookup-auth-config').forEach(el => {
        el.style.display = (el.dataset.authFor === type) ? '' : 'none';
    });
}

// Hapus dua tahap (tanpa browser confirm)
function armLookupDelete(btn) {
    if (btn.dataset.armed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = btn.dataset.deleteUrl;
        const csrf = document.createElement('input');
        csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = btn.dataset.csrf;
        const method = document.createElement('input');
        method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
        form.append(csrf, method);
        document.body.appendChild(form);
        form.submit();
        return;
    }
    btn.dataset.armed = '1';
    btn.dataset.label = btn.textContent;
    btn.textContent = 'Klik lagi untuk hapus';
    btn.classList.add('bg-red-600', 'text-white', 'border-red-600');
    setTimeout(() => {
        delete btn.dataset.armed;
        btn.textContent = btn.dataset.label;
        btn.classList.remove('bg-red-600', 'text-white', 'border-red-600');
    }, 4000);
}

async function runLookupTest(id) {
    const panel = document.getElementById('lookup-test-' + id);
    const btn = panel.querySelector('[data-test-url]');
    const params = {};
    panel.querySelectorAll('[data-test-param]').forEach(el => { params[el.dataset.testParam] = el.value; });
    const box = document.getElementById('lookup-test-result-' + id);
    box.innerHTML = '';
    const loading = document.createElement('p');
    loading.className = 'text-sm text-muted-foreground';
    loading.textContent = 'Menjalankan...';
    box.appendChild(loading);
    btn.disabled = true;
    try {
        const res = await fetch(btn.dataset.testUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': btn.dataset.csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ test_params: params }),
        });
        const data = await res.json();
        box.innerHTML = '';
        box.appendChild(lookupTestResult(data));
    } catch (e) {
        box.innerHTML = '';
        const err = document.createElement('p');
        err.className = 'text-sm text-red-600';
        err.textContent = 'Gagal menjalankan tes: ' + e.message;
        box.appendChild(err);
    } finally {
        btn.disabled = false;
    }
}

// Bangun tampilan hasil tes dengan textContent (aman dari injeksi HTML)
function lookupTestResult(data) {
    const wrap = document.createElement('div');
    wrap.className = 'grid gap-2 rounded-lg border bg-background p-4 text-sm';

    const badge = document.createElement('p');
    badge.className = 'font-semibold ' + (data.ok ? 'text-emerald-600' : 'text-red-600');
    badge.textContent = data.ok
        ? 'Berhasil (HTTP ' + data.http_status + ', ' + data.duration_ms + ' ms)'
        : 'Gagal' + (data.http_status ? ' (HTTP ' + data.http_status + ')' : '') + (data.error ? ': ' + data.error : '');
    wrap.appendChild(badge);

    const urlRow = document.createElement('p');
    urlRow.className = 'font-mono text-xs text-muted-foreground break-all';
    const urlLabel = document.createElement('span');
    urlLabel.textContent = 'URL: ';
    const urlVal = document.createElement('span');
    urlVal.textContent = data.url || '-';
    urlRow.append(urlLabel, urlVal);
    wrap.appendChild(urlRow);

    if (data.mapped && Object.keys(data.mapped).length) {
        const title = document.createElement('p');
        title.className = 'font-medium mt-1';
        title.textContent = 'Hasil mapping:';
        wrap.appendChild(title);
        const dl = document.createElement('dl');
        dl.className = 'grid gap-1';
        Object.entries(data.mapped).forEach(([k, v]) => {
            const row = document.createElement('div');
            row.className = 'flex gap-2 text-xs';
            const dt = document.createElement('dt');
            dt.className = 'font-mono text-muted-foreground shrink-0';
            dt.textContent = k + ':';
            const dd = document.createElement('dd');
            dd.textContent = v;
            row.append(dt, dd);
            dl.appendChild(row);
        });
        wrap.appendChild(dl);
    }

    if (data.reply) {
        const title = document.createElement('p');
        title.className = 'font-medium mt-1';
        title.textContent = 'Balasan ke pengunjung:';
        const pre = document.createElement('pre');
        pre.className = 'whitespace-pre-wrap rounded bg-muted p-3 text-xs';
        pre.textContent = data.reply;
        wrap.append(title, pre);
    }

    return wrap;
}
