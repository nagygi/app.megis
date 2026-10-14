<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * The three starting products (02 · 2.1) with their keys (17 · 17.3). Safe to run repeatedly.
 */
class ProductSeeder extends Seeder
{
    public const SNAPSHOT_EXTERNAL = 'Snapshot · Külső';

    public const SNAPSHOT_INTERNAL = 'Snapshot · Belső';

    public const FULL = 'Teljes';

    public function run(): void
    {
        foreach ([
            Product::SNAPSHOT_EXTERNAL => self::SNAPSHOT_EXTERNAL,
            Product::SNAPSHOT_INTERNAL => self::SNAPSHOT_INTERNAL,
            Product::FULL => self::FULL,
        ] as $key => $name) {
            $product = Product::query()->where('key', $key)->first()
                ?? Product::query()->firstOrCreate(['name' => $name]);

            $product->forceFill(['key' => $key])->save();
        }
    }
}
