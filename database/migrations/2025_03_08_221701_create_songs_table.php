<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('desc');
            $table->string('image');
            $table->unsignedBigInteger('album_id')->nullable();
            $table->string('genre');
            $table->string('duration');
            $table->date('date')->nullable();
            $table->timestamps();

            $table->foreign('album_id')->references('id')->on('albums')->onDelete('set null');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('albums');
    }
};
