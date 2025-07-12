<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RestoreCFDIMail extends Mailable {
    use Queueable, SerializesModels;

    public $uuid;
    public $pdfContent;
    public $xmlContent;

    public function __construct($uuid, $pdfContent, $xmlContent) {
        $this->uuid = $uuid;
        $this->pdfContent = $pdfContent;
        $this->xmlContent = $xmlContent;
    }

    public function envelope() {
        return new Envelope(
            subject: 'Envió de CFDI',
        );
    }

    public function content() {
        return new Content(
            markdown: 'emails.sendCfdi',
        );
    }

    public function build() {
        $email = $this->markdown('emails.sendCfdi')->subject('Envío de CFDI');

        $pdfFileName = $this->uuid . ".pdf";
        $email->attachData($this->pdfContent, $pdfFileName, [
            'mime' => 'application/pdf',
        ]);

        $xmlFileName = $this->uuid . ".xml";
        $email->attachData($this->xmlContent, $xmlFileName, [
            'mime' => 'application/xml',
        ]);

        return $email;
    }
}