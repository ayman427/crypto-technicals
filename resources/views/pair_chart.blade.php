<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_back" />
<x-layouts.app :title="($pair['symbol'] ?? 'Unknown').' Chart'">

<div class="flex items-center justify-between mb-6 p-4">
    <h2 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
        {{ $pair['symbol'] ?? 'Unknown' }} Chart
    </h2>
    <a href="{{ url()->previous() }}">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
</div>

<div id="tradingview_chart" style="width: 100%; height: 100vh;"></div>

<!-- TradingView Widget -->
<script src="https://s3.tradingview.com/tv.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    new TradingView.widget({
        autosize: true,
        symbol: "{{ strtoupper($pair['symbol'] ?? 'BTCUSDT') }}",
        interval: "1",
        timezone: "Etc/UTC",
        theme: document.documentElement.classList.contains('dark') ? "dark" : "light",
        style: "1",
        locale: "en",
        enable_publishing: false,
        allow_symbol_change: false,
        hide_legend: false,
        container_id: "tradingview_chart"
    });
});
</script>

</x-layouts.app>
