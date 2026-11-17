<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Pencatatan cuti pegawai, kalender, saldo, jenis cuti, dan impor dari sheet rekap cuti bulanan. */
class Cuti extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Cuti_model', 'cuti');
        $this->load->model('Pegawai_model', 'pegawai');
    }

    private function tahun_bulan()
    {
        $tahun = (int) $this->input->get('tahun');
        $bulan = (int) $this->input->get('bulan');
        return [$tahun >= 2000 && $tahun <= 2100 ? $tahun : (int) date('Y'), $bulan >= 1 && $bulan <= 12 ? $bulan : 0];
    }

    // ---- Daftar -------------------------------------------------------------

    public function index()
    {
        list($tahun, $bulan) = $this->tahun_bulan();
        $f = ['tahun' => $tahun, 'bulan' => $bulan, 'jenis' => (int) $this->input->get('jenis'), 'q' => trim((string) $this->input->get('q'))];
        $hal = max(1, (int) $this->input->get('hal'));
        $per = 25;
        $this->render('cuti/index', [
            'judul' => 'Cuti pegawai', 'f' => $f, 'hal' => $hal, 'per' => $per,
            'total' => $this->cuti->hitung($f), 'rows' => $this->cuti->daftar($f, $per, ($hal - 1) * $per),
            'jenis' => $this->cuti->jenis(),
        ]);
    }

    // ---- Tambah / ubah ------------------------------------------------------

    private function form_data($c, array $errors)
    {
        $this->render('cuti/form', [
            'judul' => $c['id'] ? 'Ubah catatan cuti' : 'Catatan cuti baru', 'c' => $c, 'errors' => $errors,
            'pegawai' => $this->pegawai->semua_aktif(), 'jenis' => $this->cuti->jenis(true),
        ]);
    }

    public function tambah()
    {
        $this->form_data(['id' => 0, 'pegawai_id' => (int) $this->input->get('pegawai'), 'jenis_id' => 0, 'mulai' => '', 'selesai' => '',
            'jumlah_hari' => '', 'keterangan' => '', 'tautan_dokumen' => ''], []);
    }

    public function ubah($id = 0)
    {
        $c = $this->cuti->ambil($id);
        if (!$c) {
            show_404();
        }
        $this->form_data($c, []);
    }

    public function simpan()
    {
        $this->wajib_post();
        $id = (int) $this->input->post('id');
        $c = [
            'id' => $id, 'pegawai_id' => (int) $this->input->post('pegawai_id'), 'jenis_id' => (int) $this->input->post('jenis_id'),
            'mulai' => trim((string) $this->input->post('mulai')), 'selesai' => trim((string) $this->input->post('selesai')),
            'jumlah_hari' => trim((string) $this->input->post('jumlah_hari')), 'keterangan' => trim((string) $this->input->post('keterangan')),
            'tautan_dokumen' => trim((string) $this->input->post('tautan_dokumen')),
        ];
        $errors = [];
        $p = $this->pegawai->ambil($c['pegawai_id']);
        $j = $this->cuti->jenis_ambil($c['jenis_id']);
        if (!$p) {
            $errors[] = 'Pilih pegawai.';
        }
        if (!$j) {
            $errors[] = 'Pilih jenis cuti.';
        }
        $valid = function ($t) { $d = DateTime::createFromFormat('Y-m-d', $t); return $d && $d->format('Y-m-d') === $t; };
        if (!$valid($c['mulai'])) {
            $errors[] = 'Tanggal mulai wajib diisi.';
        }
        if ($c['selesai'] === '') {
            $c['selesai'] = $c['mulai'];
        } elseif (!$valid($c['selesai'])) {
            $errors[] = 'Tanggal selesai tidak valid.';
        }
        if (!$errors && $c['selesai'] < $c['mulai']) {
            $errors[] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
        }
        if ($c['jumlah_hari'] !== '' && (!is_numeric(str_replace(',', '.', $c['jumlah_hari'])) || (float) str_replace(',', '.', $c['jumlah_hari']) < 0 || (float) str_replace(',', '.', $c['jumlah_hari']) > 400)) {
            $errors[] = 'Jumlah hari harus berupa angka, misalnya 3 atau 0,5.';
        }
        if ($c['tautan_dokumen'] !== '' && !preg_match('#^https?://#i', $c['tautan_dokumen'])) {
            $errors[] = 'Tautan dokumen harus diawali http:// atau https://.';
        }
        if (!$errors && $this->cuti->tumpang_tindih($p['id'], $c['mulai'], $c['selesai'], $id)) {
            $errors[] = 'Pegawai ini sudah memiliki catatan pada tanggal tersebut. Periksa daftar cuti atau ubah rentang tanggalnya.';
        }
        if ($errors) {
            $this->form_data($c, $errors);
            return;
        }
        $hari = $c['jumlah_hari'] !== '' ? (float) str_replace(',', '.', $c['jumlah_hari'])
            : hari_kerja($c['mulai'], $c['selesai']) * ($j['setengah_hari'] ? 0.5 : 1);
        $data = [
            'pegawai_id' => (int) $p['id'], 'nama_pegawai' => $p['nama'], 'jenis_id' => (int) $j['id'],
            'mulai' => $c['mulai'], 'selesai' => $c['selesai'], 'jumlah_hari' => $hari,
            'keterangan' => $c['keterangan'] !== '' ? mb_substr($c['keterangan'], 0, 300) : null,
            'tautan_dokumen' => $c['tautan_dokumen'] !== '' ? mb_substr($c['tautan_dokumen'], 0, 400) : null,
        ];
        if (!$id) {
            $data['dibuat_oleh'] = $this->user['id'];
        }
        $this->cuti->simpan($id, $data);
        $this->catat($id ? 'ubah_cuti' : 'tambah_cuti', $p['nama'] . ' ' . $j['kode'] . ' ' . $c['mulai']);
        flash_set('ok', 'Catatan ' . $j['nama'] . ' untuk ' . $p['nama'] . ' disimpan (' . angka_hari($hari) . ' hari).');
        redirect('cuti');
    }

    public function hapus($id = 0)
    {
        $this->wajib_post();
        $c = $this->cuti->ambil($id);
        if (!$c) {
            show_404();
        }
        $this->cuti->hapus($c['id']);
        $this->catat('hapus_cuti', $c['nama_pegawai'] . ' ' . $c['mulai']);
        flash_set('ok', 'Catatan cuti ' . $c['nama_pegawai'] . ' dihapus.');
        redirect('cuti');
    }

    // ---- Kalender -----------------------------------------------------------

    public function kalender()
    {
        list($tahun, $bulan) = $this->tahun_bulan();
        $bulan = $bulan ?: (int) date('n');
        $semua = $this->input->get('semua') === '1';
        list($pegawai, $peta) = $this->cuti->kalender($tahun, $bulan, !$semua);
        $this->render('cuti/kalender', [
            'judul' => 'Kalender cuti', 'tahun' => $tahun, 'bulan' => $bulan, 'semua' => $semua,
            'pegawai' => $pegawai, 'peta' => $peta, 'hari_bulan' => (int) date('t', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan))),
            'jenis' => $this->cuti->jenis(),
        ]);
    }

    // ---- Saldo --------------------------------------------------------------

    public function saldo()
    {
        list($tahun) = $this->tahun_bulan();
        $this->render('cuti/saldo', ['judul' => 'Saldo cuti tahunan', 'tahun' => $tahun, 'rows' => $this->cuti->saldo_tahun($tahun)]);
    }

    public function saldo_ubah($pegawaiId = 0)
    {
        $p = $this->pegawai->ambil($pegawaiId);
        if (!$p) {
            show_404();
        }
        list($tahun) = $this->tahun_bulan();
        $this->render('cuti/saldo_form', [
            'judul' => 'Atur saldo cuti', 'p' => $p, 'tahun' => $tahun,
            's' => $this->cuti->saldo_ambil($p['id'], $tahun), 'terpakai' => $this->cuti->terpakai($p['id'], $tahun), 'errors' => [],
        ]);
    }

    public function saldo_simpan()
    {
        $this->wajib_post();
        $p = $this->pegawai->ambil((int) $this->input->post('pegawai_id'));
        $tahun = (int) $this->input->post('tahun');
        if (!$p || $tahun < 2000 || $tahun > 2100) {
            show_404();
        }
        $angka = function ($nama) {
            $v = str_replace(',', '.', trim((string) $this->input->post($nama)));
            return is_numeric($v) && (float) $v >= 0 && (float) $v <= 99 ? (float) $v : null;
        };
        $jatah = $angka('jatah');
        $n1 = $angka('sisa_n1');
        $n2 = $angka('sisa_n2');
        if ($jatah === null || $n1 === null || $n2 === null) {
            $this->render('cuti/saldo_form', [
                'judul' => 'Atur saldo cuti', 'p' => $p, 'tahun' => $tahun, 'terpakai' => $this->cuti->terpakai($p['id'], $tahun),
                's' => ['jatah' => $this->input->post('jatah'), 'sisa_n1' => $this->input->post('sisa_n1'), 'sisa_n2' => $this->input->post('sisa_n2')],
                'errors' => ['Semua isian saldo harus berupa angka 0 sampai 99, misalnya 12 atau 5,5.'],
            ]);
            return;
        }
        $this->cuti->saldo_simpan($p['id'], $tahun, $jatah, $n1, $n2);
        $this->catat('saldo_cuti', $p['nama'] . ' ' . $tahun);
        flash_set('ok', 'Saldo cuti ' . $p['nama'] . ' tahun ' . $tahun . ' disimpan.');
        redirect('cuti/saldo?tahun=' . $tahun);
    }

    // ---- Jenis cuti (admin) -------------------------------------------------

    public function jenis()
    {
        $this->wajib_admin();
        $ubah = (int) $this->input->get('ubah');
        $this->render('cuti/jenis', [
            'judul' => 'Jenis cuti', 'rows' => $this->cuti->jenis(), 'ubah' => $ubah ? $this->cuti->jenis_ambil($ubah) : null,
        ]);
    }

    public function jenis_simpan()
    {
        $this->wajib_admin();
        $this->wajib_post();
        $id = (int) $this->input->post('id');
        $d = [
            'kode' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input->post('kode'))),
            'nama' => trim((string) $this->input->post('nama')),
            'kategori' => in_array($this->input->post('kategori'), ['cuti', 'penugasan', 'lainnya'], true) ? $this->input->post('kategori') : 'cuti',
            'potong_saldo' => $this->input->post('potong_saldo') === '1' ? 1 : 0,
            'setengah_hari' => $this->input->post('setengah_hari') === '1' ? 1 : 0,
            'aktif' => $this->input->post('aktif') === '1' ? 1 : 0,
            'catatan' => trim((string) $this->input->post('catatan')) ?: null,
        ];
        if ($d['kode'] === '' || $d['nama'] === '') {
            flash_set('bad', 'Kode dan nama jenis wajib diisi.');
        } elseif ($this->db->where('kode', $d['kode'])->where('id !=', $id)->count_all_results('jenis_cuti') > 0) {
            flash_set('bad', 'Kode ' . $d['kode'] . ' sudah dipakai jenis lain.');
        } else {
            $this->cuti->jenis_simpan($id, $d);
            $this->catat('simpan_jenis_cuti', $d['kode']);
            flash_set('ok', 'Jenis cuti ' . $d['kode'] . ' disimpan.');
        }
        redirect('cuti/jenis');
    }

    // ---- Impor dari sheet bulanan (admin) -----------------------------------

    public function impor()
    {
        $this->wajib_admin();
        if ($this->input->method() !== 'post') {
            $this->render('cuti/impor', ['judul' => 'Impor rekap cuti', 'hasil' => null, 'errors' => []]);
            return;
        }
        $this->wajib_post();
        $tahun = (int) $this->input->post('tahun');
        $bulan = (int) $this->input->post('bulan');
        $salah = validasi_csv(isset($_FILES['berkas']) ? $_FILES['berkas'] : []);
        if (!$salah && ($tahun < 2000 || $tahun > 2100 || $bulan < 1 || $bulan > 12)) {
            $salah = 'Pilih bulan dan tahun sheet yang diimpor.';
        }
        if ($salah) {
            $this->render('cuti/impor', ['judul' => 'Impor rekap cuti', 'hasil' => null, 'errors' => [$salah]]);
            return;
        }
        $hasil = $this->proses_impor($_FILES['berkas']['tmp_name'], $tahun, $bulan, $this->input->post('ganti') === '1');
        if (isset($hasil['galat'])) {
            $this->render('cuti/impor', ['judul' => 'Impor rekap cuti', 'hasil' => null, 'errors' => [$hasil['galat']]]);
            return;
        }
        $this->catat('impor_cuti', sprintf('%04d-%02d: %d catatan', $tahun, $bulan, $hasil['dibuat']));
        $this->render('cuti/impor', ['judul' => 'Impor rekap cuti', 'hasil' => $hasil, 'errors' => []]);
    }

    /**
     * Membaca CSV sheet bulanan (baris: NO, NAMA, lalu kolom tanggal 1..31) dan mengubah kode per tanggal
     * menjadi catatan berentang. Kode tanpa jenis dan nama tanpa pegawai dilaporkan, tidak diimpor.
     */
    private function proses_impor($path, $tahun, $bulan, $ganti)
    {
        $fh = fopen($path, 'r');
        if (!$fh) {
            return ['galat' => 'Berkas tidak dapat dibuka.'];
        }
        $kolomHari = [];
        $barisData = [];
        while (($r = fgetcsv($fh)) !== false) {
            if (!$kolomHari) {
                // baris judul tanggal: sel berurutan 1, 2, 3, ... mulai dari kolom ketiga
                if (isset($r[2], $r[3]) && trim($r[2]) === '1' && trim($r[3]) === '2') {
                    foreach ($r as $i => $v) {
                        if ($i >= 2 && ctype_digit(trim($v)) && (int) $v >= 1 && (int) $v <= 31) {
                            $kolomHari[$i] = (int) $v;
                        }
                    }
                }
                continue;
            }
            $nama = isset($r[1]) ? trim($r[1]) : '';
            if ($nama === '') {
                if (count(array_filter($r, 'strlen')) === 0) {
                    continue;
                }
                break; // baris penutup (jumlah per tanggal)
            }
            $barisData[] = $r;
        }
        fclose($fh);
        if (!$kolomHari) {
            return ['galat' => 'Baris tanggal (1, 2, 3, ...) tidak ditemukan. Pastikan yang diunduh adalah sheet satu bulan rekap cuti.'];
        }

        $hariBulan = (int) date('t', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan)));
        $jenis = $this->cuti->jenis_menurut_kode();
        $peta = [];
        foreach ($this->pegawai->semua_aktif() as $p) {
            $peta[nama_kunci($p['nama'])] = $p;
        }

        $tanpaPegawai = [];
        $kodeAsing = [];
        $dibuat = 0;
        $dihapus = $ganti ? $this->cuti->hapus_impor_bulan($tahun, $bulan) : 0;
        foreach ($barisData as $r) {
            $kunci = nama_kunci($r[1]);
            $hari = [];
            foreach ($kolomHari as $i => $tgl) {
                $sel = isset($r[$i]) ? trim($r[$i]) : '';
                if ($sel === '' || $tgl > $hariBulan) {
                    continue;
                }
                $kode = strtoupper(preg_replace('/\s+/', '', $sel));
                if ($kode === 'TUGASBELAJAR') {
                    // sel gabungan di sheet: berlaku untuk seluruh bulan
                    if (isset($jenis['TB'])) {
                        for ($d = 1; $d <= $hariBulan; $d++) {
                            $hari[$d] = 'TB';
                        }
                    }
                    continue;
                }
                if (!preg_match('/^([A-Z]+)(\d?)$/', $kode, $m)) {
                    continue; // catatan bebas, bukan kode
                }
                $huruf = $m[1];
                if (!isset($jenis[$huruf])) {
                    $kodeAsing[$huruf] = true;
                    continue;
                }
                $hari[$tgl] = $huruf;
            }
            if (!$hari) {
                continue;
            }
            if (!isset($peta[$kunci])) {
                $tanpaPegawai[trim($r[1])] = true;
                continue;
            }
            $p = $peta[$kunci];
            ksort($hari);
            // gabungkan tanggal berurutan (melewati akhir pekan) berkode sama menjadi satu catatan
            $awal = null;
            $akhir = null;
            $kodeAwal = null;
            $simpanRentang = function () use (&$awal, &$akhir, &$kodeAwal, $tahun, $bulan, $p, $jenis, &$dibuat) {
                if ($awal === null) {
                    return;
                }
                $mulai = sprintf('%04d-%02d-%02d', $tahun, $bulan, $awal);
                $selesai = sprintf('%04d-%02d-%02d', $tahun, $bulan, $akhir);
                $j = $jenis[$kodeAwal];
                if ($this->cuti->tumpang_tindih($p['id'], $mulai, $selesai)) {
                    return;
                }
                $this->cuti->simpan(0, [
                    'pegawai_id' => (int) $p['id'], 'nama_pegawai' => $p['nama'], 'jenis_id' => (int) $j['id'],
                    'mulai' => $mulai, 'selesai' => $selesai,
                    'jumlah_hari' => hari_kerja($mulai, $selesai) * ($j['setengah_hari'] ? 0.5 : 1),
                    'keterangan' => 'Diimpor dari rekap cuti', 'sumber' => 'impor', 'dibuat_oleh' => $this->user['id'],
                ]);
                $dibuat++;
            };
            foreach ($hari as $tgl => $kode) {
                if ($awal !== null && $kode === $kodeAwal && $this->berurutan($tahun, $bulan, $akhir, $tgl)) {
                    $akhir = $tgl;
                    continue;
                }
                $simpanRentang();
                $awal = $akhir = $tgl;
                $kodeAwal = $kode;
            }
            $simpanRentang();
        }
        return ['dibuat' => $dibuat, 'dihapus' => $dihapus, 'tanpa_pegawai' => array_keys($tanpaPegawai), 'kode_asing' => array_keys($kodeAsing)];
    }

    /** Tanggal $b langsung menyusul $a, atau menyusul setelah akhir pekan saja. */
    private function berurutan($tahun, $bulan, $a, $b)
    {
        $t = strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $a)) + 86400;
        $target = strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $b));
        while ($t < $target && (int) date('N', $t) >= 6) {
            $t += 86400;
        }
        return $t === $target;
    }
}
