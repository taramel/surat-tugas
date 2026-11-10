<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Register extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Nomor_model', 'nomor');
        $this->load->model('Pengaturan_model', 'pengaturan');
    }

    private function filter()
    {
        $tahun = $this->input->get('tahun');
        return [
            'tahun' => $tahun === null ? date('Y') : $tahun,
            'bulan' => $this->input->get('bulan'),
            'status' => $this->input->get('status'),
            'q' => trim((string) $this->input->get('q')),
        ];
    }

    public function index()
    {
        $f = $this->filter();
        $per = 25;
        $hal = max(1, (int) $this->input->get('hal'));
        $tahunStat = (int) ($f['tahun'] ?: date('Y'));
        $this->render('register/index', [
            'judul' => 'Register nomor', 'f' => $f, 'hal' => $hal, 'per' => $per,
            'total' => $this->nomor->hitung($f), 'rows' => $this->nomor->daftar($f, $per, ($hal - 1) * $per),
            'tahun_opsi' => $this->nomor->tahun_tersedia(), 'tahun_stat' => $tahunStat,
            'terakhir' => $this->nomor->maks($tahunStat), 'kosong' => count($this->nomor->bebas($tahunStat)),
        ]);
    }

    public function kotak()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $register = $this->nomor->register($tahun);
        $this->render('register/kotak', [
            'judul' => 'Peta nomor', 'tahun' => $tahun, 'register' => $register,
            'maks' => $register ? max(array_keys($register)) : 0, 'bebas' => $this->nomor->bebas($tahun),
            'tahun_opsi' => $this->nomor->tahun_tersedia(),
            'format' => $this->pengaturan->get('format_nomor'), 'satker' => $this->pengaturan->get('kode_satker'),
            'klas' => $this->pengaturan->get('kode_klasifikasi'),
        ]);
    }

    private function ambil_atau_404($id)
    {
        $b = $this->nomor->baris($id);
        if (!$b || $b['status'] === 'tersedia') {
            show_404();
        }
        return $b;
    }

    public function ubah($id = 0)
    {
        $b = $this->ambil_atau_404($id);
        if ($b['status'] === 'dibatalkan') {
            flash_set('warn', 'Nomor yang dibatalkan tidak dapat diubah. Pulihkan dahulu bila perlu.');
            redirect('register');
        }
        $this->load->model('Arsip_model', 'arsip');
        $this->render('register/ubah', ['judul' => 'Ubah ' . $b['nomor_surat'], 'b' => $b, 'errors' => [],
            'arsip_nomor_id' => (int) $b['id'], 'arsip' => $this->arsip->daftar($b['id']), 'kembali' => 'register/ubah/' . (int) $b['id']]);
    }

    public function simpan($id = 0)
    {
        $this->wajib_post();
        $b = $this->ambil_atau_404($id);
        if ($b['status'] === 'dibatalkan') {
            redirect('register');
        }
        $uraian = trim(preg_replace('/\s+/', ' ', (string) $this->input->post('uraian', false)));
        $tgl = trim((string) $this->input->post('tanggal_surat'));
        $errors = [];
        if (mb_strlen($uraian) < 3) {
            $errors[] = 'Uraian kegiatan wajib diisi.';
        }
        if ($tgl !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $tgl);
            if (!$d || $d->format('Y-m-d') !== $tgl) {
                $errors[] = 'Tanggal surat tidak valid.';
            } elseif ((int) substr($tgl, 0, 4) !== (int) $b['tahun']) {
                $errors[] = 'Tahun tanggal surat harus ' . $b['tahun'] . ', sesuai tahun nomor.';
            }
        } elseif ($b['status'] === 'terpakai') {
            $errors[] = 'Tanggal surat wajib diisi.';
        }
        if ($errors) {
            $b['uraian'] = $uraian;
            $b['tanggal_surat'] = $tgl;
            $this->render('register/ubah', ['judul' => 'Ubah ' . $b['nomor_surat'], 'b' => $b, 'errors' => $errors]);
            return;
        }
        $this->nomor->ubah($b['id'], $uraian, $tgl !== '' ? $tgl : null);
        $this->catat('ubah_nomor', $b['nomor_surat']);
        flash_set('ok', 'Data ' . $b['nomor_surat'] . ' disimpan.');
        redirect('register?q=' . rawurlencode($b['nomor_surat']) . '&tahun=' . $b['tahun']);
    }

    public function batal($id = 0)
    {
        $this->wajib_post();
        $b = $this->ambil_atau_404($id);
        $alasan = trim((string) $this->input->post('alasan'));
        if ($b['status'] === 'dibatalkan') {
            flash_set('warn', 'Nomor ini sudah dibatalkan.');
        } elseif ($alasan === '') {
            flash_set('bad', 'Alasan pembatalan wajib diisi.');
        } else {
            $this->nomor->batal($b['id'], mb_substr($alasan, 0, 300));
            $this->catat('batal_nomor', $b['nomor_surat'] . ': ' . $alasan);
            flash_set('ok', $b['nomor_surat'] . ' dibatalkan. Nomornya tetap tercatat dan tidak dipakai ulang.');
        }
        redirect($this->input->post('kembali') === 'register' ? 'register?q=' . rawurlencode($b['nomor_surat']) . '&tahun=' . $b['tahun'] : 'register');
    }

    public function pulihkan($id = 0)
    {
        $this->wajib_post();
        $this->wajib_admin();
        $b = $this->ambil_atau_404($id);
        if ($b['status'] === 'dibatalkan' && $this->nomor->pulihkan($b['id'])) {
            $this->catat('pulihkan_nomor', $b['nomor_surat']);
            flash_set('ok', $b['nomor_surat'] . ' dipulihkan.');
        }
        redirect('register?q=' . rawurlencode($b['nomor_surat']) . '&tahun=' . $b['tahun']);
    }

    public function ekspor()
    {
        $rows = $this->nomor->daftar($this->filter());
        $this->catat('ekspor_register', count($rows) . ' baris');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="register_nomor_' . date('Ymd') . '.csv"');
        echo "\xEF\xBB\xBF";
        $o = fopen('php://output', 'w');
        fputcsv($o, ['Nomor surat', 'Tanggal surat', 'Uraian kegiatan', 'Pembuat', 'Jenis surat', 'Status', 'Alasan pembatalan']);
        foreach ($rows as $r) {
            fputcsv($o, [$r['nomor_surat'], $r['tanggal_surat'], $r['uraian'], $r['pembuat'], $r['template_nama'], $r['status'], $r['alasan_batal']]);
        }
        fclose($o);
    }
}
