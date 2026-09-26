<?php

namespace Tests\Feature\Admin;

use App\Models\FinanceTransaction;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentFeeAccount;
use App\Models\StudentFeePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFinanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function bindCurrentSchool(School $school): void
    {
        app()->instance('currentSchool', $school);
    }

    private function makePrincipal(School $school): User
    {
        $user = User::factory()->create([
            'role' => 'principal',
            'school_id' => $school->id,
        ]);

        return $user;
    }

    private function makeStudent(School $school): Student
    {
        return Student::query()->create([
            'school_id' => $school->id,
            'admission_no' => 'ADM-' . fake()->unique()->numerify('####'),
            'first_name' => 'Test',
            'last_name' => 'Student',
            'class_level' => 'Form 1',
            'stream' => 'A',
        ]);
    }

    public function test_account_upsert_creates_or_updates_student_account(): void
    {
        $school = School::factory()->create();
        $this->bindCurrentSchool($school);

        $user = $this->makePrincipal($school);
        $student = $this->makeStudent($school);

        $response = $this->actingAs($user)->post(route('student-finance.account.upsert'), [
            'student_id' => $student->id,
            'fee_structure_id' => null,
            'total_fee_due' => 12000,
            'sponsor_org_name' => null,
            'sponsor_org_id' => null,
            'notes' => 'Initial setup',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('student_fee_accounts', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'total_fee_due' => '12000.00',
        ]);
    }

    public function test_payment_reduces_balance_and_creates_transaction(): void
    {
        $school = School::factory()->create();
        $this->bindCurrentSchool($school);

        $user = $this->makePrincipal($school);
        $student = $this->makeStudent($school);

        $account = StudentFeeAccount::query()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'total_fee_due' => 10000,
            'fee_structure_id' => null,
        ]);

        $response = $this->actingAs($user)->post(route('student-finance.payment.store'), [
            'student_id' => $student->id,
            'amount' => 2500,
            'payment_method' => 'cash',
            'organization_name' => null,
            'organization_id' => null,
            'paid_at' => now()->toDateString(),
            'notes' => 'First installment',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $account->refresh();
        $this->assertSame(7500.0, (float) $account->total_fee_due);

        $this->assertDatabaseHas('student_fee_payments', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'student_fee_account_id' => $account->id,
            'payment_method' => 'cash',
            'amount' => '2500.00',
        ]);

        $this->assertTrue(
            FinanceTransaction::query()
                ->where('school_id', $school->id)
                ->where('type', 'income')
                ->where('category', 'student_fee')
                ->where('amount', 2500)
                ->exists()
        );
    }

    public function test_overpayment_is_rejected_and_state_is_unchanged(): void
    {
        $school = School::factory()->create();
        $this->bindCurrentSchool($school);

        $user = $this->makePrincipal($school);
        $student = $this->makeStudent($school);

        $account = StudentFeeAccount::query()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'total_fee_due' => 3000,
            'fee_structure_id' => null,
        ]);

        $response = $this->from(route('student-finance.index'))
            ->actingAs($user)
            ->post(route('student-finance.payment.store'), [
                'student_id' => $student->id,
                'amount' => 5000,
                'payment_method' => 'cash',
                'organization_name' => null,
                'organization_id' => null,
                'paid_at' => now()->toDateString(),
                'notes' => 'Should fail',
            ]);

        $response->assertRedirect(route('student-finance.index'));
        $response->assertSessionHasErrors('amount');

        $account->refresh();
        $this->assertSame(3000.0, (float) $account->total_fee_due);

        $this->assertFalse(
            StudentFeePayment::query()
                ->where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('amount', 5000)
                ->exists()
        );
    }
}