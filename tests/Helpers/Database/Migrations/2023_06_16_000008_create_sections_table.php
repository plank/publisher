<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->string('heading');
            $table->text('text');
            $table->string('status');
            $table->boolean('has_been_published')->default(false);
            $table->json('draft')->nullable();
            $table->boolean('should_delete')->default(false);
            $table->timestamps();

            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->nullOnDelete();
        });
    }
};
