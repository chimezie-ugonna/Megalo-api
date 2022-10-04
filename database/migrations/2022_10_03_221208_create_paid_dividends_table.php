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
        Schema::create('paid_dividends', function (Blueprint $table) {
            $table->increments('id');
            $table->text("property_id");
            $table->decimal("amount_usd", 11, 2);
            $table->integer("investor_count");
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
        Schema::dropIfExists('paid_dividends');
    }
};
