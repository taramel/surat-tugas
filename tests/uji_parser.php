<?php
// Pengujian pembaca teks tanggal pelaksanaan (dipakai impor register lama):  php tests/uji_parser.php
define('BASEPATH', 1);
require __DIR__ . '/../application/helpers/impor_helper.php';

$u = [
    ['2 s.d. 9 Januari 2026', 2026, [['2026-01-02', '2026-01-09']]],
    ['16 dan 20 April 2026', 2026, [['2026-04-16', '2026-04-16'], ['2026-04-20', '2026-04-20']]],
    ['30 Maret - 22 Mei 2026', 2026, [['2026-03-30', '2026-05-22']]],
    ['26 februri dan 12 maret 2026', 2026, [['2026-02-26', '2026-02-26'], ['2026-03-12', '2026-03-12']]],
    ['31 s.d 3 Februari 2026', 2026, [['2026-01-31', '2026-02-03']]],
    ['30 Desember s.d 2 Januari 2026', 2026, [['2025-12-30', '2026-01-02']]],
    ['tahun 2026', 2026, [['2026-01-01', '2026-12-31']]],
    ['15 Oktober 2026', 2026, [['2026-10-15', '2026-10-15']]],
    ['2 s.d. Maret 2026', 2026, null], ['10 s.d. 13', 2026, null], ['5 hari', 2026, null], ['', 2026, null],
    ['31 Februari 2026', 2026, null], ['9 s.d. 2 Januari 2026', 2026, null],
];
$gagal = 0;
foreach ($u as $c) {
    $r = parse_pelaksanaan($c[0], $c[1]);
    $r = $r === null ? null : array_map(function ($x) { return [$x['mulai'], $x['selesai']]; }, $r);
    if ($r !== $c[2]) { $gagal++; echo "GAGAL: '{$c[0]}' => " . json_encode($r) . "\n"; }
}
echo 'uji parser: ' . (count($u) - $gagal) . '/' . count($u) . " lulus\n";
exit($gagal ? 1 : 0);
