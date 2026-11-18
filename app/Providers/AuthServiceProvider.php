<?php

namespace App\Providers;

use App\Models\ExamResult; // <-- Changed to ExamResult
use App\Policies\ExamResultPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ExamResult::class => ExamResultPolicy::class, // <-- Changed to ExamResult
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}