<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });

        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday')->unique(); // 0 = Sunday
            $table->boolean('is_open')->default(true);
            $table->time('opens')->nullable();
            $table->time('closes')->nullable();
        });

        Schema::create('closures', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('reason');
        });

        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 8);
            $table->string('theme', 20);
            $table->string('blurb');
            $table->boolean('ladies_pricing')->default(false); // drives the developer's "I'm a guy" disclaimer
            $table->unsignedSmallInteger('sort')->default(0);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->unsignedSmallInteger('duration'); // minutes
            $table->unsignedSmallInteger('buffer')->default(0); // minutes of cleanup after
            $table->unsignedInteger('price'); // cents
            $table->boolean('deposit_required')->default(true);
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('title');
            $table->text('bio');
            $table->string('specialties');
            $table->string('color', 7);
            $table->unsignedTinyInteger('years')->default(3);
            $table->string('instagram')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('service_staff', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->primary(['service_id', 'staff_id']);
        });

        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->boolean('is_working')->default(true);
            $table->time('starts')->nullable();
            $table->time('ends')->nullable();
            $table->time('break_starts')->nullable();
            $table->time('break_ends')->nullable();
            $table->unique(['staff_id', 'weekday']);
        });

        Schema::create('time_off', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason')->nullable();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 30)->nullable();
            $table->date('birthday')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('vip')->default(false);
            $table->boolean('marketing_opt_in')->default(true);
            $table->string('source', 20)->default('web');
            $table->timestamps();
        });

        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedTinyInteger('percent');
            $table->string('description');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('uses')->default(0);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 10)->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status', 12)->default('confirmed')->index();
            $table->unsignedInteger('price'); // cents, after discount
            $table->unsignedInteger('deposit')->default(0);
            $table->boolean('deposit_paid')->default(false);
            $table->string('promo_code')->nullable();
            $table->string('source', 12)->default('web');
            $table->text('notes')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
            $table->index(['staff_id', 'start_at']);
            $table->index('start_at');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->boolean('published')->default(true);
            $table->text('reply')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);
            $table->string('subject')->nullable();
            $table->string('details')->nullable();
            $table->string('url')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('sent_emails', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('subject');
            $table->longText('html');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['sent_emails', 'activities', 'messages', 'reviews', 'appointments', 'promo_codes', 'clients', 'time_off',
            'working_hours', 'service_staff', 'staff', 'services', 'service_categories', 'closures', 'business_hours', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
