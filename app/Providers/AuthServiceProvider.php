<?php

namespace App\Providers;

use App\Models\Exam;
use App\Policies\ExamResultPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Exam::class => ExamResultPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}