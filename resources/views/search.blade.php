<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=token" />
<x-layouts.app title="Search Pairs">
    <h2 class="flex items-center text-2xl font-bold mb-4">Search Crypto Pairs<span class="material-symbols-outlined" style="font-size: 36px;">token</span></h2>
    <form method="GET" action="/search" class="flex gap-2 mb-4">
        <flux:input type="text" name="q" value="{{ $q }}"
               placeholder="Enter symbol (e.g. BTCUSDT)"/>
        <button>Search</button>
    </form>
    @if($q === '')
        <p class="text-gray-500">🔍 Start typing to search trading pairs...</p>
    @else
        <ul class="divide-y bg-white shadow rounded">
            @forelse($symbols as $s)
                <li class="p-3 bg-white dark:bg-zinc-800 flex justify-between">
                    <span>{{ $s['symbol'] }} ({{ $s['baseAsset'] }}/{{ $s['quoteAsset'] }})</span>
                    <a href="{{ route('pair.details', $s['symbol']) }}"
                       class="text-indigo-600 font-semibold"><flux:button>View</flux:button></a>
                </li>
            @empty
                <li class="p-3 text-red-500">No results found.</li>
            @endforelse
        </ul>
    @endif
</x-layouts.app>
