<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdatePrincipalBySuperAdminRequest;
use App\Http\Requests\UpdateSchoolBySuperAdminRequest;
use App\Models\School;
use App\Models\SchoolInvoice;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SchoolRegistrationController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'super_admin') {
            abort(403, 'Only super admin can register schools.');
        }

        return Inertia::render('Schools/RegisterSchool', [
            'submitRoute' => route('superadmin.schools.store'),
            'backRoute' => route('superadmin.dashboard'),
            'isSuperAdminContext' => true,
        ]);
    }

    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data): void {
                $school = School::query()->create([
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'location' => $data['location'] ?? null,
                    'county' => $data['county'] ?? null,
                    'type' => $data['type'] ?? null,
                    'status' => $data['status'] ?? 'active',
                    'note' => $data['note'] ?? null,
                    'code' => $data['code'] ?? null,
                    'logo_path' => $data['logo_path'] ?? null,
                    'hero_image_path' => $data['hero_image_path'] ?? null,
                ]);

                User::query()->create([
                    'school_id' => $school->id,
                    'name' => $data['principal_name'],
                    'email' => $data['principal_email'],
                    'password' => Hash::make($data['principal_password']),
                    'role' => 'principal',
                    'email_verified_at' => now(),
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintError($e)) {
                return back()
                    ->withInput()
                    ->withErrors($this->mapSchoolUniqueConstraintError($e));
            }

            throw $e;
        }

        return redirect()
            ->route('superadmin.dashboard')
            ->with('success', 'School registered successfully. Principal account created.');
    }

    public function createPrincipalForSchool(School $school): RedirectResponse
    {
        $exists = User::query()
            ->where('school_id', $school->id)
            ->where('role', 'principal')
            ->exists();

        if ($exists) {
            return back()->with('error', 'This school already has a principal account.');
        }

        $domain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'schoolportal.local';
        $baseLocalPart = 'principal.'.Str::slug((string) ($school->slug ?: $school->name));
        $email = $baseLocalPart.'@'.$domain;

        $counter = 2;
        while (User::query()->where('email', $email)->exists()) {
            $email = $baseLocalPart.$counter.'@'.$domain;
            $counter++;
        }

        $temporaryPassword = Str::password(10);

        User::query()->create([
            'school_id' => $school->id,
            'name' => 'Principal - '.$school->name,
            'email' => $email,
            'password' => Hash::make($temporaryPassword),
            'role' => 'principal',
            'email_verified_at' => now(),
        ]);

        return back()->with(
            'success',
            "Principal account created for {$school->name}. Email: {$email} | Temporary Password: {$temporaryPassword}. Please update immediately."
        );
    }

    public function updateSchool(UpdateSchoolBySuperAdminRequest $request, School $school): RedirectResponse
    {
        $data = $request->validated();

        try {
            $school->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'location' => $data['location'] ?? null,
                'county' => $data['county'] ?? null,
                'type' => $data['type'] ?? null,
                'status' => $data['status'] ?? $school->status,
                'note' => $data['note'] ?? null,
                'code' => $data['code'] ?? null,
                'logo_path' => $data['logo_path'] ?? null,
                'hero_image_path' => $data['hero_image_path'] ?? null,
            ]);
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintError($e)) {
                return back()
                    ->withInput()
                    ->withErrors($this->mapSchoolUniqueConstraintError($e));
            }

            throw $e;
        }

        return back()->with('success', 'School details updated successfully.');
    }

    public function updatePrincipal(UpdatePrincipalBySuperAdminRequest $request, User $principal): RedirectResponse
    {
        if ($principal->role !== 'principal') {
            abort(404);
        }

        $data = $request->validated();

        $principal->name = $data['name'];
        $principal->email = $data['email'];

        if (! empty($data['password'])) {
            $principal->password = Hash::make($data['password']);
        }

        $principal->save();

        return back()->with('success', 'Principal details updated successfully.');
    }

    public function superAdminDashboard(): Response
    {
        $schoolsRaw = School::query()
            ->latest('id')
            ->get([
                'id',
                'name',
                'slug',
                'location',
                'county',
                'type',
                'status',
                'note',
                'code',
                'logo_path',
                'hero_image_path',
                'created_at',
            ]);

        $principals = User::query()
            ->where('role', 'principal')
            ->with('school:id,name,slug')
            ->latest('id')
            ->get([
                'id',
                'school_id',
                'name',
                'email',
                'role',
                'created_at',
            ]);

        $principalBySchool = $principals->keyBy('school_id');

        $schools = $schoolsRaw->map(function (School $school) use ($principalBySchool) {
            $principal = $principalBySchool->get($school->id);

            return [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
                'location' => $school->location,
                'county' => $school->county,
                'type' => $school->type,
                'status' => $school->status,
                'note' => $school->note,
                'code' => $school->code,
                'logo_path' => $school->logo_path,
                'hero_image_path' => $school->hero_image_path,
                'created_at' => optional($school->created_at)->format('Y-m-d H:i'),
                'has_principal' => (bool) $principal,
                'principal_name' => optional($principal)->name,
                'principal_email' => optional($principal)->email,
            ];
        });

        $billing = [
            'enabled' => false,
            'total_invoiced' => '0.00',
            'outstanding' => '0.00',
            'overdue_count' => 0,
            'due_7_days' => 0,
            'recent_invoices' => [],
            'renewal_watchlist' => [],
        ];

        if (Schema::hasTable('school_invoices')) {
            $totalInvoiced = (float) SchoolInvoice::query()->sum('invoiced_amount');
            $outstanding = (float) SchoolInvoice::query()->sum('balance_amount');

            $overdueCount = (int) SchoolInvoice::query()
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count();

            $due7Days = (int) SchoolInvoice::query()
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->whereDate('due_date', '>=', now()->toDateString())
                ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
                ->count();

            $recentInvoices = SchoolInvoice::query()
                ->with('school:id,name,slug')
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(function (SchoolInvoice $invoice) {
                    return [
                        'id' => $invoice->id,
                        'reference_no' => $invoice->reference_no,
                        'school_name' => optional($invoice->school)->name,
                        'school_slug' => optional($invoice->school)->slug,
                        'item' => $invoice->item,
                        'status' => $invoice->status,
                        'due_date' => optional($invoice->due_date)->toDateString(),
                        'invoiced_amount' => number_format((float) $invoice->invoiced_amount, 2),
                        'balance_amount' => number_format((float) $invoice->balance_amount, 2),
                    ];
                });

            $renewalWatchlist = SchoolInvoice::query()
                ->with('school:id,name,slug')
                ->where('category', 'subscription')
                ->where('balance_amount', '>', 0)
                ->whereDate('due_date', '>=', now()->toDateString())
                ->whereDate('due_date', '<=', now()->addDays(45)->toDateString())
                ->orderBy('due_date')
                ->limit(15)
                ->get()
                ->map(function (SchoolInvoice $invoice) {
                    return [
                        'id' => $invoice->id,
                        'school_name' => optional($invoice->school)->name,
                        'school_slug' => optional($invoice->school)->slug,
                        'reference_no' => $invoice->reference_no,
                        'due_date' => optional($invoice->due_date)->toDateString(),
                        'balance_amount' => number_format((float) $invoice->balance_amount, 2),
                    ];
                });

            $billing = [
                'enabled' => true,
                'total_invoiced' => number_format($totalInvoiced, 2),
                'outstanding' => number_format($outstanding, 2),
                'overdue_count' => $overdueCount,
                'due_7_days' => $due7Days,
                'recent_invoices' => $recentInvoices,
                'renewal_watchlist' => $renewalWatchlist,
            ];
        }

        return Inertia::render('SuperAdmin/Dashboard', [
            'schools' => $schools,
            'schoolAdmins' => $principals,
            'totals' => [
                'schools' => $schools->count(),
                'school_admins' => $principals->count(),
                'schools_without_principal' => $schools->where('has_principal', false)->count(),
            ],
            'billing' => $billing,
        ]);
    }

    private function isUniqueConstraintError(QueryException $e): bool
    {
        return (string) $e->getCode() === '23000';
    }

    private function mapSchoolUniqueConstraintError(QueryException $e): array
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'schools_slug_unique_idx') || str_contains($message, 'slug')) {
            return ['slug' => 'This school slug is already in use. Please choose a different slug.'];
        }

        if (str_contains($message, 'schools_code_unique_idx') || str_contains($message, 'code')) {
            return ['code' => 'This school code is already in use. Please choose a different code.'];
        }

        return ['name' => 'The school could not be saved due to a uniqueness conflict. Please retry.'];
    }
}