<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\Menu;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Assignments Table
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('grading_component_id')->nullable()->constrained('grading_components')->nullOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('due_at');
            $table->boolean('is_published')->default(false);
            $table->foreignId('grading_item_id')->nullable()->constrained('grading_items')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Assignment Questions Table
        Schema::create('assignment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->enum('type', ['essay', 'mcq'])->default('essay');
            $table->text('question_text');
            $table->json('options')->nullable(); // For MCQ: [{"key":"A","text":"..."}, ...]
            $table->text('correct_answer')->nullable(); // For MCQ option key e.g. "A"
            $table->json('keywords')->nullable(); // For Essay: array of keywords or reference terms
            $table->boolean('allow_url_upload')->default(false); // For Essay: permit Google Drive / URL submission
            $table->float('max_score')->default(10);
            $table->integer('sort_order')->default(1);
            $table->timestamps();
        });

        // 3. Assignment Submissions Table
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['submitted', 'graded'])->default('submitted');
            $table->dateTime('submitted_at');
            $table->float('total_score')->default(0); // Scaled to max 100
            $table->text('teacher_notes')->nullable();
            $table->timestamps();
        });

        // 4. Assignment Answers Table
        Schema::create('assignment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('assignment_questions')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->string('url_upload')->nullable();
            $table->float('similarity_percentage')->nullable(); // 0 - 100 % for essay
            $table->float('score')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });

        // 5. Add Menu for Guru
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $guruRole = Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);

        $penugasanMenu = Menu::updateOrCreate(
            ['to' => '/teacher/assignments'],
            [
                'label' => 'Penugasan Mata Pelajaran',
                'icon' => 'pi pi-file-edit',
                'sort_order' => 4,
                'is_active' => true,
            ]
        );

        if (!$penugasanMenu->roles->contains($guruRole->id)) {
            $penugasanMenu->roles()->attach($guruRole);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $penugasanMenu = Menu::where('to', '/teacher/assignments')->first();
        if ($penugasanMenu) {
            $penugasanMenu->roles()->detach();
            $penugasanMenu->delete();
        }

        Schema::dropIfExists('assignment_answers');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignment_questions');
        Schema::dropIfExists('assignments');
    }
};
