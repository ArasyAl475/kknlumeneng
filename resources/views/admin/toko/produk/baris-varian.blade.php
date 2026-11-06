<fieldset class="grid gap-2 rounded-md border border-garis-halus p-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,2fr)_auto] sm:items-end">
    <legend class="sr-only">Varian</legend>
    <div>
        <label for="varian-{{ $i }}-nama" class="mb-1 block text-sm font-bold">Nama varian</label>
        <input id="varian-{{ $i }}-nama" name="varian[{{ $i }}][nama]" maxlength="60" class="isian" placeholder="contoh: 250 g" value="{{ $b['nama'] ?? '' }}"
            @error("varian.{$i}.nama") aria-invalid="true" @enderror>
        @error("varian.{$i}.nama")
            <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="varian-{{ $i }}-harga" class="mb-1 block text-sm font-bold">Harga (Rp)</label>
        <input id="varian-{{ $i }}-harga" name="varian[{{ $i }}][harga]" inputmode="numeric" maxlength="15" class="isian" value="{{ $b['harga'] ?? '' }}"
            @error("varian.{$i}.harga") aria-invalid="true" @enderror>
        @error("varian.{$i}.harga")
            <p class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <input type="hidden" name="varian[{{ $i }}][tersedia]" value="0">
        <label for="varian-{{ $i }}-tersedia" class="flex min-h-11 items-center gap-2 font-bold">
            <input type="checkbox" id="varian-{{ $i }}-tersedia" name="varian[{{ $i }}][tersedia]" value="1" class="size-6" @checked(($b['tersedia'] ?? '1') == '1')>
            Tersedia
        </label>
    </div>
</fieldset>
