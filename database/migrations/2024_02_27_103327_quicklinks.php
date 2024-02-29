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
        Schema::create('quicklinks', function (Blueprint $table) {
            $table->id(); 
            $table->text('email')->nullable();
            $table->text('customer_id')->nullable();
            $table->text('items')->nullable();
            $table->text('quicklink')->nullable();
            $table->text('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quicklinks');
    }
};
