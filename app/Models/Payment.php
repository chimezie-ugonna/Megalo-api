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
        "payment_id",
        "user_id",
        "type",
        "reference",
        "amount_usd"
    ];
    protected $casts = [
        "reference" => "encrypted"
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
