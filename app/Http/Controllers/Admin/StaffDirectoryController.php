<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRecordRequest;
use App\Http\Requests\UpdateStaffRecordRequest;
use App\Models\StaffRecord;
use App\Models\User;
use App\Notifications\StaffLoginCredentialsNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class StaffDirectoryController extends Controller
{
    private const ASSIGNABLE_ROLES = [
        'deputy_principal',
        'dean',
        'hod',
        'school_examiner',
        'class_teacher',
        'secretary',
        'accountant',
        'store_keeper',
    ];

    public function index(Request $request): Response
    {
        $school = app('currentSchool');

        $records = StaffRecord::query()
            ->where('school_id', $school->id)
            ->with('loginUser:id,name,email,role')
            ->latest('id')
            ->get()
            ->map(function (StaffRecord $record) {
                return [
                    'id' => $record->id,
                    'full_name' => $record->full_name,
                    'staff_type' => $record->staff_type,
                    'role_category' => $record->role_category,
                    'department' => $record->department,
                    'phone' => $record->phone,
                    'email' => $record->email,
                    'employment_status' => $record->employment_status,
                    'is_on_duty' => (bool) $record->is_on_duty,
                    'duty_date' => optional($record->duty_date)?->toDateString(),
                    'notes' => $record->notes,
                    'has_login' => (bool) $record->login_user_id,
                    'login_email' => optional($record->loginUser)?->email,
                    'login_role' => optional($record->loginUser)?->role,
                    'login_user_id' => $record->login_user_id,
                ];
            })
            ->values();

        return Inertia::render('Admin/Staff/Index', [
            // keep both keys for frontend compatibility
            'records' => $records,
            'staffRecords' => $records,
            'assignableRoles' => self::ASSIGNABLE_ROLES,
        ]);
    }

    public function store(StoreStaffRecordRequest $request): RedirectResponse
    {
        $school = app('currentSchool');
        $data = $request->validated();

        $notifyPayload = null;

        DB::transaction(function () use ($request, $school, $data, &$notifyPayload): void {
            $record = StaffRecord::query()->create([
                'school_id' => $school->id,
                'full_name' => $data['full_name'],
                'staff_type' => $data['staff_type'] ?? null,
                'role_category' => $data['role_category'] ?? ($data['login_role'] ?? null),
                'department' => $data['department'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? ($data['login_email'] ?? null),
                'employment_status' => $data['employment_status'] ?? null,
                'is_on_duty' => (bool) ($data['is_on_duty'] ?? false),
                'duty_date' => $data['duty_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);

            if (!empty($data['assign_login'])) {
                $plainPassword = $data['login_password'];

                $loginUser = User::query()->create([
                    'school_id' => $school->id,
                    'name' => $data['full_name'],
                    'email' => $data['login_email'],
                    'password' => Hash::make($plainPassword),
                    'role' => $data['login_role'],
                    'email_verified_at' => now(),
                ]);

                $record->update([
                    'login_user_id' => $loginUser->id,
                    'email' => $data['login_email'],
                    'role_category' => $data['login_role'],
                ]);

                $notifyPayload = [
                    'user_id' => $loginUser->id,
                    'email' => $data['login_email'],
                    'role' => $data['login_role'],
                    'plain_password' => $plainPassword,
                    'school_name' => $school->name,
                    'school_slug' => $school->slug,
                ];
            }
        });

        // notify AFTER successful commit
        if ($notifyPayload) {
            $loginUser = User::query()->find($notifyPayload['user_id']);
            if ($loginUser) {
                $loginUser->notify(new StaffLoginCredentialsNotification(
                    schoolName: $notifyPayload['school_name'],
                    schoolSlug: $notifyPayload['school_slug'],
                    role: $notifyPayload['role'],
                    email: $notifyPayload['email'],
                    plainPassword: $notifyPayload['plain_password'],
                ));
            }
        }

        return back()->with('success', 'Staff record created successfully.');
    }

    public function update(UpdateStaffRecordRequest $request, StaffRecord $staffRecord): RedirectResponse
    {
        $school = app('currentSchool');
        $this->assertSameSchool($staffRecord, $school->id);

        $data = $request->validated();

        DB::transaction(function () use ($request, $school, $staffRecord, $data): void {
            $staffRecord->update([
                'full_name' => $data['full_name'],
                'staff_type' => $data['staff_type'] ?? null,
                'role_category' => $data['role_category'] ?? ($data['login_role'] ?? $staffRecord->role_category),
                'department' => $data['department'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? ($data['login_email'] ?? $staffRecord->email),
                'employment_status' => $data['employment_status'] ?? null,
                'is_on_duty' => (bool) ($data['is_on_duty'] ?? false),
                'duty_date' => $data['duty_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Staff record updated successfully.');
    }

    public function destroy(StaffRecord $staffRecord): RedirectResponse
    {
        $school = app('currentSchool');
        $this->assertSameSchool($staffRecord, $school->id);

        DB::transaction(function () use ($staffRecord): void {
            if ($staffRecord->login_user_id) {
                $linkedUser = User::query()->find($staffRecord->login_user_id);
                if ($linkedUser && !in_array($linkedUser->role, ['super_admin', 'principal'], true)) {
                    $linkedUser->delete();
                }
            }

            $staffRecord->delete();
        });

        return back()->with('success', 'Staff record removed.');
    }

    private function assertSameSchool(StaffRecord $record, int $schoolId): void
    {
        abort_if((int) $record->school_id !== $schoolId, 404);
    }
}