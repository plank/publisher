<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mediables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained();
            $table->morphs('mediable');
            $table->string('collection')->default('default');
            $table->boolean('has_been_published');
            $table->boolean('should_delete');
            $table->timestamps();
        });
    }
};
