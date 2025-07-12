<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Mail;

use App\Services\InvoiceService;
use App\Services\PaginationService;;
use App\Services\TaxVaultService;

use App\Mail\RestoreCFDIMail;
use App\Services\ExcelService;

class PacController extends Controller {
    private $invoiceService;
    private $paginationService;
    private $vaultService;
    private $excelService;

    public function __construct(
        InvoiceService $invoiceService,
        PaginationService $paginationService,
        TaxVaultService $vaultService, 
        ExcelService $excelService
    ){
        $this->invoiceService = $invoiceService;
        $this->paginationService = $paginationService;
        $this->vaultService = $vaultService;
        $this->excelService = $excelService;
    }

    public function getInvoices(Request $request) {
        return $this->handleInvoiceRequest($request, function($start, $end, $businessId) {
            return $this->vaultService->getEmitedCfdi($start, $end, 'I' ,$businessId);
        });
    }

    public function getPayrolls(Request $request) {
        return $this->handleInvoiceRequest($request, function($start, $end, $businessId) {
            return $this->vaultService->getEmitedCfdi($start, $end, 'N', $businessId);
        });
    }

    public function getPaymentSupplements(Request $request) {
        return $this->handleInvoiceRequest($request, function($start, $end, $businessId) {
            return $this->vaultService->getEmitedCfdi($start, $end, 'P', $businessId);
        });
    }

    private function handleInvoiceRequest(Request $request, callable $serviceMethod) {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'business_id' => 'required|exists:erp_businesses,id',
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
        $businessId = $request->input('business_id');
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);

        $data = call_user_func($serviceMethod, $start, $end, $businessId);
        $paged = $this->paginationService->paginateData($data, $perPage, $page);

