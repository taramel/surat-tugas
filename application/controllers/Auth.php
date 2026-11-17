<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model', 'users');
        atur_zona_waktu_db();
    }

    private function catat($aksi, $userId, $ket = '')
    {
        $this->db->insert('log_aktivitas', [
            'user_id' => $userId, 'aksi' => $aksi, 'keterangan' => $ket, 'ip' => $this->input->ip_address(),
        ]);
    }

    public function login()
    {
        if ($this->session->userdata('user')) {
            redirect('/');
        }
        $error = null;
        if ($this->input->method() === 'post') {
            $username = trim((string) $this->input->post('username'));
            if ($this->users->terlalu_banyak_gagal($this->input->ip_address(), $username)) {
                $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 10 menit.';
            } else {
                $u = $this->users->cek($username, (string) $this->input->post('password', false));
                if ($u) {
                    $this->session->sess_regenerate(true);
                    $this->session->set_userdata('user', [
                        'id' => (int) $u->id, 'username' => $u->username, 'nama' => $u->nama, 'role' => $u->role,
                    ]);
                    $this->catat('login', $u->id);
                    redirect('/');
                }
                $this->catat('login_gagal', null, $username);
                $error = 'Username atau kata sandi salah.';
            }
        }
        kirim_header_aman(true);
        $this->load->view('auth/login', ['error' => $error]);
    }

    public function logout()
    {
        $this->session->unset_userdata('user');
        $this->session->sess_destroy();
        redirect('login');
    }

    public function password()
    {
        kirim_header_aman(true);
        $user = $this->session->userdata('user');
        if (!$user) {
            redirect('login');
        }
        $error = null;
        if ($this->input->method() === 'post') {
            $lama = (string) $this->input->post('lama', false);
            $baru = (string) $this->input->post('baru', false);
            $ulang = (string) $this->input->post('ulang', false);
            $row = $this->users->ambil($user['id']);
            if (!$row || !password_verify($lama, $row['password_hash'])) {
                $error = 'Kata sandi saat ini tidak cocok.';
            } elseif ($baru === $lama) {
                $error = 'Kata sandi baru harus berbeda dari yang lama.';
            } elseif (strlen($baru) < 8) {
                $error = 'Kata sandi baru minimal 8 karakter.';
            } elseif ($baru !== $ulang) {
                $error = 'Pengulangan kata sandi baru tidak sama.';
            } else {
                $this->users->simpan($user['id'], [
                    'password_hash' => password_hash($baru, PASSWORD_DEFAULT), 'wajib_ganti_sandi' => 0,
                ]);
                $this->catat('ganti_password', $user['id']);
                flash_set('ok', 'Kata sandi diperbarui.');
                redirect('/');
            }
        }
        $data = ['user' => $user, 'judul' => 'Ganti kata sandi', 'error' => $error];
        $this->load->view('layout/header', $data);
        $this->load->view('auth/password', $data);
        $this->load->view('layout/footer', $data);
    }
}
