{{-- Tabel bulanan untuk pembaca tanpa JavaScript. Dengan JavaScript, tabel dibuat dari data yang sama di sisi peramban. --}}
@php use App\Services\Toko\FormatHasil; @endphp
<div class="grafik__tabel">
    <table class="tabel-data">
        <caption>Data per bulan, urut dari bulan terlama.</caption>
        <thead>
            <tr>
                <th scope="col">Bulan</th>
                <th scope="col">Panen kopi</th>
                <th scope="col">Panen jamur</th>
                <th scope="col">Jamur terjual</th>
                <th scope="col">Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($skala['lengkap'] as $i => $bulan)
                <tr>
                    <th scope="row">{{ $bulan }}</th>
                    <td>{{ FormatHasil::kg($skala['kopi'][$i]) }}</td>
                    <td>{{ FormatHasil::kg($skala['jamur'][$i]) }}</td>
                    <td>{{ FormatHasil::kg($skala['terjual'][$i]) }}</td>
                    <td>{{ FormatHasil::rupiah($skala['pendapatan'][$i]) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
