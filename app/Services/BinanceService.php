<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BinanceService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.binance.base_url', 'https://api.binance.com');
    }

    /**
     * Get and cache only the "symbols" from exchangeInfo (lightweight).
     */
    public function getExchangeSymbols()
    {
        return Cache::remember('binance:symbols', now()->addHours(12), function () {
            $res = Http::timeout(15)->get($this->baseUrl . '/api/v3/exchangeInfo');

            if ($res->successful()) {
                $json = $res->json();

                // Only keep minimal fields per symbol to save memory
                return collect($json['symbols'] ?? [])->map(function ($s) {
                    return [
                        'symbol'     => $s['symbol'],
                        'baseAsset'  => $s['baseAsset'],
                        'quoteAsset' => $s['quoteAsset'],
                    ];
                })->all();
            }

            return [];
        });
    }

    /**
     * Get full details of a single trading pair (status, filters, etc.)
     */
    public function getPair(string $symbol)
    {
        $res = Http::timeout(15)->get($this->baseUrl . '/api/v3/exchangeInfo', [
            'symbol' => strtoupper($symbol),
        ]);

        if ($res->successful()) {
            $json = $res->json();
            return $json['symbols'][0] ?? null;
        }

        return null;
    }

    /**
     * Get 24hr ticker stats for one symbol.
     */
    public function getTicker24hr(string $symbol)
    {
        $res = Http::timeout(10)->get($this->baseUrl . '/api/v3/ticker/24hr', [
            'symbol' => strtoupper($symbol),
        ]);

        return $res->successful() ? $res->json() : null;
    }

    /**
     * Get historical klines (candles).
     */
    public function getKlines(string $symbol, string $interval = '1h', int $limit = 500)
    {
        $res = Http::timeout(20)->get($this->baseUrl . '/api/v3/klines', [
            'symbol'   => strtoupper($symbol),
            'interval' => $interval,
            'limit'    => $limit,
        ]);

        return $res->successful() ? $res->json() : [];
    }
}
