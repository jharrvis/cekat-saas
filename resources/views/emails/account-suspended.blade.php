<x-emails.layout title="Akun {{ $type === 'banned' ? 'Diblokir' : 'Ditangguhkan' }}" category="Status Akun">
    <x-emails.heading>Akun {{ $type === 'banned' ? 'Diblokir' : 'Ditangguhkan' }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Halo {{ $user->name }},
    </p>

    @if($type === 'banned')
        <x-emails.panel tone="danger">
            <p style="font-size:14px;line-height:1.6;color:#7f1d1d;margin:0;">
                Akun Anda telah <strong>diblokir secara permanen</strong> karena melanggar ketentuan layanan.
            </p>
        </x-emails.panel>

        <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
            Dampak pemblokiran:
        </p>
        <ul style="color:#52525b;font-size:14px;line-height:1.9;padding-left:22px;margin:0 0 8px;">
            <li>Anda tidak dapat mengakses dashboard</li>
            <li>Semua widget chatbot dinonaktifkan</li>
            <li>Data akun tetap tersimpan</li>
        </ul>
    @else
        <x-emails.panel tone="alert">
            <p style="font-size:14px;line-height:1.6;color:#78350f;margin:0;">
                Akun Anda telah <strong>ditangguhkan sementara</strong>.
            </p>
        </x-emails.panel>

        @if($reason)
            <x-emails.panel>
                <x-emails.field label="Alasan" :value="$reason" />
            </x-emails.panel>
        @endif

        <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
            Selama penangguhan:
        </p>
        <ul style="color:#52525b;font-size:14px;line-height:1.9;padding-left:22px;margin:0 0 8px;">
            <li>Anda tidak dapat mengakses dashboard</li>
            <li>Widget chatbot dinonaktifkan sementara</li>
            <li>Akun dapat diaktifkan kembali setelah masalah diselesaikan</li>
        </ul>
    @endif

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:20px 0 4px;">
        Jika Anda merasa ini adalah kesalahan, silakan hubungi tim support kami.
    </p>

    <div style="margin:28px 0 6px;">
        <x-emails.button href="mailto:support@cekat.biz.id">Hubungi Support</x-emails.button>
    </div>
</x-emails.layout>
