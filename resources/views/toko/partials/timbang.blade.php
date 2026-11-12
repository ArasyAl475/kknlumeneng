@php use App\Services\Toko\FormatHasil; @endphp
{{-- Tabel catatan timbang di layar lebar, daftar kartu di HP (CSS mengubah barisnya jadi blok; role dipasang agar pembaca layar tetap membaca tabel). --}}
<table class="timbang" role="table">
    <caption class="sr-only">Catatan timbang panen kopi</caption>
    <thead role="rowgroup">
        <tr role="row">
            <th scope="col" role="columnheader">Tanggal Timbang</th>
            <th scope="col" role="columnheader">Nama Petani/Kelompok</th>
            <th scope="col" role="columnheader">Blok Lahan</th>
            <th scope="col" role="columnheader" class="timbang__angka">Berat Kotor (kg)</th>
            <th scope="col" role="columnheader" class="timbang__angka">Berat Bersih (kg)</th>
        </tr>
    </thead>
    {{-- Isi tbody diganti di tempat saat data berubah; paginasi app.js memantau anak-anaknya, jadi ikut menyesuaikan. --}}
    <tbody role="rowgroup" data-paginasi="10" data-segar-bagian="timbang-baris">
        @foreach ($baris as $b)
            <tr role="row">
                <th scope="row" role="rowheader" class="timbang__tanggal">{{ $b['tanggal']->format('d-m-Y') }}</th>
                <td role="cell" data-sel="Nama Petani/Kelompok">{{ $b['petani'] ?: '–' }}</td>
                <td role="cell" data-sel="Blok Lahan">{{ $b['blok'] }}</td>
                <td role="cell" data-sel="Berat Kotor (kg)" class="timbang__angka">{{ FormatHasil::angka($b['kotor']) }}</td>
                <td role="cell" data-sel="Berat Bersih (kg)" class="timbang__angka">{{ FormatHasil::angka($b['bersih']) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot role="rowgroup" data-segar-bagian="timbang-jumlah">
        <tr role="row">
            <th scope="row" role="rowheader" colspan="3" class="timbang__tanggal">Jumlah seluruh catatan ({{ $jumlah }})</th>
            <td role="cell" data-sel="Berat Kotor (kg)" class="timbang__angka">{{ FormatHasil::angka($totalKotor) }}</td>
            <td role="cell" data-sel="Berat Bersih (kg)" class="timbang__angka">{{ FormatHasil::angka($totalBersih) }}</td>
        </tr>
    </tfoot>
</table>
