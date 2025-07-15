<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('sync_records', function (Blueprint $table) {
            $table->id();
            $table->string('user', 50);
            $table->integer('updated');
            $table->integer('inserted');
            $table->integer('errors');
            $table->enum('status', ['success', 'error', 'partial']);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('sync_records');
    }
};
