<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Nomor_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Pengaturan_model', 'pengaturan');
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    /** Seluruh nomor pada satu tahun, dengan kunci nomor urut. */
    public function register($tahun)
    {
        $rows = $this->db->select('n.*, s.untuk AS untuk_surat')
            ->from('nomor_surat n')
            ->join('surat_tugas s', 's.id = n.surat_tugas_id', 'left')
            ->where('n.tahun', (int) $tahun)
            ->order_by('n.nomor_urut')
            ->get()->result_array();
        $peta = [];
        foreach ($rows as $r) {
            $peta[(int) $r['nomor_urut']] = $r;
        }
        return $peta;
    }

    public function maks($tahun)
    {
        $m = $this->db->select_max('nomor_urut', 'm')->where('tahun', (int) $tahun)->get('nomor_surat')->row();
        return (int) $m->m;
    }

    /** Nomor yang boleh dipakai: celah di antara 1..terakhir, atau berstatus tersedia. */
    public function bebas($tahun)
    {
        $peta = $this->register($tahun);
        $maks = $peta ? max(array_keys($peta)) : 0;
        $bebas = [];
        for ($i = 1; $i <= $maks; $i++) {
            if (!isset($peta[$i]) || $peta[$i]['status'] === 'tersedia') {
                $bebas[] = $i;
            }
        }
        return $bebas;
    }

    public function tahun_tersedia()
    {
        $rows = $this->db->distinct()->select('tahun')->order_by('tahun', 'DESC')->get('nomor_surat')->result_array();
        $tahun = array_map('intval', array_column($rows, 'tahun'));
        $ini = (int) date('Y');
        if (!in_array($ini, $tahun, true)) {
            array_unshift($tahun, $ini);
        }
        return $tahun;
    }

    private function filter(array $f)
    {
        if (!empty($f['tahun'])) {
            $this->db->where('tahun', (int) $f['tahun']);
        }
        if (!empty($f['bulan'])) {
            $this->db->where('MONTH(tanggal_surat)', (int) $f['bulan'], false);
        }
        if (!empty($f['status'])) {
            $this->db->where('status', $f['status']);
        } else {
            $this->db->where('status !=', 'tersedia');
        }
        if (!empty($f['q'])) {
            $this->db->group_start()->like('nomor_surat', $f['q'])->or_like('uraian', $f['q'])->or_like('pembuat', $f['q'])->group_end();
        }
    }

    public function daftar(array $f, $limit = null, $offset = 0)
    {
        $this->filter($f);
        $this->db->order_by('tahun', 'DESC')->order_by('nomor_urut', 'DESC');
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        return $this->db->get('nomor_surat')->result_array();
    }

    public function hitung(array $f)
    {
        $this->filter($f);
        return $this->db->count_all_results('nomor_surat');
    }

    public function baris($id)
    {
        return $this->db->get_where('nomor_surat', ['id' => (int) $id])->row_array();
    }

    public function terbaru_pengguna($userId, $n = 5)
    {
        return $this->db->where('dibuat_oleh', (int) $userId)->where_in('status', ['terpakai', 'dibatalkan'])
            ->order_by('id', 'DESC')->limit($n)->get('nomor_surat')->result_array();
    }

    // ------------------------------------------------------------------
    // Mengambil nomor: diterbitkan di dalam kunci database agar tidak pernah ganda
    // ------------------------------------------------------------------

    public function ambil(array $d, $pilih)
    {
        $tahun = (int) $d['tahun'];
        $kunci = 'surtu_nomor_' . $tahun;
        $ok = $this->db->query('SELECT GET_LOCK(?, 8) AS k', [$kunci])->row();
        if (!$ok || (int) $ok->k !== 1) {
            return [false, 'Nomor sedang diambil oleh pengguna lain. Coba lagi sebentar lagi.'];
        }
        try {
            return $this->ambil_terkunci($d, (int) $pilih);
        } finally {
            $this->db->query('SELECT RELEASE_LOCK(?)', [$kunci]);
        }
    }

    private function ambil_terkunci(array $d, $pilih)
    {
        $tahun = (int) $d['tahun'];
        $terakhir = $this->maks($tahun);
        $baris = null;

        if ($pilih > 0) {
            if ($pilih > $terakhir + 1) {
                return [false, 'Nomor urut ' . $pilih . ' terlalu jauh dari nomor terakhir tahun ' . $tahun . ' (' . $terakhir
                    . '). Pilih nomor kosong yang tertinggal, atau kosongkan kolom untuk memakai nomor berikutnya.'];
            }
            $baris = $this->db->get_where('nomor_surat', ['tahun' => $tahun, 'nomor_urut' => $pilih])->row();
            if ($baris && $baris->status !== 'tersedia') {
                return [false, 'Nomor urut ' . $pilih . ' tahun ' . $tahun . ' sudah terpakai.'];
            }
            $urut = $pilih;
        } else {
            $urut = $terakhir + 1;
        }

        $d['nomor_urut'] = $urut;
        $d['status'] = 'terpakai';
        $d['nomor_surat'] = format_nomor(
            $this->pengaturan->get('format_nomor', 'B-{urut}/{satker}/KP.{klas}/{tahun}'),
            $urut, $this->pengaturan->get('kode_satker', '61513'),
            $this->pengaturan->get('kode_klasifikasi', '650'), $tahun
        );

        $this->db->trans_start();
        if ($baris) {
            $this->db->where('id', $baris->id)->update('nomor_surat', $d);
            $id = (int) $baris->id;
        } else {
            $this->db->insert('nomor_surat', $d);
            $id = (int) $this->db->insert_id();
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            return [false, 'Nomor gagal disimpan. Nomor tidak terpakai; silakan coba lagi.'];
        }
        return [true, $id];
    }

    public function ubah($id, $uraian, $tanggal)
    {
        return $this->db->where('id', (int) $id)->update('nomor_surat', ['uraian' => $uraian, 'tanggal_surat' => $tanggal]);
    }

    public function batal($id, $alasan)
    {
        return $this->db->where('id', (int) $id)->update('nomor_surat', ['status' => 'dibatalkan', 'alasan_batal' => $alasan]);
    }

    /** Memulihkan nomor dibatalkan. Baris hasil impor kembali menjadi arsip, baris dari aplikasi menjadi terpakai. */
    public function pulihkan($id)
    {
        $r = $this->baris($id);
        if (!$r) {
            return false;
        }
        return $this->db->where('id', (int) $id)->update('nomor_surat', [
            'status' => $r['dibuat_oleh'] ? 'terpakai' : 'arsip', 'alasan_batal' => null,
        ]);
    }

    // ------------------------------------------------------------------
    // Impor register dari sheet "Agenda Surat Tugas" (.xlsx atau .csv)
    // ------------------------------------------------------------------

    public function impor_berkas($path, $namaAsli, $tahunDefault)
    {
        $lap = ['baru' => 0, 'tersedia' => 0, 'dilewati' => 0, 'ganda' => [], 'tidak_baku' => [],
            'tanpa_uraian' => [], 'tanggal_ragu' => 0, 'sheet' => [], 'error' => null];

        $this->db->trans_start();
        if (strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION)) === 'xlsx') {
            $this->impor_xlsx($path, $lap);
        } else {
            $this->impor_csv($path, (int) $tahunDefault, $lap);
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status() && !$lap['error']) {
            $lap['error'] = 'Impor dibatalkan karena kesalahan database. Tidak ada data yang diubah.';
        }
        return $lap;
    }

    private function impor_xlsx($path, array &$lap)
    {
        $this->load->library('xlsx_reader');
        if (!$this->xlsx_reader->tersedia()) {
            $lap['error'] = 'Ekstensi PHP "zip" belum aktif. Aktifkan di php.ini, atau unggah berkas CSV.';
            return;
        }
        try {
            foreach ($this->xlsx_reader->daftar_sheet($path) as $sh) {
                if (!$sh['tampil'] || !preg_match('/surtu\D*(\d{4})/i', $sh['nama'], $m)) {
                    continue;
                }
                $sebelum = [$lap['baru'], $lap['tersedia'], $lap['dilewati']];
                $this->impor_data($this->xlsx_reader->baca($path, $sh['nama']), (int) $m[1], $lap);
                $lap['sheet'][$sh['nama']] = [$lap['baru'] - $sebelum[0], $lap['tersedia'] - $sebelum[1], $lap['dilewati'] - $sebelum[2]];
            }
        } catch (Throwable $e) {
            $lap['error'] = 'Berkas Excel tidak dapat dibaca: ' . $e->getMessage();
            return;
        }
        if (!$lap['sheet']) {
            $lap['error'] = 'Tidak ada sheet bernama "SURTU <tahun>" (misalnya SURTU 2026) pada berkas ini.';
        }
    }

    private function impor_csv($path, $tahunDefault, array &$lap)
    {
        $fh = fopen($path, 'r');
        if (!$fh) {
            $lap['error'] = 'Berkas tidak dapat dibaca.';
            return;
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
        $this->impor_data($rows, $tahunDefault, $lap);
    }

    private function impor_data(array $rows, $tahunDefault, array &$lap)
    {
        $peta = null;
        foreach ($rows as $n => $r) {
            if ($peta === null) {
                $peta = $this->deteksi_judul($r);
                if ($peta === null && $n > 12) {
                    break;
                }
                continue;
            }
            $this->impor_baris($r, $peta, $tahunDefault, $lap);
        }
        if ($peta === null) {
            $lap['error'] = 'Baris judul kolom tidak ditemukan. Pastikan ada kolom "No Surat Tugas".';
        }
    }

    private function deteksi_judul(array $r)
    {
        $peta = [];
        foreach ($r as $i => $h) {
            $h = strtolower(trim(is_scalar($h) ? (string) $h : ''));
            if ($h === 'no') {
                $peta['no'] = $i;
            } elseif (strpos($h, 'tanggal surat') !== false) {
                $peta['tgl'] = $i;
            } elseif (preg_match('/^no(mor)?\s*surat/', $h)) {
                $peta['nomor'] = $i;
            } elseif (strpos($h, 'uraia') !== false) {
                $peta['uraian'] = $i;
            } elseif (strpos($h, 'membuat') !== false) {
                $peta['pembuat'] = $i;
            }
        }
        return isset($peta['nomor']) ? $peta : null;
    }

    private function mentah(array $r, array $peta, $kunci)
    {
        return isset($peta[$kunci], $r[$peta[$kunci]]) ? $r[$peta[$kunci]] : null;
    }

    private function sel(array $r, array $peta, $kunci)
    {
        $v = $this->mentah($r, $peta, $kunci);
        return is_scalar($v) ? trim((string) $v) : '';
    }

    private function impor_baris(array $r, array $peta, $tahunDefault, array &$lap)
    {
        $nomorTeks = $this->sel($r, $peta, 'nomor');
        $no = (int) $this->sel($r, $peta, 'no');

        if ($nomorTeks === '') {
            if ($no > 0 && !$this->ada($tahunDefault, $no)) {
                $this->db->insert('nomor_surat', ['tahun' => $tahunDefault, 'nomor_urut' => $no, 'status' => 'tersedia']);
                $lap['tersedia']++;
            }
            return;
        }

        if (!preg_match('/B-?\s*(\d+)\s*\/+\s*(\d+)\s*\/+\s*KP\.?\s*(\d+)\s*\/+\s*(\d{4})/i', $nomorTeks, $m)) {
            $lap['tidak_baku'][] = 'Nomor tidak dikenali dan dilewati: ' . $nomorTeks;
            $lap['dilewati']++;
            return;
        }
        $urut = (int) $m[1];
        $tahun = (int) $m[4];
        $kanonik = 'B-' . $urut . '/' . $m[2] . '/KP.' . $m[3] . '/' . $tahun;

        if ($this->ada($tahun, $urut)) {
            $lap['ganda'][] = $kanonik . ' muncul lebih dari sekali; baris kedua dilewati.';
            $lap['dilewati']++;
            return;
        }

        $catatan = [];
        if (strpos($nomorTeks, '//') !== false) {
            $catatan[] = 'Penulisan asli memakai "//" dan sudah dinormalkan';
            $lap['tidak_baku'][] = $nomorTeks . ' dinormalkan menjadi ' . $kanonik;
        }
        $uraian = $this->sel($r, $peta, 'uraian');
        if ($uraian === '') {
            $catatan[] = 'Nomor terpakai tanpa uraian';
            $lap['tanpa_uraian'][] = $kanonik;
        }
        $ambigu = false;
        $tgl = parse_tanggal_sel($this->mentah($r, $peta, 'tgl'), $tahun, $ambigu);
        if ($ambigu) {
            $tgl = null;
            $catatan[] = 'Tanggal surat pada sheet lama berupa sel tanggal yang hari dan bulannya mungkin tertukar, sehingga tidak diimpor';
            $lap['tanggal_ragu']++;
        }

        $this->db->insert('nomor_surat', [
            'tahun' => $tahun, 'nomor_urut' => $urut, 'nomor_surat' => $kanonik, 'status' => 'arsip',
            'uraian' => $uraian !== '' ? $uraian : null,
            'pembuat' => $this->sel($r, $peta, 'pembuat') ?: null,
            'tanggal_surat' => $tgl,
            'nomor_asli' => $nomorTeks,
            'catatan' => $catatan ? implode('; ', $catatan) : null,
        ]);
        $lap['baru']++;
    }

    private function ada($tahun, $urut)
    {
        return $this->db->where(['tahun' => $tahun, 'nomor_urut' => $urut])->count_all_results('nomor_surat') > 0;
    }
}
