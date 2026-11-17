<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Impor riwayat surat tugas dari sheet "Data mail" (Data Utama): analisis dahulu, terapkan setelah dikonfirmasi. */
class Riwayat_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Pengaturan_model', 'pengaturan');
    }

    private $alias = [
        'nomor' => ['nosurtug', 'nosurattugas', 'nomorsurattugas'],
        'pengirim' => ['ttdsuratrujukan'], 'nomor_rujukan' => ['nosuratrujukan'],
        'hal' => ['halsrtrujukan', 'halsuratrujukan'],
        'nama' => ['namapenerima'], 'nip' => ['nip'], 'pangkat' => ['pangkatgol', 'pangkat'], 'jabatan' => ['jabatan'],
        'untuk' => ['untuk'], 'tglkeg' => ['tglkeg', 'tanggalpelaksanaan'], 'peran' => ['peran'],
        'tempat' => ['tempatpengesahan'], 'tglsah' => ['tglpengesahan', 'tanggalpengesahan'],
        'jabttd' => ['jabttd'], 'ttd' => ['ttd'],
        'url_tanpa' => ['mergeddocurlsurattugastanpatabel'], 'url_dengan' => ['mergeddocurlsurattugasdengantabel'],
    ];

    // ------------------------------------------------------------------
    // Membaca berkas
    // ------------------------------------------------------------------

    public function deteksi_judul(array $r)
    {
        $peta = [];
        foreach ($r as $i => $h) {
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower(is_scalar($h) ? (string) $h : ''));
            foreach ($this->alias as $kunci => $daftar) {
                if (in_array($norm, $daftar, true) && !isset($peta[$kunci])) {
                    $peta[$kunci] = $i;
                }
            }
        }
        return (isset($peta['nomor'], $peta['untuk'])) ? $peta : null;
    }

    /** Mengembalikan daftar baris (larik) dari xlsx atau csv; melempar RuntimeException bila gagal. */
    public function baca_berkas($path, $ekstensi)
    {
        if ($ekstensi === 'xlsx') {
            $this->load->library('xlsx_reader');
            if (!$this->xlsx_reader->tersedia()) {
                throw new RuntimeException('Ekstensi PHP "zip" belum aktif. Aktifkan di php.ini, atau unggah berkas CSV.');
            }
            $kandidat = [];
            foreach ($this->xlsx_reader->daftar_sheet($path) as $sh) {
                if ($sh['tampil']) {
                    $kandidat[] = $sh['nama'];
                }
            }
            usort($kandidat, function ($a, $b) {
                return (int) (strcasecmp($b, 'Data Utama') === 0) <=> (int) (strcasecmp($a, 'Data Utama') === 0);
            });
            foreach ($kandidat as $nama) {
                $rows = $this->xlsx_reader->baca($path, $nama);
                foreach (array_slice($rows, 0, 13) as $r) {
                    if ($this->deteksi_judul($r) !== null) {
                        return $rows;
                    }
                }
            }
            throw new RuntimeException('Tidak ada sheet dengan kolom "No Surtug" dan "Untuk". Gunakan sheet "Data Utama" dari Data mail.');
        }

        $fh = fopen($path, 'r');
        if (!$fh) {
            throw new RuntimeException('Berkas tidak dapat dibaca.');
        }
        $pertama = (string) fgets($fh);
        rewind($fh);
        $pemisah = substr_count($pertama, ';') > substr_count($pertama, ',') ? ';' : ',';
        $rows = [];
        while (($r = fgetcsv($fh, 0, $pemisah)) !== false) {
            if ($rows === [] && isset($r[0])) {
                $r[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $r[0]);
            }
            $rows[] = $r;
        }
        fclose($fh);
        return $rows;
    }

    // ------------------------------------------------------------------
    // Analisis (tidak mengubah database)
    // ------------------------------------------------------------------

    private function sel(array $r, array $peta, $kunci)
    {
        if (!isset($peta[$kunci], $r[$peta[$kunci]])) {
            return '';
        }
        $v = $r[$peta[$kunci]];
        return is_scalar($v) ? trim((string) $v) : '';
    }

    private function mentah(array $r, array $peta, $kunci)
    {
        return isset($peta[$kunci], $r[$peta[$kunci]]) ? $r[$peta[$kunci]] : null;
    }

    public function analisis(array $rows)
    {
        $peta = null;
        $mulai = 0;
        foreach ($rows as $i => $r) {
            if (($peta = $this->deteksi_judul($r)) !== null) {
                $mulai = $i + 1;
                break;
            }
            if ($i > 12) {
                break;
            }
        }
        if ($peta === null) {
            return ['galat' => 'Baris judul tidak ditemukan. Pastikan ada kolom "No Surtug" dan "Untuk".'];
        }

        $suratAda = [];
        foreach ($this->db->select('nomor_surat')->get('surat_tugas')->result() as $x) {
            $suratAda[$x->nomor_surat] = true;
        }
        $statusNomor = [];
        foreach ($this->db->select('tahun, nomor_urut, status')->get('nomor_surat')->result() as $x) {
            $statusNomor[$x->tahun . '-' . $x->nomor_urut] = $x->status;
        }

        $surat = [];
        $masalah = [];
        $lihat = [];
        $r = ['baris' => 0, 'dilewati' => 0, 'banyak' => 0, 'tunggal' => 0, 'rentang_terbaca' => 0,
            'rentang_kosong' => 0, 'rentang_gagal' => 0, 'tanpa_tgl' => 0];

        for ($i = $mulai; $i < count($rows); $i++) {
            $row = $rows[$i];
            $nomorTeks = $this->sel($row, $peta, 'nomor');
            if ($nomorTeks === '') {
                continue;
            }
            $r['baris']++;
            $label = 'Baris data ' . $r['baris'] . ' (' . $nomorTeks . ')';

            if (!preg_match('/B-?\s*(\d+)\s*\/+\s*(\d+)\s*\/+\s*KP\.?\s*(\d+)\s*\/+\s*(\d{4})/i', $nomorTeks, $m)) {
                $masalah[] = $label . ': nomor tidak dikenali, dilewati.';
                $r['dilewati']++;
                continue;
            }
            $urut = (int) $m[1];
            $tahun = (int) $m[4];
            $nomor = 'B-' . $urut . '/' . $m[2] . '/KP.' . $m[3] . '/' . $tahun;
            $kunci = $tahun . '-' . $urut;

            if (isset($lihat[$kunci])) {
                $masalah[] = $label . ': nomor muncul lebih dari sekali pada berkas, baris ini dilewati.';
                $r['dilewati']++;
                continue;
            }
            $lihat[$kunci] = true;
            if (isset($suratAda[$nomor]) || (isset($statusNomor[$kunci]) && in_array($statusNomor[$kunci], ['terpakai', 'dibatalkan'], true))) {
                $masalah[] = $label . ': nomor sudah ada di aplikasi, dilewati.';
                $r['dilewati']++;
                continue;
            }

            $catatan = [];
            $ambigu = false;
            $tgl = parse_tanggal_sel($this->mentah($row, $peta, 'tglsah'), $tahun, $ambigu);
            if ($tgl === null) {
                $masalah[] = $label . ': tanggal pengesahan kosong atau tidak terbaca, dilewati.';
                $r['dilewati']++;
                $r['tanpa_tgl']++;
                continue;
            }

            $nama = $this->sel($row, $peta, 'nama');
            $nipTeks = $this->sel($row, $peta, 'nip');
            $banyak = stripos($nipTeks, 'dengan tabel') !== false || (bool) preg_match('/,\s*dkk\.?$/i', $nama);
            $abaikan = function ($v) { return stripos($v, 'dengan tabel') !== false ? '' : $v; };
            $nip = $banyak ? '' : preg_replace('/\D+/', '', $nipTeks);

            $teksKeg = '';
            $rentang = [];
            $mentahKeg = $this->mentah($row, $peta, 'tglkeg');
            if ($mentahKeg instanceof DateTimeInterface) {
                $catatan[] = 'Tanggal pelaksanaan pada sheet lama berupa sel tanggal (' . $mentahKeg->format('Y-m-d') . ') dan tidak diimpor';
                $r['rentang_gagal']++;
            } else {
                $teksKeg = $this->sel($row, $peta, 'tglkeg');
                if ($teksKeg === '') {
                    $r['rentang_kosong']++;
                } elseif (($rentang = parse_pelaksanaan($teksKeg, $tahun)) === null) {
                    $rentang = [];
                    $catatan[] = 'Tanggal pelaksanaan asli: ' . $teksKeg;
                    $masalah[] = $label . ': tanggal pelaksanaan "' . $teksKeg . '" tidak terbaca; teks aslinya disimpan pada catatan.';
                    $r['rentang_gagal']++;
                } else {
                    $r['rentang_terbaca']++;
                }
            }
            if ($banyak) {
                $catatan[] = 'Daftar penerima lengkap tidak tersedia pada sheet lama';
                $r['banyak']++;
            } else {
                $r['tunggal']++;
            }

            $pengirim = $this->sel($row, $peta, 'pengirim');
            $nomorRujukan = $this->sel($row, $peta, 'nomor_rujukan');
            $asal = (preg_match('#/615\d\d/#', $nomorRujukan) || preg_match('/^(ketua tim|kepala bagian|koordinator|kasubbag)/i', $pengirim))
                ? 'internal' : 'eksternal';

            $urlDengan = $this->sel($row, $peta, 'url_dengan');
            $urlTanpa = $this->sel($row, $peta, 'url_tanpa');
            $tautan = null;
            foreach ($banyak ? [$urlDengan, $urlTanpa] : [$urlTanpa, $urlDengan] as $u) {
                if (preg_match('#^https://docs\.google\.com/#', $u)) {
                    $tautan = $u;
                    break;
                }
            }

            $untukSheet = $this->sel($row, $peta, 'untuk');
            $awalan = trim($this->pengaturan->get('awalan_untuk', 'Mengikuti Kegiatan'));
            // Pada surat asli, kalimat Untuk = awalan template + isi sheet (kecuali isi sheet sudah diawali "Mengikuti").
            $untukPenuh = $untukSheet === '' ? ($this->sel($row, $peta, 'hal') ?: '-')
                : ((stripos($untukSheet, 'mengikuti') === 0 || $awalan === '') ? $untukSheet : $awalan . ' ' . $untukSheet);

            $surat[] = [
                'nomor' => $nomor, 'urut' => $urut, 'tahun' => $tahun, 'tgl_surat' => $tgl,
                'tempat' => $this->sel($row, $peta, 'tempat') ?: 'Pontianak', 'asal' => $asal,
                'pengirim' => $pengirim ?: null, 'nomor_rujukan' => $nomorRujukan ?: null,
                'hal' => $this->sel($row, $peta, 'hal') ?: null,
                'untuk' => $untukPenuh,
                'rentang' => $rentang, 'teks_keg' => $teksKeg, 'banyak' => $banyak,
                'nama' => $nama !== '' ? $nama : '(tidak tercatat)', 'nip' => $nip,
                'pangkat' => $abaikan($this->sel($row, $peta, 'pangkat')), 'jabatan' => $abaikan($this->sel($row, $peta, 'jabatan')),
                'peran' => $this->sel($row, $peta, 'peran'),
                'ttd_nama' => $this->sel($row, $peta, 'ttd'), 'ttd_jabatan' => $this->sel($row, $peta, 'jabttd'),
                'tautan' => $tautan,
                'catatan' => mb_substr(implode('; ', $catatan), 0, 300),
                'ada_baris_nomor' => isset($statusNomor[$kunci]),
            ];
        }

        // Data master baru yang akan dibuat
        $peran = [];
        foreach ($this->db->get('peran')->result() as $x) {
            $peran[strtolower($x->nama)] = true;
        }
        $pegawai = [];
        foreach ($this->db->select('nip')->where('nip IS NOT NULL', null, false)->get('pegawai')->result() as $x) {
            $pegawai[$x->nip] = true;
        }
        $ttd = [];
        foreach ($this->db->get('penandatangan')->result() as $x) {
            $ttd[strtolower(trim($x->nama))] = true;
        }
        $baru = ['peran' => [], 'pegawai' => [], 'ttd' => []];
        foreach ($surat as $s) {
            if ($s['peran'] !== '' && !isset($peran[strtolower($s['peran'])])) {
                $baru['peran'][strtolower($s['peran'])] = $s['peran'];
            }
            if (!$s['banyak'] && strlen($s['nip']) >= 8 && !isset($pegawai[$s['nip']])) {
                $baru['pegawai'][$s['nip']] = true;
            }
            if ($s['ttd_nama'] !== '' && !isset($ttd[strtolower(trim($s['ttd_nama']))])) {
                $baru['ttd'][strtolower(trim($s['ttd_nama']))] = $s['ttd_nama'];
            }
        }
        $r['impor'] = count($surat);
        return ['galat' => null, 'surat' => $surat, 'masalah' => $masalah, 'ringkas' => $r,
            'baru' => ['peran' => array_values($baru['peran']), 'pegawai' => count($baru['pegawai']), 'ttd' => array_values($baru['ttd'])]];
    }

    // ------------------------------------------------------------------
    // Penerapan ke database
    // ------------------------------------------------------------------

    public function terapkan(array $surat, $userId)
    {
        $this->db->trans_start();
        $minTgl = [];
        foreach ($surat as $s) {
            $k = strtolower(trim($s['ttd_nama']));
            if ($k !== '' && (!isset($minTgl[$k]) || $s['tgl_surat'] < $minTgl[$k])) {
                $minTgl[$k] = $s['tgl_surat'];
            }
        }
        $cache = ['ttd' => [], 'peran' => [], 'nip' => []];
        $hitung = ['surat' => 0, 'pegawai_baru' => 0, 'peran_baru' => 0, 'ttd_baru' => 0];

        foreach ($surat as $s) {
            // penandatangan
            $kt = strtolower(trim($s['ttd_nama']));
            if (!isset($cache['ttd'][$kt])) {
                $baris = $kt === '' ? null : $this->db->where('LOWER(TRIM(nama)) = ' . $this->db->escape($kt), null, false)->get('penandatangan')->row();
                if ($baris) {
                    $cache['ttd'][$kt] = (int) $baris->id;
                } else {
                    $this->db->insert('penandatangan', [
                        'nama' => $s['ttd_nama'] !== '' ? $s['ttd_nama'] : 'Tidak tercatat',
                        'jabatan' => $s['ttd_jabatan'] !== '' ? $s['ttd_jabatan'] : 'Kepala Badan Pusat Statistik Provinsi Kalimantan Barat',
                        'aktif_dari' => $kt !== '' && isset($minTgl[$kt]) ? $minTgl[$kt] : $s['tgl_surat'],
                    ]);
                    $cache['ttd'][$kt] = (int) $this->db->insert_id();
                    $hitung['ttd_baru']++;
                }
            }
            // peran
            $peranId = null;
            if ($s['peran'] !== '') {
                $kp = strtolower($s['peran']);
                if (!isset($cache['peran'][$kp])) {
                    $baris = $this->db->where('LOWER(nama) = ' . $this->db->escape($kp), null, false)->get('peran')->row();
                    if ($baris) {
                        $cache['peran'][$kp] = (int) $baris->id;
                    } else {
                        $this->db->insert('peran', ['nama' => $s['peran']]);
                        $cache['peran'][$kp] = (int) $this->db->insert_id();
                        $hitung['peran_baru']++;
                    }
                }
                $peranId = $cache['peran'][$kp];
            }
            // pegawai (hanya untuk surat satu penerima)
            $pegawaiId = null;
            if (!$s['banyak']) {
                $kn = strlen($s['nip']) >= 8 ? 'n' . $s['nip'] : 'm' . strtolower($s['nama']);
                if (!isset($cache['nip'][$kn])) {
                    $baris = strlen($s['nip']) >= 8
                        ? $this->db->get_where('pegawai', ['nip' => $s['nip']])->row()
                        : $this->db->where('LOWER(nama) = ' . $this->db->escape(strtolower($s['nama'])), null, false)->where('nip IS NULL', null, false)->get('pegawai')->row();
                    if ($baris) {
                        $cache['nip'][$kn] = (int) $baris->id;
                    } else {
                        $this->db->insert('pegawai', [
                            'nip' => strlen($s['nip']) >= 8 ? $s['nip'] : null, 'nama' => $s['nama'],
                            'pangkat_golongan' => $s['pangkat'] ?: null, 'jabatan' => $s['jabatan'] ?: '-', 'aktif' => 1,
                        ]);
                        $cache['nip'][$kn] = (int) $this->db->insert_id();
                        $hitung['pegawai_baru']++;
                    }
                }
                $pegawaiId = $cache['nip'][$kn];
            }

            $this->db->insert('surat_tugas', [
                'tahun' => $s['tahun'], 'nomor_urut' => $s['urut'], 'nomor_surat' => $s['nomor'],
                'tanggal_surat' => $s['tgl_surat'], 'tempat' => $s['tempat'], 'asal' => $s['asal'],
                'rujukan_pengirim' => $s['pengirim'], 'rujukan_nomor' => $s['nomor_rujukan'], 'rujukan_hal' => $s['hal'],
                'untuk' => $s['untuk'], 'penandatangan_id' => $cache['ttd'][$kt], 'status' => 'aktif',
                'sumber' => 'impor', 'tautan_arsip' => $s['tautan'], 'catatan' => $s['catatan'] ?: null,
                'dibuat_oleh' => (int) $userId,
            ]);
            $id = (int) $this->db->insert_id();

            $baris = $this->db->get_where('nomor_surat', ['tahun' => $s['tahun'], 'nomor_urut' => $s['urut']])->row();
            if ($baris) {
                $this->db->where('id', $baris->id)->update('nomor_surat', [
                    'status' => 'terpakai', 'surat_tugas_id' => $id, 'tanggal_surat' => $s['tgl_surat'],
                ]);
            } else {
                $this->db->insert('nomor_surat', [
                    'tahun' => $s['tahun'], 'nomor_urut' => $s['urut'], 'status' => 'terpakai',
                    'surat_tugas_id' => $id, 'tanggal_surat' => $s['tgl_surat'],
                ]);
            }

            $this->db->insert('surat_tugas_pegawai', [
                'surat_tugas_id' => $id, 'pegawai_id' => $pegawaiId, 'peran_id' => $peranId, 'urutan' => 1,
                'nama' => $s['nama'], 'nip' => strlen($s['nip']) >= 8 ? $s['nip'] : null,
                'pangkat_golongan' => $s['pangkat'] ?: null, 'jabatan' => $s['jabatan'] ?: null, 'unit_kerja' => null,
            ]);
            foreach ($s['rentang'] as $t) {
                $this->db->insert('surat_tugas_tanggal', ['surat_tugas_id' => $id, 'mulai' => $t['mulai'], 'selesai' => $t['selesai']]);
            }
            $hitung['surat']++;
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? [true, $hitung] : [false, 'Impor dibatalkan karena kesalahan database. Tidak ada data yang diubah.'];
    }
}
