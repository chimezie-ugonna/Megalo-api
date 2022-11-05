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
        Schema::create('property_value_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->text("property_id");
            $table->decimal("value_usd", 11, 2);
            $table->decimal("appreciation_rate", 17, 12);
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
        Schema::dropIfExists('property_value_histories');
    }
};
