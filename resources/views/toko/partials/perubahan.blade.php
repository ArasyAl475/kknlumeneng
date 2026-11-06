{{-- Perubahan dibanding periode sebelumnya: panah + kata + persen, tidak hanya warna. Tidak tampil bila tidak ada pembanding. --}}
@if (! empty($p))
    <p class="delta delta--{{ $p['arah'] }}">
        @include('toko.partials.ikon', ['nama' => $p['arah'] === 'tetap' ? 'tetap' : ($p['arah'] === 'naik' ? 'naik' : 'turun'), 'kelas' => 'size-4'])
        <span>
            @if ($p['arah'] === 'tetap')
                Sama seperti {{ $label }}
            @else
                {{ $p['arah'] === 'naik' ? 'Naik' : 'Turun' }} {{ $p['persen'] }}% <span class="delta__pembanding">dari {{ $label }}</span>
            @endif
        </span>
    </p>
@endif
