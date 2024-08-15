<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingCompletedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_completeds', function (Blueprint $table) {
            $table->id();
            // $table->string('details');
            $table->string('detalle')->nullable();

            // Es cuando se debe realizar, esto lo saca de pending
            $table->timestamp('fecha_realizacion')->nullable();

            // Es cuando se realizo
            $table->timestamp('fecha_realizada')->nullable();
            $table->text('notas')->nullable();
            $table->integer('expense_concept_id')->nullable();
            $table->integer('pending_id')->nullable();
            $table->integer('user_id')->nullable();
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
        Schema::dropIfExists('pending_completeds');
    }
}
