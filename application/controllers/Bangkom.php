<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Pengembangan kompetensi (Bangkom): catatan pelatihan pegawai, rekap JP per tahun, dan impor dari sheet 6100. */
class Bangkom extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Bangkom_model', 'bangkom');
        $this->load->model('Pegawai_model', 'pegawai');
        $this->load->model('Pengaturan_model', 'pengaturan');
    }

    private function tahun()
    {
        $t = (int) $this->input->get('tahun');
        return $t >= 2000 && $t <= 2100 ? $t : (int) date('Y');
    }

    private function nilai_target()
    {
        return max(0, (int) $this->pengaturan->get('bangkom_target_jp', '20'));
    }

    public function index()
    {
        $tahun = $this->tahun();
        $f = ['tahun' => $tahun, 'q' => trim((string) $this->input->get('q')), 'kategori' => trim((string) $this->input->get('kategori'))];
        $hal = max(1, (int) $this->input->get('hal'));
        $per = 25;
        $this->render('bangkom/index', [
            'judul' => 'Pengembangan kompetensi', 'f' => $f, 'hal' => $hal, 'per' => $per,
            'total' => $this->bangkom->hitung($f), 'rows' => $this->bangkom->daftar($f, $per, ($hal - 1) * $per),
            'kategori' => $this->bangkom->kategori_tahun($tahun),
        ]);
    }

    private function form_data($b, array $errors)
    {
        $this->render('bangkom/form', [
            'judul' => $b['id'] ? 'Ubah catatan Bangkom' : 'Catatan Bangkom baru', 'b' => $b, 'errors' => $errors,
            'pegawai' => $this->pegawai->semua_aktif(),
        ]);
    }

    public function tambah()
    {
        $this->form_data(['id' => 0, 'pegawai_id' => (int) $this->input->get('pegawai'), 'kegiatan' => '', 'mulai' => '', 'selesai' => '', 'jp' => '',
            'bentuk' => '', 'kategori' => '', 'jenis' => '', 'penyelenggara' => '', 'nama_penyelenggara' => '', 'tautan_sertifikat' => ''], []);
    }

    public function ubah($id = 0)
    {
        $b = $this->bangkom->ambil($id);
        if (!$b) {
            show_404();
        }
        $this->form_data($b, []);
    }

    public function simpan()
    {
        $this->wajib_post();
        $id = (int) $this->input->post('id');
        $b = ['id' => $id, 'pegawai_id' => (int) $this->input->post('pegawai_id')];
        foreach (['kegiatan', 'mulai', 'selesai', 'jp', 'bentuk', 'kategori', 'jenis', 'penyelenggara', 'nama_penyelenggara', 'tautan_sertifikat'] as $k) {
            $b[$k] = trim((string) $this->input->post($k));
        }
        $errors = [];
        $p = $this->pegawai->ambil($b['pegawai_id']);
        if (!$p) {
            $errors[] = 'Pilih pegawai.';
        }
        if ($b['kegiatan'] === '') {
            $errors[] = 'Nama kegiatan wajib diisi.';
        }
        $valid = function ($t) { $d = DateTime::createFromFormat('Y-m-d', $t); return $d && $d->format('Y-m-d') === $t; };
        if (!$valid($b['mulai'])) {
            $errors[] = 'Tanggal mulai wajib diisi.';
        }
        if ($b['selesai'] === '') {
            $b['selesai'] = $b['mulai'];
        } elseif (!$valid($b['selesai'])) {
            $errors[] = 'Tanggal selesai tidak valid.';
        } elseif ($valid($b['mulai']) && $b['selesai'] < $b['mulai']) {
            $errors[] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
        }
        if ($b['jp'] === '' || !ctype_digit($b['jp']) || (int) $b['jp'] > 5000) {
            $errors[] = 'Jam pelajaran (JP) harus berupa bilangan bulat, misalnya 8.';
        }
        if (!in_array($b['penyelenggara'], ['', 'BPS', 'Non BPS'], true)) {
            $errors[] = 'Penyelenggara tidak valid.';
        }
        if ($b['tautan_sertifikat'] !== '' && !preg_match('#^https?://#i', $b['tautan_sertifikat'])) {
            $errors[] = 'Tautan sertifikat harus diawali http:// atau https://.';
        }
        if ($errors) {
            $this->form_data($b, $errors);
            return;
        }
        $data = [
            'pegawai_id' => (int) $p['id'], 'nama_pegawai' => $p['nama'], 'kegiatan' => mb_substr($b['kegiatan'], 0, 400),
            'mulai' => $b['mulai'], 'selesai' => $b['selesai'], 'jp' => (int) $b['jp'],
            'bentuk' => $b['bentuk'] !== '' ? mb_substr($b['bentuk'], 0, 60) : null,
            'kategori' => $b['kategori'] !== '' ? mb_substr($b['kategori'], 0, 100) : null,
            'jenis' => $b['jenis'] !== '' ? mb_substr($b['jenis'], 0, 80) : null,
            'penyelenggara' => $b['penyelenggara'] !== '' ? $b['penyelenggara'] : null,
            'nama_penyelenggara' => $b['nama_penyelenggara'] !== '' ? mb_substr($b['nama_penyelenggara'], 0, 150) : null,
            'tautan_sertifikat' => $b['tautan_sertifikat'] !== '' ? mb_substr($b['tautan_sertifikat'], 0, 500) : null,
        ];
        if (!$id) {
            $data['dibuat_oleh'] = $this->user['id'];
        }
        $this->bangkom->simpan($id, $data);
        $this->catat($id ? 'ubah_bangkom' : 'tambah_bangkom', $p['nama'] . ' - ' . $data['kegiatan']);
        flash_set('ok', 'Catatan Bangkom untuk ' . $p['nama'] . ' disimpan (' . (int) $data['jp'] . ' JP).');
        redirect('bangkom?tahun=' . substr($data['mulai'], 0, 4));
    }

    public function hapus($id = 0)
    {
        $this->wajib_post();
        $b = $this->bangkom->ambil($id);
        if (!$b) {
            show_404();
        }
        $this->bangkom->hapus($b['id']);
        $this->catat('hapus_bangkom', $b['nama_pegawai'] . ' - ' . $b['kegiatan']);
        flash_set('ok', 'Catatan Bangkom ' . $b['nama_pegawai'] . ' dihapus.');
        redirect('bangkom?tahun=' . substr($b['mulai'], 0, 4));
    }

    public function rekap()
    {
        $tahun = $this->tahun();
        $this->render('bangkom/rekap', ['judul' => 'Rekap JP Bangkom', 'tahun' => $tahun, 'rows' => $this->bangkom->rekap($tahun), 'target' => $this->nilai_target()]);
    }

    public function target()
    {
        $this->wajib_admin();
        $this->wajib_post();
        $jp = trim((string) $this->input->post('target'));
        if ($jp === '' || !ctype_digit($jp) || (int) $jp > 1000) {
            flash_set('bad', 'Target JP harus berupa bilangan bulat, misalnya 20.');
        } else {
            $this->pengaturan->set('bangkom_target_jp', (string) (int) $jp);
            $this->catat('target_bangkom', $jp);
            flash_set('ok', 'Target JP per tahun diubah menjadi ' . (int) $jp . '.');
        }
        redirect('bangkom/rekap?tahun=' . (int) $this->input->post('tahun'));
    }

    // ---- Impor dari sheet 6100 (admin) -------------------------------------

    public function impor()
    {
        $this->wajib_admin();
        if ($this->input->method() !== 'post') {
            $this->render('bangkom/impor', ['judul' => 'Impor Bangkom', 'hasil' => null, 'errors' => []]);
            return;
        }
        $this->wajib_post();
        $tahun = (int) $this->input->post('tahun');
        $salah = validasi_csv(isset($_FILES['berkas']) ? $_FILES['berkas'] : []);
        if (!$salah && ($tahun < 2000 || $tahun > 2100)) {
            $salah = 'Isi tahun sheet yang diimpor.';
        }
        $hasil = $salah ? ['galat' => $salah] : $this->proses_impor($_FILES['berkas']['tmp_name'], $tahun);
        if (isset($hasil['galat'])) {
            $this->render('bangkom/impor', ['judul' => 'Impor Bangkom', 'hasil' => null, 'errors' => [$hasil['galat']]]);
            return;
        }
        $this->catat('impor_bangkom', $hasil['dibuat'] . ' catatan, ' . $hasil['kembar'] . ' kembar');
        $this->render('bangkom/impor', ['judul' => 'Impor Bangkom', 'hasil' => $hasil, 'errors' => []]);
    }

    private function peta_kolom(array $baris)
    {
        $peta = [];
        foreach ($baris as $i => $h) {
            $h = strtolower(trim(preg_replace('/\s+/', ' ', (string) $h)));
            if ($h === '') {
                continue;
            }
            if (strpos($h, 'nama pengembangan') !== false) {
                $peta['kegiatan'] = $i;
            } elseif (strpos($h, 'mulai') !== false) {
                $peta['mulai'] = $i;
            } elseif (strpos($h, 'selesai') !== false) {
                $peta['selesai'] = $i;
            } elseif (strpos($h, 'jam pelajaran') !== false || $h === 'jp') {
                $peta['jp'] = $i;
            } elseif (strpos($h, 'bentuk') !== false) {
                $peta['bentuk'] = $i;
            } elseif (strpos($h, 'kategori') !== false) {
                $peta['kategori'] = $i;
            } elseif (strpos($h, 'jenis') !== false) {
                $peta['jenis'] = $i;
            } elseif (strpos($h, 'nama penyelenggara') !== false) {
                $peta['nama_penyelenggara'] = $i;
            } elseif (strpos($h, 'penyelenggara') !== false) {
                $peta['penyelenggara'] = $i;
            } elseif (strpos($h, 'tautan') !== false || strpos($h, 'sertifikat') !== false) {
                $peta['tautan'] = $i;
            } elseif ($h === 'nama') {
                $peta['nama'] = $i;
            }
        }
        return $peta;
    }

    private function proses_impor($path, $tahun)
    {
        $fh = fopen($path, 'r');
        if (!$fh) {
            return ['galat' => 'Berkas tidak dapat dibuka.'];
        }
        $peta = [];
        $baris = [];
        while (($r = fgetcsv($fh)) !== false) {
            if (!$peta) {
                $kandidat = $this->peta_kolom($r);
                if (isset($kandidat['nama'], $kandidat['kegiatan'], $kandidat['mulai'])) {
                    $peta = $kandidat;
                }
                continue;
            }
            $baris[] = $r;
        }
        fclose($fh);
        if (!$peta) {
            return ['galat' => 'Baris judul tidak ditemukan. Pastikan yang diunduh adalah sheet "6100" yang memuat kolom Nama, Nama Pengembangan Kompetensi, dan Tanggal Mulai.'];
        }
        $ambil = function ($r, $k) use ($peta) {
            return isset($peta[$k], $r[$peta[$k]]) ? trim((string) $r[$peta[$k]]) : '';
        };
        $cocok = [];
        foreach ($this->pegawai->semua_aktif() as $p) {
            $cocok[nama_kunci($p['nama'])] = $p;
        }
        $dibuat = $kembar = 0;
        $tanpaPegawai = [];
        $tanggalRusak = 0;
        $tanpaKegiatan = 0;
        foreach ($baris as $r) {
            $nama = $ambil($r, 'nama');
            $kegiatan = trim(preg_replace('/\s+/', ' ', $ambil($r, 'kegiatan')));
            if ($nama === '' && $kegiatan === '') {
                continue;
            }
            if ($kegiatan === '') {
                $tanpaKegiatan++;
                continue;
            }
            $mulai = tanggal_fleksibel($ambil($r, 'mulai'), $tahun);
            if ($mulai === null) {
                $tanggalRusak++;
                continue;
            }
            $selesai = tanggal_fleksibel($ambil($r, 'selesai'), $tahun);
            if ($selesai === null || $selesai < $mulai) {
                $selesai = $mulai;
            }
            $kunci = nama_kunci($nama);
            if (!isset($cocok[$kunci])) {
                $tanpaPegawai[$nama] = true;
                continue;
            }
            $p = $cocok[$kunci];
            if ($this->bangkom->ada($p['id'], $kegiatan, $mulai)) {
                $kembar++;
                continue;
            }
            $pen = $ambil($r, 'penyelenggara');
            $pen = strcasecmp($pen, 'BPS') === 0 ? 'BPS' : (stripos($pen, 'non') === 0 ? 'Non BPS' : null);
            $tautan = $ambil($r, 'tautan');
            $jp = preg_replace('/\D+/', '', $ambil($r, 'jp'));
            $this->bangkom->simpan(0, [
                'pegawai_id' => (int) $p['id'], 'nama_pegawai' => $p['nama'], 'kegiatan' => mb_substr($kegiatan, 0, 400),
                'mulai' => $mulai, 'selesai' => $selesai, 'jp' => $jp === '' ? 0 : min(5000, (int) $jp),
                'bentuk' => $ambil($r, 'bentuk') ?: null, 'kategori' => $ambil($r, 'kategori') ?: null, 'jenis' => $ambil($r, 'jenis') ?: null,
                'penyelenggara' => $pen, 'nama_penyelenggara' => $ambil($r, 'nama_penyelenggara') ?: null,
                'tautan_sertifikat' => preg_match('#^https?://#i', $tautan) ? mb_substr($tautan, 0, 500) : null,
                'sumber' => 'impor', 'dibuat_oleh' => $this->user['id'],
            ]);
            $dibuat++;
        }
        return ['dibuat' => $dibuat, 'kembar' => $kembar, 'tanpa_pegawai' => array_keys($tanpaPegawai),
            'tanggal_rusak' => $tanggalRusak, 'tanpa_kegiatan' => $tanpaKegiatan];
    }
}
