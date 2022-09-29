<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $primaryKey = "notification_id";
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = [
        "notification_id",
        "sender_user_id",
        "receiver_user_id",
        "title",
        "body",
        "seen"
    ];
    protected $casts = [
        "title" => "encrypted",
        "body" => "encrypted"
    ];

    public function userSender()
    {
        return $this->belongsTo(User::class, "sender_user_id");
    }

    public function userReceiver()
    {
        return $this->belongsTo(User::class, "receiver_user_id");
    }
}
