@extends('layouts.dashboard')

@section('title', 'Pengaturan Akun')

@section('content')
    <div class="space-y-6 max-w-4xl">
        {{-- Header --}}
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Pengaturan Akun</h2>
            <p class="text-muted-foreground mt-1">Kelola informasi profil dan keamanan akun Anda</p>
        </div>

        {{-- Profile Section --}}
        <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
            <h3 class="font-semibold text-lg mb-4">Informasi Profil</h3>

            <form action="{{ route('settings.update-profile') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4 mb-6">
                    <div
                        class="w-16 h-16 rounded-full bg-primary flex items-center justify-center text-primary-foreground text-xl font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div>
                        <p class="font-medium">{{ auth()->user()->name }}</p>
                        <p class="text-sm text-muted-foreground">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ auth()->user()->name }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Email</label>
                        <input type="email" name="email" value="{{ auth()->user()->email }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-muted"
                            disabled>
                        <p class="text-xs text-muted-foreground mt-1">Email tidak dapat diubah</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        {{-- Password Section --}}
        <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
            <h3 class="font-semibold text-lg mb-4">Ubah Password</h3>

            <form action="{{ route('settings.update-password') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Password Saat Ini</label>
                        <input type="password" name="current_password"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div></div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Password Baru</label>
                        <input type="password" name="password"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                        Ubah Password
                    </button>
                </div>
            </form>
        </div>

        {{-- Plan Info --}}
        <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
            <h3 class="font-semibold text-lg mb-4">Paket Langganan</h3>

            <div class="flex items-center justify-between p-4 bg-primary/5 rounded-lg border border-primary/20">
                <div>
                    <p class="font-semibold text-primary">{{ auth()->user()->plan->name ?? 'Free Plan' }}</p>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ auth()->user()->monthly_message_used ?? 0 }} / {{ auth()->user()->monthly_message_quota ?? 100 }}
                        pesan bulan ini
                    </p>
                </div>
                <a href="#" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                    <i class="fa-solid fa-arrow-up mr-2"></i>Upgrade
                </a>
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="bg-card text-card-foreground p-6 rounded-xl border border-destructive/20 shadow-sm">
            <h3 class="font-semibold text-lg mb-4 text-destructive">Zona Berbahaya</h3>

            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">Hapus Akun</p>
                    <p class="text-sm text-muted-foreground">Semua data Anda akan dihapus secara permanen</p>
                </div>
                <button type="button"
                    class="px-4 py-2 border border-destructive text-destructive rounded-lg hover:bg-destructive hover:text-destructive-foreground transition">
                    Hapus Akun
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="fixed bottom-4 right-4 bg-emerald-500 text-white px-4 py-3 rounded-lg shadow-lg">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="fixed bottom-4 right-4 bg-destructive text-destructive-foreground px-4 py-3 rounded-lg shadow-lg">
            {{ $errors->first() }}
        </div>
    @endif
@endsection