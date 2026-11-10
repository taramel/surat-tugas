<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mengisi penanda {{NAMA}} pada berkas Word (.docx). Penanda tetap terbaca walau Word memecahnya
 * menjadi beberapa bagian teks saat diketik. Membutuhkan ekstensi PHP zip dan dom.
 */
class Docx_isi
{
    const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    const NS_XML = 'http://www.w3.org/XML/1998/namespace';

    public function tersedia()
    {
        return class_exists('ZipArchive') && class_exists('DOMDocument');
    }

    /** Memeriksa bahwa berkas adalah dokumen Word yang valid. Mengembalikan pesan kesalahan atau null. */
    public function periksa($path)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return 'Berkas bukan dokumen Word (.docx) yang valid.';
        }
        $ada = $zip->locateName('word/document.xml') !== false && $zip->locateName('[Content_Types].xml') !== false;
        $zip->close();
        return $ada ? null : 'Berkas bukan dokumen Word (.docx) yang valid.';
    }

    /** Daftar penanda yang ditemukan pada berkas. */
    public function penanda($path)
    {
        $zip = new ZipArchive();
        $hasil = [];
        if ($zip->open($path) !== true) {
            return $hasil;
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (preg_match('#^word/(document|header\d*|footer\d*)\.xml$#', $zip->getNameIndex($i))) {
                $dom = $this->muat($zip->getFromIndex($i));
                if (!$dom) {
                    continue;
                }
                $xp = $this->xpath($dom);
                foreach ($xp->query('//w:p') as $p) {
                    if (preg_match_all('/\{\{([A-Z_]+)\}\}/', $this->teks_paragraf($xp, $p), $m)) {
                        $hasil = array_merge($hasil, $m[1]);
                    }
                }
            }
        }
        $zip->close();
        return array_values(array_unique($hasil));
    }

    /** Penanda yang tidak diberi nilai diisi petunjuk bertanda kurung siku, untuk dilengkapi manual di Word. */
    public static function manual()
    {
        return [
            'MENIMBANG' => 'bahwa merujuk surat dari [pengirim surat] nomor [nomor surat rujukan] hal [hal surat rujukan], maka dipandang perlu menyesuaikan dengan jadwal kegiatan tersebut',
            'NAMA' => '[nama pegawai dan gelar]', 'NIP' => '[NIP]', 'PANGKAT' => '[pangkat/golongan]', 'JABATAN' => '[jabatan]',
            'TANGGAL_PELAKSANAAN' => '[tanggal pelaksanaan]', 'PERAN' => '[peran]',
        ];
    }

    /** Dua baris lampiran kosong bernomor, dipakai saat daftar penerima belum diketahui. */
    public static function baris_kosong()
    {
        $baris = [];
        foreach ([1, 2] as $n) {
            $baris[] = ['NO' => (string) $n, 'NAMA' => '', 'NIP' => '', 'PANGKAT' => '', 'JABATAN' => '', 'PERAN' => ''];
        }
        return $baris;
    }

    /**
     * Mengembalikan isi berkas .docx hasil pengisian penanda.
     * $nilai  : penanda => teks pengganti.
     * $baris  : daftar penerima untuk tabel lampiran (baris yang memuat {{NO}} digandakan); null = dua baris kosong.
     * $ganti  : teks bagian dokumen yang diganti persis (teks lama => teks baru).
     */
    public function isi($path, array $nilai, array $baris = null, array $ganti = [])
    {
        $nilai = $nilai + self::manual();
        if ($baris === null) {
            $baris = self::baris_kosong();
        }
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        copy($path, $tmp);
        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Berkas template tidak dapat dibuka.');
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nama = $zip->getNameIndex($i);
            if (preg_match('#^word/(document|header\\d*|footer\\d*)\\.xml$#', $nama)) {
                $baru = $this->isi_xml($zip->getFromIndex($i), $nilai, $baris, $ganti);
                if ($baru !== null) {
                    $zip->addFromString($nama, $baru);
                }
            }
        }
        $zip->close();
        $isi = file_get_contents($tmp);
        @unlink($tmp);
        return $isi;
    }

    // ------------------------------------------------------------------

    private function muat($xml)
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        return @$dom->loadXML((string) $xml, LIBXML_NONET) ? $dom : null;
    }

    private function xpath(DOMDocument $dom)
    {
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::NS_W);
        return $xp;
    }

    private function teks_paragraf(DOMXPath $xp, DOMNode $p)
    {
        $teks = '';
        foreach ($xp->query('.//w:t', $p) as $t) {
            $teks .= $t->textContent;
        }
        return $teks;
    }

    private function atur_teks(DOMDocument $dom, DOMNode $t, $teks)
    {
        while ($t->firstChild) {
            $t->removeChild($t->firstChild);
        }
        $t->appendChild($dom->createTextNode($teks));
        $t->setAttributeNS(self::NS_XML, 'xml:space', 'preserve');
    }

    /** Teks isian yang bukan petunjuk "[...]" tidak perlu tampak disorot kuning. */
    private function hapus_sorot(DOMXPath $xp, DOMNode $t)
    {
        $run = $t->parentNode;
        if (!$run) {
            return;
        }
        foreach ($xp->query('w:rPr/w:highlight', $run) as $h) {
            $h->parentNode->removeChild($h);
        }
    }

    /** Mengganti penanda pada satu paragraf. Mengembalikan true bila ada yang berubah. */
    private function ganti_di_paragraf(DOMXPath $xp, DOMDocument $dom, DOMNode $p, array $nilai)
    {
        $nodes = [];
        $teks = '';
        foreach ($xp->query('.//w:t', $p) as $t) {
            $nodes[] = $t;
            $teks .= $t->textContent;
        }
        if (strpos($teks, '{{') === false || !preg_match_all('/\\{\\{([A-Z_]+)\\}\\}/', $teks, $m, PREG_OFFSET_CAPTURE)) {
            return false;
        }
        $diubah = false;
        // dari penanda terakhir ke yang pertama, agar posisi penanda sebelumnya tidak bergeser
        for ($k = count($m[0]) - 1; $k >= 0; $k--) {
            $nama = $m[1][$k][0];
            if (!array_key_exists($nama, $nilai)) {
                continue;
            }
            $pengganti = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '', (string) $nilai[$nama]);
            $mulai = $m[0][$k][1];
            $akhir = $mulai + strlen($m[0][$k][0]);
            $pos = 0;
            $pertama = true;
            foreach ($nodes as $t) {
                $isi = $t->textContent;
                $awalNode = $pos;
                $akhirNode = $pos + strlen($isi);
                $pos = $akhirNode;
                if ($akhirNode <= $mulai || $awalNode >= $akhir) {
                    continue;
                }
                $depan = substr($isi, 0, max($mulai, $awalNode) - $awalNode);
                $belakang = substr($isi, min($akhir, $akhirNode) - $awalNode);
                $this->atur_teks($dom, $t, $depan . ($pertama ? $pengganti : '') . $belakang);
                if ($pertama && substr(ltrim($pengganti), 0, 1) !== '[') {
                    $this->hapus_sorot($xp, $t);
                }
                $pertama = false;
                $diubah = true;
            }
        }
        return $diubah;
    }

    private function isi_xml($xml, array $nilai, array $baris = [], array $ganti = [])
    {
        if (strpos($xml, '{') === false && !$ganti) {
            return null;
        }
        $dom = $this->muat($xml);
        if (!$dom) {
            return null;
        }
        $xp = $this->xpath($dom);
        $diubah = false;

        // 1. Bagian teks yang diganti persis (misalnya awalan kalimat "Untuk")
        if ($ganti) {
            foreach ($xp->query('//w:t') as $t) {
                if (array_key_exists($t->textContent, $ganti)) {
                    $this->atur_teks($dom, $t, (string) $ganti[$t->textContent]);
                    $diubah = true;
                }
            }
        }

        // 2. Tabel lampiran: baris yang memuat {{NO}} digandakan untuk setiap penerima
        foreach ($xp->query('//w:tr') as $tr) {
            $teksBaris = '';
            foreach ($xp->query('.//w:t', $tr) as $t) {
                $teksBaris .= $t->textContent;
            }
            if (strpos($teksBaris, '{{NO}}') === false) {
                continue;
            }
            foreach ($baris as $b) {
                $salin = $tr->cloneNode(true);
                $tr->parentNode->insertBefore($salin, $tr);
                foreach ($xp->query('.//w:p', $salin) as $p) {
                    $this->ganti_di_paragraf($xp, $dom, $p, $b);
                }
            }
            $tr->parentNode->removeChild($tr);
            $diubah = true;
        }

        // 3. Penanda lain pada seluruh paragraf
        foreach ($xp->query('//w:p') as $p) {
            // Menimbang tanpa surat rujukan: butir pertama dihapus agar penomoran butir berikutnya tetap rapi
            if (isset($nilai['MENIMBANG']) && $nilai['MENIMBANG'] === '' && trim($this->teks_paragraf($xp, $p)) === '{{MENIMBANG}}') {
                $p->parentNode->removeChild($p);
                $diubah = true;
                continue;
            }
            if ($this->ganti_di_paragraf($xp, $dom, $p, $nilai)) {
                $diubah = true;
            }
        }
        return $diubah ? $dom->saveXML() : null;
    }
}
