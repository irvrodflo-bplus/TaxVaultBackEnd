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

    public function getCfdiReport($startDate, $endDate, $type, $docType) {
        $url = "{$this->BASE_URL}/ws/doReporte";

        $formData = [
            ['name' => 'fFinal', 'contents' => $endDate],
            ['name' => 'fInicial', 'contents' => $startDate],
            ['name' => 'apikey', 'contents' => $this->API_KEY],
            ['name' => 'rfc', 'contents' =>  $this->RFC],
            ['name' => 'layout', 'contents' => 'GBM170505Q78_1']
        ];

        if ($type !== null && $type !== '') {
            $formData[] = ['name' => 'tipo', 'contents' => $type];
        }

        if ($docType !== null && $docType !== '') {
            $formData[] = ['name' => 'tipoDeComprobante', 'contents' => $docType];
        }

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

    public function exportReportData($startDate, $endDate, $docType) {
        $url = "{$this->BASE_URL}/ws/descargaCFDI";

        $formData = [
            ['name' => 'fFinal', 'contents' => $endDate],
            ['name' => 'fInicial', 'contents' => $startDate],
            ['name' => 'tipoDeComprobante', 'contents' => $docType],
            ['name' => 'apikey', 'contents' => $this->API_KEY],
            ['name' => 'rfc', 'contents' => $this->RFC],
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

    public function getReportStats(array $data): array {
        $stats = [
            'emited' => 0,
            'received' => 0,
            'payrolls' => 0,
            'payment_supplements' => 0,
            'revenues' => 0,
            'expenses' => 0,
            'translates' => 0,
        ];

        $typeMapping = [
            'N' => 'payrolls',
            'I' => 'revenues',
            'E' => 'expenses',
            'P' => 'payment_supplements',
            'T' => 'translates',
        ];

        foreach ($data as $item) {
            if ($item['Efecto'] === 'Emitido') {
                $stats['emited']++;
            } elseif ($item['Efecto'] === 'Recibido') {
                $stats['received']++;
            }

            if (isset($typeMapping[$item['Tipo']])) {
                $stats[$typeMapping[$item['Tipo']]]++;
            }
        }

        return $stats;
    }
}