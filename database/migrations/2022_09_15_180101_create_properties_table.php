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
        Schema::create('properties', function (Blueprint $table) {
            $table->text("property_id")->primary();
            $table->text("address");
            $table->text("image_urls");
            $table->text("description");
            $table->decimal("value_usd", 11, 2);
            $table->decimal("percentage_available", 17, 12);
            $table->decimal("monthly_earning_usd", 11, 2);
            $table->integer("size_sf");
            $table->decimal("latest_appreciation_rate", 17, 12)->default(0);
            $table->boolean("sold")->default(false);
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
        Schema::dropIfExists('properties');
    }
};
