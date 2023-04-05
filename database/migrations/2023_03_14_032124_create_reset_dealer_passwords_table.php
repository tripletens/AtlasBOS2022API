<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResetDealerPasswordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // dealer_id, code, email, status (1 = reset already, 0 = not yet verified,  default=0 ) 
        Schema::create('reset_dealer_passwords', function (Blueprint $table) {
            $table->id();
            $table->integer('dealer_id')->nullable();
            $table->string('code')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', [0, 1])->default(0);
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
        Schema::dropIfExists('reset_dealer_passwords');
    }
}
