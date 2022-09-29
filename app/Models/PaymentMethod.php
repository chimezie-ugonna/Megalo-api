<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $primaryKey = "payment_method_id";
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = [
        "payment_method_id",
        "user_id",
        "type"
    ];
    protected $casts = [];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
