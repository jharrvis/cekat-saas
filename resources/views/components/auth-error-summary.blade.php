{{-- T-08: ringkasan semua kesalahan validasi di atas form auth.
     Dipakai bersama oleh register/login/forgot/reset agar pengguna melihat
     SEMUA kolom bermasalah sekaligus, bukan satu per satu. --}}
@if($errors->any())
    <div class="bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm mb-4" role="alert">
        <p class="font-semibold mb-1">Periksa kembali isian Anda:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
