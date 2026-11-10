{{-- Isian berformat (WYSIWYG). Halaman yang memakainya wajib memuat resources/js/profil/editor.js.
     Tanpa JavaScript, kotak teks biasa tetap tampil dan bisa diisi.
     Variabel: $nama, $label, $nilai (HTML yang sudah dibersihkan), $bantuan (opsional), $wajib (opsional). --}}
<div data-editor data-unggah="{{ route('admin.profil.gambar-isi') }}" data-token="{{ csrf_token() }}">
    <label for="{{ $nama }}" class="mb-1 block font-bold">{{ $label }}</label>
    <textarea id="{{ $nama }}" name="{{ $nama }}" rows="12" class="isian font-mono text-sm"
        @if ($wajib ?? false) required @endif
        aria-describedby="{{ $nama }}-bantuan"
        @error($nama) aria-invalid="true" @enderror>{{ $nilai }}</textarea>
    <p data-editor-status id="{{ $nama }}-status" class="mt-1 min-h-5 text-sm text-redup" role="status" aria-live="polite"></p>
    <p id="{{ $nama }}-bantuan" class="text-sm text-redup">
        {{ $bantuan ?? '' }}
        Pakai tombol di atas kotak untuk menebalkan, memiringkan, mengubah ukuran, membuat daftar, atau menyisipkan foto.
    </p>
    @error($nama)
        <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
    @enderror
</div>
