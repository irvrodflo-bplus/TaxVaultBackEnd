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

        $response = Http::timeout(420)
            ->asMultipart()
            ->post($url, $formData)
            ->json();

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

        $response = Http::timeout(420)
            ->asMultipart()
            ->post($url, $formData)
            ->json();

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
            'emited' => [
                'total' => 0,
                'payrolls' => 0,
                'payment_supplements' => 0,
                'revenues' => 0,
                'expenses' => 0,
                'translates' => 0,
            ],
            'received' => [
                'total' => 0,
                'payrolls' => 0,
                'payment_supplements' => 0,
                'revenues' => 0,
                'expenses' => 0,
                'translates' => 0,
            ],
        ];

        $typeMapping = [
            'N' => 'payrolls',
            'I' => 'revenues',
            'E' => 'expenses',
            'P' => 'payment_supplements',
            'T' => 'translates',
        ];

        foreach ($data as $item) {
            if (!isset($item['Efecto'], $item['Tipo'])) {
                continue;
            }

            if ($item['Efecto'] === 'Emitido') {
                $stats['emited']['total']++;

                if (isset($typeMapping[$item['Tipo']])) {
                    $key = $typeMapping[$item['Tipo']];
                    $stats['emited'][$key]++;
                }
            } elseif ($item['Efecto'] === 'Recibido') {
                $stats['received']['total']++;

                if (isset($typeMapping[$item['Tipo']])) {
                    $key = $typeMapping[$item['Tipo']];
                    $stats['received'][$key]++;
                }
            }
        }

        return $stats;
    }

    public function getLocalSimpleStats(array $data): array {
        $stats = [
            'total' => 0,
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
            if (!isset($item['tipo_comprobante'])) {
                continue;
            }

            $stats['total']++;

            $tipo = $item['tipo_comprobante'];

            if (isset($typeMapping[$tipo])) {
                $key = $typeMapping[$tipo];
                $stats[$key]++;
            }
        }

        return $stats;
    }
}