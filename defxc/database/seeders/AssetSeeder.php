<?php

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            ['name' => 'usd',  'label' => 'US Dollar',       'icon' => 'usd.png'],
            ['name' => 'btc',  'label' => 'Bitcoin',          'icon' => 'btc.png'],
            ['name' => 'eth',  'label' => 'Ethereum',         'icon' => 'eth.png'],
            ['name' => 'usdt', 'label' => 'Tether USD',       'icon' => 'usdt.png'],
            ['name' => 'trx',  'label' => 'TRON',             'icon' => 'trx.png'],
            ['name' => 'bnb',  'label' => 'BNB',              'icon' => 'bnb.png'],
            ['name' => 'xrp',  'label' => 'XRP',              'icon' => 'xrp.png'],
            ['name' => 'aapl', 'label' => 'Apple Inc (AAPL)', 'icon' => 'aapl.png'],
            ['name' => 'tsla', 'label' => 'Tesla (TSLA)',     'icon' => 'tsla.png'],
        ];

        foreach ($assets as $asset) {
            Asset::firstOrCreate(
                ['name' => $asset['name']],
                array_merge($asset, ['id' => (string) Str::uuid(), 'active' => true])
            );
        }

        $this->command->info('Assets seeded (' . count($assets) . ' assets).');
    }
}