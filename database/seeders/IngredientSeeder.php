<?php

use Illuminate\Database\Seeder;
use App\Models\Ingredient;

class IngredientSeeder extends Seeder {
    public function run(): void {
        $items = [
            ['name'=>'Ground Meat','unit'=>'kg','default_unit_price'=>180.00],
            ['name'=>'Fat','unit'=>'kg','default_unit_price'=>120.00],
            ['name'=>'Salt','unit'=>'kg','default_unit_price'=>25.00],
            ['name'=>'Garlic','unit'=>'kg','default_unit_price'=>150.00],
            ['name'=>'Paprika','unit'=>'kg','default_unit_price'=>200.00],
            ['name'=>'Casing','unit'=>'pcs','default_unit_price'=>2.00],
            ['name'=>'Sodium Nitrite','unit'=>'kg','default_unit_price'=>600.00],
        ];
        foreach ($items as $i) Ingredient::updateOrCreate(['name'=>$i['name']], $i);
    }
}