        return response()->json([
            'success' => true,
            'paged' => $paged
        ]);
    }

    public function getXmlFromUuid(string $uuid) {
        $xml = $this->invoiceService->getXml($uuid);
        $filename = strtolower($uuid) . '.xml';
        $xml = trim($xml);

        return response($xml)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function getPdfFromUuid(string $uuid) {
        $pdf = $this->invoiceService->getCfdiDocuments($uuid)['pdf'];
        $filename = strtolower($uuid) . '.pdf';

        return Response::make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function sendCfdi(Request $request) {
        $data = $request->validate([
            'uuid' => 'required|string',
            'email' => 'required|email'
        ]);

        $cfdi = $this->invoiceService->getCfdiDocuments($data['uuid']);

        Mail::to($data['email'])->send(new RestoreCFDIMail($cfdi['uuid'], $cfdi['pdf'], $cfdi['xml']));

        return response()->json([
            'success' => true,
            'message' => 'information send successfully'
        ]);
    }

    public function showStatus(Request $request) {
        $data = $request->validate([
            'uuid' => 'required|string',
            'total_amount' => 'required|numeric',
            'rfc_receptor' => 'required|string'
        ]);    

        $response = $this->invoiceService->showStatus(
            $data['uuid'], 
            $data['rfc_receptor'], 
            $data['total_amount']
        );

        $flatStatus = str_replace(" ", "", $response['CodigoEstatus']);

        if(strpos($flatStatus, "N-602") != false){
            return response()->json([
                'success' => false,
                'message' => 'uuid not found'
            ], 404);
        } else if (strpos($flatStatus, "N-601") != false){
            return response()->json([
                'success' => false,
                'message' => 'uuid invalid',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $response['Estado'],
                'can_cancelled' => $response['EsCancelable'],
                'cancel_status' => $response['EstatusCancelacion']
            ]
        ]);
    }
    
    public function cancelInvoice(Request $request) {
        $data = $request->validate([
            'uuid' => 'required|string',
            'reason' => 'required|string',
            'total_amount' => 'required|numeric',
            'rfc_receptor' => 'required|string',
            'related_uuid' => 'sometimes|string',
            'business_id' => 'required|exists:erp_businesses,id'
        ]);    

        $response =   $this->invoiceService->cancelInvoice(
            $data['uuid'], 
            $data['reason'], 
            $data['rfc_receptor'], 
            $data['total_amount'],
            $data['business_id'],
            $data['related_uuid'] ?? null
        );

        $message = $response['code'] == 201 ? 'cancelled succesfully' : 'previously cancelled';

        return response()->json([
            'success' => true,
            'message' => $message,
            'file' => $response['file']
        ]);
    }   

    public function getReport(Request $request) {
        $data = $this->getReportData($request);

        $emited = 0;
        $received = 0;

        $payroll = 0;
        $paymentSupplement = 0;
        $revenue = 0;
        $expense = 0;
        $tranlate = 0;

        foreach ($data as $item) {

            if($item['Efecto'] == 'Emitido'){
                $emited = $emited + 1;
            } else if($item['Efecto'] == 'Recibido'){
                $received = $received + 1;
            }

            switch ($item['Tipo']) {
                case 'N':
                    $payroll = $payroll + 1;
                    break;
                case 'I':
                    $revenue = $revenue + 1;
                    break;
                case 'E':
                    $expense = $expense + 1;
                    break;
                case 'P':
                    $paymentSupplement = $paymentSupplement + 1;
                    break;
                case 'T':
                    $tranlate = $tranlate + 1;
                    break;
            }
        }

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);

        $paged = $this->paginationService->paginateData($data, $perPage, $page);

        return response()->json([
            'success' => true,
            'stats' => [
                'emited' => $emited,
                'received' => $received,
                'payrolls' => $payroll,
                'payment_supplements' => $paymentSupplement,
                'revenues' => $revenue,
                'expenses' => $expense,
                'translates' => $tranlate,
            ],
            'paged' => $paged,
        ]);
    }

    public function exportReportData(Request $request) {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
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
        $businessId = $request->input('business_id');
        $docType = $request->input('document_type');

        $zipContent = $this->vaultService->exportReportData($start, $end, $docType, $businessId);

        $filename = 'Registros-' . date('Y-m-d') . '.zip';
    
        return response($zipContent, 200)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($zipContent));
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
            //'type' => 'required|in:emitidas,recibidas',
            //'document_type' => 'required|in:I,N,P,E,T'
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
        $businessId = $request->input('business_id');
        $docType = $request->input('document_type');
        $type = $request->input('type');

        $data = $this->vaultService->getCfdiReport($start, $end, $type, $docType, $businessId);

        return $data;
    }

    private $excelHeaders = [
        'Periodo' => 'Periodo',
        'Version' => 'Version',
        'UUID' => 'UUID',
        'UUIDsRelacionados' => 'UUIDs relacionados',
        'TipoRelacion' => 'Tipo de relación',
        'CPExpedicion' => 'Código Postal Expedición',
        'Serie' => 'Serie',
        'Folio' => 'Folio',
        'Tipo' => 'Tipo',
        'FechaEmision' => 'Fecha de emisión',
        'FechaCertificacion' => 'Fecha de certificación',
        'PACCertifico' => 'PAC certificó',
        'RegimenEmisor' => 'Régimen emisor',
        'RfcEmisor' => 'RFC emisor',
        'RazonEmisor' => 'Razón social emisor',
        'RfcReceptor' => 'RFC receptor',
        'RazonReceptor' => 'Razón social receptor',
        'RegimenReceptor' => 'Régimen receptor',
        'DomicilioReceptor' => 'Domicilio receptor',
        'ClavesDeProductos' => 'Claves de productos',
        'Conceptos' => 'Conceptos',
        'UsoCFDI' => 'Uso CFDI',
        'Complementos' => 'Complementos',
        'GlobalPeriodicidad' => 'Global periodicidad',
        'GlobalMeses' => 'Global meses',
        'GlobalAnio' => 'Global año',
        'Efecto' => 'Efecto',
        'Estado' => 'Estado',
        'FolioSustitucionCancelacion' => 'Folio sustitución cancelación',
        'Moneda' => 'Moneda',
        'TipoDeCambio' => 'Tipo de cambio',
        'Exportacion' => 'Exportación',
        'MetodoPago' => 'Método de pago',
        'FormaPago' => 'Forma de pago',
        'SubTotal' => 'Subtotal',
        'Descuento' => 'Descuento',
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
        'Total' => 'Total'
    ];
}
