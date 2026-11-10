<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Pengaturan_model extends CI_Model
{
    private $cache = null;

    public function semua()
    {
        if ($this->cache === null) {
            $this->cache = [];
            foreach ($this->db->get('pengaturan')->result() as $r) {
                $this->cache[$r->kunci] = $r->nilai;
            }
        }
        return $this->cache;
    }

    public function get($kunci, $default = '')
    {
        $s = $this->semua();
        return isset($s[$kunci]) ? $s[$kunci] : $default;
    }

    public function set($kunci, $nilai)
    {
        $this->db->query(
            'INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)',
            [$kunci, $nilai]
        );
        $this->cache = null;
    }
}
