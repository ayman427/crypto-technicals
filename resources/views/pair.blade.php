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

    <!-- Chart Section -->
    <flux:heading size="xl" class="mt-5">Chart</flux:heading>
    <div class="flex gap-2 mb-4">
        @foreach(['1m','5m','15m','1h','4h','1d'] as $tf)
            <button class="timeframe-btn px-3 py-1 rounded {{ $loop->first ? 'bg-indigo-500 text-white' : 'bg-gray-300' }}"
                    data-interval="{{ $tf }}">{{ $tf }}</button>
        @endforeach
    </div>

    <div class="w-full h-96 sm:h-[500px] lg:h-[600px]">
        <canvas id="candlestickChart"></canvas>
    </div>

    <style>
        #candlestickChart {
            width: 100% !important;
            height: 100% !important;
             touch-action: none; /* Important for pinch zoom and pan */
}
    </style>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-financial"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>

<script>
const restSymbol = "{{ $pair['symbol'] ?? '' }}";
const wsSymbol   = restSymbol.toLowerCase();

const priceEl = document.getElementById('price');
const changeEl = document.getElementById('change');
const volumeEl = document.getElementById('volume');

let chart;
let candleData = [];
let earliestTime = null;
let candleWs = null;
let interval = '1m';

// Live Price
const tickerWs = new WebSocket(`wss://stream.binance.com:9443/ws/${wsSymbol}@ticker`);
tickerWs.onmessage = (event) => {
    const d = JSON.parse(event.data);
    priceEl.innerText  = `Price ($): ${parseFloat(d.c).toFixed(4)}`;
    changeEl.innerText = `24h Change: ${parseFloat(d.P).toFixed(2)}%`;
    volumeEl.innerText = `Volume: ${parseFloat(d.v).toFixed(2)}`;
    priceEl.classList.toggle("text-green-600", parseFloat(d.P) >= 0);
    priceEl.classList.toggle("text-red-600", parseFloat(d.P) < 0);
};

// Chart creation
let userInteracting = false; // Track if user is panning/zooming
let autoScroll = true;       // Auto-follow latest candle unless user moves

function createChart() {
    if (chart) chart.destroy();
    const ctx = document.getElementById('candlestickChart').getContext('2d');

    chart = new Chart(ctx, {
        type: 'candlestick',
        data: {
            datasets: [{
                label: `${restSymbol} ${interval}`,
                data: candleData,
                color: { up: '#16A34A', down: '#DC2626', unchanged: '#6B7280' }
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            parsing: false,
            animation: false,
            plugins: {
    legend: { display: false },
    zoom: {
        pan: {
            enabled: true,
            mode: 'x',
            modifierKey: null, // Allow pan without pressing shift/alt
            overScaleMode: 'x',
            onPanStart: ({ chart }) => {
                userInteracting = true;
                autoScroll = false;
                chart.canvas.style.cursor = 'grabbing';
            },
            onPanComplete: ({ chart }) => {
                chart.canvas.style.cursor = 'grab';
            }
        },
        zoom: {
            wheel: { enabled: true }, // Mouse wheel zoom for desktop
            pinch: { enabled: true }, // Enables pinch zoom for mobile
            mode: 'x',
            drag: false, // Avoid conflicts with touch scroll
            onZoomStart: () => { userInteracting = true; autoScroll = false; }
        }
    }
},

            scales: {
                x: {
                    type: 'time',
                    time: {
                        tooltipFormat: 'MMM d, HH:mm',
                        unit: interval.includes('d') ? 'day' :
                              interval.includes('h') ? 'hour' : 'minute'
                    },
                    ticks: { autoSkip: true, maxTicksLimit: 10 }
                },
                y: { beginAtZero: false }
            }
        }
    });

    chart.canvas.style.cursor = 'grab';
}


async function loadCandles(selectedInterval) {
    const res = await fetch(`https://api.binance.com/api/v3/klines?symbol=${restSymbol}&interval=${selectedInterval}&limit=500`);
    const data = await res.json();
    candleData = data.map(c => ({
        x: c[0], o:+c[1], h:+c[2], l:+c[3], c:+c[4]
    }));
    earliestTime = candleData[0].x;
    createChart();
    chart.options.scales.x.min = candleData[0].x;
    chart.options.scales.x.max = candleData[candleData.length - 1].x;
    chart.update('none');
}

async function loadMoreHistoricalCandles(selectedInterval, endTime) {
    const res = await fetch(`https://api.binance.com/api/v3/klines?symbol=${restSymbol}&interval=${selectedInterval}&limit=500&endTime=${endTime}`);
    const data = await res.json();
    if (!data.length) return;
    const olderData = data.map(c => ({
        x: c[0], o:+c[1], h:+c[2], l:+c[3], c:+c[4]
    }));
    earliestTime = olderData[0].x;
    candleData = [...olderData, ...candleData];
    chart.config.data.datasets[0].data = candleData;

    // Keep the current view where the user left off
    const scale = chart.scales.x;
    chart.options.scales.x.min = earliestTime;
    chart.options.scales.x.max = scale.max;

    chart.update('none');
}

function startCandleWebSocket(selectedInterval) {
    if (candleWs) candleWs.close();
    candleWs = new WebSocket(`wss://stream.binance.com:9443/ws/${wsSymbol}@kline_${selectedInterval}`);
    candleWs.onmessage = (event) => {
        const k = JSON.parse(event.data).k;
        const lastCandle = { x: k.t, o:+k.o, h:+k.h, l:+k.l, c:+k.c };

        if (candleData.length && candleData[candleData.length - 1].x === lastCandle.x) {
            candleData[candleData.length - 1] = lastCandle;
        } else {
            candleData.push(lastCandle);
            if (candleData.length > 500) candleData.shift();
        }

        chart.config.data.datasets[0].data = candleData;

        if (autoScroll) {
            chart.options.scales.x.max = lastCandle.x;
            chart.options.scales.x.min = candleData[candleData.length - 100]?.x || candleData[0].x;
        }

        chart.update('none');
    };
}

document.querySelectorAll('.timeframe-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        document.querySelectorAll('.timeframe-btn').forEach(b => b.classList.remove('bg-indigo-500', 'text-white'));
        btn.classList.add('bg-indigo-500', 'text-white');
        interval = btn.dataset.interval;
        autoScroll = true; // Reset auto-scroll when switching intervals
        await loadCandles(interval);
        startCandleWebSocket(interval);
    });
});

(async () => {
    await loadCandles(interval);
    startCandleWebSocket(interval);
})();



</script>

</x-layouts.app>
