<?php defined('BASEPATH') OR exit('No direct script access allowed');

function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function csrf_field()
{
    $ci =& get_instance();
    return '<input type="hidden" name="' . $ci->security->get_csrf_token_name()
        . '" value="' . $ci->security->get_csrf_hash() . '">';
}

function bulan_indo($n)
{
    static $nama = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
        'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $nama[(int) $n];
}

function tgl_indo($tanggal)
{
    if (!$tanggal) {
        return '';
    }
    $t = strtotime($tanggal);
    return date('j', $t) . ' ' . bulan_indo(date('n', $t)) . ' ' . date('Y', $t);
}



function format_nomor($format, $urut, $satker, $klasifikasi, $tahun)
{
    return strtr($format, [
        '{urut}' => $urut, '{satker}' => $satker, '{klas}' => $klasifikasi, '{tahun}' => $tahun,
    ]);
}

function nama_berkas_nomor($nomor)
{
    return preg_replace('/[^A-Za-z0-9._-]+/', '_', $nomor);
}

function label_status($status)
{
    $peta = [
        'terpakai' => ['Terpakai', 'ok'], 'arsip' => ['Arsip', 'mute'],
        'dibatalkan' => ['Dibatalkan', 'bad'], 'tersedia' => ['Kosong', 'warn'],
    ];
    $p = isset($peta[$status]) ? $peta[$status] : [$status, 'mute'];
    return '<span class="badge badge-' . $p[1] . '">' . e($p[0]) . '</span>';
}

function flash_set($jenis, $pesan)
{
    get_instance()->session->set_flashdata('flash', ['jenis' => $jenis, 'pesan' => $pesan]);
}

function aktif_menu($segmen)
{
    return get_instance()->uri->segment(1) === $segmen ? ' class="aktif"' : '';
}


function kirim_header_aman($csp = false)
{
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    if ($csp) {
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; "
            . "script-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'");
    }
}

/** Mengembalikan pesan kesalahan, atau null bila berkas CSV unggahan layak diproses. */
function validasi_csv($berkas)
{
    if (empty($berkas['tmp_name']) || $berkas['error'] !== UPLOAD_ERR_OK) {
        return 'Pilih berkas CSV terlebih dahulu.';
    }
    if ($berkas['size'] > 5 * 1024 * 1024) {
        return 'Ukuran berkas maksimal 5 MB.';
    }
    $ekstensi = strtolower(pathinfo($berkas['name'], PATHINFO_EXTENSION));
    if (!in_array($ekstensi, ['csv', 'txt'], true)) {
        return 'Berkas harus berformat CSV.';
    }
    $awal = (string) file_get_contents($berkas['tmp_name'], false, null, 0, 2048);
    if (strpos($awal, "\0") !== false) {
        return 'Isi berkas bukan teks CSV.';
    }
    return null;
}




/** Seperti validasi_csv, tetapi juga menerima .xlsx. */
function validasi_impor($berkas, $maksMb = 10)
{
    if (empty($berkas['tmp_name']) || $berkas['error'] !== UPLOAD_ERR_OK) {
        return 'Pilih berkas CSV atau Excel (.xlsx) terlebih dahulu.';
    }
    if ($berkas['size'] > $maksMb * 1024 * 1024) {
        return 'Ukuran berkas maksimal ' . $maksMb . ' MB.';
    }
    $ekstensi = strtolower(pathinfo($berkas['name'], PATHINFO_EXTENSION));
    $awal = (string) file_get_contents($berkas['tmp_name'], false, null, 0, 2048);
    if ($ekstensi === 'xlsx') {
        return substr($awal, 0, 2) === 'PK' ? null : 'Berkas bukan Excel (.xlsx) yang valid.';
    }
    if (!in_array($ekstensi, ['csv', 'txt'], true)) {
        return 'Berkas harus berformat CSV atau Excel (.xlsx).';
    }
    return strpos($awal, "\0") !== false ? 'Isi berkas bukan teks CSV.' : null;
}


/** Menyamakan zona waktu sesi MySQL dengan aplikasi (WIB, UTC+7) agar waktu pencatatan sesuai jam setempat. */
function atur_zona_waktu_db()
{
    get_instance()->db->query("SET time_zone = '+07:00'");
}


/**
 * Menuliskan tanggal pelaksanaan dalam kalimat: "4 Oktober 2026", "4 s.d. 7 Oktober 2026",
 * "4, 7, dan 9 Oktober 2026" (tanggal terpisah dalam bulan yang sama digabung).
 */
function rentang_tanggal(array $tanggal)
{
    if (!$tanggal) {
        return '-';
    }
    usort($tanggal, function ($a, $b) {
        return strcmp($a['mulai'], $b['mulai']);
    });
    $kelompok = [];
    $urut = [];
    foreach ($tanggal as $t) {
        $m = strtotime($t['mulai']);
        $s = strtotime($t['selesai']);
        if (date('n Y', $m) === date('n Y', $s)) {
            $kunci = date('Y-n', $m);
            if (!isset($kelompok[$kunci])) {
                $kelompok[$kunci] = ['akhir' => bulan_indo(date('n', $m)) . ' ' . date('Y', $m), 'butir' => []];
                $urut[] = $kunci;
            }
            $kelompok[$kunci]['butir'][] = $t['mulai'] === $t['selesai'] ? date('j', $m) : date('j', $m) . ' s.d. ' . date('j', $s);
        } else {
            $kunci = 'lintas' . count($urut);
            $teks = date('Y', $m) === date('Y', $s)
                ? date('j', $m) . ' ' . bulan_indo(date('n', $m)) . ' s.d. ' . date('j', $s) . ' ' . bulan_indo(date('n', $s)) . ' ' . date('Y', $s)
                : tgl_indo($t['mulai']) . ' s.d. ' . tgl_indo($t['selesai']);
            $kelompok[$kunci] = ['akhir' => '', 'butir' => [$teks]];
            $urut[] = $kunci;
        }
    }
    $bagian = [];
    foreach ($urut as $kunci) {
        $butir = $kelompok[$kunci]['butir'];
        $n = count($butir);
        $teks = $n > 1 ? implode(', ', array_slice($butir, 0, $n - 1)) . ' dan ' . $butir[$n - 1] : $butir[0];
        $bagian[] = trim($teks . ' ' . $kelompok[$kunci]['akhir']);
    }
    return implode('; ', $bagian);
}

