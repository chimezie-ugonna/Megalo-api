<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $primaryKey = "user_id";
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = [
        "user_id",
        "phone_number",
        "first_name",
        "last_name",
        "dob",
        "email",
        "referral_code",
        "payment_account_id",
        "payment_customer_id",
        "balance_usd",
        "is_admin",
        "email_verified",
        "identity_verified"
    ];
    protected $casts = [
        "first_name" => "encrypted",
        "last_name" => "encrypted",
        "dob" => "encrypted",
        "email" => "encrypted",
        "is_admin" => "boolean",
        "email_verified" => "boolean",
        "identity_verified" => "boolean"
    ];

    public function login()
    {
        return $this->hasMany(Login::class, "user_id");
    }

    public function investment()
    {
        return $this->hasMany(Investment::class, "user_id");
    }

    public function payment()
    {
        return $this->hasMany(Payment::class, "user_id");
    }

    public function notificationSender()
    {
        return $this->hasMany(Notification::class, "sender_user_id");
    }

    public function notificationReceiver()
    {
        return $this->hasMany(Notification::class, "receiver_user_id");
    }

    public function earning()
    {
        return $this->hasMany(Earning::class, "user_id");
    }
}
