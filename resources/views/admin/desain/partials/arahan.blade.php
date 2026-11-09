{{-- Penunjuk ke menu Desain Web dari halaman admin lain. Variabel: $teks. --}}
<div class="flex flex-wrap items-center gap-3 rounded-xl border border-biru/25 bg-biru-lembut p-4">
    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-biru text-white"><x-ikon nama="galeri" class="size-5" /></span>
    <p class="min-w-0 flex-1 basis-56 font-bold text-biru-tua">{{ $teks }}</p>
    <x-tombol varian="kedua" :href="route('admin.desain.index')">Buka Desain Web</x-tombol>
</div>
