<?php

namespace App\Models;

use App\Classes\Price;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Credit extends Model
{
    protected $fillable = [
        'currency_code',
        'amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => new Price(['price' => $this->amount, 'currency' => $this->currency])
        );
    }
}
