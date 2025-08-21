<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_back" />

<x-layouts.app :title="($pair['symbol'] ?? 'Unknown').' Details'">

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <h2 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
        {{ $pair['symbol'] ?? 'Unknown' }}
    </h2>
    <a href="/search">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
</div>

<flux:separator text="DETAILS" />

<!-- Grid Layout -->
<div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Pair Info -->
    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow p-6">
        <flux:heading size="xl" class="mb-4">Pair Information</flux:heading>
        <div class="space-y-2 text-zinc-700 dark:text-zinc-300">
            <p><span class="font-medium">Base Asset:</span> {{ $pair['baseAsset'] ?? '-' }}</p>
            <p><span class="font-medium">Quote Asset:</span> {{ $pair['quoteAsset'] ?? '-' }}</p>
            <p><span class="font-medium">Status:</span>
                <span class="px-2 py-1 rounded-md text-sm
                    {{ ($pair['status'] ?? '') === 'TRADING' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $pair['status'] ?? '-' }}
                </span>
            </p>
        </div>
    </div>

    <!-- Live Price -->
    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow p-6">
        <flux:heading size="xl" class="mb-4">📈 Live Price</flux:heading>
        <div class="space-y-3 text-lg">
            <p id="price" class="font-bold text-2xl text-green-600">
                Price ($): {{ $ticker['lastPrice'] ?? '-' }}
            </p>
            <p id="change" class="text-zinc-700 dark:text-zinc-300">
                24h Change: {{ $ticker['priceChangePercent'] ?? '-' }}%
            </p>
            <p id="volume" class="text-zinc-700 dark:text-zinc-300">
                Volume: {{ $ticker['volume'] ?? '-' }}
            </p>
        </div>
    </div>

    <!-- Trading Rules -->
    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow p-6 md:col-span-2">
        <flux:heading size="xl" class="mb-4">⚖️ Trading Rules</flux:heading>
        @php
            $filters = $pair['filters'] ?? [];
            $lot = collect($filters)->first(fn($f) => $f['filterType'] == 'LOT_SIZE');
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-zinc-700 dark:text-zinc-300">
            <p><strong>Lot Size:</strong> {{ $lot['minQty'] ?? '-' }} – {{ $lot['maxQty'] ?? '-' }}</p>
            <p><strong>Step Size:</strong> {{ $lot['stepSize'] ?? '-' }}</p>
        </div>
    </div>
</div>
<a href="{{ route('pair.chart', ['symbol' => $pair['symbol']]) }}">
   <flux:button variant="primary" class="mt-5">Launch Chart</flux:button>
</a>

<script>
const wsSymbol = "{{ $pair['symbol'] ?? 'BTCUSDT' }}".toLowerCase();

const priceEl = document.getElementById('price');
const changeEl = document.getElementById('change');
const volumeEl = document.getElementById('volume');

const tickerWs = new WebSocket(`wss://stream.binance.com:9443/ws/${wsSymbol}@ticker`);

tickerWs.onmessage = (event) => {
    const data = JSON.parse(event.data);
    const price = parseFloat(data.c).toFixed(4);
    const change = parseFloat(data.P).toFixed(2);
    const volume = parseFloat(data.v).toFixed(2);

    priceEl.innerText = `Price ($): ${price}`;
    changeEl.innerText = `24h Change: ${change}%`;
    volumeEl.innerText = `Volume: ${volume}`;

    // Color green/red for price change
    priceEl.classList.toggle("text-green-600", change >= 0);
    priceEl.classList.toggle("text-red-600", change < 0);
};
</script>


</x-layouts.app>
