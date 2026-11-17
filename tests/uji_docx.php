<?php
// Pengujian pengisi penanda Word pada template bawaan:  php tests/uji_docx.php
define('BASEPATH', 1);
require __DIR__ . '/../application/libraries/Docx_isi.php';

$d = new Docx_isi();
$gagal = 0;
$cek = function ($nama, $kondisi) use (&$gagal) { if (!$kondisi) { $gagal++; echo "GAGAL: $nama\n"; } };
$folder = __DIR__ . '/../application/storage/template_awal/';
foreach (['satu_lama', 'satu_baru', 'banyak_lama', 'banyak_baru'] as $nama) {
    $path = $folder . $nama . '.docx';
    $cek("$nama: valid", $d->periksa($path) === null);
    $cek("$nama: penanda lengkap", count(array_diff(['NOMOR', 'TANGGAL', 'TEMPAT', 'URAIAN', 'TTD_NAMA', 'TTD_JABATAN'], $d->penanda($path))) === 0);
    $isi = $d->isi($path, ['NOMOR' => 'B-1/61513/KP.650/2026', 'TANGGAL' => '5 Oktober 2026', 'TEMPAT' => 'Pontianak',
        'URAIAN' => 'Rapat <A> & "B"', 'TTD_NAMA' => 'Nama Uji', 'TTD_JABATAN' => 'Kepala Uji']);
    $tmp = tempnam(sys_get_temp_dir(), 'uji');
    file_put_contents($tmp, $isi);
    $z = new ZipArchive();
    $cek("$nama: hasil terbuka", $z->open($tmp) === true);
    $xml = $z->getFromName('word/document.xml');
    $teks = html_entity_decode(strip_tags($xml));
    $cek("$nama: nomor terisi", strpos($teks, 'B-1/61513/KP.650/2026') !== false);
    $cek("$nama: karakter khusus apa adanya", strpos($teks, 'Rapat <A> & "B"') !== false);
    $cek("$nama: tidak ada penanda tersisa", strpos($teks, '{{') === false);
    $cek("$nama: XML tetap valid", @simplexml_load_string($xml) !== false);
    $z->close();
    unlink($tmp);
}
echo 'uji docx: ' . ($gagal ? "$gagal GAGAL\n" : "semua lulus\n");
exit($gagal ? 1 : 0);
