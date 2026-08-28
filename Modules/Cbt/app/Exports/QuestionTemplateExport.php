<?php

namespace Modules\Cbt\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QuestionTemplateExport implements ShouldAutoSize, WithHeadings, FromArray
{
    public function headings(): array
    {
        return [
            'tipe_soal',
            'pertanyaan',
            'opsi_a',
            'opsi_b',
            'opsi_c',
            'opsi_d',
            'opsi_e',
            'target_penjodohan',
            'kunci_jawaban',
            'bobot',
        ];
    }

    public function array(): array
    {
        return [
            [
                'pilihan_ganda',
                'Ibu kota negara Indonesia adalah...',
                'Jakarta',
                'Bandung',
                'Surabaya',
                'Medan',
                'Bali',
                '',
                'A',
                '1.00'
            ],
            [
                'isian_singkat',
                'Presiden pertama Republik Indonesia adalah...',
                '',
                '',
                '',
                '',
                '',
                '',
                'Soekarno, Ir. Soekarno',
                '1.00'
            ],
            [
                'uraian',
                'Jelaskan mengapa bumi berputar mengelilingi matahari!',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '3.00'
            ],
            [
                'list',
                'Pilih warna dasar bendera Indonesia dari pilihan berikut:',
                'Merah',
                'Kuning',
                'Hijau',
                'Putih',
                '',
                '',
                'A,D',
                '1.00'
            ],
            [
                'checklist',
                'Pilihlah hewan-hewan yang termasuk mamalia:',
                'Kucing',
                'Ayam',
                'Paus',
                'Ular',
                '',
                '',
                'A,C',
                '1.00'
            ],
            [
                'benar_salah',
                'Tentukan Benar (B) atau Salah (S) pernyataan berikut:',
                'Matahari terbit dari sebelah barat',
                'Bumi berbentuk bulat',
                'Air mendidih pada suhu 100 derajat Celcius',
                '',
                '',
                '',
                'S,B,B',
                '1.50'
            ],
            [
                'penjodohan',
                'Jodohkan Negara (kiri) dengan Ibu Kota (kanan):',
                'Indonesia',
                'Jepang',
                'Prancis',
                '',
                '',
                'Jakarta, Tokyo, Paris',
                '1-A,2-B,3-C',
                '2.00'
            ],
            [
                'survey',
                'Bagaimana pendapat Anda tentang fasilitas perpustakaan sekolah?',
                'Sangat Baik',
                'Baik',
                'Cukup',
                'Kurang',
                '',
                '',
                '',
                '0.00'
            ],
            [
                'skor_berbeda',
                'Tindakan yang paling tepat jika melihat teman membuang sampah sembarangan:',
                'Menegur langsung secara sopan',
                'Memungutnya dan membuangnya sendiri',
                'Melaporkan ke guru piket',
                'Membiarkannya saja',
                '',
                '',
                'A:5,B:4,C:3,D:0',
                '1.00'
            ],
            [
                'sorting',
                'Urutkan tahapan metamorfosis katak berikut dari awal:',
                'Kecebong',
                'Telur',
                'Katak Dewasa',
                'Katak Muda',
                '',
                '',
                'B,A,D,C',
                '2.00'
            ],
        ];
    }
}
