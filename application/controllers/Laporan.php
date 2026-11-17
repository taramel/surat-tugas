<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Laporan extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Surat_model', 'surat');
        $this->load->model('Nomor_model', 'nomor');
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
        $tahunRekap = (int) ($f['tahun'] ?: date('Y'));
        $this->render('laporan/index', [
            'judul' => 'Laporan', 'f' => $f, 'rows' => $this->surat->daftar($f),
            'tahun_opsi' => $this->nomor->tahun_tersedia(), 'tahun_rekap' => $tahunRekap,
            'bulanan' => $this->surat->rekap_bulanan($tahunRekap),
            'per_peran' => $this->surat->rekap_peran($tahunRekap),
            'per_pegawai' => $this->surat->jumlah_penugasan_per_pegawai($tahunRekap),
        ]);
    }

    public function ekspor()
    {
        $rows = $this->surat->daftar($this->filter());
        $this->catat('ekspor_laporan', count($rows) . ' baris');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan_surat_tugas_' . date('Ymd') . '.csv"');
        echo "\xEF\xBB\xBF";
        $o = fopen('php://output', 'w');
        fputcsv($o, ['Nomor surat', 'Tanggal surat', 'Surat rujukan', 'Nomor rujukan', 'Uraian penugasan',
            'Tanggal pelaksanaan', 'Penerima', 'Pembuat', 'Status']);
        foreach ($rows as $r) {
            fputcsv($o, [$r['nomor_surat'], $r['tanggal_surat'], $r['rujukan_pengirim'], $r['rujukan_nomor'],
                $r['untuk'], $r['rentang'], $r['penerima'], $r['pembuat'], $r['status']]);
        }
        fclose($o);
    }
}
