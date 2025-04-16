<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quote;

class QuoteSeeder extends Seeder
{
    public function run()
    {
        Quote::factory()->count(30)->create(); // Crée 30 citations
    }
}
