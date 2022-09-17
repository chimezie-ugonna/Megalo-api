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
        "type"
    ];
    protected $casts = ["first_name" => "encrypted", "last_name" => "encrypted", "dob" => "encrypted", "email" => "encrypted"];

    public function login()
    {
        return $this->hasMany(Login::class, "user_id");
    }

    public function investment()
    {
        return $this->hasMany(Investment::class, "user_id");
    }
}