/** Menghitung total hari pelaksanaan (tanggal yang sama tidak dihitung dua kali). */
function lama_hari(array $tanggal)
{
    $hari = [];
    foreach ($tanggal as $t) {
        for ($d = strtotime($t['mulai']), $akhir = strtotime($t['selesai']); $d <= $akhir; $d += 86400) {
            $hari[date('Y-m-d', $d)] = true;
        }
    }
    return count($hari);
}

/** Jenis surat tugas yang memiliki template Word sendiri: slug => judul dan awalan kalimat "Untuk". */
function daftar_jenis_template()
{
    return [
        'umum' => ['judul' => '', 'awalan' => 'Mengikuti Kegiatan '],
        'narasumber' => ['judul' => 'Narasumber', 'awalan' => 'Menjadi Narasumber pada Kegiatan '],
        'perjalanan_dinas' => ['judul' => 'Perjalanan Dinas', 'awalan' => 'Melaksanakan perjalanan dinas dalam rangka '],
        'kegiatan_lapangan' => ['judul' => 'Pelaksanaan Kegiatan (Pengawasan, Supervisi, Pemantauan)', 'awalan' => 'Melaksanakan Kegiatan '],
        'tugas_belajar' => ['judul' => 'Tugas Belajar', 'awalan' => 'Mengikuti pendidikan Tugas Belajar '],
        'pembinaan' => ['judul' => 'Pembinaan', 'awalan' => 'Melaksanakan Pembinaan '],
        'internal' => ['judul' => 'Internal (tanpa surat rujukan)', 'awalan' => 'Mengikuti Kegiatan '],
    ];
}

/** Nama template Word untuk jenis dan bentuk surat tertentu (sama dengan yang terdaftar di menu Template Word). */
function nama_template_surat($slug, $banyak)
{
    $j = daftar_jenis_template();
    $judul = isset($j[$slug]) ? $j[$slug]['judul'] : '';
    return 'Surat Tugas ' . ($judul !== '' ? $judul . ' ' : '') . '- ' . ($banyak ? 'banyak penerima (dengan lampiran)' : 'satu penerima');
}


/** Format baris daftar bertingkat untuk dokumen PDF (Menimbang & Mengingat). */
function pdf_daftar(array $baris)
{
    $html = '<table class="daftar" style="width:100%; border-collapse:collapse;">';
    foreach ($baris as $b) {
        $html .= '<tr><td class="no" style="width:0.75cm; vertical-align:top; padding-bottom:5px;">' . e($b[0]) . '</td>'
            . '<td style="vertical-align:top; text-align:justify; padding-bottom:5px;">' . e($b[1]) . '</td></tr>';
    }
    $html .= '</table>';
    return $html;
}

/** Format blok tanda tangan pejabat untuk dokumen PDF. */
function pdf_ttd(array $s, $tglSurat)
{
    $tempat = !empty($s['tempat']) ? $s['tempat'] : 'Pontianak';
    $jabatan = !empty($s['ttd_jabatan']) ? $s['ttd_jabatan'] : 'Kepala Badan Pusat Statistik Provinsi Kalimantan Barat';
    $nama = !empty($s['ttd_nama']) ? $s['ttd_nama'] : 'Muh Saichudin';
    $nip = !empty($s['ttd_nip']) ? 'NIP. ' . $s['ttd_nip'] : '';

    $html = '<div class="ttd" style="width:8.6cm; margin-left:7.8cm; margin-top:18px; text-align:center; page-break-inside:avoid;">';
    $html .= '<div>' . e($tempat) . ', ' . e($tglSurat) . '</div>';
    $html .= '<div style="margin-top:2px;">' . e($jabatan) . '</div>';
    $html .= '<div class="ruang" style="height:2.1cm;"></div>';
    $html .= '<div><strong style="text-decoration:underline;">' . e($nama) . '</strong></div>';
    if ($nip !== '') {
        $html .= '<div style="margin-top:2px;">' . e($nip) . '</div>';
    }
    $html .= '</div>';
    return $html;
}

/** Ikon SVG inline (sprite ada di views/layout/ikon.php). Hanya untuk tampilan. */
function ikon($nama, $kelas = '')
{
    return '<svg width="18" height="18" class="ik' . ($kelas !== '' ? ' ' . e($kelas) : '') . '" aria-hidden="true" focusable="false"><use href="#i-' . e($nama) . '"></use></svg>';
}

/** Ukuran berkas yang mudah dibaca, misalnya "1,2 MB". */
function ukuran_berkas($byte)
{
    $byte = (int) $byte;
    if ($byte >= 1048576) {
        return str_replace('.', ',', (string) round($byte / 1048576, 1)) . ' MB';
    }
    return max(1, (int) round($byte / 1024)) . ' KB';
}
