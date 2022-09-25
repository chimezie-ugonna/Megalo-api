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
            $table->decimal("value_usd");
            $table->decimal("percentage_available");
            $table->decimal("dividend_usd");
            $table->decimal("size_sf");
        });
        DB::statement("ALTER TABLE properties ADD COLUMN image_urls text[],
            ADD COLUMN created_at TIMESTAMP(0) with time zone,
            ADD COLUMN updated_at TIMESTAMP(0) with time zone");
        DB::statement("ALTER TABLE properties ALTER COLUMN image_urls SET NOT NULL,
            ALTER COLUMN created_at SET NOT NULL,
            ALTER COLUMN updated_at SET NOT NULL");
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
