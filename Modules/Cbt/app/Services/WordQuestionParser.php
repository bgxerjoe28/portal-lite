<?php

namespace Modules\Cbt\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Cbt\Models\CbtQuestion;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WordQuestionParser
{
    public static function import(int $cbtBankId, string $docxPath, string $xlsxPath): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($docxPath) !== true) {
            throw new \Exception('Gagal membuka dokumen Word (.docx). File mungkin rusak.');
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xmlContent) {
            throw new \Exception('Struktur dokumen Word (.docx) tidak valid.');
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadXML($xmlContent);
        libxml_clear_errors();

        $tables = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tbl');
        if ($tables->length === 0) {
            throw new \Exception('Tidak ada tabel soal ditemukan di dokumen Word.');
        }

        // Ambil tabel pertama (tabel soal utama)
        $table = $tables->item(0);
        $xmlRows = $table->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tr');

        // Deteksi format: Legacy (multi-kolom dengan Tipe Soal) atau New (format TemplateSoal.docx)
        $firstXmlRow = $xmlRows->item(0);
        $firstRowCells = $firstXmlRow->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tc');

        $isLegacyFormat = false;
        foreach ($firstRowCells as $cell) {
            $cellText = strtolower(trim($cell->textContent));
            if (str_contains($cellText, 'tipe soal') || str_contains($cellText, 'tipe_soal')) {
                $isLegacyFormat = true;
                break;
            }
        }

        if ($isLegacyFormat) {
            self::importLegacy($cbtBankId, $xmlRows);
        } else {
            self::importNewFormat($cbtBankId, $table, $docxPath, $xlsxPath);
        }
    }

    private static function importLegacy(int $cbtBankId, $xmlRows): void
    {
        $rows = [];
        foreach ($xmlRows as $xmlRow) {
            $cells = $xmlRow->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tc');
            $rowData = [];
            foreach ($cells as $cell) {
                $text = '';
                $pElements = $cell->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p');
                foreach ($pElements as $p) {
                    $rElements = $p->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'r');
                    foreach ($rElements as $r) {
                        $tElements = $r->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't');
                        foreach ($tElements as $t) {
                            $text .= $t->nodeValue;
                        }
                    }
                    $text .= "\n";
                }
                $rowData[] = trim($text);
            }
            $rows[] = $rowData;
        }

        if (empty($rows)) {
            throw new \Exception('Tabel soal di dokumen Word kosong.');
        }

        // Ambil header row
        $headerRow = array_shift($rows);
        $mappedHeaders = [];
        foreach ($headerRow as $index => $cellText) {
            $clean = strtolower(trim($cellText));
            $clean = str_replace(' ', '_', $clean);

            if (str_contains($clean, 'tipe')) {
                $mappedHeaders[$index] = 'tipe_soal';
            } elseif (str_contains($clean, 'tanya') || str_contains($clean, 'soal')) {
                $mappedHeaders[$index] = 'pertanyaan';
            } elseif ($clean === 'opsi_a' || $clean === 'a' || $clean === 'opsia') {
                $mappedHeaders[$index] = 'opsi_a';
            } elseif ($clean === 'opsi_b' || $clean === 'b' || $clean === 'opsib') {
                $mappedHeaders[$index] = 'opsi_b';
            } elseif ($clean === 'opsi_c' || $clean === 'c' || $clean === 'opsic') {
                $mappedHeaders[$index] = 'opsi_c';
            } elseif ($clean === 'opsi_d' || $clean === 'd' || $clean === 'opsid') {
                $mappedHeaders[$index] = 'opsi_d';
            } elseif ($clean === 'opsi_e' || $clean === 'e' || $clean === 'opsie') {
                $mappedHeaders[$index] = 'opsi_e';
            } elseif (str_contains($clean, 'target') || str_contains($clean, 'jodoh')) {
                $mappedHeaders[$index] = 'target_penjodohan';
            } elseif (str_contains($clean, 'kunci') || str_contains($clean, 'jawaban')) {
                $mappedHeaders[$index] = 'kunci_jawaban';
            } elseif (str_contains($clean, 'bobot') || str_contains($clean, 'skor') || str_contains($clean, 'nilai')) {
                $mappedHeaders[$index] = 'bobot';
            } else {
                $mappedHeaders[$index] = $clean;
            }
        }

        // Pastikan header minimum terpenuhi
        if (! in_array('tipe_soal', $mappedHeaders) || ! in_array('pertanyaan', $mappedHeaders)) {
            throw new \Exception("Header kolom tabel Word harus memiliki 'Tipe Soal' dan 'Pertanyaan'.");
        }

        CbtQuestion::where('cbt_bank_id', $cbtBankId)->delete();

        foreach ($rows as $rowData) {
            // Gabungkan header dengan data baris
            $row = [];
            foreach ($mappedHeaders as $index => $headerName) {
                $row[$headerName] = $rowData[$index] ?? '';
            }

            $questionType = strtolower(trim($row['tipe_soal'] ?? ''));
            $questionText = trim($row['pertanyaan'] ?? '');

            if (empty($questionType) || empty($questionText)) {
                continue;
            }

            $opsiA = isset($row['opsi_a']) ? trim($row['opsi_a']) : null;
            $opsiB = isset($row['opsi_b']) ? trim($row['opsi_b']) : null;
            $opsiC = isset($row['opsi_c']) ? trim($row['opsi_c']) : null;
            $opsiD = isset($row['opsi_d']) ? trim($row['opsi_d']) : null;
            $opsiE = isset($row['opsi_e']) ? trim($row['opsi_e']) : null;

            $options = null;
            $correctAnswer = null;
            $score = isset($row['bobot']) && is_numeric($row['bobot']) ? (float) $row['bobot'] : 1.00;
            $kunciRaw = isset($row['kunci_jawaban']) ? trim($row['kunci_jawaban']) : '';

            switch ($questionType) {
                case 'pilihan_ganda':
                case 'survey':
                    $options = array_filter([
                        'A' => $opsiA,
                        'B' => $opsiB,
                        'C' => $opsiC,
                        'D' => $opsiD,
                        'E' => $opsiE,
                    ]);
                    $correctAnswer = $questionType === 'survey' ? null : strtoupper($kunciRaw);
                    break;

                case 'isian_singkat':
                    $options = null;
                    $correctAnswer = array_map(fn ($item) => strtolower(trim($item)), explode(',', $kunciRaw));
                    break;

                case 'uraian':
                    $options = null;
                    $correctAnswer = empty($kunciRaw) ? null : [$kunciRaw];
                    break;

                case 'list':
                case 'checklist':
                    $options = array_filter([
                        'A' => $opsiA,
                        'B' => $opsiB,
                        'C' => $opsiC,
                        'D' => $opsiD,
                        'E' => $opsiE,
                    ]);
                    $correctAnswer = array_map(fn ($item) => strtoupper(trim($item)), explode(',', $kunciRaw));
                    break;

                case 'benar_salah':
                    $statements = array_filter([
                        '1' => $opsiA,
                        '2' => $opsiB,
                        '3' => $opsiC,
                        '4' => $opsiD,
                        '5' => $opsiE,
                    ]);
                    $options = ['statements' => $statements];
                    $answers = array_map(fn ($item) => strtoupper(trim($item)), explode(',', $kunciRaw));
                    $correctMap = [];
                    foreach (array_keys($statements) as $idx => $key) {
                        $correctMap[$key] = isset($answers[$idx]) ? $answers[$idx] : 'S';
                    }
                    $correctAnswer = $correctMap;
                    break;

                case 'penjodohan':
                    $premises = array_filter([
                        '1' => $opsiA,
                        '2' => $opsiB,
                        '3' => $opsiC,
                        '4' => $opsiD,
                        '5' => $opsiE,
                    ]);

                    $targetsRaw = isset($row['target_penjodohan']) ? trim($row['target_penjodohan']) : '';
                    $targetsArr = array_map('trim', explode(',', $targetsRaw));
                    $targets = [];
                    foreach ($targetsArr as $idx => $t) {
                        if (! empty($t)) {
                            $charKey = chr(65 + $idx); // A, B, C...
                            $targets[$charKey] = $t;
                        }
                    }

                    $options = [
                        'premises' => $premises,
                        'targets' => $targets,
                    ];

                    $matches = array_map('trim', explode(',', $kunciRaw));
                    $correctMap = [];
                    foreach ($matches as $match) {
                        $parts = explode('-', $match);
                        if (count($parts) === 2) {
                            $correctMap[trim($parts[0])] = strtoupper(trim($parts[1]));
                        }
                    }
                    $correctAnswer = $correctMap;
                    break;

                case 'skor_berbeda':
                    $options = array_filter([
                        'A' => $opsiA,
                        'B' => $opsiB,
                        'C' => $opsiC,
                        'D' => $opsiD,
                        'E' => $opsiE,
                    ]);
                    $scores = array_map('trim', explode(',', $kunciRaw));
                    $correctMap = [];
                    foreach ($scores as $s) {
                        $parts = explode(':', $s);
                        if (count($parts) === 2) {
                            $correctMap[strtoupper(trim($parts[0]))] = (float) trim($parts[1]);
                        }
                    }
                    $correctAnswer = $correctMap;
                    break;

                case 'sorting':
                    $items = array_filter([
                        '1' => $opsiA,
                        '2' => $opsiB,
                        '3' => $opsiC,
                        '4' => $opsiD,
                        '5' => $opsiE,
                    ]);
                    $options = ['items' => $items];
                    $correctAnswer = array_map('trim', explode(',', $kunciRaw));
                    break;
            }

            CbtQuestion::create([
                'cbt_bank_id' => $cbtBankId,
                'question_type' => $questionType,
                'question_text' => $questionText,
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'score' => $score,
            ]);
        }
    }

    private static function importNewFormat(int $cbtBankId, $table, string $docxPath, string $xlsxPath): void
    {
        // 1. Validasi file kunci
        if (! file_exists($xlsxPath)) {
            throw new \Exception('File Kunci Jawaban (.xlsx) tidak ditemukan.');
        }

        // 2. Folder media tujuan & disk storage
        $disk = Storage::disk(config('filesystems.cbt_disk', 's3_cbt'));
        $mediaDest = storage_path('app/public/cbt_questions');
        if (! file_exists($mediaDest)) {
            mkdir($mediaDest, 0777, true);
        }
        // Bersihkan media lama dari bank soal ini di folder lokal
        foreach (glob("{$mediaDest}/*bank_{$cbtBankId}_*") ?: [] as $oldMediaFile) {
            @unlink($oldMediaFile);
        }

        // 3. Baca rId -> target gambar dari rels (dipetakan dulu sebelum extract)
        $relsMap = [];          // relId => nama file final di $mediaDest
        $relIdToMediaPath = []; // relId => 'media/image1.png' (dipakai saat extract)
        $zip = new \ZipArchive;
        if ($zip->open($docxPath) === true) {
            $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
            $zip->close();
            if ($relsXml) {
                $domRels = new \DOMDocument;
                libxml_use_internal_errors(true);
                $domRels->loadXML($relsXml);
                libxml_clear_errors();
                foreach ($domRels->getElementsByTagName('Relationship') as $rel) {
                    /** @var \DOMElement $rel */
                    $relId = $rel->getAttribute('Id');
                    $target = $rel->getAttribute('Target');
                    if (str_contains($rel->getAttribute('Type'), 'image')) {
                        $baseName = basename($target);
                        $relIdToMediaPath[$relId] = 'media/'.$baseName;

                        // Ekstensi file yang dibersihkan (default: png)
                        $ext = strtolower(pathinfo($baseName, PATHINFO_EXTENSION)) ?: 'png';
                        // Generate Hash MD5 Unik & Anti-Tebak: cbt_bank_{bankId}_{hash_32char}.{ext}
                        $hash = md5("bank_{$cbtBankId}_{$relId}_{$baseName}_" . microtime(true) . '_' . uniqid());
                        $relsMap[$relId] = "cbt_bank_{$cbtBankId}_{$hash}.{$ext}";
                    }
                }
            }
        }

        // 4. Ekstrak gambar — loop berdasarkan RELS ($relsMap), bukan isi zip.
        //    Simpan ke Storage disk (Support MinIO/S3 dan Local Storage)
        $zip = new \ZipArchive;
        if ($zip->open($docxPath) === true) {
            foreach ($relsMap as $relId => $newName) {
                $mediaPath = $relIdToMediaPath[$relId] ?? null;
                if ($mediaPath && $zip->locateName("word/{$mediaPath}") !== false) {
                    $stream = $zip->getStream("word/{$mediaPath}");
                    if ($stream) {
                        $contents = stream_get_contents($stream);
                        fclose($stream);

                        // Simpan ke Storage disk aktif (S3 / MinIO / Local)
                        // Visibility 'public' diset agar kompatibel dengan MinIO ACL jika dikonfigurasi.
                        // Tampilan gambar tetap melalui proxy route (CbtImageController) agar tidak 403.
                        $disk->put("cbt_questions/{$newName}", $contents, 'public');

                        // Juga simpan ke folder fisik lokal storage/app/public/cbt_questions sebagai fallback
                        file_put_contents("{$mediaDest}/{$newName}", $contents);
                    }
                }
            }
            $zip->close();
        }

        // 5. Baca Kunci Jawaban, Skor, Grouping, Lock N dari Excel
        // Kolom A: No. Soal (angka biasa = soal, angka >= 1000 = sub-ID, "X" kunci = soal punya sub-item)
        // Kolom B: Kunci Jawaban (A/B/C/D, X=punya sub-item, -CHECK, dll)
        // Kolom C: Skor
        // Kolom D: Grouping (angka grup: soal-soal dalam grup yang sama tetap berurutan saat shuffle)
        // Kolom E: Lock N (L = kunci posisi, tidak ikut diacak)
        $keyMap = [];
        try {
            $spreadsheet = IOFactory::load($xlsxPath);
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $data = $sheet->rangeToArray("A1:{$highestCol}{$highestRow}", null, true, true, true);

            foreach ($data as $row) {
                $idRaw = trim((string) ($row['A'] ?? ''));
                $kunci = trim((string) ($row['B'] ?? ''));
                $skor = (float) ($row['C'] ?? 0.00);
                $grouping = trim((string) ($row['D'] ?? ''));
                $lockN = strtoupper(trim((string) ($row['E'] ?? ''))) === 'L';

                if ($idRaw !== '' && strtolower($idRaw) !== 'no. soal' && strtolower($idRaw) !== 'no soal') {
                    $keyMap[$idRaw] = [
                        'kunci' => $kunci,
                        'skor' => $skor,
                        'grouping' => $grouping !== '' ? $grouping : null,
                        'lock_n' => $lockN,
                    ];
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Gagal membaca File Kunci (.xlsx): '.$e->getMessage());
        }

        // ── Baca word/numbering.xml untuk mendapatkan startVal dari setiap numId ──
        // Diperlukan ketika nomor soal menggunakan auto-numbering Word (List/numPr),
        // di mana angkanya tidak tersimpan sebagai teks di XML melainkan di-generate otomatis.
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $numStartVals = []; // numId => startVal
        $zip3 = new \ZipArchive;
        if ($zip3->open($docxPath) === true) {
            $numXml = $zip3->getFromName('word/numbering.xml');
            $zip3->close();
            if ($numXml) {
                $domNum = new \DOMDocument;
                libxml_use_internal_errors(true);
                $domNum->loadXML($numXml);
                libxml_clear_errors();

                // Step 1: abstractNumId → startVal (level 0)
                $abstractStartVals = [];
                foreach ($domNum->getElementsByTagNameNS($ns, 'abstractNum') as $absNum) {
                    /** @var \DOMElement $absNum */
                    $absId = $absNum->getAttributeNS($ns, 'abstractNumId');
                    foreach ($absNum->getElementsByTagNameNS($ns, 'lvl') as $lvl) {
                        if ($lvl->getAttributeNS($ns, 'ilvl') === '0') {
                            foreach ($lvl->getElementsByTagNameNS($ns, 'start') as $startEl) {
                                $abstractStartVals[$absId] = (int) $startEl->getAttributeNS($ns, 'val');
                                break;
                            }
                            break;
                        }
                    }
                }
                // Step 2: numId → abstractNumId → startVal
                foreach ($domNum->getElementsByTagNameNS($ns, 'num') as $num) {
                    /** @var \DOMElement $num */
                    $numId = $num->getAttributeNS($ns, 'numId');
                    foreach ($num->getElementsByTagNameNS($ns, 'abstractNumId') as $absRef) {
                        $absId = $absRef->getAttributeNS($ns, 'val');
                        $numStartVals[$numId] = $abstractStartVals[$absId] ?? 1;
                        break;
                    }
                    if (! isset($numStartVals[$numId])) {
                        $numStartVals[$numId] = 1; // Default: mulai dari 1
                    }
                }
            }
        }
        $numCounters = []; // numId => sudah terpakai berapa kali (untuk increment)

        $rows = $table->getElementsByTagNameNS($ns, 'tr');

        // Filter hanya top-level rows (bukan row dari nested tabel di dalam sel)
        $topLevelRows = [];
        foreach ($rows as $row) {
            if ($row->parentNode === $table) {
                $topLevelRows[] = $row;
            }
        }

        // Kelompokkan baris berdasarkan nomor soal (kolom pertama berisi angka bulat)
        $soalGroups = [];
        $currentNo = null;
        $currentGroup = [];

        foreach ($topLevelRows as $row) {
            $cols = [];
            foreach ($row->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tc') {
                    $cols[] = $child;
                }
            }

            if (count($cols) >= 1) {
                $firstText = trim($cols[0]->textContent);

                // Cek 1: nomor soal ditulis manual (e.g. "1", "2.", "10.")
                $cleanNum = rtrim($firstText, '.');
                if ($firstText !== '' && is_numeric($cleanNum) && floor((float) $cleanNum) == (float) $cleanNum && (int) $cleanNum >= 1) {
                    if ($currentNo !== null) {
                        $soalGroups[$currentNo] = $currentGroup;
                    }
                    $currentNo = (int) $cleanNum;
                    $currentGroup = [];
                }
                // Cek 2: nomor soal menggunakan auto-numbering Word (numPr di dalam col0)
                elseif ($firstText === '') {
                    $numPrNodes = $cols[0]->getElementsByTagNameNS($ns, 'numPr');
                    if ($numPrNodes->length > 0) {
                        $numPr = $numPrNodes->item(0);
                        $numIdNodes = $numPr->getElementsByTagNameNS($ns, 'numId');
                        if ($numIdNodes->length > 0) {
                            $numIdVal = $numIdNodes->item(0)->getAttributeNS($ns, 'val');
                            $startVal = $numStartVals[$numIdVal] ?? 1;
                            $counter = $numCounters[$numIdVal] ?? 0;
                            $autoNum = $startVal + $counter;
                            $numCounters[$numIdVal] = $counter + 1;

                            if ($currentNo !== null) {
                                $soalGroups[$currentNo] = $currentGroup;
                            }
                            $currentNo = $autoNum;
                            $currentGroup = [];
                        }
                    }
                }
            }

            if ($currentNo !== null) {
                $currentGroup[] = $row;
            }
        }
        if ($currentNo !== null) {
            $soalGroups[$currentNo] = $currentGroup;
        }

        if (empty($soalGroups)) {
            throw new \Exception('Tidak ada soal yang ditemukan di dokumen Word. Pastikan kolom pertama berisi nomor soal.');
        }
        // ==============================================================
        // 6. VALIDASI KECOCOKAN antara File Soal dan File Kunci
        // ==============================================================
        $soalNumbers = array_keys($soalGroups); // [1, 2, 3, ...]
        $maxSoal = max($soalNumbers);

        // Pisahkan keyMap:
        // - soal biasa (angka < 1000): termasuk yang kunci='X' (soal dengan sub-item)
        //   Keduanya dihitung dalam max soal, tapi 'X' tidak dipakai sebagai single kunci
        // - sub-ID (angka >= 1000): kunci per sub-item
        $kunciSoalNumbers = []; // SEMUA nomor soal (termasuk yang kunci='X')
        $kunciSubIds = []; // sub-ID (>= 1000)

        foreach ($keyMap as $kid => $kval) {
            if (! is_numeric($kid)) {
                continue;
            }
            $numKid = (int) $kid;
            if ($numKid >= 1000) {
                // Sub-ID: parent = floor(numKid / 1000)
                $kunciSubIds[] = (string) $kid;
            } else {
                // Semua nomor soal dimasukkan — baik kunci biasa maupun kunci='X'
                $kunciSoalNumbers[] = $numKid;
            }
        }
        sort($kunciSoalNumbers);
        sort($soalNumbers);

        // Kumpulkan semua sub-ID dari shortcodes dalam teks soal Word
        $soalSubIds = [];
        foreach ($soalGroups as $groupRows) {
            foreach ($groupRows as $gRow) {
                $rawText = $gRow->textContent;
                preg_match_all('/\bno=(\d{4,})/', $rawText, $mIds); // >= 4 digit = sub-ID
                foreach ($mIds[1] as $sid) {
                    $soalSubIds[] = (string) $sid;
                }
            }
        }
        $soalSubIds = array_unique($soalSubIds);

        // Validasi: jumlah soal biasa harus cocok
        if (! empty($kunciSoalNumbers)) {
            $expectedMax = max($kunciSoalNumbers);
            if ($maxSoal !== $expectedMax) {
                throw new \Exception(
                    'Jumlah soal tidak cocok antara File Soal dan File Kunci. '.
                    "File Soal memiliki soal nomor 1–{$maxSoal}, ".
                    "sedangkan File Kunci berisi hingga nomor {$expectedMax}. ".
                    'Pastikan jumlah nomor soal di kedua file sama.'
                );
            }
        }

        // Validasi: sub-ID di soal Word harus ada di kunci Excel
        if (! empty($soalSubIds)) {
            $kunciSubIds = array_unique($kunciSubIds);
            $missingInKunci = array_diff($soalSubIds, $kunciSubIds);
            if (! empty($missingInKunci)) {
                $missing = implode(', ', array_slice($missingInKunci, 0, 5));
                throw new \Exception(
                    "Sub-ID berikut ada di File Soal tapi tidak ada di File Kunci: {$missing}. ".
                    'Pastikan kolom A File Kunci memuat semua kode sub-soal yang dipakai.'
                );
            }
        }

        // Bersihkan soal lama di bank ini
        CbtQuestion::where('cbt_bank_id', $cbtBankId)->delete();

        $maxQuestions = $maxSoal;

        // 7. Proses masing-masing soal 1 - $maxQuestions
        for ($n = 1; $n <= $maxQuestions; $n++) {
            if (! isset($soalGroups[$n])) {
                continue;
            }

            $groupRows = $soalGroups[$n];

            // Baris pertama kelompok adalah baris pertanyaan
            $firstRow = $groupRows[0];
            $firstCols = [];
            foreach ($firstRow->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tc') {
                    $firstCols[] = $child;
                }
            }

            $questionHtmlRaw = self::getHtmlFromNode($firstCols[1], $relsMap);

            // Gabungkan semua teks dari grup untuk mendeteksi tipe soal
            $allText = '';
            foreach ($groupRows as $row) {
                $allText .= $row->textContent.' ';
            }

            // Deteksi tipe soal berdasarkan shortcode
            // PRIORITAS 1: cek teks soal utama (questionHtmlRaw / firstCols[1])
            // Shortcode inline di soal utama menentukan tipe, walau ada [radio2] di baris opsi
            $questionType = 'pilihan_ganda'; // Default

            $questionRawText = strip_tags($questionHtmlRaw); // teks bersih dari sel soal utama

            if (str_contains($questionRawText, '[list no=') && str_contains($questionRawText, 'pilihan=')) {
                // [list no=14001 pilihan="1;2;3;4"] di soal utama → select dropdown inline
                $questionType = 'isian_singkat';
            } elseif (str_contains($questionRawText, '[isiansingkat')) {
                $questionType = 'isian_singkat';
            } elseif (str_contains($questionRawText, '[isian') && ! str_contains($questionRawText, '[isiansingkat')) {
                $questionType = 'uraian';
            } elseif (preg_match('/\[opsi\s+no=\d+\s+pg=[A-Z]\]/i', $questionRawText)) {
                // [opsi no=2001 pg=A/B/S] di dalam tabel soal (Soal Radio Dinamis / Pilihan Berkolom)
                $questionType = 'uraian';
            }
            // PRIORITAS 2: jika teks soal utama tidak punya shortcode inline, cek semua baris
            elseif (str_contains($allText, '[radio2')) {
                $questionType = 'penjodohan';
            } elseif (str_contains($allText, '[opsi') && ! str_contains($allText, 'pg=CHECK')) {
                $questionType = 'benar_salah';
            } elseif (str_contains($allText, 'pg=CHECK') || str_contains($allText, '[checklist')) {
                $questionType = 'checklist';
            }

            $options = null;
            $correctAnswer = null;
            $score = 2.00;

            $optionRows = array_slice($groupRows, 1);

            if ($questionType === 'pilihan_ganda') {
                $options = [];
                foreach ($optionRows as $row) {
                    $cols = [];
                    foreach ($row->childNodes as $child) {
                        if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tc') {
                            $cols[] = $child;
                        }
                    }
                    if (count($cols) === 3) {
                        $optLetter = trim($cols[1]->textContent);
                        if (in_array($optLetter, ['A', 'B', 'C', 'D', 'E'])) {
                            $options[$optLetter] = trim(self::getHtmlFromNode($cols[2], $relsMap));
                        }
                    }
                }

                foreach ($options as $k => $v) {
                    $options[$k] = trim($v);
                }

                $correctAnswer = $keyMap[$n]['kunci'] ?? 'A';
                $score = $keyMap[$n]['skor'] ?? 2.00;

                $questionHtml = preg_replace('/\[radio\s+no=\d+\]/', '', $questionHtmlRaw);
            } elseif ($questionType === 'benar_salah') {
                $statements = [];
                $correctAnswer = [];
                $score = 0;

                foreach ($optionRows as $row) {
                    $cols = [];
                    foreach ($row->childNodes as $child) {
                        if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tc') {
                            $cols[] = $child;
                        }
                    }
                    if (count($cols) == 2) {
                        $cell1Text = trim($cols[0]->textContent);
                        if (preg_match('/\[opsi\s+no=(\d+)\]/', $cell1Text, $m) || preg_match('/\[opsi\s+no=(\d+)\s+pg=[BS]\]/', $cell1Text, $m)) {
                            $id = $m[1];
                            $stmtText = trim(self::getHtmlFromNode($cols[1], $relsMap));
                            $statements[$id] = $stmtText;

                            $correctAnswer[$id] = $keyMap[$id]['kunci'] ?? 'S';
                            $score += $keyMap[$id]['skor'] ?? 2.00;
                        }
                    }
                }

                $options = ['statements' => $statements];

                if (empty($statements)) {
                    // Tidak ada pernyataan yang ditemukan dari shortcode [opsi no=...]
                    // Coba baca dari baris opsi dengan 2 kolom teks
                    foreach ($optionRows as $oRow) {
                        $oCols = [];
                        foreach ($oRow->childNodes as $ch) {
                            if ($ch->nodeType === XML_ELEMENT_NODE && $ch->localName === 'tc') {
                                $oCols[] = $ch;
                            }
                        }
                        if (count($oCols) >= 2) {
                            $stmtText = trim(self::getHtmlFromNode($oCols[1], $relsMap));
                            if (! empty($stmtText)) {
                                $idKey = (string) ($n * 1000 + count($statements) + 1);
                                $statements[$idKey] = $stmtText;
                                $correctAnswer[$idKey] = $keyMap[$idKey]['kunci'] ?? 'S';
                                $score += $keyMap[$idKey]['skor'] ?? 2.00;
                            }
                        }
                    }
                    $options = ['statements' => $statements];
                }

                $questionHtml = 'Tentukan pernyataan berikut benar atau salah dengan memilih opsi yang disediakan, lalu klik simpan';
            } elseif ($questionType === 'penjodohan') {
                $premises = [];
                $targets = [];
                $correctAnswer = [];
                $score = 0;

                foreach ($optionRows as $row) {
                    $cols = [];
                    foreach ($row->childNodes as $child) {
                        if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tc') {
                            $cols[] = $child;
                        }
                    }
                    if (count($cols) == 2) {
                        $cell1Text = trim($cols[0]->textContent);
                        $cell2Text = trim($cols[1]->textContent);

                        if (preg_match('/\[radio\s+no=(\d+)\]/i', $cell2Text, $m)) {
                            $id = $m[1];
                            $premHtml = trim(self::getHtmlFromNode($cols[0], $relsMap));
                            $premises[$id] = $premHtml;

                            $correctAnswer[$id] = $keyMap[$id]['kunci'] ?? 'A';
                            $score += $keyMap[$id]['skor'] ?? 2.00;
                        } elseif (preg_match('/\[radio\s+no=(\d+)\]/i', $cell1Text, $m)) {
                            $id = $m[1];
                            $premHtml = trim(self::getHtmlFromNode($cols[1], $relsMap));
                            $premises[$id] = $premHtml;

                            $correctAnswer[$id] = $keyMap[$id]['kunci'] ?? 'A';
                            $score += $keyMap[$id]['skor'] ?? 2.00;
                        } elseif (preg_match('/\[radio2\s+opsi=([a-zA-Z])\]/i', $cell1Text, $m)) {
                            $letter = strtoupper($m[1]);
                            $targetHtml = trim(self::getHtmlFromNode($cols[1], $relsMap));
                            $targets[$letter] = $targetHtml;
                        } elseif (preg_match('/\[radio2\s+opsi=([a-zA-Z])\]/i', $cell2Text, $m)) {
                            $letter = strtoupper($m[1]);
                            $targetHtml = trim(self::getHtmlFromNode($cols[0], $relsMap));
                            $targets[$letter] = $targetHtml;
                        }
                    }
                }

                if (empty($premises)) {
                    // Fallback: cari tabel penjodohan yang di-nest di dalam teks soal (misalnya 2 tabel berdampingan)
                    $dom = new \DOMDocument;
                    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$questionHtmlRaw, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    $trNodes = $dom->getElementsByTagName('tr');

                    foreach ($trNodes as $tr) {
                        /** @var \DOMElement $tr */
                        $tdNodes = $tr->getElementsByTagName('td');
                        if ($tdNodes->length >= 2) {
                            $c1Html = '';
                            foreach ($tdNodes->item(0)->childNodes as $child) {
                                $c1Html .= $dom->saveHTML($child);
                            }
                            $c2Html = '';
                            foreach ($tdNodes->item(1)->childNodes as $child) {
                                $c2Html .= $dom->saveHTML($child);
                            }

                            $c1Text = trim($tdNodes->item(0)->textContent);
                            $c2Text = trim($tdNodes->item(1)->textContent);

                            if (preg_match('/\[radio\s+no=(\d+)\]/i', $c2Text, $m)) {
                                $id = $m[1];
                                $premises[$id] = trim($c1Html);
                                $correctAnswer[$id] = $keyMap[$id]['kunci'] ?? 'A';
                                $score += $keyMap[$id]['skor'] ?? 2.00;
                            } elseif (preg_match('/\[radio\s+no=(\d+)\]/i', $c1Text, $m)) {
                                $id = $m[1];
                                $premises[$id] = trim($c2Html);
                                $correctAnswer[$id] = $keyMap[$id]['kunci'] ?? 'A';
                                $score += $keyMap[$id]['skor'] ?? 2.00;
                            } elseif (preg_match('/\[radio2\s+opsi=([a-zA-Z])\]/i', $c1Text, $m)) {
                                $letter = strtoupper($m[1]);
                                $targets[$letter] = trim($c2Html);
                            } elseif (preg_match('/\[radio2\s+opsi=([a-zA-Z])\]/i', $c2Text, $m)) {
                                $letter = strtoupper($m[1]);
                                $targets[$letter] = trim($c1Html);
                            }
                        }
                    }
                }

                if (empty($premises)) {
                    // Jika tetap tidak ada premis yang ditemukan, skip soal ini
                    continue;
                }

                $options = [
                    'premises' => $premises,
                    'targets' => $targets,
                ];

                $questionHtml = 'Soal Penjodohan. Silakan jodohkan premis di sebelah kiri dengan target gambar di sebelah kanan secara tepat.';
            } elseif ($questionType === 'checklist') {
                preg_match_all('/\[(?:checklist|list)\s+no=(\d+)/', $questionHtmlRaw, $matches);
                if (! empty($matches[1])) {
                    $options = null;
                    $correctAnswer = [];
                    $score = 0;
                    $ids = $matches[1];
                    foreach ($ids as $id) {
                        $kunci = $keyMap[$id]['kunci'] ?? '-CHECK';
                        $correctAnswer[(string) $id] = $kunci;
                        $score += $keyMap[$id]['skor'] ?? 2.00;
                    }

                    $questionHtml = preg_replace_callback('/\[(?:checklist|list)\s+no=(\d+)\s+pg=CHECK\]/', function ($m) {
                        $id = $m[1];

                        return '<div class="flex justify-content-center align-items-center py-1"><input type="checkbox" class="cbt-dynamic-checkbox cursor-pointer" value="'.$id.'" data-id="'.$id.'" style="width: 22px; height: 22px;" /></div>';
                    }, $questionHtmlRaw);
                } else {
                    $options = [];
                    foreach ($optionRows as $row) {
                        $cols = [];
                        foreach ($row->childNodes as $child) {
                            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tc') {
                                $cols[] = $child;
                            }
                        }
                        if (count($cols) === 3) {
                            $optLetter = trim($cols[1]->textContent);
                            if (in_array($optLetter, ['A', 'B', 'C', 'D', 'E'])) {
                                $options[$optLetter] = trim(self::getHtmlFromNode($cols[2], $relsMap));
                            }
                        }
                    }

                    $kunciRaw = $keyMap[$n]['kunci'] ?? '';
                    $correctAnswer = array_filter(array_map('trim', explode(',', $kunciRaw)));
                    $score = $keyMap[$n]['skor'] ?? 2.00;

                    $questionHtml = $questionHtmlRaw;
                }
            } elseif ($questionType === 'isian_singkat') {
                $options = null;
                $correctAnswer = [];
                $score = 0;

                // Kumpulkan kunci untuk [isiansingkat no=...]
                preg_match_all('/\[isiansingkat\s+no=(\d+)/', $questionHtmlRaw, $matches);
                $uniqueIsian = array_unique($matches[1]);
                foreach ($uniqueIsian as $sid) {
                    $kunciRaw = $keyMap[$sid]['kunci'] ?? '';
                    $correctAnswer[(string) $sid] = array_map('trim', explode(',', $kunciRaw));
                    $score += $keyMap[$sid]['skor'] ?? 2.00;
                }

                // Kumpulkan kunci untuk [list no=... pilihan="..."]
                // Gunakan pola luwes untuk menangkap smart quotes atau &quot;
                preg_match_all('/\[list\s+no=(\d+)\s+pilihan=([^\]]+)\]/u', $questionHtmlRaw, $listMatches, PREG_SET_ORDER);
                $seenListIds = [];
                foreach ($listMatches as $lm) {
                    $sid = $lm[1];
                    if (! in_array($sid, $seenListIds) && ! in_array($sid, $uniqueIsian ?? [])) {
                        $seenListIds[] = $sid;
                        $kunciRaw = $keyMap[$sid]['kunci'] ?? '';
                        $correctAnswer[(string) $sid] = array_map('trim', explode(',', $kunciRaw));
                        $score += $keyMap[$sid]['skor'] ?? 2.00;
                    }
                }

                // Render [isiansingkat no=...] → <input text>
                $questionHtml = preg_replace_callback('/\[isiansingkat\s+no=(\d+)\]/', function ($m) {
                    return '<input type="text"'
                        .' class="cbt-dynamic-input inline-block border-round px-2 py-1 mx-1 text-center"'
                        .' data-id="'.$m[1].'"'
                        .' style="width:150px; background:#1e293b; color:#fff; border:1px solid #475569;"'
                        .' placeholder="..." />';
                }, $questionHtmlRaw);

                // Render [list no=14001 pilihan="1;2;3;4"] → <select>
                $questionHtml = preg_replace_callback(
                    '/\[list\s+no=(\d+)\s+pilihan=([^\]]+)\]/u',
                    function ($m) {
                        $sid = $m[1];
                        // Bersihkan tanda kutip biasa, smart quotes, dan HTML entities
                        $choicesStr = html_entity_decode(trim($m[2]), ENT_QUOTES);
                        $choicesStr = trim($choicesStr, " \t\n\r\0\x0B\"'”“”");
                        $choices = array_map('trim', explode(';', $choicesStr));

                        $opts = '<option value="">-- Pilih --</option>';
                        foreach ($choices as $choice) {
                            $opts .= '<option value="'.htmlspecialchars($choice).'">'
                                   .htmlspecialchars($choice).'</option>';
                        }

                        return '<select'
                            .' class="cbt-dynamic-input border-round px-2 py-1 mx-1 align-middle"'
                            .' data-id="'.$sid.'"'
                            .' style="min-width:120px; background:#1e293b; color:#fff; border:1px solid #475569;">'
                            .$opts
                            .'</select>';
                    },
                    $questionHtml
                );
            } elseif ($questionType === 'uraian') {
                $options = null;

                preg_match_all('/\[(?:isian|opsi)\s+no=(\d+)/', $questionHtmlRaw, $matches);
                if (! empty($matches[1])) {
                    $correctAnswer = [];
                    $score = 0;
                    $uniqueIds = array_unique($matches[1]);
                    foreach ($uniqueIds as $id) {
                        $correctAnswer[(string) $id] = $keyMap[$id]['kunci'] ?? '';
                        $score += $keyMap[$id]['skor'] ?? 2.00;
                    }

                    $questionHtml = preg_replace_callback('/\[isian\s+no=(\d+)\]/', function ($m) {
                        return '<textarea class="cbt-dynamic-input w-full p-2 bg-slate-950 text-white border border-slate-700 border-round mt-2" data-id="'.$m[1].'" rows="3" placeholder="Tulis jawaban..."></textarea>';
                    }, $questionHtmlRaw);

                    $questionHtml = preg_replace_callback('/\[opsi\s+no=(\d+)\s+pg=([A-Z])\]/', function ($m) {
                        $id = $m[1];
                        $val = $m[2];

                        return '<div class="flex justify-content-center align-items-center py-2"><input type="radio" class="cbt-dynamic-radio cursor-pointer" name="cbt_radio_'.$id.'" value="'.$val.'" data-id="'.$id.'" style="width: 22px; height: 22px;" /></div>';
                    }, $questionHtml);
                } else {
                    $correctAnswer = empty($keyMap[$n]['kunci']) ? null : [$keyMap[$n]['kunci']];
                    $score = $keyMap[$n]['skor'] ?? 4.00;
                    $questionHtml = $questionHtmlRaw;
                }
            }

            // ============================================================
            // POST-PROCESSING UNIVERSAL: render [list no=... pilihan="..."] → <select>
            // Dilakukan di sini agar bekerja untuk SEMUA tipe soal,
            // termasuk jika [list] muncul di soal yang tipenya penjodohan/uraian dll.
            // ============================================================
            if (isset($questionHtml) && str_contains($questionHtml, '[list no=')) {
                $questionHtml = preg_replace_callback(
                    '/\[list\s+no=(\d+)\s+pilihan=([^\]]+)\]/u',
                    function ($m) use ($keyMap, &$correctAnswer, &$score) {
                        $sid = $m[1];
                        // Bersihkan tanda kutip biasa, smart quotes, dan HTML entities
                        $choicesStr = html_entity_decode(trim($m[2]), ENT_QUOTES);
                        $choicesStr = trim($choicesStr, " \t\n\r\0\x0B\"'”“”");
                        $choices = array_map('trim', explode(';', $choicesStr));

                        // Tambahkan kunci ke correctAnswer jika belum ada
                        if (! isset($correctAnswer[$sid])) {
                            $kunciRaw = $keyMap[$sid]['kunci'] ?? '';
                            $correctAnswer[(string) $sid] = array_map('trim', explode(',', $kunciRaw));
                            $score += $keyMap[$sid]['skor'] ?? 2.00;
                        }

                        $opts = '<option value="">-- Pilih --</option>';
                        foreach ($choices as $choice) {
                            $opts .= '<option value="'.htmlspecialchars($choice).'">'
                                   .htmlspecialchars($choice).'</option>';
                        }

                        return '<select'
                            .' class="cbt-dynamic-input border-round px-2 py-1 mx-1 align-middle"'
                            .' data-id="'.$sid.'"'
                            .' style="min-width:120px; background:#1e293b; color:#fff; border:1px solid #475569;">'
                            .$opts
                            .'</select>';
                    },
                    $questionHtml
                );
            }

            $questionHtml = isset($questionHtml) ? trim($questionHtml) : trim($questionHtmlRaw);

            // ============================================================
            // PEMBACAAN SKOR STRICT BERDASARKAN KUNCI EXCEL
            // ============================================================
            $kunciUtama = $keyMap[(string) $n]['kunci'] ?? '';
            if (strtoupper(trim($kunciUtama)) === 'X') {
                // Soal memiliki sub-item, skor adalah akumulasi seluruh sub-item miliknya dari Kunci Excel
                $overrideScore = 0;
                foreach ($keyMap as $kid => $kval) {
                    if (! is_numeric($kid)) {
                        continue;
                    }
                    $numKid = (int) $kid;
                    if ($numKid >= 1000) {
                        $parentId = (int) floor($numKid / 1000);
                        if ($parentId === (int) $n) {
                            $overrideScore += (float) ($kval['skor'] ?? 0);
                        }
                    }
                }
                $score = $overrideScore;
            } else {
                // Soal biasa, skor diambil langsung dari baris induk kunci
                $score = (float) ($keyMap[(string) $n]['skor'] ?? 2.00);
            }

            // Ambil lock_n dan grouping dari keyMap (dari baris induk soal ke-$n)
            // Untuk soal dengan sub-item (kunci='X'), lock/grouping tetap diambil dari baris induk
            $lockN = $keyMap[(string) $n]['lock_n'] ?? false;
            $grouping = $keyMap[(string) $n]['grouping'] ?? null;

            CbtQuestion::create([
                'cbt_bank_id' => $cbtBankId,
                'question_type' => $questionType,
                'question_text' => $questionHtml,
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'score' => $score,
                'lock_n' => $lockN,
                'grouping' => $grouping,
            ]);
        }
    }

    private static function getHtmlFromNode($node, $relsMap)
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue);
        }

        if ($node->nodeType === XML_ELEMENT_NODE) {
            $localName = $node->localName;

            if ($localName === 'drawing') {
                $blips = $node->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'blip');
                if ($blips->length > 0) {
                    $rId = $blips->item(0)->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
                    if (isset($relsMap[$rId])) {
                        $fileName = $relsMap[$rId];

                        // Jika CDN aktif dan direct URL diaktifkan, gunakan URL CDN langsung
                        $cdnUrl = config('filesystems.disks.s3_cbt.url');
                        if ($cdnUrl && env('CBT_DIRECT_CDN_URL', false)) {
                            $imageUrl = rtrim($cdnUrl, '/') . '/cbt_questions/' . $fileName;
                        } else {
                            // Default: Gunakan route proxy Laravel dengan CDN Edge Caching
                            $imageUrl = route('cbt.questions.image', ['filename' => $fileName]);
                        }

                        return '<img src="'.$imageUrl.'" style="max-width: 100%; height: auto; display: block; margin: 10px 0;" />';
                    }
                }

                return '';
            }

            if ($localName === 'tbl') {
                // Render tabel Word → HTML table (termasuk nested tables dalam soal)
                $html = '<table style="border-collapse:collapse; border:1px solid #475569; width:100%; margin:8px 0;">';
                foreach ($node->childNodes as $tChild) {
                    if ($tChild->nodeType !== XML_ELEMENT_NODE) {
                        continue;
                    }
                    if ($tChild->localName === 'tr') {
                        $html .= '<tr>';
                        foreach ($tChild->childNodes as $tCell) {
                            if ($tCell->nodeType === XML_ELEMENT_NODE && $tCell->localName === 'tc') {
                                // Deteksi colspan dari gridSpan
                                $colspan = 1;
                                $tcPr = $tCell->getElementsByTagNameNS(
                                    'http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tcPr'
                                );
                                if ($tcPr->length > 0) {
                                    $gridSpan = $tcPr->item(0)->getElementsByTagNameNS(
                                        'http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'gridSpan'
                                    );
                                    if ($gridSpan->length > 0) {
                                        $colspan = (int) $gridSpan->item(0)->getAttribute(
                                            'http://schemas.openxmlformats.org/wordprocessingml/2006/main:val'
                                        ) ?: (int) $gridSpan->item(0)->getAttributeNS(
                                            'http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val'
                                        ) ?: 1;
                                    }
                                }
                                $colspanAttr = $colspan > 1 ? ' colspan="'.$colspan.'"' : '';
                                $html .= '<td'.$colspanAttr.' style="border:1px solid #475569; padding:6px 8px; vertical-align:top;">';
                                $html .= self::getHtmlFromNode($tCell, $relsMap);
                                $html .= '</td>';
                            }
                        }
                        $html .= '</tr>';
                    } elseif ($tChild->localName === 'tblPr' || $tChild->localName === 'tblGrid') {
                        // Skip table properties/grid nodes
                        continue;
                    }
                }
                $html .= '</table>';

                return $html;
            }

            if ($localName === 'p') {
                $html = '';
                foreach ($node->childNodes as $child) {
                    $html .= self::getHtmlFromNode($child, $relsMap);
                }
                if (empty(trim(strip_tags($html))) && ! str_contains($html, '<img')) {
                    return '';
                }

                return '<p class="mb-2">'.$html.'</p>';
            }

            if ($localName === 'r') {
                $html = '';
                foreach ($node->childNodes as $child) {
                    $html .= self::getHtmlFromNode($child, $relsMap);
                }

                return $html;
            }

            if ($localName === 't') {
                return htmlspecialchars($node->nodeValue);
            }

            if ($localName === 'br') {
                return '<br />';
            }

            $html = '';
            foreach ($node->childNodes as $child) {
                $html .= self::getHtmlFromNode($child, $relsMap);
            }

            return $html;
        }

        return '';
    }
}
