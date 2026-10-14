<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * The three starting products (02 · 2.1). Safe to run repeatedly.
 */
class ProductSeeder extends Seeder
{
    public const SNAPSHOT_EXTERNAL = 'Snapshot · Külső';

    public const SNAPSHOT_INTERNAL = 'Snapshot · Belső';

    public const FULL = 'Teljes';

    public function run(): void
    {
        foreach ([self::SNAPSHOT_EXTERNAL, self::SNAPSHOT_INTERNAL, self::FULL] as $name) {
            Product::firstOrCreate(['name' => $name]);
        }
    }
}
