<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $primaryKey = "payment_id";
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = [
        "type",
        "reference",
        "amount_usd"
    ];
    protected $casts = [
        "reference" => "encrypted"
    ];

    public function investment()
    {
        return $this->hasMany(Investment::class, "payment_id");
    }
}
