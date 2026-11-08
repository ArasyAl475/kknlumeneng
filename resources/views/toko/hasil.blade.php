@extends('toko.kerangka')
@section('judul', 'Hasil panen dan penjualan')
@section('deskripsi', 'Total panen kopi, limbah kopi, panen jamur, dan penjualan jamur Desa Lumeneng, lengkap dengan grafik perkembangannya.')

@php
    use App\Services\Toko\FormatHasil;
    use App\Services\Toko\RekapHasil;

    $deretSeri = [
        'kopi' => 'Panen kopi',
        'jamur' => 'Panen jamur',
        'terjual' => 'Jamur terjual',
        'pendapatan' => 'Pendapatan',
    ];
    // Satu sumber ikon untuk kartu angka, pilihan grafik, dan ringkasan, supaya selalu sama.
    $ikonSeri = [
        'kopi' => 'biji-kopi',
        'jamur' => 'jamur',
        'terjual' => 'terjual',
        'pendapatan' => 'uang',
    ];
    $labelLalu = $perubahan['label'] ?? '';
    // "Lihat semua waktu" tetap membawa pilihan jenis dan tempat.
    $semuaWaktu = route('toko.hasil', $periode['pilihan'][0]['kueri']);
    $penjualanTanpaMedia = round($penjualan['kg'] - collect($perbandingan)->sum('terjual_kg'), 2) > 0;
@endphp

@push('kepala')
    <noscript><style>[data-buka-filter], [data-kontrol], [data-tombol-tabel] { display: none; } .lembar { display: block; position: static; width: auto; height: auto; margin-top: 1rem; border-radius: 0.75rem; } .lembar__kepala button { display: none; }</style></noscript>
@endpush

