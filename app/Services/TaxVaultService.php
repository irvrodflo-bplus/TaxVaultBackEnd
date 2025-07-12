<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class TaxVaultService {    
    private $BASE_URL;
    private $API_KEY;
    private $RFC;

    public function __construct() {
        $this->BASE_URL = config('pac-connection.vault-url');
        $this->API_KEY = config('pac-connection.vault-api-key');
        $this->RFC = config('pac-connection.rfc');
    }

    public function getEmitedCfdi($startDate, $endDate, $docType, $businessId) {
        return $this->getCfdiReport($startDate, $endDate, 'emitidas', $docType, $businessId);
    }

    public function getCfdiReport($startDate, $endDate, $type, $docType, $businessId) {
        $url = "{$this->BASE_URL}/ws/doReporte";

        $rfc = $this->RFC;

        $formData = [
            ['name' => 'fFinal', 'contents' => $endDate],
            ['name' => 'fInicial', 'contents' => $startDate],
           // ['name' => 'tipoDeComprobante', 'contents' => $docType],
           // ['name' => 'tipo', 'contents' =>  $type],
            ['name' => 'apikey', 'contents' => $this->API_KEY],
            ['name' => 'rfc', 'contents' => $rfc],
            ['name' => 'layout', 'contents' => 'GBM170505Q78_1']
        ];

        $response = Http::asMultipart()->post($url, $formData)->json();

        if (!is_array($response) 
            || !isset($response['codigo'], $response['data']) 
            || !is_int($response['codigo']) 
            || !is_array($response['data'])
        ) {
            throw new \Exception("Connection with tax vault failed");
        }

        return $response['data'];
    }

    public function exportReportData($startDate, $endDate, $docType, $businessId) {
        $url = "{$this->BASE_URL}/ws/descargaCFDI";

         $rfc = $this->RFC;

        $formData = [
            ['name' => 'fFinal', 'contents' => $endDate],
            ['name' => 'fInicial', 'contents' => $startDate],
            ['name' => 'tipoDeComprobante', 'contents' => $docType],
            ['name' => 'apikey', 'contents' => $this->API_KEY],
            ['name' => 'rfc', 'contents' => $rfc],
        ];

        $response = Http::asMultipart()->post($url, $formData)->json();

        if (!is_array($response) 
            || !isset($response['codigo'], $response['zip']) 
            || $response['codigo'] != 200
        ) {
            throw new \Exception("Connection with tax vault failed");
        }
        
        $zip = base64_decode($response['zip']);

        return $zip;
    }
}