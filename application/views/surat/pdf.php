<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 2cm 2cm 2.5cm 2.54cm; }
    body { font-family: "Times", serif; font-size: 12pt; line-height: 1.35; color: #000; }
    table { border-collapse: collapse; }
    td { vertical-align: top; padding: 0; }
    .kop { text-align: center; margin-bottom: 14px; }
    .kop img { width: 2.6cm; }
    .kop .k { font-family: "Helvetica", Arial, sans-serif; font-size: 12pt; font-weight: bold; font-style: italic; line-height: 1.25; }
    .judul { text-align: center; line-height: 1.5; margin-bottom: 14px; }
    .blok { width: 100%; margin-bottom: 8px; }
    .blok .l { width: 3.1cm; }
    .blok .t { width: 0.55cm; }
    .daftar { width: 100%; }
    .daftar .no { width: 0.85cm; }
    .daftar td { text-align: justify; padding-bottom: 6px; }
    .kepada { width: 100%; }
    .kepada .k1 { width: 3.0cm; }
    .kepada .k2 { width: 0.5cm; }
    .tengah { text-align: center; margin: 8px 0; }
    .isi { text-align: justify; }
    .ttd { width: 8.6cm; margin-left: 7.8cm; margin-top: 18px; text-align: center; page-break-inside: avoid; }
    .ttd .ruang { height: 2.1cm; }
    .lampiran { page-break-before: always; }
    .tbl { width: 100%; margin: 12px 0 4px; font-size: 10pt; line-height: 1.25; }
    .tbl th, .tbl td { border: 1px solid #000; padding: 2px 4px; vertical-align: top; }
    .tbl th { text-align: center; }
    .batal { position: fixed; top: 40%; left: 0; right: 0; text-align: center; font-size: 72pt; color: #c33; opacity: .22; }
</style>
</head>
<body>
<?php foreach ($daftar as $i => $s): ?>
<div<?= $i > 0 ? ' style="page-break-before: always"' : '' ?>>
<?= $this->load->view('surat/pdf_isi', ['s' => $s, 'set' => $set, 'logo' => $logo], true) ?>
</div>
<?php endforeach; ?>
</body>
</html>
