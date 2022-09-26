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
        "value_usd",
        "image_urls",
        "percentage_available",
        "size_sf",
        "dividend_usd"
    ];
    protected $casts = [
        "address" => "encrypted",
        "image_urls" => "array"
    ];

    public function investment()
    {
        return $this->hasMany(Investment::class, "property_id");
    }
}
