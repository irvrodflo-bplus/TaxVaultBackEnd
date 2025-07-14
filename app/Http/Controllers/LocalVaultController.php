<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\PaginationService;;
use App\Services\TaxVaultService;
use App\Services\ExcelService;

use App\Models\LocalVaultEmitted;
use App\Models\LocalVaultReceived;

use Illuminate\Support\Facades\Validator;

class LocalVaultController extends Controller {
    private $paginationService;
    private $vaultService;
    private $excelService;

    public function __construct(
        PaginationService $paginationService,
        TaxVaultService $vaultService, 
        ExcelService $excelService
    ){
        $this->paginationService = $paginationService;
        $this->vaultService = $vaultService;
        $this->excelService = $excelService;
    }

    public function getReportStats(Request $request) {
        $request->validate([
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = $request->input('start_date');
        $end = $request->input('end_date');

        $emitedData = LocalVaultEmitted::getByExpeditionDateRange($start, $end);
        $receivedData = LocalVaultReceived::getByExpeditionDateRange($start, $end);
        
        $emitedStats = $this->vaultService->getLocalSimpleStats($emitedData->toArray());
        $receivedStats = $this->vaultService->getLocalSimpleStats($receivedData->toArray());

        return response()->json([
            'success' => true,
            'stats' => [
                'emited' => $emitedStats,
                'received' => $receivedStats,
            ],
        ]);
    }

    public function exportReport(Request $request) {
        $data = $this->getReportData($request);
        $response = $this->excelService->buildExcelData($data, $this->excelHeaders);

        $fileName = 'Reporte-' . date('Y-m-d') . '.xlsx';

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
    
        return $response;
    }

    private function getReportData(Request $request){
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:emitidas,recibidas',
            'document_type' => 'required|in:I,N,P,E,T'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'invalid dates',
                'errors' => $validator->errors()
            ], 422);
        }

        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $docType = $request->input('document_type');
        $type = $request->input('type');

        $model = $type == 'emitidas' ?  LocalVaultEmitted::class : LocalVaultReceived::class;
        $data = $model ::whereBetween('fecha_expedicion', [$start, $end])
                    ->where('tipo_comprobante', $docType)
                    ->get();

        return $data;
    }

    private $excelHeaders = [
        'uuid' => 'UUID',
        'uuid_relacionado' => 'UUIDs relacionados',
        'fecha_expedicion' => 'Fecha de emisión',
        'fecha_certificacion' => 'Fecha de certificación',
        'pac' => 'PAC certificó',
        'rfc_emisor' => 'RFC emisor',
        'nombre_emisor' => 'Razón social emisor',
        'rfc_receptor' => 'RFC receptor',
        'nombre_receptor' => 'Razón social receptor',
        'uso_cfdi' => 'Uso CFDI',
        'tipo_comprobante' => 'Tipo',
        'metodo_pago' => 'Método de pago',
        'forma_pago' => 'Forma de pago',
        'version' => 'Versión',
        'serie' => 'Serie',
        'folio' => 'Folio',
        'moneda' => 'Moneda',
        'tipo_cambio' => 'Tipo de cambio',
        'subtotal' => 'Subtotal',
        'descuento' => 'Descuento',
        'total' => 'Total',
        'IVATrasladado0' => 'IVA trasladado 0%',
        'IVATrasladado16' => 'IVA trasladado 16%',
        'IVAExento' => 'IVA exento',
        'IVARetenido' => 'IVA retenido',
        'ISRRetenido' => 'ISR retenido',
        'IEPSTrasladado' => 'IEPS trasladado',
        'IEPSTrasladado0' => 'IEPS trasladado 0%',
        'IEPSTrasladado45' => 'IEPS trasladado 45%',
        'IEPSTrasladado54' => 'IEPS trasladado 54%',
        'IEPSTrasladado66' => 'IEPS trasladado 66%',
        'IEPSRetenido' => 'IEPS retenido',
        'LocalRetenido' => 'Local retenido',
        'LocalTrasladado' => 'Local trasladado',
        'status_sat' => 'Estado',
    ];
}
