<?php

namespace Database\Seeders;

use App\Models\FeeItem;
use Illuminate\Database\Seeder;

class FeeItemsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Scolarité', 'billing_type' => 'monthly', 'default_amount' => null, 'due_month' => null],
            ['name' => 'Transport', 'billing_type' => 'monthly', 'default_amount' => null, 'due_month' => null],
            ['name' => 'Cantine', 'billing_type' => 'monthly', 'default_amount' => null, 'due_month' => null],
            ['name' => 'Frais annuels', 'billing_type' => 'yearly', 'default_amount' => null, 'due_month' => 9],
            ['name' => 'Assurance', 'billing_type' => 'one_time', 'default_amount' => null, 'due_month' => 9],
        ];

        foreach ($items as $it) {
            FeeItem::updateOrCreate(
                ['name' => $it['name']],
                $it + ['is_active' => true]
            );
        }
    }
}
