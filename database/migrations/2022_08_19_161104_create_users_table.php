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
            $table->text("dob");
            $table->text("email");
            $table->decimal("balance_usd", 11, 2)->default(0);
            $table->text("type")->default("user");
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
