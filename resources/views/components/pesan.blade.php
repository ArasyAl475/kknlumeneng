{{-- Pesan hasil aksi dan galat formulir; dibaca pembaca layar lewat role status/alert. --}}
@if (session('sukses'))
    <div role="status" data-warna="hijau" class="status-lembut status-garis mb-5 flex items-start gap-3 rounded-xl border-2 p-4 font-bold">
        <x-ikon nama="aman" class="mt-0.5 size-5 shrink-0" />
        <span>{{ session('sukses') }}</span>
    </div>
@endif
@if (session('galat'))
    <div role="alert" data-warna="merah" class="status-lembut status-garis mb-5 flex items-start gap-3 rounded-xl border-2 p-4 font-bold">
        <x-ikon nama="awas" class="mt-0.5 size-5 shrink-0" />
        <span>{{ session('galat') }}</span>
    </div>
@endif
@if ($errors->any())
    <div role="alert" data-warna="merah" class="status-lembut status-garis mb-5 rounded-xl border-2 p-4">
        <p class="flex items-center gap-2 font-bold">
            <x-ikon nama="awas" class="size-5 shrink-0" />
            Periksa kembali isian berikut:
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-8 text-sm">
            @foreach ($errors->all() as $galat)
                <li>{{ $galat }}</li>
            @endforeach
        </ul>
    </div>
@endif
