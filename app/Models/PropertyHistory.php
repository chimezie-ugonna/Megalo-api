<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        "property_id",
        "value_usd",
        "monthly_earning_usd",
        "value_annual_change_rate",
        "monthly_earning_annual_change_rate",
        "value_changed",
        "monthly_earning_changed"
    ];

    protected $casts = [
        "value_changed" => "boolean",
        "monthly_earning_changed" => "boolean"
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, "property_id");
    }
}
