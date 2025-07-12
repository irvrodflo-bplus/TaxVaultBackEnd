<?php

namespace App\Services;

use App\Models\TaxSignature;

use Illuminate\Support\Facades\Http;

use PhpCfdi\CfdiCleaner\Cleaner;
use PhpCfdi\CfdiToPdf\CfdiDataBuilder;
use PhpCfdi\CfdiToPdf\Converter;
use PhpCfdi\CfdiToPdf\Builders\Html2PdfBuilder;
use CfdiUtils\Nodes\XmlNodeUtils;

class InvoiceService {    
    private $BASE_URL;
    private $API_KEY;
    private $RFC;

    public function __construct() {
        $this->BASE_URL = config('pac-connection.facturalo_plus-url');
        $this->API_KEY = config('pac-connection.facturalo_plus-api-key');
        $this->RFC = config('pac-connection.rfc');
    }

    public function getXml(string $uuid) {
        $url = "{$this->BASE_URL}/servicio/consultarCFDI";

        $formData = [
            ['name' => 'uuid', 'contents' => $uuid],
            ['name' => 'apikey', 'contents' => $this->API_KEY],
        ];

        $response = Http::asMultipart()->post($url, $formData);

        if (!isset($response['data']) || empty($response['code']) || $response['code'] != '200') {
            throw new \Exception('Fail to get XML from external service');
        }

        return $response['data'];
    }

    public function buildPdf(string $xml): string {
        try {
            $xmlClean = Cleaner::staticClean($xml);
            $comprobante = XmlNodeUtils::nodeFromXmlString($xmlClean);
            $cfdiData = (new CfdiDataBuilder())->build($comprobante);
            $converter = new Converter(new Html2PdfBuilder());

            $pdfFilePath = $converter->createPdf($cfdiData);

            if (empty($pdfFilePath) || !file_exists($pdfFilePath)) {
                throw new \Exception('Error into generate PDF document');
            }

            $pdfContent = file_get_contents($pdfFilePath);

            unlink($pdfFilePath);

            if (empty($pdfContent)) {
                throw new \Exception('The PDF is empty');
            }

            return $pdfContent;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getCfdiDocuments(string $uuid) {
        $xml = $this->getXml($uuid);
        $pdf = $this->buildPdf($xml);

        return [
            'uuid' => $uuid,
            'pdf' => $pdf,
            'xml' => $xml,
        ];
    }

    public function cancelInvoice($uuid, $reason, $rfcReceptor, $totalAmount, $businessId, $related = null) {
        $url = "{$this->BASE_URL}/servicio/cancelar2";

        $signature = TaxSignature::findByBusinessId($businessId);

        $formData = [
            ['name' => 'uuid', 'contents' => $uuid],
            ['name' => 'apikey', 'contents' => $this->API_KEY],
            ['name' => 'motivo', 'contents' => $reason],
            ['name' => 'total', 'contents' => $totalAmount],
            ['name' => 'rfcEmisor', 'contents' => $this->RFC],
            ['name' => 'rfcReceptor', 'contents' => $rfcReceptor],
            ['name' => 'keyCSD', 'contents' => $signature->csd_key],
            ['name' => 'cerCSD', 'contents' => $signature->csd_cer],
            ['name' => 'passCSD', 'contents' => $signature->csd_password]
        ];

        if (!is_null($related)) {
            $formData[] = ['name' => 'folioSustitucion', 'contents' => $related];
        }

        $response = Http::asMultipart()->post($url, $formData);

        if (($response['code'] != '201' && $response['code'] != '202') || !$response['data']['acuse']) {
            throw new \Exception('Error while cancel');
        }

        return [
            'file' => base64_encode($response['data']['acuse']),
            'code' => $response['code']
        ];
    }

    public function showStatus($uuid, $rfcReceptor, $totalAmount) {
        $url = "{$this->BASE_URL}/servicio/consultarEstadoSAT";

        $formData = [
            ['name' => 'uuid', 'contents' => $uuid],
            ['name' => 'apikey', 'contents' => $this->API_KEY],
            ['name' => 'rfcEmisor', 'contents' => $this->RFC],
            ['name' => 'rfcReceptor', 'contents' => $rfcReceptor],
            ['name' => 'total', 'contents' => $totalAmount]
        ];

        $response = Http::asMultipart()->post($url, $formData);

        return $response;
    }
}
