<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        "referrer_phone_number",
        "referrer_user_id",
        "referree_phone_number",
        "referree_user_id",
        "rewarded"
    ];

    protected $casts = [
        "rewarded" => "boolean"
    ];
}
