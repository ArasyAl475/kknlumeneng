@php use App\Services\Toko\FormatHasil; @endphp
{{-- Tabel di layar lebar, daftar ringkas di HP (CSS mengubah barisnya jadi blok; role dipasang agar pembaca layar tetap membaca tabel). --}}
<table class="rincian" role="table" data-tabel-rincian>
    <caption class="sr-only">{{ $judul }}</caption>
    <thead role="rowgroup">
        <tr role="row">
            <th scope="col" role="columnheader" aria-sort="none" data-kolom="label">
                <button type="button" class="tombol-urut" data-urut="label">{{ $kolom }} @include('toko.partials.ikon', ['nama' => 'urut', 'kelas' => 'size-4'])</button>
            </th>
            <th scope="col" role="columnheader"><span class="sr-only">Perbandingan panen</span></th>
            <th scope="col" role="columnheader" aria-sort="none" data-kolom="panen">
                <button type="button" class="tombol-urut" data-urut="panen">Panen @include('toko.partials.ikon', ['nama' => 'urut', 'kelas' => 'size-4'])</button>
            </th>
            <th scope="col" role="columnheader" aria-sort="none" data-kolom="limbah">
                <button type="button" class="tombol-urut" data-urut="limbah">Limbah @include('toko.partials.ikon', ['nama' => 'urut', 'kelas' => 'size-4'])</button>
            </th>
        </tr>
    </thead>
    <tbody role="rowgroup">
        @foreach ($baris as $b)
            <tr role="row" data-awal="{{ $loop->index }}" data-label="{{ $b['label'] }}" data-panen="{{ $b['panen'] }}" data-limbah="{{ $b['limbah'] }}">
                <th scope="row" role="rowheader" class="rincian__label">{{ $b['label'] }}</th>
                <td role="cell" class="rincian__sebaran">
                    <span class="batang" aria-hidden="true"><span class="batang__isi" style="width: {{ $b['lebar'] }}%"></span></span>
                </td>
                <td role="cell" data-sel="Panen">{{ FormatHasil::kg($b['panen']) }}</td>
                <td role="cell" data-sel="Limbah">{{ FormatHasil::kg($b['limbah']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
