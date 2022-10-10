<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $primaryKey = "property_id";
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = [
        "property_id",
        "address",
        "image_urls",
        "description",
        "value_usd",
        "percentage_available",
        "monthly_earning_usd",
        "monthly_dividend_usd",
        "size_sf"
    ];
    protected $casts = [
        "address" => "encrypted"
    ];

    public function investment()
    {
        return $this->hasMany(Investment::class, "property_id");
    }

    public function paidDividend()
    {
        return $this->hasMany(PaidDividend::class, "property_id");
    }

    public function earning()
    {
        return $this->hasMany(Earning::class, "property_id");
    }
}
