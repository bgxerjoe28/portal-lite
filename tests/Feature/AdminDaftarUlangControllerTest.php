<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\ClassroomStudent;
use Modules\Akademik\Models\Student;
use Modules\DaftarUlang\Models\NewStudent;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDaftarUlangControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required roles for Spatie Permission
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'siswa', 'guard_name' => 'web']);
    }

    public function test_unauthorized_users_cannot_access_reset_all(): void
    {
        $response = $this->delete(route('admin.daftar-ulang.reset-all'));
        $response->assertRedirect('/login');

        $nonAdmin = User::factory()->create([
            'is_active' => true,
            'password_must_change' => false,
        ]);
        $response = $this->actingAs($nonAdmin)->delete(route('admin.daftar-ulang.reset-all'));
        $response->assertStatus(403);
    }

    public function test_admin_can_reset_all_data_for_active_academic_year(): void
    {
        Storage::fake('public');

        // 1. Create Active Academic Year
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        // 2. Create Inactive Academic Year
        $inactiveYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);

        // 3. Create Classroom
        $classroom = Classroom::create([
            'academic_year_id' => $activeYear->id,
            'name' => 'X IPA 1',
            'level' => 10,
        ]);

        // 4. Create mock files
        Storage::disk('public')->put('daftar_ulang/kk_active.pdf', 'active kk content');
        Storage::disk('public')->put('daftar_ulang/kk_inactive.pdf', 'inactive kk content');

        // 5. Create NewStudent in Inactive Year (should not be deleted)
        $inactiveNewStudent = NewStudent::create([
            'academic_year_id' => $inactiveYear->id,
            'no_pendaftaran' => 'PPDB-2025-0001',
            'full_name' => 'Old Student',
            'status' => 'imported',
            'login_code' => 'OLD123',
            'file_kk' => 'daftar_ulang/kk_inactive.pdf',
        ]);

        // 6. Create NewStudents in Active Year (should be deleted)
        // Student A: Status imported (not migrated)
        $newStudentA = NewStudent::create([
            'academic_year_id' => $activeYear->id,
            'no_pendaftaran' => 'PPDB-2026-0001',
            'full_name' => 'Active Student A',
            'status' => 'imported',
            'login_code' => 'ACT123',
        ]);

        // Student B: Status migrated (has active student profile, user account, classroom student mapping, and files)
        $studentBUser = User::factory()->create([
            'name' => 'Active Student B',
            'email' => 'studentb@school.id',
            'is_active' => true,
            'password_must_change' => false,
        ]);
        $studentBUser->assignRole('siswa');

        $studentBProfile = Student::create([
            'user_id' => $studentBUser->id,
            'full_name' => 'Active Student B',
            'nisn' => '9999999999',
            'gender' => true,
        ]);

        $classroomStudent = ClassroomStudent::create([
            'academic_year_id' => $activeYear->id,
            'student_id' => $studentBProfile->id,
            'classroom_id' => $classroom->id,
            'status' => 'aktif',
        ]);

        $newStudentB = NewStudent::create([
            'academic_year_id' => $activeYear->id,
            'no_pendaftaran' => 'PPDB-2026-0002',
            'full_name' => 'Active Student B',
            'status' => 'migrated',
            'nisn' => '9999999999',
            'email' => 'studentb@school.id',
            'login_code' => 'ACT124',
            'file_kk' => 'daftar_ulang/kk_active.pdf',
        ]);

        // 7. Act as Admin and dispatch Reset All
        $admin = User::factory()->create([
            'is_active' => true,
            'password_must_change' => false,
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->delete(route('admin.daftar-ulang.reset-all'));

        // 8. Assertions
        $response->assertRedirect(route('admin.daftar-ulang.index'));
        $response->assertSessionHas('success');

        // Active year NewStudents should be deleted (forceDelete, so they are not in DB at all)
        $this->assertDatabaseMissing('new_students', ['id' => $newStudentA->id]);
        $this->assertDatabaseMissing('new_students', ['id' => $newStudentB->id]);

        // Inactive year NewStudent should NOT be deleted
        $this->assertDatabaseHas('new_students', ['id' => $inactiveNewStudent->id]);

        // Active year migrated data (Student, User, ClassroomStudent mapping) should be deleted
        $this->assertDatabaseMissing('users', ['id' => $studentBUser->id]);
        $this->assertDatabaseMissing('students', ['id' => $studentBProfile->id]);
        $this->assertDatabaseMissing('classroom_students', ['id' => $classroomStudent->id]);

        // Physical files of active year student should be deleted
        Storage::disk('public')->assertMissing('daftar_ulang/kk_active.pdf');

        // Physical files of inactive year student should NOT be deleted
        Storage::disk('public')->assertExists('daftar_ulang/kk_inactive.pdf');
    }
}
