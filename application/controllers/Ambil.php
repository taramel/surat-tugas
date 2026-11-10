<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Halaman utama: mengambil nomor surat tugas dan mengunduh template Word yang sudah terisi. */
class Ambil extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Nomor_model', 'nomor');
        $this->load->model('Template_model', 'templat');
        $this->load->model('Master_model', 'master');
        $this->load->model('Pengaturan_model', 'pengaturan');
        $this->load->library('docx_isi');
    }

    private function form(array $v, array $errors = [])
    {
        $tahun = (int) date('Y');
        $this->render('ambil/form', [
            'judul' => 'Ambil nomor saja', 'v' => $v, 'errors' => $errors,
            'templat' => $this->templat->nama_aktif(), 'bebas' => $this->nomor->bebas($tahun),
            'terakhir' => $this->nomor->maks($tahun), 'tahun' => $tahun,
            'saya' => $this->nomor->terbaru_pengguna($this->user['id'], 5),
        ]);
    }

    public function index()
    {
        $nama = $this->templat->nama_aktif();
        $this->form([
            'tanggal_surat' => date('Y-m-d'), 'template_nama' => $nama ? $nama[0] : '', 'uraian' => '',
            'nomor_pilih' => (int) $this->input->get('nomor') ?: '',
        ]);
    }

    private function tanggal_valid($t)
    {
        $d = DateTime::createFromFormat('Y-m-d', (string) $t);
        return $d && $d->format('Y-m-d') === $t;
    }

    public function proses()
    {
        $this->wajib_post();
        $in = $this->input->post(null, false);
        $errors = [];

        $tgl = trim(isset($in['tanggal_surat']) ? $in['tanggal_surat'] : '');
        $uraian = trim(preg_replace('/\s+/', ' ', isset($in['uraian']) ? $in['uraian'] : ''));
        $nama = isset($in['template_nama']) ? $in['template_nama'] : '';
        $pilih = (int) (isset($in['nomor_pilih']) ? $in['nomor_pilih'] : 0);

        if (!$this->tanggal_valid($tgl)) {
            $errors[] = 'Tanggal surat belum diisi dengan benar.';
        }
        if (mb_strlen($uraian) < 3) {
            $errors[] = 'Uraian kegiatan wajib diisi.';
        } elseif (mb_strlen($uraian) > 1000) {
            $errors[] = 'Uraian kegiatan terlalu panjang (maksimal 1000 karakter).';
        }
        if (!in_array($nama, $this->templat->nama_aktif(), true)) {
            $errors[] = 'Pilih jenis surat (template) yang tersedia.';
        }
        $ttd = $this->tanggal_valid($tgl) ? $this->master->penandatangan_pada($tgl) : null;
        if ($this->tanggal_valid($tgl) && !$ttd) {
            $errors[] = 'Belum ada penandatangan yang berlaku pada ' . tgl_indo($tgl) . '. Minta admin mengaturnya di menu Penandatangan.';
        }

        $v = ['tanggal_surat' => $tgl, 'template_nama' => $nama, 'uraian' => $uraian, 'nomor_pilih' => $pilih ?: ''];
        if ($errors) {
            $this->form($v, $errors);
            return;
        }

        list($ok, $hasil) = $this->nomor->ambil([
            'tahun' => (int) substr($tgl, 0, 4), 'tanggal_surat' => $tgl, 'uraian' => $uraian,
            'template_nama' => $nama, 'penandatangan_id' => $ttd['id'],
            'pembuat' => $this->user['nama'], 'dibuat_oleh' => $this->user['id'],
        ], $pilih);
        if (!$ok) {
            $this->form($v, [$hasil]);
            return;
        }
        $baris = $this->nomor->baris($hasil);
        $this->catat('ambil_nomor', $baris['nomor_surat']);
        redirect('ambil/hasil/' . $hasil);
    }

    public function hasil($id = 0)
    {
        $b = $this->nomor->baris($id);
        if (!$b || $b['status'] === 'tersedia') {
            show_404();
        }
        $this->load->model('Arsip_model', 'arsip');
        $this->render('ambil/hasil', ['judul' => $b['nomor_surat'], 'b' => $b,
            'arsip_nomor_id' => (int) $b['id'], 'arsip' => $this->arsip->daftar($b['id']), 'kembali' => 'ambil/hasil/' . (int) $b['id']]);
    }

    /** Unduh berkas Word yang sudah terisi nomor, tanggal, penandatangan, dan uraian. */
    public function unduh($id = 0)
    {
        $b = $this->nomor->baris($id);
        if (!$b || $b['status'] !== 'terpakai' || !$b['template_nama']) {
            flash_set('warn', 'Berkas Word hanya tersedia untuk nomor yang diambil lewat aplikasi dan masih berlaku.');
            redirect('register');
        }
        $t = $this->templat->untuk($b['template_nama'], $b['tanggal_surat']);
        if (!$t || !$this->templat->berkas_ada($t)) {
            flash_set('bad', 'Template "' . $b['template_nama'] . '" tidak ditemukan. Hubungi admin.');
            redirect('register');
        }
        $ttd = $b['penandatangan_id']
            ? $this->db->get_where('penandatangan', ['id' => (int) $b['penandatangan_id']])->row_array()
            : $this->master->penandatangan_pada($b['tanggal_surat']);
        $uraian = preg_replace('/^\s*mengikuti\s+kegiatan\s+/iu', '', trim($b['uraian']));

        try {
            $isi = $this->docx_isi->isi(Template_model::folder() . $t['berkas'], [
                'NOMOR' => $b['nomor_surat'], 'TANGGAL' => tgl_indo($b['tanggal_surat']),
                'TEMPAT' => $this->pengaturan->get('tempat_default', 'Pontianak'),
                'TTD_JABATAN' => $ttd ? $ttd['jabatan'] : '', 'TTD_NAMA' => $ttd ? $ttd['nama'] : '', 'TTD_NIP' => $ttd ? (string) $ttd['nip'] : '',
                'URAIAN' => $uraian, 'TAHUN' => $b['tahun'], 'PEMBUAT' => (string) $b['pembuat'],
            ]);
        } catch (Throwable $e) {
            flash_set('bad', 'Berkas Word gagal dibuat: ' . $e->getMessage());
            redirect('register');
        }
        $this->catat('unduh_word', $b['nomor_surat']);
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . nama_berkas_nomor($b['nomor_surat']) . '.docx"');
        header('Content-Length: ' . strlen($isi));
        echo $isi;
        exit;
    }
}
