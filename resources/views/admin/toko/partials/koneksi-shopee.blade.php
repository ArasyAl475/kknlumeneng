{{-- Koneksi Shopee Open Platform. Status selalu ikon + kata, bukan warna saja. --}}
@php $token = $shopee['token']; @endphp
<x-kartu judul="Koneksi otomatis Shopee" class="mt-4">
    <div class="flex flex-wrap gap-2">
        @if (! $shopee['siap'])
            <span data-warna="kosong" class="status-lembut inline-flex items-center gap-1 rounded px-2 py-1 text-sm font-bold"><x-ikon nama="kosong" class="size-4" />Kunci belum diisi</span>
        @elseif (! $token)
            <span data-warna="kosong" class="status-lembut inline-flex items-center gap-1 rounded px-2 py-1 text-sm font-bold"><x-ikon nama="kosong" class="size-4" />Belum terhubung</span>
        @elseif ($token->masihBerlaku())
            <span data-warna="hijau" class="status-lembut inline-flex items-center gap-1 rounded px-2 py-1 text-sm font-bold"><x-ikon nama="aman" class="size-4" />Terhubung</span>
        @else
            <span data-warna="kuning" class="status-lembut inline-flex items-center gap-1 rounded px-2 py-1 text-sm font-bold"><x-ikon nama="waspada" class="size-4" />Izin habis, hubungkan ulang</span>
        @endif
        @if ($shopee['sandbox'])
            <span data-warna="kuning" class="status-lembut inline-flex items-center gap-1 rounded px-2 py-1 text-sm font-bold"><x-ikon nama="waspada" class="size-4" />Mode uji (sandbox)</span>
        @endif
    </div>

    @if (! $shopee['siap'])
        <p class="mt-3 text-redup">
            Isi <code>SHOPEE_PARTNER_ID</code> dan <code>SHOPEE_PARTNER_KEY</code> di berkas <code>.env</code> server.
            Keduanya didapat dari menu Console di open.shopee.com setelah pendaftaran disetujui.
        </p>
    @else
        @if ($token)
            <dl class="mt-4 grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-x-4 gap-y-2">
                <dt class="text-redup">Toko</dt>
                <dd class="font-bold">{{ $token->nama_toko ?? 'Nama belum terbaca' }}</dd>
                <dt class="text-redup">Nomor toko</dt>
                <dd class="font-bold">{{ $token->shop_id }}</dd>
                <dt class="text-redup">Izin berlaku sampai</dt>
                <dd class="font-bold">{{ $token->refresh_kedaluwarsa_pada->timezone('Asia/Jakarta')->format('d-m-Y H:i') }} WIB</dd>
            </dl>
            <p class="mt-3 text-sm text-redup">Izin diperpanjang otomatis selama penjadwal server berjalan. Bila lewat dari tanggal di atas, tekan "Hubungkan ulang".</p>
        @else
            <p class="mt-3 text-redup">
                Tekan tombol di bawah. Anda akan dibawa ke Shopee untuk login dengan <strong>akun penjual</strong>
                dan menyetujui akses, lalu otomatis kembali ke halaman ini.
            </p>
        @endif

        <div class="mt-4 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.toko.shopee.hubungkan') }}">
                @csrf
                <x-tombol>{{ $token ? 'Hubungkan ulang' : 'Hubungkan dengan Shopee' }}@include('toko.partials.ikon-keluar')</x-tombol>
            </form>
            @if ($token)
                <form method="POST" action="{{ route('admin.toko.shopee.putuskan') }}" data-konfirmasi="Putus koneksi Shopee? Produk yang sudah ada tetap tersimpan.">
                    @csrf
                    @method('DELETE')
                    <x-tombol varian="bahaya">Putuskan koneksi</x-tombol>
                </form>
            @endif
        </div>
    @endif
</x-kartu>
