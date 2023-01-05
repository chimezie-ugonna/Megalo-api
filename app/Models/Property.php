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
        "company_percentage",
        "percentage_available",
        "monthly_earning_usd",
        "size_sf",
        "value_average_annual_change_percentage",
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

    public function propertyValueHistory()
    {
        return $this->hasMany(PropertyValueHistory::class, "property_id");
    }
}
