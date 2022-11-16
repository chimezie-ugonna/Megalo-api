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
        Schema::create('property_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->text("property_id");
            $table->decimal("value_usd", 11, 2);
            $table->decimal("monthly_earning_usd", 11, 2);
            $table->decimal("value_annual_change_rate", 17, 12);
            $table->decimal("monthly_earning_annual_change_rate", 17, 12);
            $table->boolean("value_changed");
            $table->boolean("monthly_earning_changed");
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
        Schema::dropIfExists('property_histories');
    }
};
