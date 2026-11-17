<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    public function cek($username, $password)
    {
        $u = $this->db->get_where('users', ['username' => $username, 'aktif' => 1])->row();
        return ($u && password_verify($password, $u->password_hash)) ? $u : null;
    }

    public function terlalu_banyak_gagal($ip, $username = '')
    {
        $batas = date('Y-m-d H:i:s', time() - 600);
        $perIp = $this->db->where('aksi', 'login_gagal')->where('ip', $ip)
            ->where('created_at >', $batas)->count_all_results('log_aktivitas');
        if ($perIp >= 5) {
            return true;
        }
        if ($username === '') {
            return false;
        }
        return $this->db->where('aksi', 'login_gagal')->where('keterangan', $username)
            ->where('created_at >', $batas)->count_all_results('log_aktivitas') >= 10;
    }

    public function semua()
    {
        return $this->db->order_by('nama')->get('users')->result_array();
    }

    public function ambil($id)
    {
        return $this->db->get_where('users', ['id' => (int) $id])->row_array();
    }

    public function simpan($id, array $data)
    {
        if ($id) {
            return $this->db->where('id', $id)->update('users', $data);
        }
        return $this->db->insert('users', $data);
    }

    public function username_ada($username, $kecuali = 0)
    {
        return $this->db->where('username', $username)->where('id !=', (int) $kecuali)
            ->count_all_results('users') > 0;
    }
}
