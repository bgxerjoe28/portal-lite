<?php

namespace Modules\Cbt\Services;

class WordTemplateGenerator
{
    public static function generate(string $outputPath): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        // 1. Add [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. Add _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. Add word/document.xml
        $headers = [
            'Tipe Soal',
            'Pertanyaan',
            'Opsi A',
            'Opsi B',
            'Opsi C',
            'Opsi D',
            'Opsi E',
            'Target Penjodohan',
            'Kunci Jawaban',
            'Bobot'
        ];

        $rows = [
            // Pilihan Ganda
            [
                'pilihan_ganda',
                'Siapa presiden pertama Republik Indonesia?',
                'Soekarno',
                'Soeharto',
                'Habibie',
                'Abdurrahman Wahid',
                'Megawati Soekarnoputri',
                '',
                'A',
                '10'
            ],
            // Isian Singkat
            [
                'isian_singkat',
                'Ibu kota negara Indonesia saat ini adalah...',
                '',
                '',
                '',
                '',
                '',
                '',
                'jakarta, dki jakarta',
                '10'
            ],
            // Checklist
            [
                'checklist',
                'Pilih kota-kota yang terletak di Pulau Jawa! (Jawaban bisa lebih dari satu)',
                'Jakarta',
                'Medan',
                'Surabaya',
                'Makassar',
                'Bandung',
                '',
                'A,C,E',
                '15'
            ],
            // Benar Salah
            [
                'benar_salah',
                'Tentukan BENAR (B) atau SALAH (S) untuk setiap pernyataan berikut!',
                '1. Pancasila terdiri dari 5 sila',
                '2. Proklamasi kemerdekaan RI dilakukan tahun 1950',
                '3. Bendera RI berwarna merah putih',
                '',
                '',
                '',
                'B,S,B',
                '20'
            ],
            // Penjodohan
            [
                'penjodohan',
                'Jodohkan ibukota negara berikut dengan negaranya yang sesuai!',
                '1. Indonesia',
                '2. Jepang',
                '3. Perancis',
                '',
                '',
                'Jakarta, Tokyo, Paris',
                '1-A,2-B,3-C',
                '20'
            ],
        ];

        // Construct XML for document.xml
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
            '<w:body>' .
            '<w:p>' .
            '<w:r>' .
            '<w:rPr><w:b/><w:sz w:val="28"/></w:rPr>' .
            '<w:t>TEMPLATE IMPORT SOAL CBT - PORTAL SMA</w:t>' .
            '</w:r>' .
            '</w:p>' .
            '<w:p>' .
            '<w:r>' .
            '<w:t>Silakan isi tabel di bawah ini untuk mengunggah soal secara massal. Jangan mengubah kolom header.</w:t>' .
            '</w:r>' .
            '</w:p>' .
            '<w:tbl>' .
            '<w:tblPr>' .
            '<w:tblBorders>' .
            '<w:top w:val="single" w:sz="4" w:space="0" w:color="auto"/>' .
            '<w:left w:val="single" w:sz="4" w:space="0" w:color="auto"/>' .
            '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="auto"/>' .
            '<w:right w:val="single" w:sz="4" w:space="0" w:color="auto"/>' .
            '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="auto"/>' .
            '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="auto"/>' .
            '</w:tblBorders>' .
            '</w:tblPr>';

        // Append header row
        $xml .= '<w:tr>';
        foreach ($headers as $header) {
            $xml .= '<w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>' . htmlspecialchars($header) . '</w:t></w:r></w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        // Append data rows
        foreach ($rows as $row) {
            $xml .= '<w:tr>';
            foreach ($row as $cell) {
                $xml .= '<w:tc><w:p><w:r><w:t>' . htmlspecialchars($cell) . '</w:t></w:r></w:p></w:tc>';
            }
            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>' .
            '</w:body>' .
            '</w:document>';

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return true;
    }
}
