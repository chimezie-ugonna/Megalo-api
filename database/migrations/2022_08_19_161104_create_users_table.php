<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->text("user_id")->primary();
            $table->text("phone_number");
            $table->text("first_name");
            $table->text("last_name");
            $table->text("email");
            $table->text("gender")->default("");
            $table->text("dob");
            $table->text("nationality")->default("");
            $table->text("image_url")->default("");
            $table->text("referral_code");
            $table->text("payment_account_id")->default("");
            $table->text("payment_customer_id")->default("");
            $table->decimal("balance_usd", 11, 2)->default(0);
            $table->boolean("is_admin")->default(false);
            $table->boolean("email_verified")->default(false);
            $table->boolean("identity_verified")->default(false);
            $table->text("identity_verification_id")->default("");
            $table->timestampTz("created_at");
            $table->timestampTz("updated_at");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
