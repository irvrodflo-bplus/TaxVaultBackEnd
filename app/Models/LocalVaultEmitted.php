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
}
