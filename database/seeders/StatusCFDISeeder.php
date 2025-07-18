<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class StatusCFDISeeder extends Seeder {
    public function run() {
        DB::statement("
            INSERT INTO cfdi_estados (id_cfdi, codigo_estatus, es_cancelable, estatus_cancelacion, validacion_efos, fecha_consulta)
            SELECT
                uuid,
                ELT(FLOOR(1 + RAND() * 3), 'S', 'N-601', 'N-602') AS codigo_estatus,
                ELT(FLOOR(1 + RAND() * 3), 'No cancelable', 'Cancelable sin aceptación', 'Cancelable con aceptación') AS es_cancelable,
                ELT(FLOOR(1 + RAND() * 6), NULL, 'Cancelado sin aceptación', 'En proceso', 'Plazo vencido', 'Cancelado con aceptación', 'Solicitud rechazada') AS estatus_cancelacion,
                ELT(FLOOR(1 + RAND() * 4), 100, 200, 200, 200) AS validacion_efos,
                NOW() AS fecha_consulta
            FROM cfdi_emitidos;
        ");

        echo "emited table seeded \n";

        DB::statement("
            INSERT INTO cfdi_estados_r (id_cfdi, codigo_estatus, es_cancelable, estatus_cancelacion, validacion_efos, fecha_consulta)
            SELECT
                uuid,
                ELT(FLOOR(1 + RAND() * 3), 'S', 'N-601', 'N-602') AS codigo_estatus,
                ELT(FLOOR(1 + RAND() * 3), 'No cancelable', 'Cancelable sin aceptación', 'Cancelable con aceptación') AS es_cancelable,
                ELT(FLOOR(1 + RAND() * 6), NULL, 'Cancelado sin aceptación', 'En proceso', 'Plazo vencido', 'Cancelado con aceptación', 'Solicitud rechazada') AS estatus_cancelacion,
                ELT(FLOOR(1 + RAND() * 4), 100, 200, 200, 200) AS validacion_efos,
                NOW() AS fecha_consulta
            FROM cfdi_recibidos;
        ");
        
        echo "received table seeded \n";
    }
}
