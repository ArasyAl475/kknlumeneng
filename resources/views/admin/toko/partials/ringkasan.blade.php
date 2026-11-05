<dl class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
    @foreach ($angka as $label => $nilai)
        <div class="rounded-2xl border border-garis-halus bg-permukaan p-4 shadow-sm">
            <dt class="text-sm font-bold text-redup">{{ $label }}</dt>
            <dd class="mt-1 text-2xl font-bold break-words">{{ $nilai }}</dd>
        </div>
    @endforeach
</dl>
