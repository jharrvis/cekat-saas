<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun {{ $type === 'banned' ? 'Diblokir' : 'Ditangguhkan' }} - Cekat.ai</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
            @if($type === 'banned')
                {{-- Banned --}}
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-ban text-4xl text-red-500"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Akun Anda Diblokir</h1>
                <p class="text-gray-600 mb-6">
                    Akun Anda telah diblokir secara permanen karena melanggar ketentuan layanan.
                    Semua widget chatbot Anda telah dinonaktifkan.
                </p>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-red-700">
                        <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                        Anda tidak dapat lagi menggunakan layanan Cekat.ai.
                    </p>
                </div>
            @else
                {{-- Suspended --}}
                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-pause text-4xl text-amber-500"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Akun Anda Ditangguhkan</h1>
                <p class="text-gray-600 mb-6">
                    Akun Anda sementara ditangguhkan. Selama periode ini, Anda tidak dapat mengakses dashboard
                    dan semua widget chatbot Anda dinonaktifkan sementara.
                </p>

                @if($reason)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-left">
                        <p class="text-sm font-medium text-amber-800 mb-1">Alasan Penangguhan:</p>
                        <p class="text-sm text-amber-700">{{ $reason }}</p>
                    </div>
                @endif

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-700">
                        <i class="fa-solid fa-info-circle mr-2"></i>
                        Akun Anda dapat diaktifkan kembali setelah masalah diselesaikan.
                    </p>
                </div>
            @endif

            <div class="space-y-3">
                <a href="mailto:support@cekat.ai"
                    class="block w-full bg-gray-900 text-white py-3 rounded-lg hover:bg-gray-800 transition">
                    <i class="fa-solid fa-envelope mr-2"></i>Hubungi Support
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full bg-gray-100 text-gray-700 py-3 rounded-lg hover:bg-gray-200 transition">
                        <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-gray-500 text-sm mt-6">
            &copy; {{ date('Y') }} Cekat.ai - All rights reserved.
        </p>
    </div>
</body>

</html>