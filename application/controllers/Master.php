<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Master extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->wajib_admin();
        $this->load->model('Master_model', 'master');
        $this->load->model('Pengaturan_model', 'pengaturan');
        $this->load->model('User_model', 'users');
    }

    // ---- Penandatangan --------------------------------------------------

    public function penandatangan()
    {
        $ubah = (int) $this->input->get('ubah');
        $this->render('master/penandatangan', [
            'judul' => 'Penandatangan', 'rows' => $this->master->penandatangan(),
            'edit' => $ubah ? $this->db->get_where('penandatangan', ['id' => $ubah])->row_array() : null,
        ]);
    }

    public function penandatangan_simpan()
    {
        $this->wajib_post();
        $id = (int) $this->input->post('id');
        $d = [
            'nama' => trim((string) $this->input->post('nama')),
            'nip' => trim((string) $this->input->post('nip')) ?: null,
            'jabatan' => trim((string) $this->input->post('jabatan')),
            'aktif_dari' => trim((string) $this->input->post('aktif_dari')),
            'aktif_sampai' => trim((string) $this->input->post('aktif_sampai')) ?: null,
        ];
        if ($d['nama'] === '' || $d['jabatan'] === '' || $d['aktif_dari'] === '') {
            flash_set('bad', 'Nama, jabatan, dan tanggal mulai berlaku wajib diisi.');
        } elseif ($d['aktif_sampai'] && $d['aktif_sampai'] < $d['aktif_dari']) {
            flash_set('bad', 'Tanggal akhir berlaku lebih awal dari tanggal mulai.');
        } else {
            $this->master->simpan_penandatangan($id, $d);
            $this->catat('simpan_penandatangan', $d['nama']);
            flash_set('ok', 'Penandatangan disimpan.');
        }
        redirect('master/penandatangan');
    }

    // ---- Peran ----------------------------------------------------------

    public function peran()
    {
        $ubah = (int) $this->input->get('ubah');
        $this->render('master/peran', [
            'judul' => 'Peran penugasan', 'rows' => $this->master->peran(),
            'edit' => $ubah ? $this->db->get_where('peran', ['id' => $ubah])->row_array() : null,
        ]);
    }

    public function peran_simpan()
    {
        $this->wajib_post();
        $id = (int) $this->input->post('id');
        $nama = trim((string) $this->input->post('nama'));
        if ($nama === '') {
            flash_set('bad', 'Nama peran wajib diisi.');
        } else {
            $this->master->simpan_peran($id, ['nama' => $nama]);
            $this->catat('simpan_peran', $nama);
            flash_set('ok', 'Peran disimpan.');
        }
        redirect('master/peran');
    }

    public function peran_hapus($id = 0)
    {
        $this->wajib_post();
        $id = (int) $id;
        if ($this->db->where('peran_id', $id)->count_all_results('surat_tugas_pegawai') > 0) {
            flash_set('bad', 'Peran tidak dapat dihapus karena sedang digunakan dalam surat tugas.');
        } else {
            $this->db->delete('peran', ['id' => $id]);
            $this->catat('hapus_peran', 'id ' . $id);
            flash_set('ok', 'Peran dihapus.');
        }
        redirect('master/peran');
    }

    // ---- Dasar hukum ----------------------------------------------------

    public function dasar_hukum()
    {
        $ubah = (int) $this->input->get('ubah');
        $this->render('master/dasar_hukum', [
            'judul' => 'Dasar hukum (mengingat)', 'rows' => $this->master->dasar_hukum_semua(),
            'edit' => $ubah ? $this->db->get_where('dasar_hukum', ['id' => $ubah])->row_array() : null,
        ]);
    }

    public function dasar_hukum_simpan()
    {
        $this->wajib_post();
        $id = (int) $this->input->post('id');
        $d = [
            'berlaku_mulai' => trim((string) $this->input->post('berlaku_mulai')),
            'keterangan' => trim((string) $this->input->post('keterangan')) ?: null,
            'mengingat' => trim((string) $this->input->post('mengingat')),
        ];
        if ($d['berlaku_mulai'] === '' || $d['mengingat'] === '') {
            flash_set('bad', 'Tanggal mulai berlaku dan isi mengingat wajib diisi.');
        } else {
            $this->master->simpan_dasar_hukum($id, $d);
            $this->catat('simpan_dasar_hukum', $d['berlaku_mulai']);
            flash_set('ok', 'Dasar hukum disimpan.');
        }
        redirect('master/dasar_hukum');
    }

    // ---- Pengaturan -----------------------------------------------------

    public function pengaturan()
    {
        $this->render('master/pengaturan', ['judul' => 'Pengaturan', 'set' => $this->pengaturan->semua()]);
    }

    public function pengaturan_simpan()
    {
        $this->wajib_post();
        $kunci = ['kode_satker', 'kode_klasifikasi', 'format_nomor', 'tempat_default', 'awalan_untuk',
            'kop_baris1', 'kop_baris2', 'menimbang_b', 'kalimat_memberi_tugas'];
        $format = trim((string) $this->input->post('format_nomor'));
        if (strpos($format, '{urut}') === false) {
            flash_set('bad', 'Format nomor harus memuat {urut}.');
            redirect('master/pengaturan');
        }
        foreach ($kunci as $k) {
            $v = $this->input->post($k);
            if ($v !== null) {
                $this->pengaturan->set($k, trim((string) $v));
            }
        }
        $this->catat('simpan_pengaturan');
        flash_set('ok', 'Pengaturan disimpan. Nomor yang sudah terbit tidak berubah.');
        redirect('master/pengaturan');
    }

    // ---- Pengguna -------------------------------------------------------

    public function pengguna()
    {
        $ubah = (int) $this->input->get('ubah');
        $this->render('master/pengguna', [
            'judul' => 'Pengguna', 'rows' => $this->users->semua(), 'edit' => $ubah ? $this->users->ambil($ubah) : null,
        ]);
    }

    public function pengguna_simpan()
    {
        $this->wajib_post();
        $id = (int) $this->input->post('id');
        $username = strtolower(trim((string) $this->input->post('username')));
        $nama = trim((string) $this->input->post('nama'));
        $role = $this->input->post('role') === 'admin' ? 'admin' : 'operator';
        $aktif = $this->input->post('aktif') ? 1 : 0;
        $sandi = (string) $this->input->post('password', false);

        $err = null;
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
            $err = 'Username 3 sampai 50 karakter: huruf kecil, angka, titik, garis bawah, atau strip.';
        } elseif ($nama === '') {
            $err = 'Nama wajib diisi.';
        } elseif ($this->users->username_ada($username, $id)) {
            $err = 'Username sudah dipakai.';
        } elseif (!$id && strlen($sandi) < 8) {
            $err = 'Kata sandi awal minimal 8 karakter.';
        } elseif ($sandi !== '' && strlen($sandi) < 8) {
            $err = 'Kata sandi minimal 8 karakter.';
        } elseif ($id === (int) $this->user['id'] && (!$aktif || $role !== 'admin')) {
            $err = 'Akun yang sedang dipakai tidak dapat dinonaktifkan atau diturunkan perannya.';
        }
        if ($err) {
            flash_set('bad', $err);
            redirect('master/pengguna' . ($id ? '?ubah=' . $id : ''));
        }

        $d = ['username' => $username, 'nama' => $nama, 'role' => $role, 'aktif' => $aktif];
        if ($sandi !== '') {
            $d['password_hash'] = password_hash($sandi, PASSWORD_DEFAULT);
            $d['wajib_ganti_sandi'] = $id === (int) $this->user['id'] ? 0 : 1;
        }
        $this->users->simpan($id, $d);
        $this->catat($id ? 'ubah_pengguna' : 'tambah_pengguna', $username);
        flash_set('ok', 'Pengguna ' . $username . ' disimpan.');
        redirect('master/pengguna');
    }

    // ---- Log ------------------------------------------------------------

    public function log()
    {
        $this->render('master/log', ['judul' => 'Log aktivitas', 'rows' => $this->db
            ->select('l.*, u.username')->from('log_aktivitas l')->join('users u', 'u.id = l.user_id', 'left')
            ->order_by('l.id', 'DESC')->limit(200)->get()->result_array()]);
    }

    // ---- Cadangan -------------------------------------------------------

    public function cadangan()
    {
        $this->render('master/cadangan', ['judul' => 'Cadangan database']);
    }

    public function cadangan_unduh()
    {
        $this->wajib_post();
        $this->load->dbutil();
        $isi = $this->dbutil->backup([
            'format' => 'txt', 'add_drop' => true, 'add_insert' => true, 'newline' => "\n",
        ]);
        $keluar = "-- Cadangan database surat_tugas, dibuat " . date('Y-m-d H:i:s') . "\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n" . $isi . "\nSET FOREIGN_KEY_CHECKS=1;\n";
        $this->catat('cadangan_database', strlen($keluar) . ' byte');
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="surat_tugas_' . date('Ymd_His') . '.sql"');
        header('Content-Length: ' . strlen($keluar));
        echo $keluar;
        exit;
    }
}
