<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "access_type",
        "device_os",
        "device_token",
        "device_brand",
        "device_model",
        "app_version",
        "os_version",
        "ip_address",
        "device_token_updated_at"
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
