<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Pdf
{
    // Kertas F4 (21,5 x 33 cm) dalam satuan point
    const LEBAR = 609.45;
    const TINGGI = 935.43;

    public function __construct()
    {
        require_once APPPATH . 'third_party/dompdf/autoload.inc.php';
    }

    public function tampilkan($html, $namaBerkas, $unduh = false)
    {
        $opsi = new Dompdf\Options();
        $opsi->set('isRemoteEnabled', false);
        $opsi->set('defaultFont', 'Times');
        $opsi->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf\Dompdf($opsi);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper([0, 0, self::LEBAR, self::TINGGI]);
        $dompdf->render();

        // Nomor halaman "-2-", "-3-", ... di tengah atas, mulai halaman kedua
        $font = $dompdf->getFontMetrics()->getFont('Times');
        $dompdf->getCanvas()->page_script(function ($nomor, $total, $canvas, $metrik) use ($font) {
            if ($nomor > 1) {
                $teks = '-' . $nomor . '-';
                $lebar = $metrik->getTextWidth($teks, $font, 10);
                $canvas->text(($canvas->get_width() - $lebar) / 2, 30, $teks, $font, 10);
            }
        });

        $dompdf->stream($namaBerkas, ['Attachment' => $unduh]);
    }
}
