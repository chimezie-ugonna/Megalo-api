<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaidDividend extends Model
{
    use HasFactory;

    protected $fillable = [
        "property_id",
        "amount_usd",
        "investor_count"
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, "property_id");
    }
}
