<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;

// Authentication routes (admin project served at admin.ambatu.my.id)
Route::get('/login', [\App\Http\Controllers\AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/login', [\App\Http\Controllers\AdminAuthController::class, 'login'])->name('admin.login.post');
Route::get('/password/forgot', [\App\Http\Controllers\AdminAuthController::class, 'showForgot'])->name('admin.password.request');
Route::post('/password/email', [\App\Http\Controllers\AdminAuthController::class, 'sendResetLink'])->name('admin.password.email');
Route::get('/password/reset/{token}', [\App\Http\Controllers\AdminAuthController::class, 'showResetForm'])->name('admin.password.reset');
Route::post('/password/reset', [\App\Http\Controllers\AdminAuthController::class, 'resetPassword'])->name('admin.password.update');
Route::post('/logout', [\App\Http\Controllers\AdminAuthController::class, 'logout'])->name('admin.logout');

// Protected admin routes
Route::middleware(['web', \App\Http\Middleware\EnsureAdmin::class])->group(function () {
	Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');

	// Vehicles
	Route::get('/vehicles', [AdminController::class, 'vehicles'])->name('admin.vehicles');
	Route::get('/vehicles/create', [AdminController::class, 'createVehicle'])->name('admin.vehicles.create');
	Route::post('/vehicles', [AdminController::class, 'storeVehicle'])->name('admin.vehicles.store');
	Route::get('/vehicles/{vehicle}/edit', [AdminController::class, 'editVehicle'])->name('admin.vehicles.edit');
	Route::put('/vehicles/{vehicle}', [AdminController::class, 'updateVehicle'])->name('admin.vehicles.update');
	Route::delete('/vehicles/{vehicle}', [AdminController::class, 'deleteVehicle'])->name('admin.vehicles.delete');

	// Schedules
	Route::get('/schedules', [AdminController::class, 'schedules'])->name('admin.schedules');
	Route::get('/schedules/create', [AdminController::class, 'createSchedule'])->name('admin.schedules.create');
	Route::post('/schedules', [AdminController::class, 'storeSchedule'])->name('admin.schedules.store');
	Route::get('/schedules/{schedule}/edit', [AdminController::class, 'editSchedule'])->name('admin.schedules.edit');
	Route::put('/schedules/{schedule}', [AdminController::class, 'updateSchedule'])->name('admin.schedules.update');
	Route::delete('/schedules/{schedule}', [AdminController::class, 'deleteSchedule'])->name('admin.schedules.delete');

	// Users
	Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
	Route::get('/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
	Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
	Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
	Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
	Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');

	// Bookings Monitoring & Verification
	Route::get('/bookings', [AdminController::class, 'bookings'])->name('admin.bookings');
	Route::get('/verifications', [AdminController::class, 'verifications'])->name('admin.bookings.verifications');
	Route::post('/bookings/{booking}/confirm', [AdminController::class, 'confirmBookingPayment'])->name('admin.bookings.confirm');
	Route::post('/bookings/{booking}/reject', [AdminController::class, 'rejectBookingPayment'])->name('admin.bookings.reject');

	// Route Templates
	Route::get('/route-templates', [\App\Http\Controllers\RouteTemplateController::class, 'index'])->name('admin.route-templates.index');
	Route::post('/route-templates', [\App\Http\Controllers\RouteTemplateController::class, 'store'])->name('admin.route-templates.store');
	Route::post('/route-templates/{routeTemplate}/toggle', [\App\Http\Controllers\RouteTemplateController::class, 'update'])->name('admin.route-templates.toggle');
	Route::delete('/route-templates/{routeTemplate}', [\App\Http\Controllers\RouteTemplateController::class, 'destroy'])->name('admin.route-templates.destroy');
	Route::post('/route-templates/generate', [\App\Http\Controllers\RouteTemplateController::class, 'generate'])->name('admin.route-templates.generate');

	// Trips
	Route::get('/trips', [AdminController::class, 'trips'])->name('admin.trips');
	Route::get('/active-trips-locations', [AdminController::class, 'activeTripsLocations'])->name('admin.trips.locations');
});
