@php use App\Services\Toko\FormatHasil; @endphp
<h3 class="mb-2 font-bold">{{ $judul }}</h3>
<ul class="divide-y divide-garis-halus">
    @foreach ($baris as $b)
        <li class="grid gap-x-4 gap-y-1 py-3 sm:grid-cols-[minmax(0,13rem)_minmax(0,1fr)_auto] sm:items-center">
            <span class="font-bold">{{ $b['label'] }}</span>
            <span class="order-last sm:order-none">
                <span class="block h-2 rounded-r bg-kopi" style="width: {{ $b['lebar'] }}%" aria-hidden="true"></span>
            </span>
            <span class="text-sm text-redup tabular-nums sm:text-right">
                Panen <strong class="text-tinta">{{ FormatHasil::kg($b['panen']) }}</strong> · Limbah <strong class="text-tinta">{{ FormatHasil::kg($b['limbah']) }}</strong>
            </span>
        </li>
    @endforeach
</ul>
