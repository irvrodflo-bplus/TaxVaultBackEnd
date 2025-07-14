<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalVaultEmitted extends Model {
    use HasFactory;

    protected $table = 'cfdi_emitidos';
    protected $primaryKey = 'id';

    public static function getByExpeditionDateRange($startDate, $endDate) {
        return self::whereBetween('fecha_expedicion', [$startDate, $endDate])->get();
    }

    public static function getByPeriodAndType($startDate, $endDate, $docType) {
        return self::whereBetween('fecha_expedicion', [$startDate, $endDate])
                    ->where('tipo_comprobante', $docType)
                    ->get();
    }

    public static function getSumTotalByPeriodAndType($startDate, $endDate, $docType) {
        return self::whereBetween('fecha_expedicion', [$startDate, $endDate])
                    ->where('tipo_comprobante', $docType)
                    ->sum('total');
    }

    public static function getCountByPeriodAndType($startDate, $endDate, $docType) {
        return self::whereBetween('fecha_expedicion', [$startDate, $endDate])
                ->where('tipo_comprobante', $docType)
                ->count();
    }
}
