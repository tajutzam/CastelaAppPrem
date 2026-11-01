<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'slug', 'status', 'description'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(ProductOffering::class);
    }
}
