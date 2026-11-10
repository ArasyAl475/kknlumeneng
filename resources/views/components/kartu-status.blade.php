@props([
    'warna' => 'kosong',
    'status' => null,
    'saran' => null,
    'lokasi' => null,
    'pemicu' => null,
    'waktu' => null,
    'jumlahWilayah' => null,
    'jumlahOffline' => 0,
    'label' => 'Status saat ini',
    'judulId' => 'judul-status',
    'tingkat' => 'h3',
    'langsung' => false,
    'offline' => null,
    'detail' => null,
    'bagikan' => null,
])
{{-- Kartu status longsor, dipakai di beranda dan dasbor EWS supaya tampilannya seragam.
     Urutan isi: label + penanda, ikon + kata status + tingkat, kondisi sensor, kotak saran, lokasi & waktu,
     ringkasan alat, catatan alat mati, lalu dua tombol (Detail sensor lingkungan, Bagikan).
     Status tetap warna + bentuk ikon + kata. Gaya ada di app.css (.kartu-status), tombol Bagikan di app.js.
     $langsung = true: elemen diberi id panel-* karena diperbarui berkala oleh resources/js/ews/dasbor.js;
     teks penanda/tingkat di sana harus sama dengan $teks di bawah ini.
     Bagian yang kosong disembunyikan (atribut hidden) dan dimunculkan lagi oleh skrip bila datanya datang. --}}
@php
    $teks = [
        'hijau' => ['ikon' => 'aman', 'penanda' => 'Kondisi normal', 'tingkat' => 'Tingkat 1'],
        'kuning' => ['ikon' => 'waspada', 'penanda' => 'Perhatian warga', 'tingkat' => 'Tingkat 2'],
        'merah' => ['ikon' => 'awas', 'penanda' => 'Darurat aktif', 'tingkat' => 'Tingkat 3'],
        'kosong' => ['ikon' => 'kosong', 'penanda' => 'Menunggu data', 'tingkat' => null],
    ][$warna] ?? ['ikon' => 'kosong', 'penanda' => 'Menunggu data', 'tingkat' => null];
    $id = fn (string $nama) => $langsung ? 'id=panel-'.$nama : '';
    $adaAlat = $jumlahWilayah !== null && $jumlahWilayah > 0;
@endphp
<article data-warna="{{ $warna }}" aria-labelledby="{{ $judulId }}" @if ($langsung) id="panel" @endif {{ $attributes->class('kartu-status') }}>
    <div class="kartu-status__kepala">
        {{-- Label ini sudah ikut dibacakan di dalam judul (sr-only), jadi di sini disembunyikan dari pembaca layar. --}}
        <p class="kartu-status__label" aria-hidden="true">
            @if ($langsung)
                {{-- Titik tanda data diperbarui otomatis; dekoratif karena status berubahnya sudah diumumkan lewat #pengumuman. --}}
                <span class="kartu-status__denyut" aria-hidden="true">
                    <span class="animate-ping absolute inline-flex size-full rounded-full bg-[var(--status-isi)] opacity-50"></span>
                    <span class="relative inline-flex size-full rounded-full bg-[var(--status-isi)]"></span>
                </span>
            @endif
            {{ $label }}
        </p>
        <span class="kartu-status__penanda" {{ $id('penanda') }}>{{ $teks['penanda'] }}</span>
    </div>

    <div class="kartu-status__isi">
        <div class="kartu-status__kiri">
            <div class="kartu-status__utama">
                <span class="kartu-status__lencana">
                    <svg class="ikon-goyang" aria-hidden="true" focusable="false"><use {{ $id('ikon') }} href="#i-{{ $teks['ikon'] }}"></use></svg>
                </span>
                <div class="min-w-0">
                    <{{ $tingkat }} id="{{ $judulId }}" class="kartu-status__judul">
                        <span class="sr-only">{{ $label }}: </span>
                        <span class="kartu-status__kata" {{ $id('status') }}>{{ $status ?? 'Belum ada data' }}</span>
                        <span class="kartu-status__tingkat" {{ $id('tingkat') }} @if (! $teks['tingkat']) hidden @endif>{{ $teks['tingkat'] }}</span>
                    </{{ $tingkat }}>
                    @if ($langsung || $pemicu)
                        <p class="kartu-status__pemicu" {{ $id('pemicu') }} @if (! $pemicu) hidden @endif>{{ $pemicu }}</p>
                    @endif
                </div>
            </div>

            <p class="kartu-status__saran" {{ $id('saran') }}>{{ $saran }}</p>
        </div>

        <div class="kartu-status__kanan">
            <dl class="kartu-status__info">
                <div class="kartu-status__sel" data-sel @if (! $lokasi) hidden @endif>
                    <dt><svg aria-hidden="true" focusable="false"><use href="#i-lokasi"></use></svg>Lokasi</dt>
                    <dd {{ $id('lokasi') }}>{{ $lokasi }}</dd>
                </div>
                <div class="kartu-status__sel" data-sel @if (! $waktu) hidden @endif>
                    <dt><svg aria-hidden="true" focusable="false"><use href="#i-jam"></use></svg>Data terakhir</dt>
                    <dd {{ $id('waktu') }}>{{ $waktu ? $waktu.' WIB' : '' }}</dd>
                </div>
            </dl>

            <dl class="kartu-status__baris" {{ $id('alat') }} @if (! $adaAlat) hidden @endif>
                <div>
                    <dt>Wilayah dipantau</dt>
                    <dd {{ $id('jumlah-wilayah') }}>{{ $jumlahWilayah }} wilayah</dd>
                </div>
                <div>
                    <dt>Alat mengirim data</dt>
                    <dd {{ $id('jumlah-aktif') }} @class(['kartu-status__nilai', 'kartu-status__nilai--kurang' => $jumlahOffline > 0])>
                        <svg aria-hidden="true" focusable="false"><use href="#i-{{ $jumlahOffline > 0 ? 'offline' : 'alat' }}"></use></svg>
                        <span>{{ $adaAlat ? ($jumlahWilayah - $jumlahOffline).' dari '.$jumlahWilayah : '' }}</span>
                    </dd>
                </div>
            </dl>

            @if ($langsung || $offline)
                <p class="kartu-status__peringatan" {{ $id('peringatan') }} @if (! $offline) hidden @endif>
                    <svg aria-hidden="true" focusable="false"><use href="#i-offline"></use></svg>
                    <span @if ($langsung) id="panel-peringatan-teks" @endif>{{ $offline }}</span>
                </p>
            @endif
        </div>
    </div>

    @if ($detail || $bagikan)
        <div class="kartu-status__aksi">
            @if ($detail)
                <a href="{{ $detail }}" class="kartu-status__tombol kartu-status__tombol--utama">
                    Detail sensor lingkungan
                    <svg aria-hidden="true" focusable="false"><use href="#i-panah"></use></svg>
                </a>
            @endif
            @if ($bagikan)
                {{-- Tanpa JavaScript tombol ini tidak berguna, jadi baru dimunculkan oleh app.js. --}}
                <button type="button" class="kartu-status__tombol" data-bagikan-status="{{ $bagikan }}" hidden>
                    <svg aria-hidden="true" focusable="false"><use href="#i-bagikan"></use></svg>
                    <span data-bagikan-teks aria-live="polite">Bagikan</span>
                </button>
            @endif
        </div>
    @endif
</article>
