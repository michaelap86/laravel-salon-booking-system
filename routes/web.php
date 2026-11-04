<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- public website
Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/services', [SiteController::class, 'services'])->name('services.index');
Route::get('/services/{service:slug}', [SiteController::class, 'service'])->name('services.show');
Route::get('/team', [SiteController::class, 'team'])->name('team.index');
Route::get('/team/{staff}', [SiteController::class, 'member'])->name('team.show');
Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
Route::post('/contact', [SiteController::class, 'sendMessage'])->name('contact.send')->middleware('throttle:6,1');

// -------------------------------------------------------------------- booking
Route::get('/book', [BookingController::class, 'create'])->name('book');
Route::post('/book', [BookingController::class, 'store'])->name('book.store')->middleware('throttle:20,1');
Route::get('/booking/{reference}', [BookingController::class, 'show'])->name('booking.show');
Route::post('/booking/{reference}/cancel', [BookingController::class, 'cancel'])->name('booking.cancel');
Route::get('/booking/{reference}/calendar.ics', [BookingController::class, 'ics'])->name('booking.ics');

Route::prefix('api/booking')->name('api.booking.')->group(function () {
    Route::get('dates', [BookingApiController::class, 'dates'])->name('dates');
    Route::get('slots', [BookingApiController::class, 'slots'])->name('slots');
    Route::post('promo', [BookingApiController::class, 'promo'])->name('promo');
});

// ----------------------------------------------------------------------- auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:15,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ------------------------------------------------------------- client portal
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/appointments', [AccountController::class, 'appointments'])->name('appointments');
    Route::get('/appointments/{appointment}', [AccountController::class, 'show'])->name('appointments.show');
    Route::post('/appointments/{appointment}/cancel', [AccountController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/reschedule', [AccountController::class, 'reschedule'])->name('appointments.reschedule');
    Route::post('/appointments/{appointment}/review', [AccountController::class, 'review'])->name('appointments.review');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::post('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
});

// ---------------------------------------------------------------- admin panel
Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/notifications/seen', [Admin\DashboardController::class, 'seen'])->name('notifications.seen');

    Route::get('/calendar', [Admin\CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/day', [Admin\CalendarController::class, 'day'])->name('calendar.day');
    Route::get('/calendar/events', [Admin\CalendarController::class, 'events'])->name('calendar.events');
    Route::post('/calendar/{appointment}/move', [Admin\CalendarController::class, 'move'])->name('calendar.move');

    Route::get('/appointments', [Admin\AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [Admin\AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [Admin\AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/export', [Admin\AppointmentController::class, 'export'])->name('appointments.export');
    Route::get('/appointments/{appointment}', [Admin\AppointmentController::class, 'show'])->name('appointments.show');
    Route::post('/appointments/{appointment}/status', [Admin\AppointmentController::class, 'status'])->name('appointments.status');
    Route::post('/appointments/{appointment}/reschedule', [Admin\AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::post('/appointments/{appointment}/remind', [Admin\AppointmentController::class, 'remind'])->name('appointments.remind');
    Route::post('/appointments/{appointment}/notes', [Admin\AppointmentController::class, 'notes'])->name('appointments.notes');

    Route::get('/clients', [Admin\ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [Admin\ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [Admin\ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/export', [Admin\ClientController::class, 'export'])->name('clients.export');
    Route::get('/clients/{client}', [Admin\ClientController::class, 'show'])->name('clients.show');
    Route::put('/clients/{client}', [Admin\ClientController::class, 'update'])->name('clients.update');

    Route::middleware('can:manage-business')->group(function () {
        Route::get('/services', [Admin\ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/create', [Admin\ServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [Admin\ServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{service}/edit', [Admin\ServiceController::class, 'edit'])->name('services.edit');
        Route::put('/services/{service}', [Admin\ServiceController::class, 'update'])->name('services.update');
        Route::post('/services/{service}/toggle', [Admin\ServiceController::class, 'toggle'])->name('services.toggle');

        Route::get('/team', [Admin\StaffController::class, 'index'])->name('staff.index');
        Route::get('/team/create', [Admin\StaffController::class, 'create'])->name('staff.create');
        Route::post('/team', [Admin\StaffController::class, 'store'])->name('staff.store');
        Route::get('/team/{staff}/edit', [Admin\StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/team/{staff}', [Admin\StaffController::class, 'update'])->name('staff.update');
        Route::put('/team/{staff}/schedule', [Admin\StaffController::class, 'schedule'])->name('staff.schedule');
        Route::post('/team/{staff}/time-off', [Admin\StaffController::class, 'addTimeOff'])->name('staff.time-off');
        Route::delete('/team/{staff}/time-off/{timeOff}', [Admin\StaffController::class, 'removeTimeOff'])->name('staff.time-off.remove');

        Route::get('/reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/{review}/publish', [Admin\ReviewController::class, 'publish'])->name('reviews.publish');
        Route::post('/reviews/{review}/reply', [Admin\ReviewController::class, 'reply'])->name('reviews.reply');

        Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [Admin\MessageController::class, 'show'])->name('messages.show');
        Route::delete('/messages/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');

        Route::get('/outbox', [Admin\OutboxController::class, 'index'])->name('outbox');
        Route::get('/outbox/{email}', [Admin\OutboxController::class, 'show'])->name('outbox.show');
    });

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports')->can('view-reports');
    Route::get('/reports/export', [Admin\ReportController::class, 'export'])->name('reports.export')->can('view-reports');

    Route::middleware('can:manage-settings')->group(function () {
        Route::get('/settings', [Admin\SettingsController::class, 'show'])->name('settings');
        Route::put('/settings/booking', [Admin\SettingsController::class, 'booking'])->name('settings.booking');
        Route::put('/settings/hours', [Admin\SettingsController::class, 'hours'])->name('settings.hours');
        Route::post('/settings/closures', [Admin\SettingsController::class, 'addClosure'])->name('settings.closures.add');
        Route::delete('/settings/closures/{closure}', [Admin\SettingsController::class, 'removeClosure'])->name('settings.closures.remove');
        Route::post('/settings/promos', [Admin\SettingsController::class, 'addPromo'])->name('settings.promos.add');
        Route::post('/settings/promos/{promo}/toggle', [Admin\SettingsController::class, 'togglePromo'])->name('settings.promos.toggle');
        Route::get('/activity', [Admin\ActivityController::class, 'index'])->name('activity');
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/role', [Admin\UserController::class, 'role'])->name('users.role');
        Route::post('/users/{user}/active', [Admin\UserController::class, 'active'])->name('users.active');
    });
});
