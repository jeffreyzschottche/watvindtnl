<?php

namespace App\Services\Reports;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

class ReportPdf
{
    public function download(array $snapshot, ?array $narrative, string $filename, ?string $stored = null): Response
    {
        $pdf = $stored !== null ? base64_decode($stored, true) : false;
        if (! $pdf) {
            abort_if($narrative === null, 409, 'Het rapport is nog niet beschikbaar.');
            $pdf = $this->render($snapshot, $narrative);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function render(array $snapshot, array $narrative): string
    {
        $poster = in_array($snapshot['compass']['version'] ?? '', CompassAnalysis::POSTER_VERSIONS, true);
        $insights = ($narrative['analysis_version'] ?? null) === 3 && isset($snapshot['analysis_context']);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        if (isset($snapshot['compass'])) {
            $options->set('dpi', 104);
        }
        $options->set('chroot', resource_path('views/reports'));
        $template = $poster ? 'reports.compass-poster' : ($insights ? 'reports.insight-v3' : (isset($snapshot['compass']) ? 'reports.snapshot' : 'reports.snapshot-v1'));
        $render = function (bool $dense) use ($options, $template, $snapshot, $narrative) {
            $pdf = new Dompdf($options);
            $pdf->setPaper('A4');
            $pdf->loadHtml(view($template, compact('snapshot', 'narrative', 'dense'))->render(), 'UTF-8');
            $pdf->render();

            return $pdf;
        };
        $pdf = $render(false);
        if (($poster && $pdf->getCanvas()->get_page_count() > 1) || ($insights && $pdf->getCanvas()->get_page_count() > 2)) {
            $pdf = $render(true);
        }
        if ($pdf->getCanvas()->get_page_count() !== ($insights ? 2 : 1)) {
            throw new \RuntimeException('report_layout_overflow');
        }

        if ($insights) {
            $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
            $pdf->getCanvas()->page_text(29, 821, 'Jouw Politieke Kompas · persoonlijk en vertrouwelijk', $font, 6.5, [.33, .41, .49]);
            $pdf->getCanvas()->page_text(425, 821, 'WDNL · {PAGE_NUM} / {PAGE_COUNT}', $font, 6.5, [.33, .41, .49]);
        }

        return $pdf->output();
    }
}
