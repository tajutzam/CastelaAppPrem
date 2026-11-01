<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOffering extends Model
{
    protected $fillable = ['product_id', 'account_type_id', 'email', 'price', 'duration_days', 'starts_at', 'expires_at', 'status'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'status' => 'string', 'starts_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
