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
        "country_name_code",
        "first_name",
        "last_name",
        "dob",
        "gender",
        "email",
        "type"
    ];
    protected $casts = ["country_name_code" => "encrypted", "first_name" => "encrypted", "last_name" => "encrypted", "dob" => "encrypted", "gender" => "encrypted", "email" => "encrypted"];

    public function login()
    {
        return $this->hasMany(Login::class, "user_id");
    }
}
