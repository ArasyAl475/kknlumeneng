<fieldset class="grid gap-2 rounded-md border border-garis-halus p-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
    <legend class="sr-only">Spesifikasi</legend>
    <div>
        <label for="spesifikasi-{{ $i }}-label" class="mb-1 block text-sm font-bold">Nama</label>
        <input id="spesifikasi-{{ $i }}-label" name="spesifikasi[{{ $i }}][label]" maxlength="60" class="isian" placeholder="contoh: Berat" value="{{ $b['label'] ?? '' }}"
            @error("spesifikasi.{$i}.label") aria-invalid="true" @enderror>
        @error("spesifikasi.{$i}.label")
            <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="spesifikasi-{{ $i }}-nilai" class="mb-1 block text-sm font-bold">Isi</label>
        <input id="spesifikasi-{{ $i }}-nilai" name="spesifikasi[{{ $i }}][nilai]" maxlength="200" class="isian" placeholder="contoh: 250 g" value="{{ $b['nilai'] ?? '' }}"
            @error("spesifikasi.{$i}.nilai") aria-invalid="true" @enderror>
        @error("spesifikasi.{$i}.nilai")
            <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
        @enderror
    </div>
</fieldset>
