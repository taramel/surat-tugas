<?php
// Pengujian fungsi tanggal dan penomoran:  php tests/uji_helper.php
define('BASEPATH', 1);
require __DIR__ . '/../application/helpers/app_helper.php';
require __DIR__ . '/../application/helpers/impor_helper.php';

$kasus = [
    ['format nomor', format_nomor('B-{urut}/{satker}/KP.{klas}/{tahun}', 738, '61513', '650', 2026), 'B-738/61513/KP.650/2026'],
    ['nama berkas aman', nama_berkas_nomor('B-738/61513/KP.650/2026'), 'B-738_61513_KP.650_2026'],
    ['tgl_indo', tgl_indo('2026-10-05'), '5 Oktober 2026'],
    ['tgl_indo kosong', tgl_indo(''), ''],
    ['bulan kata Inggris', bulan_dari_kata('February'), 2],
    ['bukan bulan', bulan_dari_kata('tanggal'), null],
    ['tanggal sel teks', parse_tanggal_sel('15 Oktober 2026', 2026), '2026-10-15'],
    ['tanggal sel dd/mm/yyyy', parse_tanggal_sel('02/01/2026', 2026), '2026-01-02'],
];
$gagal = 0;
foreach ($kasus as $k) {
    if ($k[1] !== $k[2]) { $gagal++; echo "GAGAL: {$k[0]} => " . var_export($k[1], true) . " (harusnya " . var_export($k[2], true) . ")\n"; }
}
echo 'uji helper: ' . (count($kasus) - $gagal) . '/' . count($kasus) . " lulus\n";
exit($gagal ? 1 : 0);
