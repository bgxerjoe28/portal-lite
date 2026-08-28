<?php

namespace Modules\Cbt\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\ClassroomStudent;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\Subject;
use Modules\Akademik\Models\Teacher;
use Modules\Cbt\Models\CbtBank;
use Modules\Cbt\Models\CbtExam;
use Modules\Cbt\Models\CbtQuestion;
use Modules\Cbt\Models\CbtStudentAnswer;
use Modules\Cbt\Models\CbtStudentExam;
use Modules\Cbt\Services\CbtGradingService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CbtTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;
    protected Student $student;
    protected Teacher $teacher;
    protected Subject $subject;
    protected Classroom $classroom;
    protected CbtBank $bank;
    protected CbtExam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

        // 2. Create academic structures
        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $this->classroom = Classroom::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'X IPA 1',
            'level' => 10,
        ]);

        $teacherUser = User::factory()->create(['name' => 'Guru IPA', 'is_active' => true, 'password_must_change' => false]);
        $teacherUser->assignRole('guru');
        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '1234567890',
            'full_name' => 'Guru IPA',
            'gender' => true,
        ]);

        $this->studentUser = User::factory()->create(['name' => 'Siswa A', 'is_active' => true, 'password_must_change' => false]);
        $this->studentUser->assignRole('siswa');
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'nisn' => '9988776655',
            'full_name' => 'Siswa A',
            'gender' => true,
        ]);

        ClassroomStudent::create([
            'academic_year_id' => $academicYear->id,
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'status' => 'aktif',
        ]);

        $this->subject = Subject::create([
            'code' => 'IPA101',
            'name' => 'Ilmu Pengetahuan Alam',
        ]);

        // 3. Create Bank Soal
        $this->bank = CbtBank::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'name' => 'Bank Soal UTS IPA',
        ]);

        // 4. Create Exam
        $this->exam = CbtExam::create([
            'cbt_bank_id' => $this->bank->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'UTS IPA Ganjil',
            'duration' => 60,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'is_active' => true,
        ]);

        $this->exam->classrooms()->attach($this->classroom->id);
    }

    public function test_student_can_fetch_and_start_exam(): void
    {
        // Add one multiple choice question
        $q = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'pilihan_ganda',
            'question_text' => 'Siapa presiden pertama RI?',
            'options' => ['A' => 'Soekarno', 'B' => 'Soeharto', 'C' => 'Habibie'],
            'correct_answer' => 'A',
            'score' => 10,
        ]);

        // Pre-create student exam session with status 'login' (as if proctor token is verified)
        CbtStudentExam::create([
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'login',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.cbt.exam', $this->exam->id));

        $response->assertStatus(200);

        // Verify CbtStudentExam is transitioned to 'started'
        $this->assertDatabaseHas('cbt_student_exams', [
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'started',
        ]);
    }

    public function test_student_can_save_answers(): void
    {
        $q = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'pilihan_ganda',
            'question_text' => 'Siapa presiden pertama RI?',
            'options' => ['A' => 'Soekarno', 'B' => 'Soeharto', 'C' => 'Habibie'],
            'correct_answer' => 'A',
            'score' => 10,
        ]);

        // Create student exam session
        $studentExam = CbtStudentExam::create([
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'started',
            'started_at' => now(),
            'question_order' => [$q->id],
        ]);

        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.save-answer', $this->exam->id), [
                'cbt_question_id' => $q->id,
                'selected_answer' => 'A',
                'is_doubtful' => false,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cbt_student_answers', [
            'cbt_student_exam_id' => $studentExam->id,
            'cbt_question_id' => $q->id,
            'selected_answer' => json_encode('A'),
            'is_doubtful' => false,
        ]);
    }

    public function test_cbt_grading_service_scoring(): void
    {
        // 1. Pilihan Ganda (PG)
        $qPg = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'pilihan_ganda',
            'question_text' => 'PG',
            'options' => ['A' => 'Salah', 'B' => 'Benar'],
            'correct_answer' => 'B',
            'score' => 10,
        ]);

        // 2. Isian Singkat
        $qIsian = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'isian_singkat',
            'question_text' => 'Isian',
            'correct_answer' => ['soekarno', 'ir soekarno'],
            'score' => 10,
        ]);

        // 3. Checklist
        $qChecklist = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'checklist',
            'question_text' => 'Checklist',
            'options' => ['A' => 'A', 'B' => 'B', 'C' => 'C'],
            'correct_answer' => ['A', 'C'],
            'score' => 20,
        ]);

        // 4. Benar Salah (Table)
        $qBenarSalah = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'benar_salah',
            'question_text' => 'Benar/Salah',
            'options' => ['statements' => ['1' => 'Pernyataan 1', '2' => 'Pernyataan 2']],
            'correct_answer' => ['1' => 'B', '2' => 'S'],
            'score' => 20,
        ]);

        // Create student exam session
        $studentExam = CbtStudentExam::create([
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'started',
            'started_at' => now(),
            'question_order' => [$qPg->id, $qIsian->id, $qChecklist->id, $qBenarSalah->id],
        ]);

        // Save answers
        CbtStudentAnswer::create([
            'cbt_student_exam_id' => $studentExam->id,
            'cbt_question_id' => $qPg->id,
            'selected_answer' => 'B',
        ]);

        CbtStudentAnswer::create([
            'cbt_student_exam_id' => $studentExam->id,
            'cbt_question_id' => $qIsian->id,
            'selected_answer' => 'Ir Soekarno', // Case-insensitive and trimmed check
        ]);

        CbtStudentAnswer::create([
            'cbt_student_exam_id' => $studentExam->id,
            'cbt_question_id' => $qChecklist->id,
            'selected_answer' => ['C', 'A'], // Order-independent check
        ]);

        CbtStudentAnswer::create([
            'cbt_student_exam_id' => $studentExam->id,
            'cbt_question_id' => $qBenarSalah->id,
            'selected_answer' => ['1' => 'B', '2' => 'B'], // Partial score (1 correct out of 2 = 10 points out of 20)
        ]);

        // Score the exam
        $finalScore = CbtGradingService::gradeExam($studentExam->id);

        // Max points = 10 + 10 + 20 + 20 = 60
        // Earned points = 10 (PG) + 10 (Isian) + 20 (Checklist) + 10 (Benar Salah: 1/2 * 20) = 50
        // Score = (50 / 60) * 100 = 83.33
        $this->assertEquals(83.33, $finalScore);

        $studentExam->refresh();
        $this->assertEquals(83.33, $studentExam->score);
        $this->assertEquals('submitted', $studentExam->status);
    }

    public function test_word_template_generation_and_parser(): void
    {
        // 1. Generate a docx template
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $tempFile = $tempDir . '/test_template_soal_' . uniqid() . '.docx';

        $generated = \Modules\Cbt\Services\WordTemplateGenerator::generate($tempFile);
        $this->assertTrue($generated);
        $this->assertFileExists($tempFile);

        // 2. Clear current questions in bank
        $this->bank->questions()->delete();

        // 3. Import from the generated template docx
        \Modules\Cbt\Services\WordQuestionParser::import($this->bank->id, $tempFile);

        // Check if questions are imported
        $this->assertGreaterThan(0, $this->bank->questions()->count());

        // Assert specific question type details from the template
        $qPg = $this->bank->questions()->where('question_type', 'pilihan_ganda')->first();
        $this->assertNotNull($qPg);
        $this->assertEquals('Siapa presiden pertama Republik Indonesia?', $qPg->question_text);
        $this->assertEquals('A', $qPg->correct_answer);
        $this->assertEquals('Soekarno', $qPg->options['A'] ?? null);

        $qIsian = $this->bank->questions()->where('question_type', 'isian_singkat')->first();
        $this->assertNotNull($qIsian);
        $this->assertContains('jakarta', $qIsian->correct_answer);

        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    public function test_new_format_word_parser(): void
    {
        $docxPath = base_path('lokals/TemplateSoal.docx');
        if (!file_exists($docxPath)) {
            $docxPath = base_path('TemplateSoal.docx');
        }

        if (!file_exists($docxPath)) {
            $this->markTestSkipped('TemplateSoal.docx not found.');
        }

        $this->bank->questions()->delete();

        \Modules\Cbt\Services\WordQuestionParser::import($this->bank->id, $docxPath);

        $this->assertGreaterThan(0, $this->bank->questions()->count());
        $this->assertEquals(20, $this->bank->questions()->count());
    }

    public function test_must_complete_all_validation(): void
    {
        $this->exam->update(['must_complete_all' => true]);

        $q = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'pilihan_ganda',
            'question_text' => 'Q1',
            'options' => ['A' => 'A', 'B' => 'B'],
            'correct_answer' => 'A',
            'score' => 10,
        ]);

        // Create student exam session
        $studentExam = CbtStudentExam::create([
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'started',
            'started_at' => now(),
            'question_order' => [$q->id],
        ]);

        // Case 1: Try to submit when there are unanswered questions
        $response = $this->actingAs($this->studentUser)
            ->post(route('student.cbt.submit', $this->exam->id));
        $response->assertSessionHas('error');
        $studentExam->refresh();
        $this->assertEquals('started', $studentExam->status); // Status should still be started

        // Case 2: Try to submit when the question is answered but marked as doubtful
        CbtStudentAnswer::create([
            'cbt_student_exam_id' => $studentExam->id,
            'cbt_question_id' => $q->id,
            'selected_answer' => 'A',
            'is_doubtful' => true,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.cbt.submit', $this->exam->id));
        $response->assertSessionHas('error');
        $studentExam->refresh();
        $this->assertEquals('started', $studentExam->status);

        // Case 3: Try to submit when answered and NOT doubtful
        CbtStudentAnswer::where('cbt_student_exam_id', $studentExam->id)
            ->where('cbt_question_id', $q->id)
            ->update(['is_doubtful' => false]);

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.cbt.submit', $this->exam->id));
        $response->assertSessionHasNoErrors();
        $studentExam->refresh();
        $this->assertEquals('submitted', $studentExam->status); // Should be submitted successfully
    }

    public function test_admin_and_guru_can_export_exam_results_excel(): void
    {
        $q = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'pilihan_ganda',
            'question_text' => 'Q1',
            'options' => ['A' => 'A', 'B' => 'B'],
            'correct_answer' => 'A',
            'score' => 10,
        ]);

        $studentExam = CbtStudentExam::create([
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'started_at' => now(),
            'question_order' => [$q->id],
        ]);

        CbtStudentAnswer::create([
            'cbt_student_exam_id' => $studentExam->id,
            'cbt_question_id' => $q->id,
            'selected_answer' => 'A',
        ]);

        $response = $this->actingAs($this->teacher->user)
            ->get(route('cbt.exams.results.export', $this->exam->id));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type'));
    }

    public function test_admin_and_guru_can_view_exam_results_recap(): void
    {
        $q = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'pilihan_ganda',
            'question_text' => 'Q1',
            'options' => ['A' => 'A', 'B' => 'B'],
            'correct_answer' => 'A',
            'score' => 10,
        ]);

        $studentExam = CbtStudentExam::create([
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'started_at' => now(),
            'question_order' => [$q->id],
        ]);

        CbtStudentAnswer::create([
            'cbt_student_exam_id' => $studentExam->id,
            'cbt_question_id' => $q->id,
            'selected_answer' => 'A',
        ]);

        $response = $this->actingAs($this->teacher->user)
            ->get(route('cbt.exams.results.recap', $this->exam->id));

        $response->assertStatus(200);
    }

    public function test_exam_results_recap_splits_dynamic_inputs_correctly(): void
    {
        // 1. Create a dynamic inputs question (isian_singkat)
        $q = CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'isian_singkat',
            'question_text' => 'Upgrade <input data-id="17001"/> and <input data-id="17002"/>',
            'options' => null,
            'correct_answer' => [
                '17001' => ['ram'],
                '17002' => ['hardisk', 'ssd']
            ],
            'score' => 10,
        ]);

        $studentExam = CbtStudentExam::create([
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'started_at' => now(),
            'question_order' => [$q->id],
        ]);

        CbtStudentAnswer::create([
            'cbt_student_exam_id' => $studentExam->id,
            'cbt_question_id' => $q->id,
            'selected_answer' => [
                '17001' => 'ram',
                '17002' => 'ssd'
            ],
        ]);

        // 2. Test recap page response structure
        $response = $this->actingAs($this->teacher->user)
            ->get(route('cbt.exams.results.recap', $this->exam->id));

        $response->assertStatus(200);

        // 3. Test excel export output
        $exportResponse = $this->actingAs($this->teacher->user)
            ->get(route('cbt.exams.results.export', $this->exam->id));

        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $exportResponse->headers->get('content-type'));
    }

    public function test_student_must_be_assigned_to_room_to_verify_token(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.verify-token', $this->exam->id), [
                'token' => 'ABCDEF',
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Anda belum terdaftar di ruang ujian manapun. Hubungi pengawas.']);
    }

    public function test_student_can_verify_active_proctor_token_and_start_exam(): void
    {
        // Create Room
        $room = \Modules\Cbt\Models\CbtRoom::create([
            'name' => 'Lab Komputer 1',
            'capacity' => 36,
        ]);

        // Assign Student to Seat
        \Modules\Cbt\Models\CbtRoomStudent::create([
            'cbt_room_id' => $room->id,
            'student_id' => $this->student->id,
            'seat_number' => 12,
        ]);

        // Schedule Exam in Room with Teacher as Proctor
        $examRoom = \Modules\Cbt\Models\CbtExamRoom::create([
            'cbt_exam_id' => $this->exam->id,
            'cbt_room_id' => $room->id,
            'teacher_id' => $this->teacher->id,
            'token' => 'ABCDEF',
            'token_generated_at' => now(),
            'status' => 'started',
        ]);

        // Try to access exam page before verification - should redirect to index
        $response = $this->actingAs($this->studentUser)
            ->get(route('student.cbt.exam', $this->exam->id));
        $response->assertRedirect(route('student.cbt.index'));

        // Verify with invalid token
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.verify-token', $this->exam->id), [
                'token' => 'WRONG1',
            ]);
        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Token ujian tidak valid.']);

        // Verify with correct token
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.verify-token', $this->exam->id), [
                'token' => 'ABCDEF',
            ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);

        // Verify CbtStudentExam has 'login' status
        $this->assertDatabaseHas('cbt_student_exams', [
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'login',
        ]);

        // Now can fetch exam session page
        CbtQuestion::create([
            'cbt_bank_id' => $this->bank->id,
            'question_type' => 'pilihan_ganda',
            'question_text' => 'Q1',
            'options' => ['A' => 'A', 'B' => 'B'],
            'correct_answer' => 'A',
            'score' => 10,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.cbt.exam', $this->exam->id));
        $response->assertStatus(200);
    }

    public function test_verify_token_expires_after_3_minutes(): void
    {
        $room = \Modules\Cbt\Models\CbtRoom::create([
            'name' => 'Lab Komputer 1',
            'capacity' => 36,
        ]);

        \Modules\Cbt\Models\CbtRoomStudent::create([
            'cbt_room_id' => $room->id,
            'student_id' => $this->student->id,
            'seat_number' => 12,
        ]);

        // Schedule Exam with Expired Token (4 minutes ago - more than 3 minutes)
        $examRoom = \Modules\Cbt\Models\CbtExamRoom::create([
            'cbt_exam_id' => $this->exam->id,
            'cbt_room_id' => $room->id,
            'teacher_id' => $this->teacher->id,
            'token' => 'EXPRD1',
            'token_generated_at' => now()->subMinutes(4),
            'status' => 'started',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.verify-token', $this->exam->id), [
                'token' => 'EXPRD1',
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Token kedaluwarsa. Minta token baru kepada pengawas.']);
    }

    public function test_student_exam_heartbeat_status_updates(): void
    {
        $room = \Modules\Cbt\Models\CbtRoom::create([
            'name' => 'Lab Komputer 1',
            'capacity' => 36,
        ]);

        \Modules\Cbt\Models\CbtRoomStudent::create([
            'cbt_room_id' => $room->id,
            'student_id' => $this->student->id,
            'seat_number' => 12,
        ]);

        $examRoom = \Modules\Cbt\Models\CbtExamRoom::create([
            'cbt_exam_id' => $this->exam->id,
            'cbt_room_id' => $room->id,
            'teacher_id' => $this->teacher->id,
            'token' => 'ABCDEF',
            'token_generated_at' => now(),
            'status' => 'started',
        ]);

        $studentExam = CbtStudentExam::create([
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'started',
        ]);

        // Heartbeat when exam is working and normal
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.heartbeat', $this->exam->id));
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'ok']);

        // Proctor forces logout
        $studentExam->update(['status' => 'logged_out']);
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.heartbeat', $this->exam->id));
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'logged_out']);

        // Proctor finishes exam session in room
        $studentExam->update(['status' => 'started']);
        $examRoom->update(['status' => 'ended']);
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.heartbeat', $this->exam->id));
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'ended']);
    }

    public function test_student_anti_cheat_lost_focus_warning_increments_and_blocks(): void
    {
        $studentExam = CbtStudentExam::create([
            'cbt_exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'started',
            'warning_count' => 0,
        ]);

        // 1st warning: block for 1 minute
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.cheat-warning', $this->exam->id));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'warning_count' => 1,
            'is_blocked' => false,
            'status' => 'started',
        ]);
        $this->assertNotNull($response->json('blocked_until'));

        // Update warning count to 1 manually for 2nd test
        $studentExam->refresh();
        $this->assertEquals(1, $studentExam->warning_count);

        // 2nd warning: block for 5 minutes
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.cheat-warning', $this->exam->id));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'warning_count' => 2,
            'is_blocked' => false,
            'status' => 'started',
        ]);

        // 3rd warning: auto logout
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.cheat-warning', $this->exam->id));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'warning_count' => 3,
            'is_blocked' => false,
            'status' => 'logged_out',
        ]);

        // 4th warning: permanent block & forced submit
        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.cbt.cheat-warning', $this->exam->id));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'warning_count' => 4,
            'is_blocked' => true,
            'status' => 'submitted',
        ]);
        
        $studentExam->refresh();
        $this->assertEquals('submitted', $studentExam->status);
        $this->assertTrue($studentExam->is_blocked);
        $this->assertNotNull($studentExam->submitted_at);
    }

    public function test_seating_import_requires_validations_and_assigns_seats(): void
    {
        $room = \Modules\Cbt\Models\CbtRoom::create([
            'name' => 'Lab 1',
            'capacity' => 36,
        ]);

        $adminUser = User::factory()->create(['is_active' => true, 'password_must_change' => false]);
        $adminUser->assignRole('admin');

        // Download template
        $response = $this->actingAs($adminUser)
            ->get(route('cbt.rooms.download-template'));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type'));

        // Let's create an upload simulation.
        // We can write a simple CSV string to a file and upload it.
        $csvContent = "nisn,kursi\n" .
                      "{$this->student->nisn},15\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'seating_test');
        file_put_contents($tempFile, $csvContent);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'seating.csv',
            'text/csv',
            null,
            true // test mode
        );

        $response = $this->actingAs($adminUser)
            ->post(route('cbt.rooms.import-seating', $room->id), [
                'file' => $uploadedFile,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cbt_room_students', [
            'cbt_room_id' => $room->id,
            'student_id' => $this->student->id,
            'seat_number' => 15,
        ]);

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
