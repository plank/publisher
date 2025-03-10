<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('post_post', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained();
            $table->foreignId('featured_id')->constrained('posts', 'id');
            $table->boolean('paywall')->default(false);
            $table->boolean('has_been_published');
            $table->boolean('should_delete');
            $table->timestamps();
        });
    }
};
