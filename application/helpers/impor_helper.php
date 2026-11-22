<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Nomor bulan dari kata (Indonesia atau Inggris, termasuk singkatan dan salah ketik ringan); null bila bukan bulan. */
function bulan_dari_kata($kata)
{
    static $peta = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'jui' => 7, 'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10,
        'nov' => 11, 'des' => 12, 'dec' => 12];
    $k = strtolower($kata);
    if (strlen($k) < 3) {
        return null;
    }
    $tiga = substr($k, 0, 3);
    return isset($peta[$tiga]) ? $peta[$tiga] : null;
}

/**
 * Membaca teks tanggal pelaksanaan bebas, misalnya "2 s.d. 9 Januari 2026", "16 dan 20 April 2026",
 * "30 Maret - 22 Mei 2026", "tahun 2026". Mengembalikan daftar rentang [mulai, selesai] (Y-m-d)
 * atau null bila teks tidak dapat dipastikan (misalnya bulan tidak ada).
 */
function parse_pelaksanaan($teks, $tahunDefault)
{
    $t = ' ' . mb_strtolower(trim((string) $teks), 'UTF-8') . ' ';
    if (trim($t) === '') {
        return null;
    }
    if (preg_match('/^\s*tahun\s+(\d{4})\s*$/', $t, $m)) {
        return [['mulai' => $m[1] . '-01-01', 'selesai' => $m[1] . '-12-31']];
    }

    $t = preg_replace('#s\s*[./]\s*d\b\.?#', ' ~ ', $t);
    $t = preg_replace('/(?<![a-z])sd(?![a-z])/', ' ~ ', $t);
    $t = preg_replace('/\b(sampai|hingga)\b/', ' ~ ', $t);
    $t = str_replace(['-', "\xE2\x80\x93", "\xE2\x80\x94"], ' ~ ', $t);
    $t = preg_replace_callback('#\b(\d{1,2})[/.](\d{1,2})[/.](\d{4})\b#', function ($m) {
        return ' ' . (int) $m[1] . ' ' . ['', 'jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'][min(12, max(1, (int) $m[2]))] . ' ' . $m[3] . ' ';
    }, $t);
    $t = preg_replace('/\bdan\b|&|;/', ' , ', $t);
    $t = str_replace('.', ' ', $t);

    preg_match_all('/\d+|[a-z]+|~|,/', $t, $cocok);
    $entri = [];
    $hubung = [];
    $cur = ['d' => null, 'm' => null, 'y' => null];
    $tutup = function ($sambung) use (&$entri, &$hubung, &$cur) {
        if ($cur['d'] !== null || $cur['m'] !== null || $cur['y'] !== null) {
            $entri[] = $cur;
            $hubung[count($entri) - 1] = $sambung;
            $cur = ['d' => null, 'm' => null, 'y' => null];
        } elseif ($entri && $sambung === 'range') {
            $hubung[count($entri) - 1] = 'range';
        }
    };
    foreach ($cocok[0] as $tok) {
        if (ctype_digit($tok)) {
            if (strlen($tok) === 4) {
                $cur['y'] = (int) $tok;
            } else {
                if ($cur['d'] !== null) {
                    $tutup('list');
                }
                $cur['d'] = (int) $tok;
            }
        } elseif ($tok === '~') {
            $tutup('range');
        } elseif ($tok === ',') {
            $tutup('list');
        } else {
            $bln = bulan_dari_kata($tok);
            if ($bln !== null && $cur['m'] === null) {
                $cur['m'] = $bln;
            }
        }
    }
    $tutup('list');
    if (!$entri) {
        return null;
    }

    for ($i = count($entri) - 2; $i >= 0; $i--) {
        if ($entri[$i]['m'] === null && $entri[$i + 1]['m'] !== null) {
            $entri[$i]['m'] = $entri[$i + 1]['m'];
            $entri[$i]['m_carry'] = true;
        }
        if ($entri[$i]['y'] === null && $entri[$i + 1]['y'] !== null) {
            $entri[$i]['y'] = $entri[$i + 1]['y'];
            $entri[$i]['y_carry'] = true;
        }
    }
    foreach ($entri as $i => &$e) {
        if ($e['y'] === null) {
            $e['y'] = (int) $tahunDefault;
        }
        if ($e['d'] === null || $e['m'] === null) {
            return null;
        }
        $awalRentang = isset($hubung[$i]) && $hubung[$i] === 'range' && isset($entri[$i + 1]);
        if (!checkdate($e['m'], $e['d'], $e['y'])) {
            // "31 s.d 3 Februari": tanggal awal jatuh pada bulan sebelumnya
            if ($awalRentang && !empty($e['m_carry'])) {
                $e['tgl'] = null;
                continue;
            }
            return null;
        }
        $e['tgl'] = sprintf('%04d-%02d-%02d', $e['y'], $e['m'], $e['d']);
    }
    unset($e);

    $hasil = [];
    for ($i = 0; $i < count($entri); $i++) {
        $a = $entri[$i];
        if (isset($hubung[$i]) && $hubung[$i] === 'range' && isset($entri[$i + 1])) {
            $b = $entri[$i + 1];
            if ($b['tgl'] === null) {
                return null;
            }
            if ($a['tgl'] === null || $a['tgl'] > $b['tgl']) {
                if (!empty($a['m_carry'])) {
                    $a['m']--;
                    if ($a['m'] < 1) {
                        $a['m'] = 12;
                        $a['y']--;
                    }
                } elseif (!empty($a['y_carry'])) {
                    $a['y']--;
                }
                if (!checkdate($a['m'], $a['d'], $a['y'])) {
                    return null;
                }
                $a['tgl'] = sprintf('%04d-%02d-%02d', $a['y'], $a['m'], $a['d']);
                // bulan awal hanya ditebak, jadi rentang yang terlalu panjang dianggap tidak pasti
                if (!empty($a['m_carry']) && (strtotime($b['tgl']) - strtotime($a['tgl'])) / 86400 > 15) {
                    return null;
                }
            }
            if ($a['tgl'] > $b['tgl']) {
                return null;
            }
            $hasil[] = ['mulai' => $a['tgl'], 'selesai' => $b['tgl']];
            $i++;
        } else {
            $hasil[] = ['mulai' => $a['tgl'], 'selesai' => $a['tgl']];
        }
    }
    return $hasil ?: null;
}

/**
 * Satu tanggal dari sel spreadsheet (objek tanggal atau teks). $ambigu menjadi true bila berupa sel tanggal
 * dengan hari 12 atau kurang, yang pada sheet lama sering tertukar antara hari dan bulan.
 */
function parse_tanggal_sel($nilai, $tahunDefault, &$ambigu = false)
{
    $ambigu = false;
    if ($nilai instanceof DateTime || $nilai instanceof DateTimeInterface) {
        $h = (int) $nilai->format('j');
        $b = (int) $nilai->format('n');
        $ambigu = ($h <= 12 && $h !== $b);
        return $nilai->format('Y-m-d');
    }
    $teks = trim((string) $nilai);
    if ($teks === '') {
        return null;
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $teks, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }
    $r = parse_pelaksanaan($teks, $tahunDefault);
    return ($r && count($r) === 1 && $r[0]['mulai'] === $r[0]['selesai']) ? $r[0]['mulai'] : null;
}
