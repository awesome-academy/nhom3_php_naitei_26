<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_user_cannot_access_import_page(): void
    {
        $response = $this->get('/admin/users/import');

        $response->assertRedirect('/admin/login');
    }

    public function test_citizen_cannot_access_import_page(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();

        $response = $this->actingAs($citizen)->get('/admin/users/import');

        $response->assertForbidden();
    }

    public function test_staff_cannot_access_import_page(): void
    {
        $staff = User::factory()->withRole(UserRole::Staff)->create();

        $response = $this->actingAs($staff)->get('/admin/users/import');

        $response->assertForbidden();
    }

    public function test_manager_cannot_access_import_page(): void
    {
        $manager = User::factory()->withRole(UserRole::Manager)->create();

        $response = $this->actingAs($manager)->get('/admin/users/import');

        $response->assertForbidden();
    }

    public function test_admin_can_view_import_page(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $response = $this->actingAs($admin)->get('/admin/users/import');

        $response->assertOk()
            ->assertViewIs('admin.users.import')
            ->assertSee('Nhập dữ liệu tài khoản từ tệp CSV');
    }

    public function test_admin_can_import_citizens_from_csv_with_partial_errors(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        // Create an existing user to trigger duplicate email/CCCD errors
        User::factory()->create([
            'email' => 'existing@gmail.com',
            'citizen_id' => '001098111222',
        ]);

        $csvContent = implode("\n", [
            'name,email,citizen_id,phone,address,date_of_birth',
            'Nguyen Van A,citizen1@gmail.com,001098000001,0987654321,Hanoi,1990-01-01',
            'Nguyen Van B,existing@gmail.com,001098000002,0987654322,Hanoi,1991-02-02', // duplicate email
            'Nguyen Van C,citizen3@gmail.com,001098111222,0987654323,Hanoi,1992-03-03', // duplicate citizen_id
            'Nguyen Van D,citizen4@gmail.com,001098000004,0987654324,Hanoi,1993-04-04',
            'Nguyen Van E,citizen5@gmail.com,001098000005,0987654325,Hanoi,1994-05-05',
        ]);

        $file = UploadedFile::fake()->createWithContent('citizens.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post('/admin/users/import/citizens', [
                'csv_file' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_rows', 5)
            ->assertJsonPath('data.success_count', 3)
            ->assertJsonPath('data.failure_count', 2);

        $this->assertDatabaseHas('users', [
            'email' => 'citizen1@gmail.com',
            'citizen_id' => '001098000001',
            'role' => UserRole::Citizen->value,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'citizen4@gmail.com',
            'citizen_id' => '001098000004',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'citizen5@gmail.com',
            'citizen_id' => '001098000005',
        ]);
    }

    public function test_admin_can_import_staff_from_csv(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $department = Department::factory()->create();

        $csvContent = implode("\n", [
            'name,email,department_id,role,phone',
            'Staff One,staff1@gov.vn,'.$department->id.',staff,0912345678',
            'Manager One,manager1@gov.vn,'.$department->id.',manager,0988776655',
        ]);

        $file = UploadedFile::fake()->createWithContent('staff.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post('/admin/users/import/staff', [
                'csv_file' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.success_count', 2)
            ->assertJsonPath('data.failure_count', 0);

        $this->assertDatabaseHas('users', [
            'email' => 'staff1@gov.vn',
            'role' => UserRole::Staff->value,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'manager1@gov.vn',
            'role' => UserRole::Manager->value,
        ]);

        $staffUser = User::query()->where('email', 'staff1@gov.vn')->first();
        $this->assertTrue($staffUser->departments->contains($department->id));
    }

    public function test_import_handles_utf8_bom_and_in_file_duplicates(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        // Include UTF-8 BOM (\xEF\xBB\xBF) and duplicate email on line 4
        $bom = "\xEF\xBB\xBF";
        $csvContent = $bom.implode("\n", [
            'name,email,citizen_id,phone,address,date_of_birth',
            'User A,usera@gmail.com,001098000101,0987654321,Hanoi,1990-01-01',
            'User B,usera@gmail.com,001098000102,0987654322,Hanoi,1991-02-02', // duplicate email in same file
        ]);

        $file = UploadedFile::fake()->createWithContent('citizens_bom.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post('/admin/users/import/citizens', [
                'csv_file' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.success_count', 1)
            ->assertJsonPath('data.failure_count', 1);

        $this->assertDatabaseHas('users', [
            'email' => 'usera@gmail.com',
            'citizen_id' => '001098000101',
        ]);
    }

    public function test_import_fails_when_csv_headers_are_invalid(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $invalidCsv = implode("\n", [
            'invalid_col1,invalid_col2',
            'val1,val2',
        ]);

        $file = UploadedFile::fake()->createWithContent('invalid_headers.csv', $invalidCsv);

        $response = $this->actingAs($admin)
            ->post('/admin/users/import/citizens', [
                'csv_file' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.failure_count', 0);
    }

    public function test_import_rolls_back_entirely_when_rollback_on_error_is_enabled(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();
        User::factory()->create(['email' => 'duplicate@gmail.com']);

        $csvContent = implode("\n", [
            'name,email,citizen_id,phone,address,date_of_birth',
            'User One,valid.email@gmail.com,001098000999,0987654321,Hanoi,1990-01-01',
            'User Two,duplicate@gmail.com,001098000888,0987654322,Hanoi,1991-02-02', // duplicate email
        ]);

        $file = UploadedFile::fake()->createWithContent('rollback.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post('/admin/users/import/citizens', [
                'csv_file' => $file,
                'rollback_on_error' => '1',
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.success_count', 0)
            ->assertJsonPath('data.failure_count', 1);

        $this->assertDatabaseMissing('users', [
            'email' => 'valid.email@gmail.com',
        ]);
    }
}
