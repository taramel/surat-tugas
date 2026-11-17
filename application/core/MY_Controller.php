<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    protected $user;

    public function __construct()
    {
        parent::__construct();
        kirim_header_aman();
        atur_zona_waktu_db();
        $sesi = $this->session->userdata('user');
        if (!$sesi) {
            redirect('login');
        }
        // Peran dan status akun selalu dibaca dari database agar perubahan oleh admin langsung berlaku.
        $baris = $this->db->get_where('users', ['id' => (int) $sesi['id'], 'aktif' => 1])->row();
        if (!$baris) {
            $this->session->unset_userdata('user');
            $this->session->sess_destroy();
            redirect('login');
        }
        // Database harus sudah versi terbaru. Admin diarahkan ke halaman pembaruan; pengguna lain diminta menghubungi admin.
        $this->load->library('skema');
        if ($this->skema->perlu_pembaruan()) {
            if ($baris->role === 'admin') {
                redirect('pembaruan');
            }
            show_error('Database aplikasi perlu diperbarui. Minta admin untuk masuk ke aplikasi dan menjalankan pembaruan.', 503, 'Pembaruan diperlukan');
        }
        $this->user = [
            'id' => (int) $baris->id, 'username' => $baris->username,
            'nama' => $baris->nama, 'role' => $baris->role,
        ];
        if ((int) $baris->wajib_ganti_sandi === 1) {
            flash_set('warn', 'Ganti kata sandi awal Anda terlebih dahulu.');
            redirect('akun/password');
        }
    }

    protected function wajib_admin()
    {
        if ($this->user['role'] !== 'admin') {
            show_error('Halaman ini hanya untuk admin.', 403, 'Akses ditolak');
        }
    }

    protected function wajib_post()
    {
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
        }
    }

    protected function render($view, $data = [])
    {
        $data['user'] = $this->user;
        $data['judul'] = isset($data['judul']) ? $data['judul'] : 'Surat Tugas';
        kirim_header_aman(true);
        $this->load->view('layout/header', $data);
        $this->load->view($view, $data);
        $this->load->view('layout/footer', $data);
    }

    protected function catat($aksi, $keterangan = '')
    {
        $this->db->insert('log_aktivitas', [
            'user_id' => $this->user ? $this->user['id'] : null,
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'ip' => $this->input->ip_address(),
        ]);
    }

    protected function json($data)
    {
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
