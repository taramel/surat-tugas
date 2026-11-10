<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Pegawai extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Pegawai_model', 'pegawai');
    }

    public function index()
    {
        $q = trim((string) $this->input->get('q'));
        $status = $this->input->get('status') === 'nonaktif' ? 0 : 1;
        $hal = max(1, (int) $this->input->get('hal'));
        $per = 25;
        $this->render('pegawai/index', [
            'judul' => 'Data pegawai', 'q' => $q, 'status' => $status, 'hal' => $hal, 'per' => $per,
            'total' => $this->pegawai->hitung($q, $status),
            'rows' => $this->pegawai->daftar($q, $status, $per, ($hal - 1) * $per),
        ]);
    }

    public function cari()
    {
        $this->json($this->pegawai->cari(trim((string) $this->input->get('q'))));
    }

    public function tambah()
    {
        $this->render('pegawai/form', ['judul' => 'Pegawai baru', 'p' => ['id' => 0, 'nip' => '', 'nama' => '',
            'pangkat_golongan' => '', 'jabatan' => '', 'unit_kerja' => ''], 'errors' => []]);
    }

    public function ubah($id = 0)
    {
        $p = $this->pegawai->ambil($id);
        if (!$p) {
            show_404();
        }
        $this->render('pegawai/form', ['judul' => 'Ubah pegawai', 'p' => $p, 'errors' => []]);
    }

    public function simpan()
    {
        $this->wajib_post();
        $id = (int) $this->input->post('id');
        $d = [
            'nip' => preg_replace('/\s+/', '', (string) $this->input->post('nip')),
            'nama' => trim((string) $this->input->post('nama')),
            'pangkat_golongan' => trim((string) $this->input->post('pangkat_golongan')) ?: null,
            'jabatan' => trim((string) $this->input->post('jabatan')),
            'unit_kerja' => trim((string) $this->input->post('unit_kerja')) ?: null,
        ];
        $errors = [];
        if ($d['nama'] === '') {
            $errors[] = 'Nama wajib diisi.';
        }
        if ($d['jabatan'] === '') {
            $errors[] = 'Jabatan wajib diisi.';
        }
        if ($d['nip'] !== '' && !preg_match('/^\d{8,30}$/', $d['nip'])) {
            $errors[] = 'NIP hanya boleh berisi angka (8 sampai 30 digit).';
        }
        if ($this->pegawai->nip_ada($d['nip'], $id)) {
            $errors[] = 'NIP ini sudah terdaftar pada pegawai lain.';
        }
        if ($errors) {
            $this->render('pegawai/form', ['judul' => $id ? 'Ubah pegawai' : 'Pegawai baru', 'p' => $d + ['id' => $id], 'errors' => $errors]);
            return;
        }
        $this->pegawai->simpan($id, $d);
        $this->catat($id ? 'ubah_pegawai' : 'tambah_pegawai', $d['nama']);
        flash_set('ok', 'Data ' . $d['nama'] . ' disimpan.');
        redirect('pegawai');
    }

    public function status($id = 0)
    {
        $this->wajib_post();
        $p = $this->pegawai->ambil($id);
        if (!$p) {
            show_404();
        }
        $aktif = $p['aktif'] ? 0 : 1;
        $this->db->where('id', $p['id'])->update('pegawai', ['aktif' => $aktif]);
        $this->catat($aktif ? 'aktifkan_pegawai' : 'nonaktifkan_pegawai', $p['nama']);
        flash_set('ok', $p['nama'] . ($aktif ? ' diaktifkan kembali.' : ' dinonaktifkan. Surat lama tetap utuh.'));
        redirect('pegawai' . ($aktif ? '?status=nonaktif' : ''));
    }

    public function impor()
    {
        $this->render('pegawai/impor', ['judul' => 'Impor pegawai', 'hasil' => null]);
    }

    public function contoh()
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contoh_pegawai.csv"');
        echo "\xEF\xBB\xBF";
        $o = fopen('php://output', 'w');
        fputcsv($o, ['nip', 'nama', 'pangkat_golongan', 'jabatan', 'unit_kerja']);
        fputcsv($o, ['199001012015011001', 'Contoh Nama, S.Si', 'Penata Muda - III/a', 'Statistisi Ahli Pertama', 'BPS Provinsi Kalimantan Barat']);
        fclose($o);
    }

    public function proses_impor()
    {
        $this->wajib_post();
        $salah = validasi_csv(isset($_FILES['berkas']) ? $_FILES['berkas'] : []);
        if ($salah) {
            flash_set('bad', $salah);
            redirect('pegawai/impor');
        }
        $fh = fopen($_FILES['berkas']['tmp_name'], 'r');
        $pertama = (string) fgets($fh);
        rewind($fh);
        $pemisah = substr_count($pertama, ';') > substr_count($pertama, ',') ? ';' : ',';

        $peta = null;
        $baris = [];
        $lewat = 0;
        $luar = 0;
        $nipDiberkas = [];
        $n = 0;
        while (($r = fgetcsv($fh, 0, $pemisah)) !== false) {
            $n++;
            if ($n === 1) {
                $r[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $r[0]);
                $peta = [];
                foreach ($r as $i => $h) {
                    $h = strtolower(trim($h));
                    $alias = [
                        'pangkat' => 'pangkat_golongan', 'gol.akhir' => 'pangkat_golongan', 'gol akhir' => 'pangkat_golongan', 'golongan' => 'pangkat_golongan',
                        'unit' => 'unit_kerja', 'tim kerja' => 'unit_kerja',
                    ];
                    $h = isset($alias[$h]) ? $alias[$h] : $h;
                    $peta[$h] = $i;
                }
                if (!isset($peta['nip'], $peta['nama'])) {
                    break;
                }
                continue;
            }
            $ambil = function ($k) use ($r, $peta) {
                return isset($peta[$k], $r[$peta[$k]]) ? trim($r[$peta[$k]]) : '';
            };
            $nip = preg_replace('/\D+/', '', $ambil('nip'));
            if ($nip === '' || $ambil('nama') === '') {
                $lewat++;
                continue;
            }
            // Sheet daftar pegawai memuat kolom "Wilayah": hanya yang berwilayah provinsi yang dimasukkan
            if (isset($peta['wilayah']) && stripos($ambil('wilayah'), 'prov') === false) {
                $luar++;
                continue;
            }
            $nipDiberkas[] = $nip;
            $baris[] = [
                'nip' => $nip, 'nama' => $ambil('nama'),
                'pangkat_golongan' => $ambil('pangkat_golongan') ?: null,
                'jabatan' => $ambil('jabatan') ?: '-', 'unit_kerja' => $ambil('unit_kerja') ?: null, 'aktif' => 1,
            ];
        }
        fclose($fh);

        if (!isset($peta['nip'], $peta['nama'])) {
            flash_set('bad', 'Baris judul harus memuat kolom "nip" dan "nama". Unduh contoh berkas untuk acuan.');
            redirect('pegawai/impor');
        }
        list($baru, $ubah) = $this->pegawai->impor($baris);
        $hapus = $nonaktif = 0;
        $sinkron = $this->input->post('sinkron') === '1' && $nipDiberkas;
        if ($sinkron) {
            list($hapus, $nonaktif) = $this->pegawai->bersihkan_selain($nipDiberkas);
        }
        $this->catat('impor_pegawai', "baru=$baru, diperbarui=$ubah, dilewati=$lewat, bukan_provinsi=$luar, dihapus=$hapus, dinonaktifkan=$nonaktif");
        $this->render('pegawai/impor', ['judul' => 'Impor pegawai', 'hasil' => compact('baru', 'ubah', 'lewat', 'luar', 'hapus', 'nonaktif', 'sinkron')]);
    }
}
