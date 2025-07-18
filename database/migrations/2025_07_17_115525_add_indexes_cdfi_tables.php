<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('cfdi_emitidos', function (Blueprint $table) {
            $table->index('tipo_comprobante', 'idx_tipo_comprobante');
        });

        Schema::table('cfdi_recibidos', function (Blueprint $table) {
            $table->index('tipo_comprobante', 'idx_tipo_comprobante');
        });
    }

    public function down() {
        Schema::table('cfdi_emitidos', function (Blueprint $table) {
            $table->dropIndex('idx_tipo_comprobante');
        });

        Schema::table('cfdi_recibidos', function (Blueprint $table) {
            $table->dropIndex('idx_tipo_comprobante');
        });
    }
};
