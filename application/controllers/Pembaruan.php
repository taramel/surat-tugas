<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Pembaruan database otomatis. Sengaja tidak memakai MY_Controller agar dapat diakses saat database masih versi lama. */
class Pembaruan extends CI_Controller
{
    private $user;

    public function __construct()
    {
        parent::__construct();
        kirim_header_aman();
        atur_zona_waktu_db();
        $sesi = $this->session->userdata('user');
        if (!$sesi) {
            redirect('login');
        }
        $baris = $this->db->get_where('users', ['id' => (int) $sesi['id'], 'aktif' => 1])->row();
        if (!$baris) {
            $this->session->unset_userdata('user');
            $this->session->sess_destroy();
            redirect('login');
        }
        $this->user = ['id' => (int) $baris->id, 'username' => $baris->username, 'nama' => $baris->nama, 'role' => $baris->role];
        $this->load->library('skema');
    }

    private function tampil($hasil = null, $galat = null)
    {
        kirim_header_aman(true);
        $data = [
            'user' => $this->user, 'judul' => 'Pembaruan database',
            'versi' => $this->skema->versi_sekarang(), 'versi_baru' => Skema::VERSI,
            'rencana' => $this->skema->rencana(), 'hasil' => $hasil, 'galat' => $galat,
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('pembaruan/index', $data);
        $this->load->view('layout/footer', $data);
    }

    private function wajib_admin()
    {
        if ($this->user['role'] !== 'admin') {
            show_error('Database aplikasi perlu diperbarui. Minta admin untuk masuk ke aplikasi dan menjalankan pembaruan.', 503, 'Pembaruan diperlukan');
        }
    }

    public function index()
    {
        $this->wajib_admin();
        $this->tampil();
    }

    public function terapkan()
    {
        $this->wajib_admin();
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
        }
        try {
            $hasil = $this->skema->terapkan();
        } catch (Throwable $e) {
            $this->tampil(null, $e->getMessage());
            return;
        }
        $this->db->insert('log_aktivitas', [
            'user_id' => $this->user['id'], 'aksi' => 'perbarui_database',
            'keterangan' => $hasil ? implode('; ', $hasil) : 'tidak ada perubahan', 'ip' => $this->input->ip_address(),
        ]);
        $this->tampil($hasil);
    }
}
