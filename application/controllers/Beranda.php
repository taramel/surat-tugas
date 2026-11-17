<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** Halaman depan untuk pengunjung. Pengguna yang sudah masuk langsung diarahkan ke halaman ambil nomor. */
class Beranda extends CI_Controller
{
    public function index()
    {
        if ($this->session->userdata('user')) {
            redirect('ambil');
        }
        kirim_header_aman(true);
        $this->load->view('beranda/index');
    }
}
