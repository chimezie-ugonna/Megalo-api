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
        "size_sf",
        "latest_appreciation_rate",
        "sold"
    ];
    protected $casts = [
        "sold" => "boolean"
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

    public function propertyHistory()
    {
        return $this->hasMany(PropertyHistory::class, "property_id");
    }
}
