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
        Schema::create("referrals", function (Blueprint $table) {
            $table->increments("id");
            $table->text("referrer_phone_number");
            $table->text("referrer_user_id");
            $table->text("referree_phone_number");
            $table->text("referree_user_id");
            $table->boolean("rewarded")->default(false);
            $table->decimal("reward_usd", 11, 2)->default(0);
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
        Schema::dropIfExists('referrals');
    }
};
