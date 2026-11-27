<?php

namespace App\Services;

use App\Models\Screening;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportPdfService
{
    public function generatePdfBinary(Screening $screening): string
    {
        $screening->loadMissing(['user', 'prediction', 'latestInterpretation']);

        $html = view('skrining.pdf', [
            'screening' => $screening,
            'prediction' => $screening->prediction,
            'interpretation' => $screening->latestInterpretation,
            'disclaimer' => FeatureMapper::systemDisclaimerText(),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}

