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
        Schema::create('notifications', function (Blueprint $table) {
            $table->text("notification_id")->primary();
            $table->text("sender_user_id")->default("");
            $table->text("receiver_user_id")->default("");
            $table->text("title");
            $table->text("body");
            $table->text("redirection_page")->default("");
            $table->text("redirection_page_id")->default("");
            $table->boolean("seen")->default(false);
            $table->boolean("tappable")->default(false);
            $table->boolean("tapped")->default(false);
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
        Schema::dropIfExists('notifications');
    }
};
