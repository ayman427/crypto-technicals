<?php
namespace App\Http\Controllers;

use App\Services\BinanceService;
use Illuminate\Http\Request;

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

}
