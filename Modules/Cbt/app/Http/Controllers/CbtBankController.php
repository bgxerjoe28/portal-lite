<?php

namespace Modules\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Cbt\Models\CbtBank;
use Modules\Cbt\Models\CbtExam;
use Modules\Cbt\Models\CbtQuestion;
use Modules\Cbt\Services\CbtGradingService;
use Modules\Cbt\Exports\QuestionTemplateExport;
use Modules\Cbt\Imports\QuestionImport;
use Modules\Cbt\Services\WordTemplateGenerator;
use Modules\Cbt\Services\WordQuestionParser;
use Modules\Akademik\Models\Subject;
use Modules\Cbt\Models\CbtCttExamSummary;
use Modules\Cbt\Models\CbtCttItemAnalysis;
use Modules\Cbt\Models\CbtIrtItemParameter;
use Modules\Cbt\Models\CbtAnalysisJob;
use Modules\Cbt\Models\CbtStudentExam;
use Modules\Cbt\Models\CbtStudentAnswer;
use Modules\Cbt\Models\CbtCttStudentResult;
use Modules\Cbt\Models\CbtIrtStudentAbility;
use Modules\Cbt\Services\CttAnalyticsService;
use Modules\Cbt\Services\IrtMicroserviceClient;

class CbtBankController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = CbtBank::with(['teacher', 'subject'])->withCount('questions')->withSum('questions', 'score');

        // Jika bukan admin, hanya bisa melihat bank soal miliknya sendiri
        if (!$user->hasRole('admin') && $user->hasRole('guru')) {
            $teacherId = $user->teacher?->id;
            $query->where('teacher_id', $teacherId);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        $banks = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $subjectsQuery = Subject::orderBy('name');
        
        if (!$user->hasRole('admin') && $user->hasRole('guru')) {
            $teacherId = $user->teacher?->id;
            $activeYear = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->first();
            $scheduleQuery = \Modules\Akademik\Models\Schedule::where('teacher_id', $teacherId);
            if ($activeYear) {
                $scheduleQuery->where('academic_year_id', $activeYear->id);
            }
            $subjectIds = $scheduleQuery->pluck('subject_id')->unique();
            $subjectsQuery->whereIn('id', $subjectIds);
        }
        
        $subjects = $subjectsQuery->get();

        return Inertia::render('Cbt/Bank/Index', [
            'banks' => $banks,
            'subjects' => $subjects,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'description' => 'nullable|string',
        ]);

        $user = Auth::user();
        
        // Jika pembuat adalah admin, biarkan teacher_id kosong (tidak terikat guru tertentu)
        if ($user->hasRole('admin')) {
            $teacherId = null;
        } else {
            $teacherId = $user->teacher?->id;
            // Jika bukan admin dan tidak memiliki teacherId, tolak
            if (!$teacherId) {
                return redirect()->back()->with('error', 'Akun Anda tidak terhubung dengan data Guru.');
            }
        }

        CbtBank::create([
            'name' => $request->name,
            'subject_id' => $request->subject_id,
            'teacher_id' => $teacherId, // bisa null untuk admin
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Bank Soal berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'description' => 'nullable|string',
        ]);

        $bank = CbtBank::findOrFail($id);
        $bank->update([
            'name' => $request->name,
            'subject_id' => $request->subject_id,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Bank Soal berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $bank = CbtBank::findOrFail($id);

        // Hapus semua file media soal dari storage sebelum hapus DB record
        $this->deleteBankMedia($bank->id);

        $bank->delete();

        return redirect()->back()->with('success', 'Bank Soal berhasil dihapus.');
    }

    public function questions($id)
    {
        $bank = CbtBank::with(['subject', 'teacher'])->findOrFail($id);
        $questions = CbtQuestion::where('cbt_bank_id', $id)->orderBy('id', 'asc')->get();

        return Inertia::render('Cbt/Bank/Questions', [
            'bank' => $bank,
            'questions' => $questions,
        ]);
    }

    public function downloadTemplate(Request $request)
    {
        $format = $request->query('format', 'excel');

        if ($format === 'word') {
            $path = base_path('lokals/TemplateSoal.docx');
            if (!file_exists($path)) {
                $path = base_path('TemplateSoal.docx');
            }

            if (file_exists($path)) {
                return response()->download($path, 'TemplateSoal.docx');
            }

            // Fallback ke generator dinamis jika file template statis tidak ada
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $tempFile = $tempDir . '/template_soal_cbt_' . uniqid() . '.docx';
            
            if (WordTemplateGenerator::generate($tempFile)) {
                return response()->download($tempFile, 'template_soal_cbt.docx')->deleteFileAfterSend(true);
            }
            return redirect()->back()->with('error', 'Gagal membuat template Word.');
        }

        $path = base_path('lokals/TemplateKunci.xlsx');
        if (!file_exists($path)) {
            $path = base_path('TemplateKunci.xlsx');
        }

        if (file_exists($path)) {
            return response()->download($path, 'TemplateKunci.xlsx');
        }

        // Fallback ke generator dinamis jika file template statis tidak ada
        return Excel::download(new QuestionTemplateExport, 'template_soal_cbt.xlsx');
    }

    public function importExcel(Request $request, $id)
    {
        $request->validate([
            'file_soal'  => 'required|file|mimes:docx,docm',
            'file_kunci' => 'required|file|mimes:xlsx,xls',
        ], [
            'file_soal.required'  => 'File Soal (.docx atau .docm) wajib diunggah.',
            'file_soal.mimes'     => 'File Soal harus berformat .docx atau .docm (Word).',
            'file_kunci.required' => 'File Kunci (.xlsx) wajib diunggah.',
            'file_kunci.mimes'    => 'File Kunci harus berformat .xlsx atau .xls (Excel).',
        ]);

        $bank = CbtBank::findOrFail($id);

        $fileSoal  = $request->file('file_soal');
        $fileKunci = $request->file('file_kunci');

        // Gunakan getRealPath() langsung — file upload sudah ada di PHP temp folder
        $docxPath  = $fileSoal->getRealPath();
        $xlsxPath  = $fileKunci->getRealPath();

        try {
            WordQuestionParser::import($bank->id, $docxPath, $xlsxPath);

            return response()->json([
                'message' => 'Soal berhasil di-import dari Word dan kunci jawaban berhasil diproses.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => ['general' => [$e->getMessage()]],
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal import soal: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ], 422);
        }
    }


    public function clearQuestions($id)
    {
        $bank = CbtBank::findOrFail($id);

        // Hapus file media soal dari storage sebelum hapus record soal
        $this->deleteBankMedia($bank->id);

        $bank->questions()->delete();

        return redirect()->back()->with('success', 'Semua soal di dalam Bank Soal ini telah dibersihkan.');
    }

    /**
     * Update kunci jawaban & bobot per butir soal secara manual.
     */
    public function updateQuestionKey(Request $request, $bankId, $questionId)
    {
        $user = Auth::user();
        $bank = CbtBank::findOrFail($bankId);

        // Pengaman 1: Otorisasi Kepemilikan Bank Soal
        if (!$user->hasRole('admin') && $user->hasRole('guru')) {
            if ($bank->teacher_id && $bank->teacher_id !== $user->teacher?->id) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengubah Bank Soal ini.');
            }
        }

        $question = CbtQuestion::where('cbt_bank_id', $bankId)->findOrFail($questionId);

        $request->validate([
            'correct_answer' => 'required',
            'score' => 'nullable|numeric|min:0',
        ]);

        $updateData = [
            'correct_answer' => $request->correct_answer,
        ];

        if ($request->filled('score')) {
            $updateData['score'] = (float)$request->score;
        }

        $question->update($updateData);

        return redirect()->back()->with('success', 'Kunci jawaban & bobot soal berhasil diperbarui.');
    }

    /**
     * Import revisi kunci jawaban dari file Excel tanpa menghapus data soal.
     */
    public function importKeysOnly(Request $request, $bankId)
    {
        $user = Auth::user();
        $bank = CbtBank::findOrFail($bankId);

        // Pengaman 1: Otorisasi Kepemilikan Bank Soal
        if (!$user->hasRole('admin') && $user->hasRole('guru')) {
            if ($bank->teacher_id && $bank->teacher_id !== $user->teacher?->id) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengubah Bank Soal ini.');
            }
        }

        // Pengaman 2: Validasi Ekstensi & Ukuran File
        $request->validate([
            'file_kunci' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'file_kunci.required' => 'File Kunci (.xlsx) wajib diunggah.',
            'file_kunci.mimes'    => 'File Kunci harus berformat .xlsx atau .xls (Excel).',
            'file_kunci.max'      => 'Ukuran file kunci maksimal 10MB.',
        ]);

        $fileKunci = $request->file('file_kunci');
        $xlsxPath = $fileKunci->getRealPath();

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($xlsxPath);
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $data = $sheet->rangeToArray("A1:{$highestCol}{$highestRow}", null, true, true, true);

            $keyMap = [];
            foreach ($data as $row) {
                $idRaw = trim((string) ($row['A'] ?? ''));
                $kunci = trim((string) ($row['B'] ?? ''));
                $skor = isset($row['C']) && is_numeric($row['C']) ? (float) $row['C'] : null;
                $grouping = trim((string) ($row['D'] ?? ''));
                $lockN = isset($row['E']) && strtoupper(trim((string) $row['E'])) === 'L';

                if ($idRaw !== '' && strtolower($idRaw) !== 'no. soal' && strtolower($idRaw) !== 'no soal') {
                    $keyMap[$idRaw] = [
                        'kunci' => $kunci,
                        'skor' => $skor,
                        'grouping' => $grouping !== '' ? $grouping : null,
                        'lock_n' => $lockN,
                    ];
                }
            }

            // Pengaman 3: Cek apakah isi file kunci kosong
            if (empty($keyMap)) {
                throw new \Exception('File Excel tidak memuat data baris nomor soal atau kunci jawaban.');
            }

            $questions = CbtQuestion::where('cbt_bank_id', $bankId)->orderBy('id', 'asc')->get();
            if ($questions->isEmpty()) {
                throw new \Exception('Bank Soal ini belum memiliki butir soal untuk diperbarui.');
            }

            $updatedCount = 0;

            // Pengaman 4: Gunakan DB Transaction agar atomik (rollback jika terjadi kegagalan)
            DB::transaction(function () use ($questions, $keyMap, &$updatedCount) {
                foreach ($questions as $idx => $q) {
                    $noSoal = (string)($idx + 1);
                    
                    if (isset($keyMap[$noSoal])) {
                        $kInfo = $keyMap[$noSoal];
                        $kVal = $kInfo['kunci'];
                        
                        $correctAnswer = $q->correct_answer;
                        if ($q->question_type === 'pilihan_ganda' || $q->question_type === 'survey') {
                            $correctAnswer = strtoupper(trim($kVal));
                        } elseif ($q->question_type === 'isian_singkat') {
                            $correctAnswer = array_map('trim', explode(',', $kVal));
                        } elseif ($q->question_type === 'benar_salah' && !empty($kVal)) {
                            $answers = array_map(fn ($item) => strtoupper(trim($item)), explode(',', $kVal));
                            $statements = $q->options['statements'] ?? [];
                            $correctMap = [];
                            foreach (array_keys($statements) as $sIdx => $sKey) {
                                $correctMap[$sKey] = $answers[$sIdx] ?? 'S';
                            }
                            $correctAnswer = $correctMap;
                        } elseif (!empty($kVal) && $kVal !== 'X') {
                            $correctAnswer = $kVal;
                        }

                        if (is_array($q->correct_answer)) {
                            $mergedCorrect = $q->correct_answer;
                            $hasSubUpdates = false;
                            foreach ($q->correct_answer as $subKey => $oldVal) {
                                if (isset($keyMap[(string)$subKey])) {
                                    $mergedCorrect[$subKey] = $keyMap[(string)$subKey]['kunci'];
                                    $hasSubUpdates = true;
                                }
                            }
                            if ($hasSubUpdates) {
                                $correctAnswer = $mergedCorrect;
                            }
                        }

                        $updateArr = [
                            'correct_answer' => $correctAnswer,
                        ];
                        if ($kInfo['skor'] !== null) {
                            $updateArr['score'] = $kInfo['skor'];
                        }
                        if ($kInfo['grouping'] !== null) {
                            $updateArr['grouping'] = $kInfo['grouping'];
                        }
                        $updateArr['lock_n'] = $kInfo['lock_n'];

                        $q->update($updateArr);
                        $updatedCount++;
                    }
                }
            });

            // Pengaman 5: Cek apakah ada nomor soal yang cocok
            if ($updatedCount === 0) {
                return redirect()->back()->with('error', 'Tidak ada baris nomor soal pada file Excel yang cocok dengan nomor urut soal di Bank Soal ini.');
            }

            return redirect()->back()->with('success', "Kunci jawaban berhasil diperbarui untuk {$updatedCount} butir soal.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file_kunci' => 'Gagal membaca file Excel kunci: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus semua file gambar dari storage (MinIO/S3/local) yang terkait
     * dengan bank soal tertentu.
     *
     * Strategi dua lapis:
     * 1. Scan question_text HTML untuk menemukan semua URL proxy gambar
     *    (/cbt/questions/image/{filename}) dan hapus file tersebut dari disk aktif.
     * 2. Hapus file dari folder lokal fallback (storage/app/public/cbt_questions)
     *    berdasarkan pola nama file (cbt_bank_{id}_*).
     */
    private function deleteBankMedia(int $bankId): void
    {
        $disk      = Storage::disk(config('filesystems.cbt_disk', 's3_cbt'));
        $localDisk = Storage::disk('public');

        // --- Lapis 1: Hapus berdasarkan filename yang ditemukan di question_text ---
        $questions = CbtQuestion::where('cbt_bank_id', $bankId)
            ->whereNotNull('question_text')
            ->pluck('question_text');

        $deletedFiles = [];
        foreach ($questions as $html) {
            // Ambil filename dari URL proxy: /cbt/questions/image/{filename}
            preg_match_all('#/cbt/questions/image/([\w\-\.]+)#', $html, $matches);
            foreach ($matches[1] as $filename) {
                if (in_array($filename, $deletedFiles)) {
                    continue; // Hindari hapus dua kali
                }
                $path = 'cbt_questions/' . $filename;
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
                // Hapus juga dari local fallback jika berbeda disk
                if ($localDisk->exists($path)) {
                    $localDisk->delete($path);
                }
                $deletedFiles[] = $filename;
            }
        }

        // --- Lapis 2: Hapus file fisik lokal berdasarkan pola nama (safety net) ---
        $mediaDest = storage_path('app/public/cbt_questions');
        if (is_dir($mediaDest)) {
            foreach (glob("{$mediaDest}/cbt_bank_{$bankId}_*") ?: [] as $filePath) {
                @unlink($filePath);
            }
        }
    }

    public function analytics($id)
    {
        $bank = CbtBank::with(['subject', 'teacher', 'questions', 'exams'])->findOrFail($id);

        // Decoupled Read: Hanya baca data terhitung dari DB (Instant < 50ms)
        $cttSummary = CbtCttExamSummary::where('cbt_bank_id', $bank->id)->first();

        // Apabila belum pernah dikalkulasi sama sekali, hitung 1x sebagai fallback
        if (!$cttSummary) {
            app(CttAnalyticsService::class)->calculateBankCtt($bank->id);
            $cttSummary = CbtCttExamSummary::where('cbt_bank_id', $bank->id)->first();
        }
        $cttItemAnalyses = CbtCttItemAnalysis::where('cbt_bank_id', $bank->id)
            ->with('question')
            ->get()
            ->values()
            ->map(function ($item, $idx) {
                $item->item_number = $idx + 1;
                return $item;
            });

        $irtItemParameters = CbtIrtItemParameter::where('cbt_bank_id', $bank->id)
            ->with('question')
            ->get()
            ->values()
            ->map(function ($item, $idx) {
                $item->item_number = $idx + 1;
                return $item;
            });
        $irtJob = CbtAnalysisJob::where('cbt_bank_id', $bank->id)->where('job_type', 'LIKE', 'IRT_%')->latest()->first();

        $examIds = $bank->exams->pluck('id')->toArray();
        $studentExams = !empty($examIds) 
            ? CbtStudentExam::whereIn('cbt_exam_id', $examIds)
                ->with(['student.classrooms', 'exam'])
                ->get()
            : collect();

        $studentExamIds = $studentExams->pluck('id')->toArray();
        $cttResults = !empty($studentExamIds) ? CbtCttStudentResult::whereIn('cbt_student_exam_id', $studentExamIds)->get()->keyBy('cbt_student_exam_id') : collect();
        $irtAbilities = !empty($studentExamIds) ? CbtIrtStudentAbility::whereIn('cbt_student_exam_id', $studentExamIds)->get()->keyBy('cbt_student_exam_id') : collect();

        $participants = $studentExams->map(function ($stExam) use ($cttResults, $irtAbilities) {
            $student = $stExam->student;
            $cttRes = $cttResults->get($stExam->id);
            $irtAb = $irtAbilities->get($stExam->id);

            return [
                'student_id' => $student->id ?? null,
                'student_exam_id' => $stExam->id,
                'name' => $student->full_name ?? 'Siswa',
                'nisn' => $student->nisn ?? '-',
                'classroom_name' => $student->classrooms->first()->name ?? '-',
                'exam_title' => $stExam->exam->title ?? '-',
                'status' => $stExam->status,
                'started_at' => $stExam->started_at?->toIso8601String(),
                'submitted_at' => $stExam->submitted_at?->toIso8601String(),
                'ctt_score' => $cttRes ? $cttRes->percentage : ($stExam->score ?? null),
                'ctt_raw' => $cttRes ? $cttRes->raw_score : null,
                'ctt_rank' => $cttRes ? $cttRes->rank : null,
                'irt_theta' => $irtAb ? $irtAb->theta : null,
                'irt_se' => $irtAb ? $irtAb->standard_error : null,
                'irt_scaled' => $irtAb ? $irtAb->scaled_score : null,
                'irt_percentile' => $irtAb ? $irtAb->percentile : null,
            ];
        });

        return Inertia::render('Cbt/Bank/Analytics', [
            'bank' => $bank,
            'participants' => $participants,
            'ctt_summary' => $cttSummary,
            'ctt_item_analyses' => $cttItemAnalyses,
            'irt_parameters' => $irtItemParameters,
            'irt_job_status' => $irtJob ? [
                'job_type' => $irtJob->job_type,
                'status' => $irtJob->status,
                'total_participants' => $irtJob->total_participants,
                'progress_percent' => $irtJob->progress_percent,
                'error_message' => $irtJob->error_message,
                'started_at' => $irtJob->started_at?->toIso8601String(),
                'completed_at' => $irtJob->completed_at?->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Hitung ulang analisis CTT & IRT di tingkat Bank Soal dengan prior Regrading.
     */
    public function recalculateAnalytics($id, CttAnalyticsService $cttService, IrtMicroserviceClient $irtClient)
    {
        $bank = CbtBank::findOrFail($id);

        // 1. Regrade seluruh peserta ujian yang terkait dengan bank soal ini
        $exams = CbtExam::where('cbt_bank_id', $bank->id)->get();
        foreach ($exams as $exam) {
            $studentExams = CbtStudentExam::where('cbt_exam_id', $exam->id)
                ->whereIn('status', ['submitted', 'completed'])
                ->get();
            foreach ($studentExams as $stExam) {
                \Modules\Cbt\Services\CbtGradingService::gradeExam($stExam->id, $stExam->submit_type);
            }
        }

        // 2. Hitung ulang CTT & IRT bank soal
        $cttService->calculateBankCtt($bank->id);
        $irtClient->dispatchBankIrtEstimation($bank->id);

        return redirect()->route('cbt.bank.analytics', $bank->id)->with('success', 'Nilai peserta dinilai ulang & analisis butir soal CTT & IRT bank soal berhasil dikalkulasi ulang!');
    }

    /**
     * Export / Cetak PDF Laporan Data Jawaban Siswa (Format REPORT_DATA_JAWABAN).
     */
    public function exportAnswersPdf($id)
    {
        $bank = CbtBank::with(['subject', 'teacher', 'questions' => function ($q) {
            $q->orderBy('id', 'asc');
        }, 'exams'])->findOrFail($id);

        $questions = $bank->questions;

        // Build Concatenated Answer Keys string (e.g. BCADACEACACBCCDBEBDCBACCCCCACA)
        $answerKeysArr = [];
        $maxOptionCount = 5;

        foreach ($questions as $q) {
            $keyChar = '-';
            $correct = $q->correct_answer ?? $q->answer_key;
            if (!empty($correct) || $correct === 0 || $correct === '0') {
                $rawKey = is_array($correct) ? ($correct[0] ?? '-') : $correct;
                if (is_numeric($rawKey)) {
                    $keyChar = chr(65 + (int)$rawKey);
                } else {
                    $keyChar = strtoupper(trim((string)$rawKey));
                }
            } elseif (!empty($q->options)) {
                $opts = is_string($q->options) ? json_decode($q->options, true) : $q->options;
                if (is_array($opts)) {
                    foreach ($opts as $optIdx => $optVal) {
                        if (is_array($optVal) && !empty($optVal['is_correct'])) {
                            $keyChar = !empty($optVal['key']) ? strtoupper((string)$optVal['key']) : chr(65 + (int)$optIdx);
                            break;
                        }
                    }
                }
            }
            $answerKeysArr[] = substr($keyChar, 0, 1);
        }

        $answerKeysString = implode('', $answerKeysArr);

        // Fetch all exams linked to this bank
        $examIds = $bank->exams->pluck('id')->toArray();
        $studentExams = !empty($examIds)
            ? CbtStudentExam::whereIn('cbt_exam_id', $examIds)
                ->with(['student.classrooms', 'answers', 'exam'])
                ->get()
            : collect();

        $studentRows = [];
        $scoresArr = [];
        $nilaisArr = [];
        $correctsArr = [];
        $wrongsArr = [];

        $classroomNamesSet = [];

        foreach ($studentExams as $stExam) {
            $student = $stExam->student;
            if (!$student) continue;

            $classrooms = $student->classrooms->pluck('name')->toArray();
            foreach ($classrooms as $cn) $classroomNamesSet[$cn] = true;

            $stAnswers = $stExam->answers->keyBy('cbt_question_id');
            $ansCodeArr = [];
            $correctCount = 0;
            $wrongCount = 0;

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id);
                if ($ans && !empty($ans->selected_answer)) {
                    $selected = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? 'X') : $ans->selected_answer;
                    $optChar = strtoupper(substr(trim((string)$selected), 0, 1));
                    $ansCodeArr[] = $optChar;

                    if ($ans->is_correct === true) {
                        $correctCount++;
                    } else {
                        $wrongCount++;
                    }
                } else {
                    $ansCodeArr[] = 'X';
                    $wrongCount++;
                }
            }

            $score = $correctCount;
            $maxPoss = count($questions);
            $nilai = $maxPoss > 0 ? round(($score / $maxPoss) * 100, 0) : 0;
            $isTuntas = $nilai >= 75;

            // Strict L / P formatting
            $gRaw = strtolower(trim((string)($student->gender ?? 'L')));
            $genderLabel = (str_starts_with($gRaw, 'p') || str_contains($gRaw, 'perempuan') || str_contains($gRaw, 'female') || $gRaw === '0') ? 'P' : 'L';

            $studentRows[] = [
                'name' => $student->full_name,
                'gender' => $genderLabel,
                'answer_string' => implode('', $ansCodeArr),
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'score' => $score,
                'nilai' => $nilai,
                'is_tuntas' => $isTuntas,
                'ket' => $isTuntas ? 'Tuntas' : 'Belum Tuntas',
            ];

            $scoresArr[] = $score;
            $nilaisArr[] = $nilai;
            $correctsArr[] = $correctCount;
            $wrongsArr[] = $wrongCount;
        }

        // Sort student rows alphabetically by name (A-Z)
        usort($studentRows, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $n = count($studentRows);
        $stats = [
            'sum_correct' => array_sum($correctsArr),
            'sum_wrong' => array_sum($wrongsArr),
            'sum_score' => array_sum($scoresArr),
            'sum_nilai' => array_sum($nilaisArr),
            'min_correct' => $n > 0 ? min($correctsArr) : 0,
            'min_wrong' => $n > 0 ? min($wrongsArr) : 0,
            'min_score' => $n > 0 ? min($scoresArr) : 0,
            'min_nilai' => $n > 0 ? min($nilaisArr) : 0,
            'max_correct' => $n > 0 ? max($correctsArr) : 0,
            'max_wrong' => $n > 0 ? max($wrongsArr) : 0,
            'max_score' => $n > 0 ? max($scoresArr) : 0,
            'max_nilai' => $n > 0 ? max($nilaisArr) : 0,
            'avg_correct' => $n > 0 ? array_sum($correctsArr) / $n : 0,
            'avg_wrong' => $n > 0 ? array_sum($wrongsArr) / $n : 0,
            'avg_score' => $n > 0 ? array_sum($scoresArr) / $n : 0,
            'avg_nilai' => $n > 0 ? array_sum($nilaisArr) / $n : 0,
            'std_score' => $this->calculateStdDev($scoresArr),
            'std_nilai' => $this->calculateStdDev($nilaisArr),
        ];

        $classroomNamesStr = !empty($classroomNamesSet) ? implode(', ', array_keys($classroomNamesSet)) : 'Semua Kelas';

        // Headmaster / Principal Data from DB Settings
        $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'SMA Negeri 16 Semarang'));
        $headmasterName = \App\Models\Setting::get('principal_name', 'Subchan, S. Pd.');
        $headmasterNip = \App\Models\Setting::get('principal_nip', '19740201 200012 1 002');

        return view('cbt::pdf.report_data_jawaban', [
            'bank' => $bank,
            'questions' => $questions,
            'answer_keys_string' => $answerKeysString,
            'max_option_count' => $maxOptionCount,
            'student_rows' => $studentRows,
            'stats' => $stats,
            'school_name' => $schoolName,
            'semester' => '1 (Ganjil)',
            'academic_year' => '2025 / 2026',
            'classroom_names' => $classroomNamesStr,
            'exam_date' => now()->translatedFormat('l, d F Y'),
            'school_city' => 'Semarang',
            'headmaster_name' => $headmasterName,
            'headmaster_nip' => $headmasterNip,
        ]);
    }

    public function exportDichotomousPdf($id)
    {
        $bank = CbtBank::with(['subject', 'teacher', 'questions' => function ($q) {
            $q->orderBy('id', 'asc');
        }, 'exams'])->findOrFail($id);

        $questions = $bank->questions;

        // Fetch all exams linked to this bank
        $examIds = $bank->exams->pluck('id')->toArray();
        $studentExams = !empty($examIds)
            ? CbtStudentExam::whereIn('cbt_exam_id', $examIds)
                ->with(['student.classrooms', 'answers', 'exam'])
                ->get()
            : collect();

        $studentRows = [];
        $scoresArr = [];
        $nilaisArr = [];
        $correctsArr = [];
        $wrongsArr = [];

        $classroomNamesSet = [];

        foreach ($studentExams as $stExam) {
            $student = $stExam->student;
            if (!$student) continue;

            $classrooms = $student->classrooms->pluck('name')->toArray();
            foreach ($classrooms as $cn) $classroomNamesSet[$cn] = true;

            $stAnswers = $stExam->answers->keyBy('cbt_question_id');
            $dichotomousArr = [];
            $ansCodeArr = [];
            $correctCount = 0;
            $wrongCount = 0;

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id);
                if ($ans && !empty($ans->selected_answer)) {
                    $selected = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? 'X') : $ans->selected_answer;
                    $optChar = strtoupper(substr(trim((string)$selected), 0, 1));
                    $ansCodeArr[] = $optChar;

                    if ($ans->is_correct === true) {
                        $dichotomousArr[] = '1';
                        $correctCount++;
                    } else {
                        $dichotomousArr[] = '0';
                        $wrongCount++;
                    }
                } else {
                    $ansCodeArr[] = '-';
                    $dichotomousArr[] = '0';
                    $wrongCount++;
                }
            }

            $score = $correctCount;
            $maxPoss = count($questions);
            $nilai = $maxPoss > 0 ? round(($score / $maxPoss) * 100, 0) : 0;
            $isTuntas = $nilai >= 75;

            // Strict L / P formatting
            $gRaw = strtolower(trim((string)($student->gender ?? 'L')));
            $genderLabel = (str_starts_with($gRaw, 'p') || str_contains($gRaw, 'perempuan') || str_contains($gRaw, 'female') || $gRaw === '0') ? 'P' : 'L';

            $studentRows[] = [
                'name' => $student->full_name,
                'gender' => $genderLabel,
                'answer_string' => implode('', $ansCodeArr),
                'dichotomous_arr' => $dichotomousArr,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'score' => $score,
                'nilai' => $nilai,
                'is_tuntas' => $isTuntas,
                'ket' => $isTuntas ? 'Tuntas' : 'Belum Tuntas',
            ];

            $scoresArr[] = $score;
            $nilaisArr[] = $nilai;
            $correctsArr[] = $correctCount;
            $wrongsArr[] = $wrongCount;
        }

        // Sort student rows alphabetically by name (A-Z)
        usort($studentRows, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $n = count($studentRows);
        $stats = [
            'sum_correct' => array_sum($correctsArr),
            'sum_wrong' => array_sum($wrongsArr),
            'sum_score' => array_sum($scoresArr),
            'sum_nilai' => array_sum($nilaisArr),
            'min_correct' => $n > 0 ? min($correctsArr) : 0,
            'min_wrong' => $n > 0 ? min($wrongsArr) : 0,
            'min_score' => $n > 0 ? min($scoresArr) : 0,
            'min_nilai' => $n > 0 ? min($nilaisArr) : 0,
            'max_correct' => $n > 0 ? max($correctsArr) : 0,
            'max_wrong' => $n > 0 ? max($wrongsArr) : 0,
            'max_score' => $n > 0 ? max($scoresArr) : 0,
            'max_nilai' => $n > 0 ? max($nilaisArr) : 0,
            'avg_correct' => $n > 0 ? array_sum($correctsArr) / $n : 0,
            'avg_wrong' => $n > 0 ? array_sum($wrongsArr) / $n : 0,
            'avg_score' => $n > 0 ? array_sum($scoresArr) / $n : 0,
            'avg_nilai' => $n > 0 ? array_sum($nilaisArr) / $n : 0,
            'std_score' => $this->calculateStdDev($scoresArr),
            'std_nilai' => $this->calculateStdDev($nilaisArr),
        ];

        $classroomNamesStr = !empty($classroomNamesSet) ? implode(', ', array_keys($classroomNamesSet)) : 'Semua Kelas';

        // Headmaster / Principal Data from DB Settings
        $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'SMA Negeri 16 Semarang'));
        $headmasterName = \App\Models\Setting::get('principal_name', 'Subchan, S. Pd.');
        $headmasterNip = \App\Models\Setting::get('principal_nip', '19740201 200012 1 002');

        return view('cbt::pdf.report_dichotomous', [
            'bank' => $bank,
            'questions' => $questions,
            'student_rows' => $studentRows,
            'stats' => $stats,
            'school_name' => $schoolName,
            'semester' => '1 (Ganjil)',
            'academic_year' => '2025 / 2026',
            'classroom_names' => $classroomNamesStr,
            'exam_date' => now()->translatedFormat('l, d F Y'),
            'school_city' => 'Semarang',
            'headmaster_name' => $headmasterName,
            'headmaster_nip' => $headmasterNip,
        ]);
    }

    private function calculateStdDev(array $values): float
    {
        $count = count($values);
        if ($count <= 1) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $varianceSum = 0.0;
        foreach ($values as $val) {
            $varianceSum += pow($val - $mean, 2);
        }

        return sqrt($varianceSum / ($count - 1));
    }

    public function exportDaftarNilaiPdf($id)
    {
        $bank = CbtBank::with(['subject', 'teacher', 'questions' => function ($q) {
            $q->orderBy('id', 'asc');
        }, 'exams'])->findOrFail($id);

        $questions = $bank->questions;

        // Fetch all exams linked to this bank
        $examIds = $bank->exams->pluck('id')->toArray();
        $studentExams = !empty($examIds)
            ? CbtStudentExam::whereIn('cbt_exam_id', $examIds)
                ->with(['student.classrooms', 'answers', 'exam'])
                ->get()
            : collect();

        $studentRows = [];
        $scoresArr = [];
        $nilaisArr = [];
        $correctsArr = [];
        $wrongsArr = [];
        
        $classroomNamesSet = [];

        foreach ($studentExams as $stExam) {
            $student = $stExam->student;
            if (!$student) continue;

            $classrooms = $student->classrooms->pluck('name')->toArray();
            foreach ($classrooms as $cn) $classroomNamesSet[$cn] = true;

            $stAnswers = $stExam->answers->keyBy('cbt_question_id');
            $ansCodeArr = [];
            $correctCount = 0;
            $wrongCount = 0;

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id);
                if ($ans && !empty($ans->selected_answer)) {
                    if ($ans->is_correct === true) {
                        $selected = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? 'X') : $ans->selected_answer;
                        $optChar = strtoupper(substr(trim((string)$selected), 0, 1));
                        $ansCodeArr[] = $optChar;
                        $correctCount++;
                    } else {
                        $ansCodeArr[] = '-';
                        $wrongCount++;
                    }
                } else {
                    $ansCodeArr[] = '-';
                    $wrongCount++;
                }
            }

            $score = $correctCount;
            $maxPoss = count($questions);
            $nilai = $maxPoss > 0 ? round(($score / $maxPoss) * 100, 0) : 0;
            $isTuntas = $nilai >= 70; // Hardcoded default passing grade in view is 70

            // Strict L / P formatting
            $gRaw = strtolower(trim((string)($student->gender ?? 'L')));
            $genderLabel = (str_starts_with($gRaw, 'p') || str_contains($gRaw, 'perempuan') || str_contains($gRaw, 'female') || $gRaw === '0') ? 'P' : 'L';

            $studentRows[] = [
                'name' => $student->full_name,
                'gender' => $genderLabel,
                'answer_string' => implode('', $ansCodeArr),
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'score' => $score,
                'nilai' => $nilai,
                'is_tuntas' => $isTuntas,
                'ket' => $isTuntas ? 'Tuntas' : 'Belum Tuntas',
            ];

            $scoresArr[] = $score;
            $nilaisArr[] = $nilai;
            $correctsArr[] = $correctCount;
            $wrongsArr[] = $wrongCount;
        }

        // Sort student rows alphabetically by name (A-Z)
        usort($studentRows, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $n = count($studentRows);
        
        $lulusCount = collect($studentRows)->where('is_tuntas', true)->count();
        $tidakLulusCount = collect($studentRows)->where('is_tuntas', false)->count();
        $avgScore = $n > 0 ? array_sum($scoresArr) / $n : 0;
        $diAtasRata = collect($studentRows)->filter(fn($r) => $r['score'] > $avgScore)->count();
        $diBawahRata = collect($studentRows)->filter(fn($r) => $r['score'] <= $avgScore)->count();

        $stats = [
            'sum_correct' => array_sum($correctsArr),
            'sum_wrong' => array_sum($wrongsArr),
            'sum_score' => array_sum($scoresArr),
            'sum_nilai' => array_sum($nilaisArr),
            'min_correct' => $n > 0 ? min($correctsArr) : 0,
            'min_wrong' => $n > 0 ? min($wrongsArr) : 0,
            'min_score' => $n > 0 ? min($scoresArr) : 0,
            'min_nilai' => $n > 0 ? min($nilaisArr) : 0,
            'max_correct' => $n > 0 ? max($correctsArr) : 0,
            'max_wrong' => $n > 0 ? max($wrongsArr) : 0,
            'max_score' => $n > 0 ? max($scoresArr) : 0,
            'max_nilai' => $n > 0 ? max($nilaisArr) : 0,
            'avg_correct' => $avgScore, // same as avg score
            'avg_wrong' => $n > 0 ? array_sum($wrongsArr) / $n : 0,
            'avg_score' => $avgScore,
            'avg_nilai' => $n > 0 ? array_sum($nilaisArr) / $n : 0,
            'std_score' => $this->calculateStdDev($scoresArr),
            'std_nilai' => $this->calculateStdDev($nilaisArr),
            'lulus' => $lulusCount,
            'tidak_lulus' => $tidakLulusCount,
            'di_atas_rata' => $diAtasRata,
            'di_bawah_rata' => $diBawahRata,
            'peserta' => $n
        ];

        $classroomNamesStr = !empty($classroomNamesSet) ? implode(', ', array_keys($classroomNamesSet)) : 'Semua Kelas';

        // Headmaster / Principal Data from DB Settings
        $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'SMA Negeri 16 Semarang'));
        $headmasterName = \App\Models\Setting::get('principal_name', 'Subchan, S. Pd.');
        $headmasterNip = \App\Models\Setting::get('principal_nip', '19740201 200012 1 002');

        return view('cbt::pdf.report_daftar_nilai', [
            'bank' => $bank,
            'questions' => $questions,
            'student_rows' => $studentRows,
            'stats' => $stats,
            'school_name' => $schoolName,
            'semester' => '1 (Ganjil)',
            'academic_year' => '2025 / 2026',
            'classroom_names' => $classroomNamesStr,
            'exam_date' => now()->translatedFormat('d F Y'),
            'school_city' => 'Semarang',
            'headmaster_name' => $headmasterName,
            'headmaster_nip' => $headmasterNip,
        ]);
    }

    /**
     * Export / Cetak Bundel Lengkap PDF Bank Soal (Data Jawaban + Matriks Dikotomi + Daftar Nilai).
     */
    public function exportFullReportPdf($id)
    {
        $bank = CbtBank::with(['subject', 'teacher.user', 'questions' => function ($q) {
            $q->orderBy('id', 'asc');
        }, 'exams.classrooms'])->findOrFail($id);

        $questions = $bank->questions;

        // Build Concatenated Answer Keys string
        $answerKeysArr = [];
        $maxOptionCount = 5;

        foreach ($questions as $q) {
            $keyChar = '-';
            $correct = $q->correct_answer ?? $q->answer_key;
            if (!empty($correct) || $correct === 0 || $correct === '0') {
                $rawKey = is_array($correct) ? ($correct[0] ?? '-') : $correct;
                if (is_numeric($rawKey)) {
                    $keyChar = chr(65 + (int)$rawKey);
                } else {
                    $keyChar = strtoupper(trim((string)$rawKey));
                }
            } elseif (!empty($q->options)) {
                $opts = is_string($q->options) ? json_decode($q->options, true) : $q->options;
                if (is_array($opts)) {
                    foreach ($opts as $optIdx => $optVal) {
                        if (is_array($optVal) && !empty($optVal['is_correct'])) {
                            $keyChar = !empty($optVal['key']) ? strtoupper((string)$optVal['key']) : chr(65 + (int)$optIdx);
                            break;
                        }
                    }
                }
            }
            $answerKeysArr[] = substr($keyChar, 0, 1);
        }

        $answerKeysString = implode('', $answerKeysArr);

        // Fetch student exams across all exam schedules of this bank
        $examIds = $bank->exams->pluck('id')->toArray();
        $studentExams = CbtStudentExam::whereIn('cbt_exam_id', $examIds)
            ->with(['student.classrooms', 'answers', 'exam.classrooms'])
            ->get();

        $studentRows = [];
        $scoresArr = [];
        $nilaisArr = [];
        $correctsArr = [];
        $wrongsArr = [];
        $classroomNamesSet = [];

        foreach ($studentExams as $stExam) {
            $student = $stExam->student;
            if (!$student) continue;

            if ($stExam->exam && $stExam->exam->classrooms) {
                foreach ($stExam->exam->classrooms as $cls) {
                    $classroomNamesSet[$cls->name] = true;
                }
            }

            $stAnswers = $stExam->answers->keyBy('cbt_question_id');
            $ansCodeArr = [];
            $dichotomousArr = [];
            $correctCount = 0;
            $wrongCount = 0;

            foreach ($questions as $q) {
                $ans = $stAnswers->get($q->id);
                if ($ans && !empty($ans->selected_answer)) {
                    $selected = is_array($ans->selected_answer) ? ($ans->selected_answer[0] ?? 'X') : $ans->selected_answer;
                    $optChar = strtoupper(substr(trim((string)$selected), 0, 1));
                    $ansCodeArr[] = $optChar;

                    if ($ans->is_correct === true) {
                        $dichotomousArr[] = '1';
                        $correctCount++;
                    } else {
                        $dichotomousArr[] = '0';
                        $wrongCount++;
                    }
                } else {
                    $ansCodeArr[] = 'X';
                    $dichotomousArr[] = '0';
                    $wrongCount++;
                }
            }

            $score = $correctCount;
            $maxPoss = count($questions);
            $nilai = $maxPoss > 0 ? round(($score / $maxPoss) * 100, 0) : 0;
            $isTuntas = $nilai >= 70;

            // Strict L / P formatting
            $gRaw = strtolower(trim((string)($student->gender ?? 'L')));
            $genderLabel = (str_starts_with($gRaw, 'p') || str_contains($gRaw, 'perempuan') || str_contains($gRaw, 'female') || $gRaw === '0') ? 'P' : 'L';

            $studentRows[] = [
                'name' => $student->full_name,
                'gender' => $genderLabel,
                'answer_string' => implode('', $ansCodeArr),
                'dichotomous_arr' => $dichotomousArr,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'score' => $score,
                'nilai' => $nilai,
                'is_tuntas' => $isTuntas,
                'ket' => $isTuntas ? 'Tuntas' : 'Belum Tuntas',
            ];

            $scoresArr[] = $score;
            $nilaisArr[] = $nilai;
            $correctsArr[] = $correctCount;
            $wrongsArr[] = $wrongCount;
        }

        // Sort student rows alphabetically by name (A-Z)
        usort($studentRows, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $n = count($studentRows);
        $lulusCount = collect($studentRows)->where('is_tuntas', true)->count();
        $tidakLulusCount = collect($studentRows)->where('is_tuntas', false)->count();

        $stats = [
            'sum_correct' => array_sum($correctsArr),
            'sum_wrong' => array_sum($wrongsArr),
            'sum_score' => array_sum($scoresArr),
            'sum_nilai' => array_sum($nilaisArr),
            'min_correct' => $n > 0 ? min($correctsArr) : 0,
            'min_wrong' => $n > 0 ? min($wrongsArr) : 0,
            'min_score' => $n > 0 ? min($scoresArr) : 0,
            'min_nilai' => $n > 0 ? min($nilaisArr) : 0,
            'max_correct' => $n > 0 ? max($correctsArr) : 0,
            'max_wrong' => $n > 0 ? max($wrongsArr) : 0,
            'max_score' => $n > 0 ? max($scoresArr) : 0,
            'max_nilai' => $n > 0 ? max($nilaisArr) : 0,
            'avg_correct' => $n > 0 ? array_sum($correctsArr) / $n : 0,
            'avg_wrong' => $n > 0 ? array_sum($wrongsArr) / $n : 0,
            'avg_score' => $n > 0 ? array_sum($scoresArr) / $n : 0,
            'avg_nilai' => $n > 0 ? array_sum($nilaisArr) / $n : 0,
            'std_score' => $this->calculateStdDev($scoresArr),
            'std_nilai' => $this->calculateStdDev($nilaisArr),
            'lulus' => $lulusCount,
            'tidak_lulus' => $tidakLulusCount,
        ];

        $classroomNamesStr = !empty($classroomNamesSet) ? implode(', ', array_keys($classroomNamesSet)) : 'Semua Kelas';

        // Headmaster / Principal Data from DB Settings
        $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'SMA Negeri 16 Semarang'));
        $headmasterName = \App\Models\Setting::get('principal_name', 'Subchan, S. Pd.');
        $headmasterNip = \App\Models\Setting::get('principal_nip', '19740201 200012 1 002');

        return view('cbt::pdf.report_full_bundle', [
            'exam' => $bank->exams->first(),
            'bank' => $bank,
            'questions' => $questions,
            'answer_keys_string' => $answerKeysString,
            'max_option_count' => $maxOptionCount,
            'student_rows' => $studentRows,
            'stats' => $stats,
            'school_name' => $schoolName,
            'semester' => '1 (Ganjil)',
            'academic_year' => '2025 / 2026',
            'classroom_names' => $classroomNamesStr,
            'exam_date' => now()->translatedFormat('d F Y'),
            'school_city' => 'Semarang',
            'headmaster_name' => $headmasterName,
            'headmaster_nip' => $headmasterNip,
        ]);
    }
}
