<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Reference data every environment needs. The first superadmin is created
 * with "php artisan megis:admin:create" (docs/install.md).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
