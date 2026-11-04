@extends('layouts.app')
@section('title', 'Simulator')

@section('content')
    <div class="mx-auto max-w-xl space-y-5">
        <div>
            <h1 class="text-3xl font-bold">Simulator data sensor</h1>
            <p class="mt-1 text-slate-700">Kirim data contoh untuk menguji status, validasi admin, dan Telegram tanpa alat asli.</p>
        </div>

        @if (session('sukses'))
            <div role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-emerald-900">{{ session('sukses') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-900">
                @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('simulator.store') }}" class="blok space-y-4">
            @csrf
            <div>
                <label for="lokasi" class="mb-1 block font-bold">Wilayah</label>
                <select id="lokasi" name="lokasi" required class="w-full rounded-lg border border-slate-300 p-3">
                    @foreach ($lokasi as $nama)
                        <option value="{{ $nama }}" @selected(old('lokasi') === $nama)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="kelembapan" class="mb-1 block font-bold">Kelembapan tanah (0-100%)</label>
                <input id="kelembapan" name="kelembapan" type="number" min="0" max="100" step="any"
                       value="{{ old('kelembapan', 30) }}" class="w-full rounded-lg border border-slate-300 p-3 text-2xl font-bold">
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="curah_hujan" class="mb-1 block font-bold">Curah hujan (mm/jam)</label>
                    <input id="curah_hujan" name="curah_hujan" type="number" min="0" max="1000" step="any"
                           value="{{ old('curah_hujan', 0) }}" class="w-full rounded-lg border border-slate-300 p-3">
                </div>
                <div>
                    <label for="kemiringan" class="mb-1 block font-bold">Kemiringan (derajat)</label>
                    <input id="kemiringan" name="kemiringan" type="number" min="-90" max="90" step="any"
                           value="{{ old('kemiringan', 0) }}" class="w-full rounded-lg border border-slate-300 p-3">
                </div>
                <div>
                    <label for="kelembapan_udara" class="mb-1 block font-bold">Kelembapan udara (%)</label>
                    <input id="kelembapan_udara" name="kelembapan_udara" type="number" min="0" max="100" step="any"
                           value="{{ old('kelembapan_udara', 70) }}" class="w-full rounded-lg border border-slate-300 p-3">
                </div>
            </div>
            <p class="-mt-2 text-sm text-slate-600">Kosongkan kolom untuk meniru sensor yang rusak / tidak mengirim nilai.</p>

            <div class="flex flex-wrap gap-2">
                <button type="button" data-preset='{"kelembapan":30,"curah_hujan":0,"kemiringan":0,"kelembapan_udara":65}' class="preset rounded-lg border border-slate-300 px-3 py-1">Contoh aman</button>
                <button type="button" data-preset='{"kelembapan":{{ $batas["waspada"] + 5 }},"curah_hujan":{{ $batas["hujan_waspada"] + 2 }},"kemiringan":{{ $batas["miring_waspada"] + 1 }},"kelembapan_udara":85}' class="preset rounded-lg border border-slate-300 px-3 py-1">Contoh waspada</button>
                <button type="button" data-preset='{"kelembapan":30,"curah_hujan":{{ $batas["hujan_validasi"] + 5 }},"kemiringan":0,"kelembapan_udara":95}' class="preset rounded-lg border border-slate-300 px-3 py-1">Hujan sangat lebat (perlu validasi)</button>
                <button type="button" data-preset='{"kelembapan":{{ $batas["validasi"] + 5 }},"curah_hujan":5,"kemiringan":0,"kelembapan_udara":90}' class="preset rounded-lg border border-slate-300 px-3 py-1">Tanah jenuh (perlu validasi)</button>
                <button type="button" data-preset='{"kelembapan":60,"curah_hujan":8,"kemiringan":{{ $batas["miring_validasi"] + 3 }},"kelembapan_udara":88}' class="preset rounded-lg border border-slate-300 px-3 py-1">Lereng miring (perlu validasi)</button>
            </div>

            <label class="flex items-center gap-3">
                <input type="checkbox" id="kabel_putus" name="kabel_putus" value="1" class="h-5 w-5" @checked(old('kabel_putus'))>
                <span class="font-bold">Simulasikan kabel EWS putus (langsung AWAS)</span>
            </label>

            <p>Hasil status: <span id="pratinjau" class="lencana" data-warna="hijau">Aman</span></p>

            <button type="submit" class="w-full rounded-lg bg-[#14232B] p-3 font-bold text-white hover:bg-slate-700">
                <i class="fa-solid fa-paper-plane"></i> Kirim data simulasi
            </button>
        </form>

        <p class="text-sm text-slate-600">Nilai sensor yang mencapai batas validasi (tanah {{ $batas['validasi'] }}%, hujan {{ $batas['hujan_validasi'] }} mm/jam, kemiringan {{ $batas['miring_validasi'] }}°) masuk antrean <a class="underline" href="{{ route('admin.dashboard') }}">validasi admin</a>. Status Awas hanya dari kabel putus atau persetujuan admin.</p>
    </div>
@endsection

@push('scripts')
<script>
    const BATAS = @json($batas);
    const kolom = { kelembapan: 'kelembapan', curah_hujan: 'curah_hujan', kemiringan: 'kemiringan', kelembapan_udara: 'kelembapan_udara' };
    const el = Object.fromEntries(Object.keys(kolom).map(k => [k, document.getElementById(k)]));
    const kabel = document.getElementById('kabel_putus');
    const pratinjau = document.getElementById('pratinjau');

    // Mengembalikan 0 / 1 / 2 / null, sama dengan PenilaianStatus di server
    function level(v, waspada, validasi) {
        if (v === '' || isNaN(parseFloat(v))) return null;
        const n = parseFloat(v);
        return n >= validasi ? 2 : (n >= waspada ? 1 : 0);
    }

    // Pratinjau memakai aturan yang sama dengan controller
    function perbarui() {
        const lv = [
            level(el.kelembapan.value, BATAS.waspada, BATAS.validasi),
            level(el.curah_hujan.value, BATAS.hujan_waspada, BATAS.hujan_validasi),
            level(el.kemiringan.value === '' ? '' : Math.abs(parseFloat(el.kemiringan.value)), BATAS.miring_waspada, BATAS.miring_validasi),
        ].filter(x => x !== null);
        const adaIsi = lv.length > 0 || el.kelembapan_udara.value !== '';
        const tertinggi = Math.max(0, ...lv);
        let warna = 'hijau', teks = 'Aman';
        if (kabel.checked)         { warna = 'merah';  teks = 'Awas (otomatis)'; }
        else if (tertinggi === 2)  { warna = 'kuning'; teks = 'Waspada, menunggu validasi admin'; }
        else if (tertinggi === 1)  { warna = 'kuning'; teks = 'Waspada'; }
        pratinjau.dataset.warna = (!adaIsi && !kabel.checked) ? 'kosong' : warna;
        pratinjau.textContent = (!adaIsi && !kabel.checked) ? '-' : teks;
    }
    Object.values(el).forEach(i => i.addEventListener('input', perbarui));
    kabel.addEventListener('change', perbarui);
    document.querySelectorAll('.preset').forEach(b => b.addEventListener('click', () => {
        const p = JSON.parse(b.dataset.preset);
        Object.keys(el).forEach(k => { el[k].value = p[k]; });
        perbarui();
    }));
    perbarui();
</script>
@endpush
