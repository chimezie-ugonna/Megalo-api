<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyValueHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        "property_id",
        "value_usd",
        "appreciation_rate"
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, "property_id");
    }
}
