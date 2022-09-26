<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->decimal("value_usd", 11, 2);
            $table->decimal("percentage_available", 11, 2)->default(75);
            $table->decimal("dividend_usd", 11, 2)->default(0);
            $table->decimal("size_sf", 11, 2);
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
