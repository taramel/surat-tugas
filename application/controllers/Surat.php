<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Surat extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Surat_model', 'surat');
        $this->load->model('Pegawai_model', 'pegawai');
        $this->load->model('Master_model', 'master');
        $this->load->model('Nomor_model', 'nomor');
        $this->load->model('Pengaturan_model', 'pengaturan');
        $this->load->model('Template_model', 'templat');
        $this->load->library('docx_isi');
    }

    public function index()
    {
        $tahun = $this->input->get('tahun');
        $f = [
            'tahun' => $tahun === null ? date('Y') : $tahun,
            'bulan' => $this->input->get('bulan'),
            'status' => $this->input->get('status'),
            'q' => trim((string) $this->input->get('q')),
            'flag' => $this->input->get('flag') === 'terlambat' ? 'terlambat' : '',
        ];
        $per = 20;
        $hal = max(1, (int) $this->input->get('hal'));
        $total = $this->surat->hitung($f);

        $this->render('surat/index', [
            'judul' => 'Surat tugas', 'f' => $f, 'hal' => $hal, 'per' => $per, 'total' => $total,
            'rows' => $this->surat->daftar($f, $per, ($hal - 1) * $per),
            'tahun_opsi' => $this->nomor->tahun_tersedia(),
        ]);
    }

    public function tambah()
    {
        $aktif = $this->master->penandatangan_aktif();
        $surat = [
            'tanggal_surat' => date('Y-m-d'), 'tempat' => $this->pengaturan->get('tempat_default', 'Pontianak'),
            'asal' => 'eksternal', 'unit_pemohon' => '', 'rujukan_pengirim' => '', 'rujukan_nomor' => '',
            'rujukan_hal' => '', 'untuk' => $this->awalan_untuk(), 'catatan' => '',
            'penandatangan_id' => $aktif ? $aktif[0]['id'] : '',
            'nomor_pilih' => (int) $this->input->get('nomor') ?: '',
        ];
        $this->form('tambah', $surat, [], [['mulai' => '', 'selesai' => '']], []);
    }

    public function simpan()
    {
        $this->wajib_post();
        $this->proses(null);
    }

    public function ubah($id = 0)
    {
        $s = $this->ambil_atau_404($id);
        $this->tolak_arsip($s, 'diubah');
        if ($s['status'] === 'dibatalkan') {
            flash_set('warn', 'Surat yang dibatalkan tidak dapat diubah. Pulihkan dahulu bila perlu.');
            redirect('surat/detail/' . $s['id']);
        }
        $penerima = [];
        foreach ($s['penerima'] as $p) {
            $penerima[] = $p;
        }
        $this->form('ubah', $s, $penerima, $s['tanggal'], []);
    }

    public function perbarui($id = 0)
    {
        $this->wajib_post();
        $s = $this->ambil_atau_404($id);
        $this->tolak_arsip($s, 'diubah');
        $this->proses($s);
    }

    public function detail($id = 0)
    {
        $s = $this->ambil_atau_404($id);
        $this->load->model('Arsip_model', 'arsip');
        $nid = $this->arsip->nomor_id_surat($s['id']);
        $this->render('surat/detail', ['judul' => $s['nomor_surat'], 's' => $s,
            'arsip_nomor_id' => $nid, 'arsip' => $this->arsip->daftar($nid), 'kembali' => 'surat/detail/' . (int) $s['id']]);
    }

    /** Berkas Word (.docx) surat tugas yang dapat diedit. */
    public function word($id = 0)
    {
        $s = $this->ambil_atau_404($id);
        $this->tolak_arsip($s, 'diunduh sebagai Word');
        if ($s['status'] === 'dibatalkan') {
            flash_set('warn', 'Surat yang dibatalkan tidak dapat diunduh sebagai Word.');
            redirect('surat/detail/' . $s['id']);
        }
        list($isi, $galat) = $this->susun_word($s);
        if ($galat !== null) {
            flash_set('bad', $galat);
            redirect('surat/detail/' . $s['id']);
        }
        $this->catat('unduh_word_surat', $s['nomor_surat']);
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . nama_berkas_nomor($s['nomor_surat']) . '.docx"');
        header('Content-Length: ' . strlen($isi));
        echo $isi;
        exit;
    }

    /** Beberapa surat aktif sekaligus dalam satu berkas ZIP berisi file Word: surat/word_banyak?ids=1,2,3 */
    public function word_banyak()
    {
        if (!class_exists('ZipArchive')) {
            flash_set('bad', 'Ekstensi PHP zip belum aktif di server, sehingga berkas ZIP tidak dapat dibuat.');
            redirect('surat');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'zipw');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $jumlah = 0;
        $gagal = [];
        foreach ($this->ids_dari_query() as $id) {
            $s = $this->surat->ambil($id);
            if (!$s || $s['status'] !== 'aktif' || $s['tidak_lengkap']) {
                continue;
            }
            list($isi, $galat) = $this->susun_word($s);
            if ($galat !== null) {
                $gagal[] = $s['nomor_surat'] . ': ' . $galat;
                continue;
            }
            $zip->addFromString(nama_berkas_nomor($s['nomor_surat']) . '.docx', $isi);
            $jumlah++;
        }
        $zip->close();
        if ($jumlah === 0) {
            @unlink($tmp);
            flash_set('bad', $gagal ? implode(' ', $gagal) : 'Tidak ada surat aktif yang dapat dibuatkan berkas Word.');
            redirect('surat');
        }
        $this->catat('unduh_word_banyak', $jumlah . ' surat');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="surat_tugas_' . date('Ymd_His') . '.zip"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    public function pdf($id = 0)
    {
        $s = $this->ambil_atau_404($id);
        $this->tolak_arsip($s, 'dicetak ulang');
        $this->cetak([$s], nama_berkas_nomor($s['nomor_surat']) . '.pdf', $this->input->get('unduh') === '1');
    }

    /** Beberapa surat aktif dalam satu berkas PDF: surat/pdf_banyak?ids=1,2,3 */
    public function pdf_banyak()
    {
        $daftar = [];
        foreach ($this->ids_dari_query() as $id) {
            $s = $this->surat->ambil($id);
            if ($s && $s['status'] === 'aktif' && !$s['tidak_lengkap']) {
                $daftar[] = $s;
            }
        }
        if (!$daftar) {
            show_404();
        }
        $this->cetak($daftar, 'surat_tugas_' . date('Ymd_His') . '.pdf', $this->input->get('unduh') === '1');
    }

    public function hasil()
    {
        $ids = $this->ids_dari_query();
        $rows = $this->surat->ringkas($ids);
        if (!$rows) {
            show_404();
        }
        $this->render('surat/hasil', ['judul' => 'Surat yang diterbitkan', 'rows' => $rows, 'ids' => implode(',', array_column($rows, 'id'))]);
    }

    public function salin($id = 0)
    {
        $s = $this->ambil_atau_404($id);
        $aktif = $this->master->penandatangan_aktif();
        $surat = [
            'tanggal_surat' => date('Y-m-d'), 'tempat' => $s['tempat'], 'asal' => $s['asal'],
            'unit_pemohon' => $s['unit_pemohon'], 'rujukan_pengirim' => $s['rujukan_pengirim'],
            'rujukan_nomor' => $s['rujukan_nomor'], 'rujukan_hal' => $s['rujukan_hal'], 'menimbang_a' => $s['menimbang_a'],
            'untuk' => $s['untuk'], 'catatan' => '',
            'penandatangan_id' => $aktif ? $aktif[0]['id'] : $s['penandatangan_id'], 'nomor_pilih' => '',
        ];
        $penerima = array_values(array_filter($s['penerima'], function ($p) { return $p['pegawai_id'] !== null; }));
        flash_set('warn', 'Ini salinan dari ' . $s['nomor_surat'] . '. Isi tanggal pelaksanaan dan periksa kembali sebelum diterbitkan.'
            . (count($penerima) < count($s['penerima']) ? ' Daftar penerima surat arsip tidak lengkap, jadi tambahkan pegawainya kembali.' : ''));
        $this->form('tambah', $surat, $penerima, [['mulai' => '', 'selesai' => '']], []);
    }

    public function batal($id = 0)
    {
        $this->wajib_post();
        $s = $this->ambil_atau_404($id);
        $alasan = trim((string) $this->input->post('alasan'));
        if ($alasan === '') {
            flash_set('bad', 'Alasan pembatalan wajib diisi.');
        } elseif ($this->surat->batal($s['id'], $alasan)) {
            $this->catat('batal_surat', $s['nomor_surat'] . ': ' . $alasan);
            flash_set('ok', 'Surat ' . $s['nomor_surat'] . ' dibatalkan. Nomornya tetap tercatat dan tidak dipakai ulang.');
        } else {
            flash_set('bad', 'Pembatalan gagal disimpan.');
        }
        redirect('surat/detail/' . $s['id']);
    }

    public function pulihkan($id = 0)
    {
        $this->wajib_post();
        $this->wajib_admin();
        $s = $this->ambil_atau_404($id);
        if ($this->surat->pulihkan($s['id'])) {
            $this->catat('pulihkan_surat', $s['nomor_surat']);
            flash_set('ok', 'Surat ' . $s['nomor_surat'] . ' dipulihkan.');
        }
        redirect('surat/detail/' . $s['id']);
    }

    // ------------------------------------------------------------------

    /** Surat arsip hasil impor tanpa daftar penerima lengkap hanya dapat dilihat dan dibatalkan. */
    private function tolak_arsip(array $s, $aksi)
    {
        if ($s['tidak_lengkap']) {
            flash_set('warn', 'Surat arsip hasil impor tidak dapat ' . $aksi . ' karena daftar penerimanya tidak lengkap. Gunakan dokumen aslinya.');
            redirect('surat/detail/' . $s['id']);
        }
    }

    private function awalan_untuk()
    {
        $a = trim($this->pengaturan->get('awalan_untuk', 'Mengikuti Kegiatan'));
        return $a === '' ? '' : $a . ' ';
    }

    private function ids_dari_query()
    {
        $ids = [];
        foreach (explode(',', (string) $this->input->get('ids')) as $i) {
            $i = (int) $i;
            if ($i > 0) {
                $ids[$i] = $i;
            }
        }
        return array_slice(array_values($ids), 0, 100);
    }

    private function cetak(array $daftar, $namaBerkas, $unduh)
    {
        $logo = null;
        $berkas = FCPATH . 'assets/img/logo.png';
        if (is_file($berkas)) {
            $logo = 'data:image/png;base64,' . base64_encode(file_get_contents($berkas));
        }
        foreach ($daftar as &$d) {
            $d['mengingat_teks'] = $this->master->dasar_hukum_untuk($d['tanggal_surat']);
        }
        unset($d);
        $html = $this->load->view('surat/pdf', ['daftar' => $daftar, 'set' => $this->pengaturan->semua(), 'logo' => $logo], true);
        foreach ($daftar as $d) {
            $this->catat('cetak_pdf', $d['nomor_surat']);
        }
        $this->load->library('pdf');
        $this->pdf->tampilkan($html, $namaBerkas, $unduh);
    }

    /**
     * Membuat berkas Word dari template. Jenis surat (narasumber, perjalanan dinas, dan seterusnya)
     * dikenali dari awalan kalimat "Untuk"; bentuk surat (satu atau banyak penerima) dari jumlah pegawai.
     * Mengembalikan [isi berkas, null] atau [null, pesan kesalahan].
     */
    private function susun_word(array $s)
    {
        if (!$this->docx_isi->tersedia()) {
            return [null, 'Ekstensi PHP zip dan dom belum aktif di server, sehingga berkas Word tidak dapat dibuat.'];
        }
        $penerima = array_values($s['penerima']);
        if (!$penerima) {
            return [null, 'Surat ini tidak memiliki penerima.'];
        }
        $banyak = count($penerima) > 1;

        $untuk = rtrim(trim(preg_replace('/\s+/', ' ', (string) $s['untuk'])), '. ');
        $jenis = 'umum';
        $uraian = $untuk;
        $dikenali = false;
        foreach (daftar_jenis_template() as $slug => $j) {
            $awalan = rtrim($j['awalan']);
            if ($slug !== 'internal' && strncasecmp($untuk, $awalan, strlen($awalan)) === 0) {
                $jenis = $slug;
                $uraian = ltrim(substr($untuk, strlen($awalan)));
                $dikenali = true;
                break;
            }
        }

        $jenisDipakai = null;
        $t = null;
        foreach (array_unique([$jenis, 'umum']) as $kandidat) {
            $cari = $this->templat->untuk(nama_template_surat($kandidat, $banyak), $s['tanggal_surat']);
            if ($cari && $this->templat->berkas_ada($cari)) {
                $t = $cari;
                $jenisDipakai = $kandidat;
                break;
            }
        }
        if (!$t) {
            return [null, 'Template Word surat tugas tidak ditemukan. Jalankan menu Pembaruan atau hubungi admin.'];
        }

        // Template umum selalu diawali "Mengikuti Kegiatan". Bila kalimat "Untuk" tidak cocok dengan template
        // yang dipakai, kalimat ditulis apa adanya dan awalan bawaan template dikosongkan.
        $ganti = [];
        if (!$dikenali || $jenisDipakai !== $jenis) {
            $uraian = $untuk;
            $ganti[daftar_jenis_template()[$jenisDipakai]['awalan']] = '';
        }
        if (!$banyak && empty($penerima[0]['peran'])) {
            $ganti[' sebagai '] = '';
        }

        $p0 = $penerima[0];
        $nilai = [
            'NOMOR' => $s['nomor_surat'], 'TANGGAL' => tgl_indo($s['tanggal_surat']),
            'TEMPAT' => !empty($s['tempat']) ? $s['tempat'] : 'Pontianak',
            'TTD_JABATAN' => !empty($s['ttd_jabatan']) ? $s['ttd_jabatan'] : 'Kepala Badan Pusat Statistik Provinsi Kalimantan Barat',
            'TTD_NAMA' => !empty($s['ttd_nama']) ? $s['ttd_nama'] : 'Muh Saichudin',
            'TTD_NIP' => !empty($s['ttd_nip']) ? (string) $s['ttd_nip'] : '',
            'URAIAN' => $uraian, 'TAHUN' => $s['tahun'], 'PEMBUAT' => (string) $s['pembuat'],
            'MENIMBANG' => $this->menimbang_word($s),
            'TANGGAL_PELAKSANAAN' => $s['rentang'],
            'NAMA' => (string) $p0['nama'], 'NIP' => (string) $p0['nip'], 'PANGKAT' => (string) $p0['pangkat_golongan'],
            'JABATAN' => (string) $p0['jabatan'], 'PERAN' => (string) $p0['peran'],
        ];
        $baris = [];
        if ($banyak) {
            foreach ($penerima as $i => $r) {
                $baris[] = [
                    'NO' => (string) ($i + 1), 'NAMA' => (string) $r['nama'], 'NIP' => (string) $r['nip'],
                    'PANGKAT' => (string) $r['pangkat_golongan'], 'JABATAN' => (string) $r['jabatan'], 'PERAN' => (string) $r['peran'],
                ];
            }
        }
        try {
            return [$this->docx_isi->isi(Template_model::folder() . $t['berkas'], $nilai, $baris, $ganti), null];
        } catch (Throwable $e) {
            return [null, 'Berkas Word gagal dibuat: ' . $e->getMessage()];
        }
    }

    /** Butir pertama "Menimbang" (surat rujukan), disusun seperti pada PDF. Kosong bila tidak ada surat rujukan. */
    private function menimbang_word(array $s)
    {
        if (!empty($s['menimbang_a'])) {
            return $s['menimbang_a'];
        }
        $tpl = trim((string) $this->pengaturan->get('menimbang_a', ''));
        $adaRujukan = $s['rujukan_pengirim'] || $s['rujukan_nomor'] || $s['rujukan_hal'];
        if (!$adaRujukan || $tpl === '') {
            return '';
        }
        foreach (['pengirim' => ['dari', 'rujukan_pengirim'], 'nomor' => ['nomor', 'rujukan_nomor'], 'hal' => ['hal', 'rujukan_hal']] as $kunci => $d) {
            if (!$s[$d[1]]) {
                $tpl = preg_replace('/\s*' . $d[0] . '\s+\{' . $kunci . '\}/u', '', $tpl);
            }
        }
        $teks = strtr($tpl, ['{pengirim}' => $s['rujukan_pengirim'], '{nomor}' => $s['rujukan_nomor'], '{hal}' => $s['rujukan_hal']]);
        return rtrim($teks, '; ');
    }

    private function ambil_atau_404($id)
    {
        $s = $this->surat->ambil((int) $id);
        if (!$s) {
            show_404();
        }
        return $s;
    }

    private function form($mode, array $surat, array $penerima, array $tanggal, array $errors)
    {
        $this->render('surat/form', [
            'judul' => $mode === 'tambah' ? 'Surat tugas baru' : 'Ubah ' . $surat['nomor_surat'],
            'mode' => $mode, 's' => $surat, 'penerima' => $penerima, 'tanggal' => $tanggal, 'errors' => $errors,
            'peran' => $this->master->peran(),
            'ttd' => $this->master->penandatangan(),
            'bebas' => $this->nomor->bebas((int) date('Y')),
        ]);
    }

    private function tanggal_valid($t)
    {
        $d = DateTime::createFromFormat('Y-m-d', (string) $t);
        return $d && $d->format('Y-m-d') === $t;
    }

    private function proses($lama)
    {
        $in = $this->input->post(null, false);
        $errors = [];

        $tglSurat = trim(isset($in['tanggal_surat']) ? $in['tanggal_surat'] : '');
        if (!$this->tanggal_valid($tglSurat)) {
            $errors[] = 'Tanggal surat belum diisi dengan benar.';
        }
        $untuk = trim(isset($in['untuk']) ? $in['untuk'] : '');
        if ($untuk === '' || strcasecmp($untuk, trim($this->awalan_untuk())) === 0) {
            $errors[] = 'Isi penugasan ("Untuk") wajib diisi dengan nama kegiatan.';
        }
        $asal = (isset($in['asal']) && $in['asal'] === 'internal') ? 'internal' : 'eksternal';

        $ttdId = (int) (isset($in['penandatangan_id']) ? $in['penandatangan_id'] : 0);
        $ttdRow = $this->db->get_where('penandatangan', ['id' => $ttdId])->row_array();
        if (!$ttdRow) {
            $errors[] = 'Penandatangan belum dipilih.';
        } elseif ($this->tanggal_valid($tglSurat)
            && ($tglSurat < $ttdRow['aktif_dari'] || ($ttdRow['aktif_sampai'] && $tglSurat > $ttdRow['aktif_sampai']))) {
            $errors[] = $ttdRow['nama'] . ' tidak berlaku sebagai penandatangan pada ' . tgl_indo($tglSurat)
                . ' (berlaku ' . tgl_indo($ttdRow['aktif_dari']) . ' sampai ' . ($ttdRow['aktif_sampai'] ? tgl_indo($ttdRow['aktif_sampai']) : 'sekarang') . ').';
        }

        // Penerima
        $ids = isset($in['pegawai_id']) ? (array) $in['pegawai_id'] : [];
        $perans = isset($in['peran_id']) ? (array) $in['peran_id'] : [];
        $penerima = [];
        $dipakai = [];
        foreach ($ids as $i => $pid) {
            $pid = (int) $pid;
            if ($pid > 0 && !isset($dipakai[$pid])) {
                $dipakai[$pid] = true;
                $penerima[] = ['pegawai_id' => $pid, 'peran_id' => (int) (isset($perans[$i]) ? $perans[$i] : 0)];
            }
        }
        $ada = $this->pegawai->ambil_banyak(array_column($penerima, 'pegawai_id'));
        $penerima = array_values(array_filter($penerima, function ($p) use ($ada) {
            return isset($ada[$p['pegawai_id']]);
        }));
        if (!$penerima) {
            $errors[] = 'Tambahkan minimal satu pegawai yang ditugaskan.';
        }

        // Tanggal pelaksanaan
        $tanggal = [];
        $mulaiList = isset($in['mulai']) ? (array) $in['mulai'] : [];
        $selesaiList = isset($in['selesai']) ? (array) $in['selesai'] : [];
        foreach ($mulaiList as $i => $m) {
            $m = trim($m);
            $s = trim(isset($selesaiList[$i]) ? $selesaiList[$i] : '');
            if ($m === '' && $s === '') {
                continue;
            }
            $s = $s === '' ? $m : $s;
            if (!$this->tanggal_valid($m) || !$this->tanggal_valid($s)) {
                $errors[] = 'Tanggal pelaksanaan baris ' . ($i + 1) . ' tidak valid.';
            } elseif ($s < $m) {
                $errors[] = 'Tanggal selesai baris ' . ($i + 1) . ' lebih awal dari tanggal mulai.';
            } else {
                $tanggal[] = ['mulai' => $m, 'selesai' => $s];
            }
        }
        if (!$tanggal && !array_filter($errors, function ($e) { return strpos($e, 'pelaksanaan') !== false; })) {
            $errors[] = 'Tanggal pelaksanaan wajib diisi.';
        }
        // Urutkan dari yang paling awal dan buang tanggal kembar
        usort($tanggal, function ($a, $b) {
            return strcmp($a['mulai'], $b['mulai']) ?: strcmp($a['selesai'], $b['selesai']);
        });
        $tanggal = array_values(array_unique($tanggal, SORT_REGULAR));

        $tahun = $this->tanggal_valid($tglSurat) ? (int) substr($tglSurat, 0, 4) : (int) date('Y');
        if ($lama && $this->tanggal_valid($tglSurat) && $tahun !== (int) $lama['tahun']) {
            $errors[] = 'Tahun tanggal surat harus ' . $lama['tahun'] . ', sesuai tahun nomor surat.';
        }
        $pilih = (int) (isset($in['nomor_pilih']) ? $in['nomor_pilih'] : 0);

        $data = [
            'tahun' => $tahun, 'tanggal_surat' => $tglSurat,
            'tempat' => trim(isset($in['tempat']) ? $in['tempat'] : '') ?: 'Pontianak',
            'asal' => $asal,
            'unit_pemohon' => trim(isset($in['unit_pemohon']) ? $in['unit_pemohon'] : '') ?: null,
            'rujukan_pengirim' => trim(isset($in['rujukan_pengirim']) ? $in['rujukan_pengirim'] : '') ?: null,
            'rujukan_nomor' => trim(isset($in['rujukan_nomor']) ? $in['rujukan_nomor'] : '') ?: null,
            'rujukan_hal' => trim(isset($in['rujukan_hal']) ? $in['rujukan_hal'] : '') ?: null,
            'menimbang_a' => trim(isset($in['menimbang_a']) ? $in['menimbang_a'] : '') ?: null,
            'untuk' => $untuk, 'penandatangan_id' => $ttdId,
            'catatan' => trim(isset($in['catatan']) ? $in['catatan'] : '') ?: null,
        ];

        if ($errors) {
            $formS = $data + ['nomor_pilih' => $pilih ?: '', 'penandatangan_id' => $ttdId];
            if ($lama) {
                $formS = $formS + $lama;
            }
            $tampil = [];
            foreach ($penerima as $p) {
                $r = $ada[$p['pegawai_id']];
                $r['pegawai_id'] = $r['id'];
                $r['peran_id'] = $p['peran_id'];
                $tampil[] = $r;
            }
            $this->form($lama ? 'ubah' : 'tambah', $formS, $tampil, $tanggal ?: [['mulai' => '', 'selesai' => '']], $errors);
            return;
        }

        $peringatan = '';
        if ($tglSurat > $tanggal[0]['mulai']) {
            $peringatan = ' Perhatian: tanggal surat lebih akhir daripada tanggal pelaksanaan pertama.';
        }

        if ($lama) {
            if ($this->surat->perbarui($lama['id'], $data, $penerima, $tanggal)) {
                $this->catat('ubah_surat', $lama['nomor_surat']);
                flash_set('ok', 'Perubahan surat ' . $lama['nomor_surat'] . ' disimpan.' . $peringatan);
                redirect('surat/detail/' . $lama['id']);
            }
            flash_set('bad', 'Perubahan gagal disimpan.');
            redirect('surat/ubah/' . $lama['id']);
        }

        $data['dibuat_oleh'] = $this->user['id'];

        if (!empty($in['massal']) && count($penerima) >= 2) {
            list($ok, $hasil) = $this->surat->simpan_massal($data, $penerima, $tanggal);
            if (!$ok) {
                $tampil = [];
                foreach ($penerima as $p) {
                    $r = $ada[$p['pegawai_id']];
                    $r['pegawai_id'] = $r['id'];
                    $r['peran_id'] = $p['peran_id'];
                    $tampil[] = $r;
                }
                $this->form('tambah', $data + ['nomor_pilih' => '', 'massal' => 1], $tampil, $tanggal, [$hasil]);
                return;
            }
            $this->catat('buat_surat_massal', count($hasil) . ' surat');
            flash_set('ok', count($hasil) . ' surat tugas diterbitkan, satu untuk setiap pegawai. Unduh berkas Word-nya di bawah.' . $peringatan);
            redirect('surat/hasil?ids=' . implode(',', $hasil));
        }

        list($ok, $hasil) = $this->surat->simpan($data, $penerima, $tanggal, $pilih);
        if (!$ok) {
            $formS = $data + ['nomor_pilih' => $pilih ?: ''];
            $tampil = [];
            foreach ($penerima as $p) {
                $r = $ada[$p['pegawai_id']];
                $r['pegawai_id'] = $r['id'];
                $r['peran_id'] = $p['peran_id'];
                $tampil[] = $r;
            }
            $this->form('tambah', $formS, $tampil, $tanggal, [$hasil]);
            return;
        }
        $baru = $this->surat->ambil($hasil);
        $this->catat('buat_surat', $baru['nomor_surat']);
        flash_set('ok', 'Surat tugas ' . $baru['nomor_surat'] . ' diterbitkan. Unduh berkas Word di bawah untuk disunting, atau buka PDF untuk dicetak.' . $peringatan);
        redirect('surat/detail/' . $hasil);
    }
}
