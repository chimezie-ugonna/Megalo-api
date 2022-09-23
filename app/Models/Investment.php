<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        "property_id",
        "user_id",
        "share"
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function property()
    {
        return $this->belongsTo(Property::class, "property_id");
    }
}
