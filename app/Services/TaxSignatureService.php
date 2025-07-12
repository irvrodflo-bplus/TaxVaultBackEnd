<?php
namespace App\Services;

use App\Models\TaxSignature;

class TaxSignatureService {   
    private $seriesSuffixes = [
        'invoice_series' => 'FE',
        'payroll_series' => 'NOM',
        'cancellation_series' => 'CAN',
        'payment_series' => 'PA',
        'credit_note_series' => 'NC',
        'advance_series' => 'AP'
    ];
    
    public function generateSignature($businessId, $data) {
        $serie = $this->generateSeries($data['code']);

        $createData = array_merge($data, $serie, [
            'business_id' => $businessId,
            'expedition_place' => $data['postal_code']
        ]);

        $signature = TaxSignature::create($createData);
        return $signature;
    }

    private function generateSeries($code) {
        $series = [];

        foreach ($this->seriesSuffixes as $field => $suffix) {
            $series[$field] = $code . $suffix;
        }

        return $series;
    }

    public function updateSignature($businessId, $data) {
        $signature = TaxSignature::findByBusinessId($businessId);

        if (!isset($data['access_key'])) {
            unset($data['access_key']);
        }
    
        if (!isset($data['csd_password'])) {
            unset($data['csd_password']);
        }
    
        $signature->update($data);

        return $signature;
    }
}
