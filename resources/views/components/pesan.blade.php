{{-- Pesan hasil aksi dan galat formulir; dibaca pembaca layar lewat role status/alert. --}}
@if (session('sukses'))
    <div role="status" data-warna="hijau" class="status-lembut status-garis mb-4 flex items-start gap-2 rounded-md border p-3 font-bold">
        <x-ikon nama="aman" class="mt-0.5 size-5 shrink-0" /><span>{{ session('sukses') }}</span>
    </div>
@endif
@if (session('galat'))
    <div role="alert" data-warna="merah" class="status-lembut status-garis mb-4 flex items-start gap-2 rounded-md border p-3 font-bold">
        <x-ikon nama="awas" class="mt-0.5 size-5 shrink-0" /><span>{{ session('galat') }}</span>
    </div>
@endif
@if ($errors->any())
    <div role="alert" data-warna="merah" class="status-lembut status-garis mb-4 rounded-md border p-3">
        <p class="flex items-center gap-2 font-bold"><x-ikon nama="awas" />Periksa kembali isian berikut:</p>
        <ul class="mt-1 list-disc pl-8">
            @foreach ($errors->all() as $galat)
                <li>{{ $galat }}</li>
            @endforeach
        </ul>
    </div>
@endif
