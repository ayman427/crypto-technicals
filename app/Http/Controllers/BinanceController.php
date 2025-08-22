<?php
namespace App\Http\Controllers;

use App\Services\BinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BinanceController extends Controller
{
    protected $binance;

    public function __construct(BinanceService $binance)
    {
        $this->binance = $binance;
    }

// Search page (all symbols, optional q)
    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        if ($q === '') {
            // ✅ No query → show nothing
            $symbols = collect([]);
        } else {
            // ✅ Fetch all, then filter
            $symbols = collect($this->binance->getExchangeSymbols())
                ->filter(function ($s) use ($q) {
                    return stripos($s['symbol'], $q) !== false
                    || stripos($s['baseAsset'], $q) !== false
                    || stripos($s['quoteAsset'], $q) !== false;
                });
        }

        return view('search', [
            'symbols' => $symbols->values()->all(),
            'q'       => $q,
        ]);
    }

    public function pair(string $symbol)
    {
        $symbol = strtoupper($symbol);

        // full pair info (status, filters, etc.)
        $pair = $this->binance->getPair($symbol);

        if (! $pair) {
            abort(404, 'Pair not found');
        }

        // ticker info (price, 24h change, volume)
        $ticker = $this->binance->getTicker24hr($symbol);

        return view('pair', compact('pair', 'ticker'));
    }

// Optional JSON endpoints
    public function ticker(string $symbol)
    {
        return response()->json($this->binance->getTicker24hr($symbol));
    }

    public function chart($symbol)
    {
        // Fetch pair info using your existing logic
        $exchangeInfo = json_decode(file_get_contents('https://api.binance.com/api/v3/exchangeInfo'), true);
        $pair         = collect($exchangeInfo['symbols'])->first(fn($s) => $s['symbol'] === strtoupper($symbol));

        return view('pair_chart', compact('pair'));
    }

    public function dashboard()
    {
        $response = Http::get('https://api.binance.com/api/v3/ticker/24hr');

        if ($response->failed()) {
            return view('dashboard', [
                'topGainers'    => collect(),
                'topLosers'     => collect(),
                'volumeGainers' => collect(),
            ]);
        }

        $excluded = ['BUSDUSDT', 'FDUSDUSDT', 'USDCUSDT'];

        $data = collect($response->json())
            ->filter(fn($row) =>
                isset($row['symbol']) &&
                str_ends_with($row['symbol'], 'USDT') &&
                ! in_array($row['symbol'], $excluded)
            )
            ->map(function ($row) {
                $last = (float) ($row['lastPrice'] ?? 0);
                $open = (float) ($row['openPrice'] ?? 0);

                return [
                    'symbol'             => $row['symbol'],
                    'lastPrice'          => $last,
                    'priceChange'        => $last - $open,
                    'priceChangePercent' => isset($row['priceChangePercent']) ? (float) $row['priceChangePercent'] : 0,
                    'highPrice'          => (float) ($row['highPrice'] ?? 0),
                    'lowPrice'           => (float) ($row['lowPrice'] ?? 0),
                    'quoteVolume'        => (float) ($row['quoteVolume'] ?? 0),
                ];
            })
            ->filter(fn($row) => $row['lastPrice'] > 0 && $row['quoteVolume'] > 1000000); // Only active pairs

        $topGainers    = $data->sortByDesc(fn($c) => $c['priceChangePercent'] * log1p($c['quoteVolume']))->take(10);
        $topLosers     = $data->sortBy('priceChangePercent')->take(10);
        $volumeGainers = $data->sortByDesc('quoteVolume')->take(10);

        return view('dashboard', compact('topGainers', 'topLosers', 'volumeGainers'));
    }

}