@section('isi')
    {{-- KEPALA HALAMAN: judul + penanda status + pilihan waktu (berlaku untuk semua bagian di bawahnya) --}}
    <section class="kepala-halaman" aria-label="Judul dan pilihan data">
        <div class="kepala-halaman__teks">
            <p class="kepala-halaman__label">
                @include('toko.partials.ikon', ['nama' => 'terverifikasi', 'kelas' => 'size-5'])
                Sistem Informasi Agraria Terpadu Desa Lumeneng
            </p>
            <h1>Hasil Panen dan Penjualan Komoditas Desa</h1>
            <p class="kepala-halaman__deskripsi">Portal transparansi publik hasil bumi Desa Lumeneng: memantau perkembangan panen kopi rakyat,<br class="kepala-halaman__putus"> inovasi pemanfaatan cangkang kopi, dan produksi jamur tiram warga.</p>
        </div>

        <div class="kepala-halaman__kontrol">
            <div class="status-pill"><span class="status-pill__titik" aria-hidden="true"></span>Transparansi Terbuka untuk Warga</div>

            <div class="kontrol-waktu">
                <nav aria-label="Rentang waktu" class="segmen segmen--atas">
                    @foreach ($periode['pilihan'] as $p)
                        <a href="{{ route('toko.hasil', $p['kueri']) }}" @if ($p['aktif']) aria-current="true" @endif @class(['segmen__item', 'is-aktif' => $p['aktif']])>{{ $p['label'] }}</a>
                    @endforeach
                </nav>
                {{-- Kustom bukan pilihan rentang, tetapi pintu ke filter tanggal/jenis/tempat, jadi dibuat tombol tersendiri. --}}
                <button type="button" class="tombol-kustom" data-buka-filter aria-haspopup="dialog" aria-controls="lembar-filter">
                    @include('toko.partials.ikon', ['nama' => 'filter', 'kelas' => 'size-4'])
                    Kustom
                    @if ($penyaring['jumlah'] > 0)
                        <span class="tombol-filter__jumlah" aria-label="{{ $penyaring['jumlah'] }} pilihan aktif">{{ $penyaring['jumlah'] }}</span>
                    @endif
                </button>
                {{-- Unduhan membawa filter yang sedang aktif, jadi isi Excel sama dengan yang terlihat di layar. --}}
                <a href="{{ route('toko.hasil.excel', array_filter($filter)) }}" class="tombol-kustom" download>
                    @include('toko.partials.ikon', ['nama' => 'unduh', 'kelas' => 'size-4'])
                    Unduh Excel
                </a>
            </div>
        </div>
    </section>

    <div class="saring-bawah">
        @if ($penyaring['chip'])
            <ul class="chip-daftar" aria-label="Pilihan yang sedang aktif">
                @foreach ($penyaring['chip'] as $c)
                    <li>
                        <a href="{{ route('toko.hasil', $c['kueri']) }}" class="chip" aria-label="Hapus pilihan {{ $c['label'] }}">
                            {{ $c['label'] }}
                            @include('toko.partials.ikon', ['nama' => 'tutup', 'kelas' => 'size-4'])
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
        <p class="saring__ket">Menampilkan: <strong>{{ $periode['keterangan'] }}</strong></p>
    </div>

    <dialog id="lembar-filter" class="lembar" aria-labelledby="judul-lembar" data-lembar>
        <form method="GET" action="{{ route('toko.hasil') }}" class="lembar__isi">
            <div class="lembar__kepala">
                <h2 id="judul-lembar">Filter</h2>
                <button type="button" class="tombol-ikon" data-tutup-filter aria-label="Tutup filter">
                    @include('toko.partials.ikon', ['nama' => 'tutup', 'kelas' => 'size-6'])
                </button>
            </div>
            <div class="lembar__badan">
                <div>
                    <label for="dari" class="label-h">Dari tanggal</label>
                    <input id="dari" name="dari" type="date" class="isian-h" value="{{ $filter['dari'] ?? '' }}">
                </div>
                <div>
                    <label for="sampai" class="label-h">Sampai tanggal</label>
                    <input id="sampai" name="sampai" type="date" class="isian-h" value="{{ $filter['sampai'] ?? '' }}">
                </div>
                <div>
                    <label for="jenis" class="label-h">Jenis kopi</label>
                    <select id="jenis" name="jenis" class="isian-h">
                        <option value="">Semua jenis</option>
                        @foreach ($pilihan['jenis'] as $jenis)
                            <option value="{{ $jenis }}" @selected(($filter['jenis'] ?? null) === $jenis)>{{ $jenis }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tempat" class="label-h">Tempat panen kopi</label>
                    <select id="tempat" name="tempat" class="isian-h">
                        <option value="">Semua tempat</option>
                        @foreach ($pilihan['tempat'] as $tempat)
                            <option value="{{ $tempat }}" @selected(($filter['tempat'] ?? null) === $tempat)>{{ $tempat }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="bantu-h">Jenis dan tempat hanya berlaku untuk data kopi. Tanggal berlaku untuk semua data.</p>
            </div>
            <div class="lembar__kaki">
                <button type="submit" class="tombol-h tombol-h--utama">Tampilkan</button>
                <a href="{{ route('toko.hasil') }}" class="tombol-h tombol-h--tepi">Atur ulang</a>
            </div>
        </form>
    </dialog>

    {{-- RINGKASAN: satu kartu sorotan (uang) + empat kartu angka. Satu angka = satu kartu, tanpa kartu di dalam kartu. --}}
    <section aria-labelledby="judul-ringkasan" class="ringkasan">
        <h2 id="judul-ringkasan" class="sr-only">Ringkasan</h2>
        @if ($perubahan)
            <p class="ringkasan__ket">{{ $perubahan['keterangan'] }}</p>
        @endif

        <div class="metrik">
            <article class="kartu-hero" aria-labelledby="judul-uang">
                <div class="kartu__atas">
                    <h3 id="judul-uang" class="kartu__label">Uang dari jamur terjual</h3>
                    <span class="medalion" aria-hidden="true">@include('toko.partials.ikon', ['nama' => $ikonSeri['pendapatan'], 'kelas' => 'size-5'])</span>
                </div>
                @include('toko.partials.angka', ['awalan' => 'Rp', 'nilai' => FormatHasil::angka($penjualan['rupiah'], 0), 'ukuran' => 'angka--hero', 'kosong' => $penjualan['rupiah'] <= 0])
                <span class="lencana lencana--gelap">{{ $periode['keterangan'] }}</span>
                <div class="kartu__kaki">
                    @if ($penjualan['rupiah'] <= 0)
                        <p>Belum ada penjualan jamur pada pilihan ini.</p>
                    @else
                        <p>Hasil menjual {{ FormatHasil::kg($penjualan['kg']) }} jamur.</p>
                        @include('toko.partials.perubahan', ['p' => $perubahan['pendapatan'] ?? null, 'label' => $labelLalu])
                    @endif
                </div>
            </article>

            <article class="kartu kartu--angka">
                <div class="kartu__atas">
                    <h3 class="kartu__label">Panen kopi</h3>
                    <span class="medalion medalion--terang" aria-hidden="true">@include('toko.partials.ikon', ['nama' => $ikonSeri['kopi'], 'kelas' => 'size-5'])</span>
                </div>
                @include('toko.partials.angka', ['nilai' => FormatHasil::angka($kopi['panen']), 'satuan' => 'kg', 'ukuran' => 'angka--metrik', 'kosong' => $kopi['panen'] <= 0])
                @if ($kopi['panen'] <= 0)
                    <p class="kartu__ket">Belum ada catatan panen kopi.</p>
                @else
                    <p class="kartu__ket">{{ count($kopi['per_jenis']) }} jenis · {{ count($kopi['per_tempat']) }} tempat panen</p>
                    @if (! empty($perubahan['kopi']))
                        <div class="kartu__kaki">@include('toko.partials.perubahan', ['p' => $perubahan['kopi'], 'label' => $labelLalu])</div>
                    @endif
                @endif
            </article>

            <article class="kartu kartu--angka">
                <div class="kartu__atas">
                    <h3 class="kartu__label">Limbah kopi (cangkang)</h3>
                    <span class="medalion medalion--terang" aria-hidden="true">@include('toko.partials.ikon', ['nama' => 'biji-kopi', 'kelas' => 'size-5'])</span>
                </div>
                @include('toko.partials.angka', ['nilai' => FormatHasil::angka($kopi['limbah']), 'satuan' => 'kg', 'ukuran' => 'angka--metrik', 'kosong' => $kopi['limbah'] <= 0])
                @if ($kopi['limbah'] <= 0)
                    <p class="kartu__ket">Belum ada catatan limbah.</p>
                @else
                    <p class="kartu__ket">{{ FormatHasil::persen($kopi['persen_limbah']) }} dari panen kopi</p>
                    <div class="meter" aria-hidden="true"><span class="meter__isi" style="width: {{ min($kopi['persen_limbah'], 100) }}%"></span></div>
                    @if (! empty($perubahan['limbah']))
                        <div class="kartu__kaki">@include('toko.partials.perubahan', ['p' => $perubahan['limbah'], 'label' => $labelLalu])</div>
                    @endif
                @endif
            </article>

            <article class="kartu kartu--angka">
                <div class="kartu__atas">
                    <h3 class="kartu__label">Panen jamur</h3>
                    <span class="medalion medalion--terang" aria-hidden="true">@include('toko.partials.ikon', ['nama' => $ikonSeri['jamur'], 'kelas' => 'size-5'])</span>
                </div>
                @include('toko.partials.angka', ['nilai' => FormatHasil::angka($jamur['total']), 'satuan' => 'kg', 'ukuran' => 'angka--metrik', 'kosong' => $jamur['total'] <= 0])
                @if ($jamur['total'] <= 0)
                    <p class="kartu__ket">Belum ada catatan panen jamur.</p>
                @else
                    <p class="kartu__ket">{{ FormatHasil::persen($jamur['persen_cangkang']) }} memakai cangkang kopi</p>
                    <div class="meter" aria-hidden="true"><span class="meter__isi" style="width: {{ min($jamur['persen_cangkang'], 100) }}%"></span></div>
                    @if (! empty($perubahan['jamur']))
                        <div class="kartu__kaki">@include('toko.partials.perubahan', ['p' => $perubahan['jamur'], 'label' => $labelLalu])</div>
                    @endif
                @endif
            </article>

            <article class="kartu kartu--angka">
                <div class="kartu__atas">
                    <h3 class="kartu__label">Jamur terjual</h3>
                    <span class="medalion medalion--terang" aria-hidden="true">@include('toko.partials.ikon', ['nama' => $ikonSeri['terjual'], 'kelas' => 'size-5'])</span>
                </div>
                @include('toko.partials.angka', ['nilai' => FormatHasil::angka($penjualan['kg']), 'satuan' => 'kg', 'ukuran' => 'angka--metrik', 'kosong' => $penjualan['kg'] <= 0])
                @if ($penjualan['kg'] <= 0)
                    <p class="kartu__ket">Belum ada penjualan.</p>
                @else
                    <p class="kartu__ket">Sudah sampai ke pembeli</p>
                    @if (! empty($perubahan['terjual']))
                        <div class="kartu__kaki">@include('toko.partials.perubahan', ['p' => $perubahan['terjual'], 'label' => $labelLalu])</div>
                    @endif
                @endif
            </article>
        </div>
    </section>

    {{-- BARIS UTAMA 7:5 — grafik perkembangan dan perbandingan cangkang kopi, masing-masing satu kartu --}}
    <div class="utama">
        <section aria-labelledby="judul-grafik" class="bagian bagian--tren" id="grafik">
            <div class="bagian__kepala">
                <h2 id="judul-grafik" class="bagian__judul">Perkembangan Hasil Desa</h2>
                <p class="bagian__ket">Rentang waktu mengikuti pilihan di atas: {{ $periode['keterangan'] }}.</p>
            </div>

            @if ($grafik['ada'])
                <div class="grafik" data-grafik data-data="{{ json_encode($grafik['skala']) }}">
                    <div class="grafik__kontrol" data-kontrol>
                        <div class="segmen segmen--kecil" role="group" aria-label="Skala waktu grafik">
                            @foreach (['hari' => 'Hari', 'bulan' => 'Bulan', 'tahun' => 'Tahun'] as $kunci => $nama)
                                <button type="button" class="segmen__item" data-skala="{{ $kunci }}" aria-pressed="{{ $kunci === 'bulan' ? 'true' : 'false' }}">{{ $nama }}</button>
                            @endforeach
                        </div>
                        <div class="grafik__seri" role="group" aria-label="Data yang ditampilkan">
                            @foreach ($deretSeri as $kunci => $nama)
                                <button type="button" class="chip-seri" data-seri="{{ $kunci }}" aria-pressed="{{ in_array($kunci, ['kopi', 'jamur']) ? 'true' : 'false' }}">
                                    @include('toko.partials.ikon', ['nama' => $ikonSeri[$kunci], 'kelas' => 'size-5 ikon-seri'])
                                    {{ $nama }}
                                    @include('toko.partials.ikon', ['nama' => 'centang', 'kelas' => 'size-4 chip-seri__centang'])
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <dl class="grafik__ringkas" data-ringkas></dl>
                    <p class="grafik__catatan" data-catatan hidden></p>

                    <div class="grafik__bidang" data-bidang>
                        <div class="grafik__gulir" data-gulir="kg" tabindex="0" role="region" aria-label="Grafik hasil dalam kilogram, bisa digeser ke samping">
                            <div class="grafik__kanvas" data-kanvas-wadah="kg">
                                <canvas data-kanvas="kg" role="img" aria-label="Grafik hasil panen dan penjualan jamur dalam kilogram" aria-describedby="deskripsi-grafik"></canvas>
                            </div>
                        </div>
                        <div data-bagian-rupiah hidden>
                            <h3 class="grafik__subjudul">Pendapatan (Rp)</h3>
                            <div class="grafik__gulir" data-gulir="rupiah" tabindex="0" role="region" aria-label="Grafik pendapatan dalam rupiah, bisa digeser ke samping">
                                <div class="grafik__kanvas grafik__kanvas--rupiah" data-kanvas-wadah="rupiah">
                                    <canvas data-kanvas="rupiah" role="img" aria-label="Grafik pendapatan dari penjualan jamur dalam rupiah" aria-describedby="deskripsi-grafik"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="grafik__lapis" data-muat aria-hidden="true">
                            <div class="kerangka-batang"><span style="height: 40%"></span><span style="height: 65%"></span><span style="height: 50%"></span><span style="height: 85%"></span><span style="height: 60%"></span><span style="height: 75%"></span></div>
                        </div>
                        <div class="grafik__lapis" data-tanpa-data hidden>
                            <div>
                                @include('toko.partials.ikon', ['nama' => 'grafik', 'kelas' => 'size-8'])
                                <p><strong data-tanpa-judul></strong></p>
                                <p class="bantu-h" data-tanpa-ket></p>
                            </div>
                        </div>
                        <div class="grafik__lapis" data-galat role="alert" hidden>
                            <div>
                                <p><strong>Grafik belum bisa dimuat.</strong></p>
                                <p class="bantu-h">Sinyal mungkin lemah. Angka lengkap tetap bisa dilihat di tabel.</p>
                                <button type="button" class="tombol-h tombol-h--utama" data-coba-lagi style="margin-top: 14px">Coba lagi</button>
                            </div>
                        </div>
                    </div>

                    <p id="deskripsi-grafik" class="sr-only" data-deskripsi></p>

                    <div class="grafik__aksi">
                        <button type="button" class="tombol-tautan" data-tombol-tabel aria-expanded="false" aria-controls="tabel-grafik">
                            @include('toko.partials.ikon', ['nama' => 'tabel', 'kelas' => 'size-5'])
                            <span data-teks-tabel>Lihat sebagai tabel</span>
                        </button>
                    </div>
                    <div id="tabel-grafik" data-tabel hidden></div>

                    <noscript>
                        @include('toko.partials.tabel-grafik', ['skala' => $grafik['skala']['bulan']])
                    </noscript>
                </div>
            @else
                <div class="grafik-kosong">
                    <div class="kerangka-batang" aria-hidden="true"><span style="height: 30%"></span><span style="height: 55%"></span><span style="height: 40%"></span><span style="height: 70%"></span><span style="height: 50%"></span><span style="height: 62%"></span><span style="height: 35%"></span><span style="height: 58%"></span></div>
                    <div class="grafik-kosong__pesan">
                        @include('toko.partials.ikon', ['nama' => 'grafik', 'kelas' => 'size-8'])
                        <strong>Belum ada data untuk periode ini</strong>
                        @if ($penyaring['ada'])
                            <span class="bantu-h">Coba lihat catatan dari seluruh waktu.</span>
                            <a href="{{ $semuaWaktu }}" class="tombol-h tombol-h--utama">Lihat semua waktu</a>
                        @else
                            <span class="bantu-h">Grafik muncul setelah admin desa mencatat panen atau penjualan.</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        {{-- PERBANDINGAN CANGKANG KOPI --}}
        <section aria-labelledby="judul-baglog" class="bagian bagian--banding" id="baglog">
            <div class="bagian__kepala">
                <span class="lencana lencana--tersier">Riset Sirkular KKN &amp; Aksara Peduli</span>
                <h2 id="judul-baglog" class="bagian__judul">Apakah cangkang kopi membantu jamur?</h2>
                <p class="bagian__ket">Hasil jamur per baglog (kantong media tanam), dibandingkan antara yang dicampur cangkang kopi dan yang tidak.</p>
            </div>

            @if ($kesimpulan['cukup'])
                <div class="banding">
                    <div>
                        <p class="banding__selisih">{{ $kesimpulan['selisih'] }}</p>
                        <p class="banding__kalimat">{{ $kesimpulan['kalimat'] }}</p>
                    </div>
                    <ul class="banding__batang">
                        @foreach ([[$kesimpulan['dengan'], ''], [$kesimpulan['tanpa'], 'batang__isi--tanpa']] as [$m, $pola])
                            <li>
                                <div class="banding__label">
                                    <span>{{ $m['label'] }}</span>
                                    <span><strong>{{ FormatHasil::kg($m['kg_per_baglog']) }}</strong> <small>per baglog</small></span>
                                </div>
                                <span class="batang" aria-hidden="true"><span class="batang__isi {{ $pola }}" style="width: {{ $m['lebar'] }}%"></span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="keadaan-kosong">
                    @include('toko.partials.ikon', ['nama' => 'jamur', 'kelas' => 'size-8'])
                    <strong>{{ $kesimpulan['kalimat'] }}</strong>
                    <span>Perbandingan muncul setelah kedua kelompok punya catatan jumlah baglog dan panen.</span>
                </div>
            @endif

            {{-- Dua sisi dipisah garis aksen kiri (bukan kotak), jadi tidak ada kartu di dalam kartu. --}}
            <div class="media-banding">
                @foreach ($perbandingan as $m)
                    <section aria-labelledby="kelompok-{{ $loop->index }}" @class(['media', 'media--sorot' => $loop->first])>
                        <div class="media__kepala">
                            <h3 id="kelompok-{{ $loop->index }}">
                                @include('toko.partials.ikon', ['nama' => $loop->first ? 'terverifikasi' : 'minus-bulat', 'kelas' => 'size-5'])
                                {{ $m['label'] }}
                            </h3>
                            <span @class(['lencana', 'lencana--mint' => $loop->first, 'lencana--netral' => ! $loop->first])>{{ $loop->first ? 'Substrat Eksperimen' : 'Kontrol Konvensional' }}</span>
                        </div>
                        <dl>
                            <div><dt>Jumlah baglog</dt><dd>{{ number_format($m['baglog'], 0, ',', '.') }} baglog</dd></div>
                            <div><dt>Total panen jamur</dt><dd>{{ FormatHasil::kg($m['panen']) }}</dd></div>
                            @unless ($kesimpulan['cukup'])
                                <div><dt>Hasil per baglog</dt><dd>{{ $m['kg_per_baglog'] === null ? 'Belum ada data' : FormatHasil::kg($m['kg_per_baglog']) }}</dd></div>
                            @endunless
                            <div><dt>Jamur terjual</dt><dd>{{ FormatHasil::kg($m['terjual_kg']) }}</dd></div>
                        </dl>
                    </section>
                @endforeach
            </div>
            @if ($penjualanTanpaMedia)
                <p class="catatan-kecil">Sebagian penjualan lama belum mencatat media tanam, jadi tidak masuk hitungan jamur terjual per kelompok.</p>
            @endif
        </section>
    </div>

    {{-- HASIL PER BAGLOG TIAP BULAN --}}
    @if ($grafikBaglog['ada'])
        <section aria-labelledby="judul-baglog-bulanan" class="bagian" id="baglog-bulanan">
            <div class="bagian__kepala">
                <h2 id="judul-baglog-bulanan" class="bagian__judul">Hasil per baglog tiap bulan</h2>
                <p class="bagian__ket">Bulan tanpa catatan baglog tidak digambar sebagai batang.</p>
            </div>
            <div class="grafik grafik-baglog" data-grafik-baglog data-data="{{ json_encode($grafikBaglog) }}">
                <ul class="legenda" aria-label="Keterangan grafik">
                    <li><span class="swatch swatch--dengan" aria-hidden="true"></span> Dengan cangkang kopi</li>
                    <li><span class="swatch swatch--tanpa" aria-hidden="true"></span> Tanpa cangkang kopi</li>
                </ul>
                <div class="grafik__bidang" data-bidang>
                    <div class="grafik__gulir" data-gulir tabindex="0" role="region" aria-label="Grafik hasil jamur per baglog tiap bulan, bisa digeser ke samping">
                        <div class="grafik__kanvas" data-kanvas-wadah>
                            <canvas role="img" aria-label="Grafik batang hasil jamur per baglog tiap bulan, dengan dan tanpa cangkang kopi. Angka lengkap ada di tabel di bawahnya."></canvas>
                        </div>
                    </div>
                    <div class="grafik__lapis" data-muat aria-hidden="true">
                        <div class="kerangka-batang"><span style="height: 45%"></span><span style="height: 70%"></span><span style="height: 55%"></span><span style="height: 80%"></span><span style="height: 60%"></span><span style="height: 75%"></span></div>
                    </div>
                    <div class="grafik__lapis" data-galat role="alert" hidden>
                        <div>
                            <p><strong>Grafik belum bisa dimuat.</strong></p>
                            <p class="bantu-h">Angka lengkap ada di tabel di bawahnya.</p>
                            <button type="button" class="tombol-h tombol-h--utama" data-coba-lagi style="margin-top: 14px">Coba lagi</button>
                        </div>
                    </div>
                </div>
                @if ($grafikBaglog['terpotong'])
                    <p class="catatan-kecil">Menampilkan 36 bulan terakhir saja.</p>
                @endif
                <details class="tabel-lipat">
                    <summary class="tombol-tautan">
                        @include('toko.partials.ikon', ['nama' => 'tabel', 'kelas' => 'size-5'])
                        Lihat sebagai tabel
                    </summary>
                    <div class="grafik__tabel">
                        <table class="tabel-data">
                            <caption>Hasil jamur per baglog tiap bulan.</caption>
                            <thead>
                                <tr><th scope="col">Bulan</th><th scope="col">Dengan cangkang kopi</th><th scope="col">Tanpa cangkang kopi</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($grafikBaglog['lengkap'] as $i => $bulan)
                                    <tr>
                                        <th scope="row">{{ $bulan }}</th>
                                        <td>{{ $grafikBaglog['dengan'][$i] === null ? 'Belum ada catatan' : FormatHasil::kg($grafikBaglog['dengan'][$i]).' per baglog' }}</td>
                                        <td>{{ $grafikBaglog['tanpa'][$i] === null ? 'Belum ada catatan' : FormatHasil::kg($grafikBaglog['tanpa'][$i]).' per baglog' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </section>
    @endif

    {{-- RINCIAN PANEN KOPI --}}
    <section aria-labelledby="judul-rincian" class="bagian" id="rincian">
        <div class="bagian__kepala">
            <h2 id="judul-rincian" class="bagian__judul">Rincian panen kopi</h2>
            <p class="bagian__ket">Catatan timbang panen kopi, yang terbaru di atas. Berat bersih adalah berat kotor dikurangi limbah.</p>
        </div>

        @if (empty($kopi['timbang']))
            <div class="keadaan-kosong">
                @include('toko.partials.ikon', ['nama' => 'kopi', 'kelas' => 'size-8'])
                <strong>Belum ada data panen kopi pada pilihan ini.</strong>
                <span>Coba pilih Semua waktu atau hapus pilihan jenis dan tempat.</span>
            </div>
        @else
            @include('toko.partials.timbang', ['baris' => $kopi['timbang'], 'jumlah' => $kopi['timbang_jumlah'], 'totalKotor' => $kopi['panen'], 'totalBersih' => $kopi['bersih']])
            @if ($kopi['timbang_jumlah'] > count($kopi['timbang']))
                <p class="catatan-kecil">Menampilkan {{ count($kopi['timbang']) }} catatan terbaru dari {{ $kopi['timbang_jumlah'] }}. Unduh Excel untuk melihat semuanya, atau pilih tanggal di Kustom.</p>
            @endif
        @endif
    </section>
@endsection
