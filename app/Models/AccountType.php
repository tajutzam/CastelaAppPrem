<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    protected $fillable = ['code', 'name', 'max_users', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean', 'max_users' => 'integer'];
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(ProductOffering::class);
    }
}
