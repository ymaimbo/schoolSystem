<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                if (!Schema::hasColumn('exams', 'subject')) {
                    $table->string('subject', 120)->nullable()->after('class_level');
                }

                if (!Schema::hasColumn('exams', 'stream')) {
                    $table->string('stream', 120)->nullable()->after('class_level');
                }

                if (!Schema::hasColumn('exams', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id')->index();
                }
            });

            if (Schema::hasColumn('exams', 'subject')) {
                DB::table('exams')
                    ->whereNull('subject')
                    ->orWhere('subject', '')
                    ->update(['subject' => 'General']);
            }
        }

        if (!Schema::hasTable('subject_teacher_assignments')) {
            Schema::create('subject_teacher_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id')->index();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('subject', 120);
                $table->string('class_level', 120);
                $table->string('stream', 120)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->unique(
                    ['teacher_id', 'subject', 'class_level', 'stream'],
                    'sta_teacher_subject_class_stream_unique'
                );
            });
        }

        if (Schema::hasTable('class_teacher_assignments')) {
            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('class_teacher_assignments', 'stream')) {
                    $table->string('stream', 120)->nullable()->after('class_level');
                }

                if (!Schema::hasColumn('class_teacher_assignments', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('teacher_id')->index();
                }

                if (!Schema::hasColumn('class_teacher_assignments', 'is_active')) {
                    $table->boolean('is_active')->default(true)->index();
                }
            });
        }

        if (Schema::hasTable('exam_results')) {
            Schema::table('exam_results', function (Blueprint $table) {
                if (!Schema::hasColumn('exam_results', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('remarks')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_results')) {
            Schema::table('exam_results', function (Blueprint $table) {
                if (Schema::hasColumn('exam_results', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
            });
        }

        if (Schema::hasTable('subject_teacher_assignments')) {
            Schema::dropIfExists('subject_teacher_assignments');
        }

        if (Schema::hasTable('class_teacher_assignments')) {
            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('class_teacher_assignments', 'is_active')) {
                    $table->dropColumn('is_active');
                }
                if (Schema::hasColumn('class_teacher_assignments', 'school_id')) {
                    $table->dropColumn('school_id');
                }
                if (Schema::hasColumn('class_teacher_assignments', 'stream')) {
                    $table->dropColumn('stream');
                }
            });
        }

        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                if (Schema::hasColumn('exams', 'school_id')) {
                    $table->dropColumn('school_id');
                }
                if (Schema::hasColumn('exams', 'stream')) {
                    $table->dropColumn('stream');
                }
                if (Schema::hasColumn('exams', 'subject')) {
                    $table->dropColumn('subject');
                }
            });
        }
    }
};