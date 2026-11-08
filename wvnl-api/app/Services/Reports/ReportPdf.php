<?php

namespace App\Services\Reports;

use Dompdf\Dompdf;
use Dompdf\Options;

class ReportPdf
{
    public function render(array $snapshot, array $narrative): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', resource_path('views/reports'));
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4');
        $pdf->loadHtml(view('reports.snapshot', compact('snapshot', 'narrative'))->render(), 'UTF-8');
        $pdf->render();
        if ($pdf->getCanvas()->get_page_count() !== 1) {
            throw new \RuntimeException('report_layout_overflow');
        }

        return $pdf->output();
    }
}
