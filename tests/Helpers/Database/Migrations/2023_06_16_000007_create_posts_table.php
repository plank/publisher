<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_id');
            $table->string('title');
            $table->string('subtitle');
            $table->string('slug');
            $table->text('teaser');
            $table->text('body');
            $table->string('status');
            $table->boolean('has_been_published')->default(false);
            $table->json('draft')->nullable();
            $table->boolean('should_delete')->default(false);
            $table->timestamps();

            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
