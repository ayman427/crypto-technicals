@php
    $topGainers = $topGainers ?? collect();
    $topLosers = $topLosers ?? collect();
    $volumeGainers = $volumeGainers ?? collect();
@endphp

<x-layouts.app :title="__('Dashboard')">
    <div class="container mx-auto px-4 py-6">

        <!-- Filters -->
        <div class="mb-6 p-4 rounded-xl shadow">
            <form id="filters" class="flex flex-wrap gap-4 items-center w-full">
                <label class="flex flex-col text-sm font-medium">
    Min Volume
    <input type="number" name="min_volume" value="1000000"
        class="mt-1 w-full border border-neutral-300 dark:border-neutral-700 rounded-lg px-3 py-2 text-sm dark:bg-neutral-900 dark:text-neutral-200">
</label>

<label class="flex flex-col text-sm font-medium">
    Show
    <flux:select name="type" placeholder="Sort"
        class="mt-1 w-full border border-neutral-300 dark:border-neutral-700 rounded-lg px-3 py-2 text-sm dark:bg-neutral-900 dark:text-neutral-200">
        <flux:select.option value="all">All</flux:select.option>
        <flux:select.option value="gainers">Gainers</flux:select.option>
        <flux:select.option value="losers">Losers</flux:select.option>
    </flux:select>
</label>

<label class="flex flex-col text-sm font-medium">
    Search
    <input type="text" name="search" placeholder="BTCUSDT"
        class="mt-1 w-full border border-neutral-300 dark:border-neutral-700 rounded-lg px-3 py-2 text-sm dark:bg-neutral-900 dark:text-neutral-200">
</label>

            </form>
        </div>

        <!-- Sections -->
        <div class="container mx-auto sm:px-4 lg:px-6 py-6">
    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-4 mb-6 p-4 rounded-xl shadow">
        <!-- your existing filters code -->
    </div>

    <!-- 3-column layout -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Gainers -->
        <section data-type="gainers" class="w-full">
    <h2 class="text-lg font-semibold mb-3">🚀 Top Gainers</h2>
    <div id="gainers" class="space-y-2"></div>
</section>

<section data-type="losers" class="w-full">
    <h2 class="text-lg font-semibold mb-3">📉 Top Losers</h2>
    <div id="losers" class="space-y-2"></div>
</section>

<section data-type="volume" class="w-full">
    <h2 class="text-lg font-semibold mb-3">💹 High Volume</h2>
    <div id="volume" class="space-y-2"></div>
</section>


    </div>
</div>

</x-layouts.app>

<script>
let minVolume = 1000000;
let filterType = 'all';
let searchTerm = '';

document.getElementById('filters').addEventListener('input', (e) => {
    const formData = new FormData(e.currentTarget);
    minVolume = parseFloat(formData.get('min_volume')) || 0;
    filterType = formData.get('type');
    searchTerm = formData.get('search').toUpperCase();
});

const socket = new WebSocket('wss://stream.binance.com:9443/ws/!ticker@arr');

socket.onmessage = (event) => {
    const tickers = JSON.parse(event.data);
    const usdtPairs = tickers.filter(t =>
        t.s.endsWith('USDT') &&
        t.q > minVolume &&
        !['BUSDUSDT','FDUSDUSDT','USDCUSDT'].includes(t.s)
    );

    const coins = usdtPairs.map(t => ({
        symbol: t.s,
        priceChangePercent: parseFloat(t.P),
        quoteVolume: parseFloat(t.q)
    }));

    const filtered = searchTerm
        ? coins.filter(c => c.symbol.includes(searchTerm))
        : coins;

    let gainers = [...filtered].sort((a,b) => b.priceChangePercent - a.priceChangePercent).slice(0,10);
    let losers  = [...filtered].sort((a,b) => a.priceChangePercent - b.priceChangePercent).slice(0,10);
    let volume  = [...filtered].sort((a,b) => b.quoteVolume - a.quoteVolume).slice(0,10);

    // Select each section wrapper
const gainersSection = document.querySelector('section[data-type="gainers"]');
const losersSection  = document.querySelector('section[data-type="losers"]');
const volumeSection  = document.querySelector('section[data-type="volume"]');

// Control visibility based on filterType
if (filterType === 'gainers') {
    gainersSection.style.display = '';
    losersSection.style.display = 'none';
    volumeSection.style.display = 'none';
} else if (filterType === 'losers') {
    gainersSection.style.display = 'none';
    losersSection.style.display = '';
    volumeSection.style.display = 'none';
} else {
    gainersSection.style.display = '';
    losersSection.style.display = '';
    volumeSection.style.display = '';
}


    updateSection('#gainers', gainers, 'green');
    updateSection('#losers', losers, 'red');
    updateSection('#volume', volume, 'blue');
};

function updateSection(id, data, color) {
    const container = document.querySelector(id);
    if (!container) return;
    container.innerHTML = data.map(c => `
        <a href="/pair/${c.symbol}/chart"
           class="flex justify-between items-center p-3 rounded-lg border border-neutral-200 dark:border-neutral-700
                  bg-${color}-50 dark:bg-${color}-900/20 shadow hover:shadow-md transition
                  hover:scale-[1.01] transform">
            <div class="font-semibold">${c.symbol}</div>
            <div class="text-${color}-600 dark:text-${color}-400 font-bold">
                ${c.priceChangePercent.toFixed(2)}%
            </div>
            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                Vol: ${Math.round(c.quoteVolume).toLocaleString()}
            </div>
        </a>
    `).join('');
}



</script>
