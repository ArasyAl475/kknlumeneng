@extends('layouts.admin')
@section('judul', 'Desain Web')
@push('skrip')
    @vite('resources/js/desain/admin.js')
@endpush

@php
    $semuaSlot = $kelompok->flatten();
    $jumlahSendiri = $semuaSlot->filter(fn ($s) => ! $s->pakaiBawaan())->count();
@endphp

@section('isi')
    <x-judul-halaman judul="Desain Web" keterangan="Ganti gambar hiasan dan tata letak situs tanpa menyentuh kode. Perubahan langsung tampil setelah disimpan." />

    {{-- Ringkasan dan aturan singkat --}}
    <div class="mb-6 grid gap-3 md:grid-cols-3">
        <div class="rounded-[0.9rem] border border-garis-halus bg-permukaan p-4 shadow-kartu">
            <p class="text-sm font-bold text-redup">Memakai gambar sendiri</p>
            <p class="mt-1 font-judul text-3xl font-extrabold text-navy">{{ $jumlahSendiri }} <span class="text-base font-bold text-redup">dari {{ $semuaSlot->count() }} slot</span></p>
        </div>
        <div class="rounded-[0.9rem] border border-garis-halus bg-permukaan p-4 shadow-kartu">
            <p class="flex items-center gap-2 font-bold"><x-ikon nama="unggah" class="size-5 text-biru" />Format dan ukuran</p>
            <p class="mt-1 text-sm text-redup">JPG, PNG, atau WebP, paling besar {{ $batas }}. Gambar besar otomatis dikecilkan agar ringan dibuka warga.</p>
        </div>
        <div class="rounded-[0.9rem] border border-garis-halus bg-permukaan p-4 shadow-kartu">
            <p class="flex items-center gap-2 font-bold"><x-ikon nama="info" class="size-5 text-biru" />Bukan untuk foto berita</p>
            <p class="mt-1 text-sm text-redup">Foto berita, galeri, dan produk toko tetap dikelola dari menunya masing-masing.</p>
        </div>
    </div>

    {{-- Lompat ke halaman --}}
    <nav aria-label="Lompat ke halaman" class="tanpa-batang-gulir sticky top-0 z-20 -mx-4 mb-6 flex gap-2 overflow-x-auto bg-kertas/95 px-4 py-2 backdrop-blur lg:mx-0 lg:px-0">
        @foreach ($kelompok as $halaman => $daftar)
            <a href="#halaman-{{ \Illuminate\Support\Str::slug($halaman) }}" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full border border-garis-halus bg-permukaan px-4 font-bold hover:border-biru hover:text-biru">
                {{ $halaman }} <span class="rounded-full bg-biru-lembut px-2 text-sm text-biru">{{ $daftar->count() }}</span>
            </a>
        @endforeach
    </nav>

    @foreach ($kelompok as $halaman => $daftar)
        <section id="halaman-{{ \Illuminate\Support\Str::slug($halaman) }}" aria-labelledby="judul-halaman-{{ $loop->index }}" class="mb-10 scroll-mt-20">
            <h2 id="judul-halaman-{{ $loop->index }}" class="mb-4 flex items-center gap-3 text-xl font-extrabold text-navy">
                <span class="h-6 w-1.5 rounded-full bg-aksen" aria-hidden="true"></span>
                Halaman {{ $halaman }}
            </h2>

            <div class="grid gap-5 lg:grid-cols-2">
                @foreach ($daftar as $slot)
                    @php
                        $galat = $errors->getBag('slot'.$slot->id);
                        $alt = $galat->any() ? old('alt', $slot->alt) : $slot->alt;
                        $sendiri = ! $slot->pakaiBawaan();
                    @endphp
                    <article id="slot-{{ $slot->id }}" data-slot class="scroll-mt-20 overflow-hidden rounded-[0.9rem] border border-garis-halus bg-permukaan shadow-kartu">
                        <div class="relative bg-krem-tua" style="aspect-ratio: {{ $slot->rasio }}">
                            <img src="{{ site_image($slot->kunci) }}" alt="Pratinjau {{ $slot->nama }}" data-pratinjau loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover">
                            <span @class([
                                'absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-bold shadow',
                                'bg-biru text-white' => $sendiri,
                                'bg-white text-tinta' => ! $sendiri,
                            ])>
                                <x-ikon :nama="$sendiri ? 'aman' : 'galeri'" class="size-4" />
                                {{ $sendiri ? 'Gambar sendiri' : 'Gambar bawaan' }}
                            </span>
                            <span data-lencana-pratinjau hidden class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-aksen px-3 py-1 text-sm font-bold text-white shadow">
                                <x-ikon nama="info" class="size-4" />Pratinjau, belum disimpan
                            </span>
                        </div>

                        <div class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="text-lg font-extrabold">{{ $slot->nama }}</h3>
                                <code class="rounded bg-krem px-2 py-0.5 text-xs text-redup">{{ $slot->kunci }}</code>
                            </div>
                            <p class="mt-2 flex gap-2 text-redup"><x-ikon nama="lokasi" class="mt-0.5 size-4 shrink-0 text-biru" /><span><span class="font-bold text-tinta">Muncul di:</span> {{ $slot->penjelasan }}</span></p>
                            <dl class="mt-3 grid gap-x-4 gap-y-1 text-sm sm:grid-cols-2">
                                <div><dt class="inline text-redup">Ukuran disarankan:</dt> <dd class="inline font-bold">{{ $slot->ukuran_saran }}</dd></div>
                                <div><dt class="inline text-redup">Terakhir diubah:</dt> <dd class="inline font-bold">{{ $sendiri ? $slot->updated_at?->format('d-m-Y H:i').' WIB' : 'belum pernah' }}</dd></div>
                            </dl>

                            <form method="POST" action="{{ route('admin.desain.update', $slot) }}" enctype="multipart/form-data" class="mt-4 space-y-4 border-t border-garis-halus pt-4" data-simpan-slot>
                                @csrf
                                @method('PUT')

                                <div>
                                    <p class="mb-1 font-bold" id="label-gambar-{{ $slot->id }}">Ganti gambar</p>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-[0.7rem] border border-garis bg-permukaan px-4 font-judul font-bold hover:border-biru hover:bg-biru-lembut focus-within:outline-3 focus-within:outline-offset-2 focus-within:outline-biru">
                                            <x-ikon nama="unggah" class="size-5 text-biru" />
                                            Pilih gambar
                                            <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp" class="sr-only"
                                                   data-maks-kb="{{ $maksKb }}" aria-labelledby="label-gambar-{{ $slot->id }}" aria-describedby="bantuan-gambar-{{ $slot->id }}"
                                                   @if ($galat->has('gambar')) aria-invalid="true" @endif>
                                        </label>
                                        <span data-nama-berkas class="min-w-0 text-sm text-redup">Belum ada gambar baru dipilih.</span>
                                        <button type="button" data-batal hidden class="inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-sm font-bold text-redup hover:bg-krem hover:text-tinta">
                                            <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>Batal
                                        </button>
                                    </div>
                                    <p id="bantuan-gambar-{{ $slot->id }}" class="mt-1 text-sm text-redup">JPG, PNG, atau WebP, paling besar {{ $batas }}. Pratinjau tampil di atas sebelum disimpan.</p>
                                    <p data-galat-berkas role="alert" hidden class="mt-1 text-sm font-bold text-awas"></p>
                                    @if ($galat->has('gambar'))
                                        <p class="mt-1 text-sm font-bold text-awas">{{ $galat->first('gambar') }}</p>
                                    @endif
                                </div>

                                <div>
                                    <label for="alt-{{ $slot->id }}" class="mb-1 block font-bold">Teks alternatif (alt)</label>
                                    <input id="alt-{{ $slot->id }}" name="alt" type="text" maxlength="200" value="{{ $alt }}" class="isian"
                                           aria-describedby="bantuan-alt-{{ $slot->id }}" @if ($galat->has('alt')) aria-invalid="true" @endif>
                                    <p id="bantuan-alt-{{ $slot->id }}" class="mt-1 text-sm text-redup">Gambaran singkat isi gambar untuk pembaca layar. Kosongkan bila gambar hanya hiasan latar.</p>
                                    @if ($galat->has('alt'))
                                        <p class="mt-1 text-sm font-bold text-awas">{{ $galat->first('alt') }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <x-tombol data-tombol-simpan><x-ikon nama="aman" class="size-5" /><span data-teks>Simpan</span></x-tombol>
                                </div>
                            </form>

                            @if ($sendiri)
                                <form method="POST" action="{{ route('admin.desain.pulihkan', $slot) }}" class="mt-2" data-konfirmasi="Kembalikan “{{ $slot->nama }}” ke gambar bawaan? Gambar unggahan Anda akan dihapus.">
                                    @csrf
                                    <x-tombol varian="kedua"><x-ikon nama="pulihkan" class="size-5" />Pulihkan bawaan</x-tombol>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
