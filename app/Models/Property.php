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
        "available_shares",
        "size_sf",
        "dividend_ps_usd"
    ];
    protected $casts = [
        "address" => "encrypted",
        "value_usd" => "encrypted",
        "image_urls" => "encrypted",
        "available_shares" => "encrypted",
        "size_sf" => "encrypted",
        "dividend_ps_usd" => "encrypted"
    ];

    public function investment()
    {
        return $this->hasMany(Investment::class, "property_id");
    }
}
