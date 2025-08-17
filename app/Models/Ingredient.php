<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = ['name','unit','default_unit_price'];

    public function recipes() {
        return $this->hasMany(ProductRecipe::class);
    }
}
