<?php

use App\Http\Controllers\BookingGygSyncController;
use App\Http\Controllers\BookingMagicLinkPageController;
use App\Http\Controllers\BookingReminderController;
use App\Http\Controllers\BookingRescheduleController;
use App\Http\Controllers\BookingResourceAllocationController;
use App\Http\Controllers\BookingRevenueRecapController;
use App\Http\Controllers\ChannelSyncLogController;
use App\Http\Controllers\ManualBookingController;
use App\Http\Controllers\OperationsResourceController;
use App\Http\Controllers\SuperAdminImpersonationController;
use App\Http\Controllers\SuperAdminLandingPricingController;
use App\Http\Controllers\TenantAuditLogController;
use App\Http\Controllers\TenantCategoryController;
use App\Http\Controllers\TenantInvoiceController;
use App\Http\Controllers\TenantRolePermissionController;
use App\Http\Controllers\TenantUserController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\TourDayCapacityController;
use App\Http\Controllers\TravelAgentController;
use App\Http\Controllers\WhatsAppTemplateController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
//Language Translation
Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);

Route::get('/', [App\Http\Controllers\HomeController::class, 'root'])->name('root');
Route::get('/booking/{booking}/respond', [BookingMagicLinkPageController::class, 'show'])->name('bookings.magic-link.show');
Route::post('/booking/{booking}/respond', [BookingMagicLinkPageController::class, 'submit'])->name('bookings.magic-link.submit');

//Update User Details
Route::post('/update-profile/{id}', [App\Http\Controllers\HomeController::class, 'updateProfile'])->name('updateProfile');
Route::post('/update-password/{id}', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('updatePassword');
Route::get('/apps-whatsapp-template-message', [WhatsAppTemplateController::class, 'index'])->name('whatsapp-template-message.index');
Route::post('/apps-whatsapp-template-message', [WhatsAppTemplateController::class, 'update'])->name('whatsapp-template-message.update');
Route::post('/apps-whatsapp-template-message/{templateId}/delete', [WhatsAppTemplateController::class, 'destroy'])->name('whatsapp-template-message.destroy');
Route::post('/apps-bookings/{booking}/send-reminder', [BookingReminderController::class, 'send'])->name('bookings.send-reminder');
Route::post('/apps-bookings/{booking}/gyg-sync', [BookingGygSyncController::class, 'store'])->name('bookings.gyg-sync');
Route::post('/apps-bookings/{booking}/reschedule-workflow', [BookingRescheduleController::class, 'updateWorkflow'])->name('bookings.reschedule-workflow');
Route::post('/apps-bookings/{booking}/resource-allocations', [BookingResourceAllocationController::class, 'store'])->name('bookings.resource-allocations.store');
Route::post('/apps-bookings/{booking}/resource-allocations/{allocation}/delete', [BookingResourceAllocationController::class, 'destroy'])->name('bookings.resource-allocations.destroy');
Route::get('/apps-bookings-manual-create', [ManualBookingController::class, 'create'])->name('bookings.manual.create');
Route::post('/apps-bookings-manual', [ManualBookingController::class, 'store'])->name('bookings.manual.store');
Route::get('/apps-bookings-recap', [BookingRevenueRecapController::class, 'index'])->name('bookings.recap');
Route::get('/apps-bookings-recap/export', [BookingRevenueRecapController::class, 'export'])->name('bookings.recap.export');
Route::get('/apps-invoices', [TenantInvoiceController::class, 'index'])->name('tenant-invoices.index');
Route::get('/apps-audit-logs', [TenantAuditLogController::class, 'index'])->name('tenant-audit-logs.index');
Route::post('/apps-invoices/branding', [TenantInvoiceController::class, 'updateBranding'])->name('tenant-invoices.branding');
Route::get('/apps-invoices/{booking}', [TenantInvoiceController::class, 'show'])->name('tenant-invoices.show');
Route::get('/apps-tenant-users', [TenantUserController::class, 'index'])->name('tenant-users.index');
Route::post('/apps-tenant-users', [TenantUserController::class, 'store'])->name('tenant-users.store');
Route::post('/apps-tenant-users/roles', [TenantUserController::class, 'storeRole'])->name('tenant-users.store-role');
Route::post('/apps-tenant-users/{user}/status', [TenantUserController::class, 'updateStatus'])->name('tenant-users.update-status');
Route::get('/apps-tenant-role-permissions', [TenantRolePermissionController::class, 'index'])->name('tenant-role-permissions.index');
Route::post('/apps-tenant-role-permissions', [TenantRolePermissionController::class, 'update'])->name('tenant-role-permissions.update');
Route::get('/apps-superadmin-landing-pricing', [SuperAdminLandingPricingController::class, 'index'])->name('superadmin-landing-pricing.index');
Route::post('/apps-superadmin-landing-pricing/{plan}', [SuperAdminLandingPricingController::class, 'update'])->name('superadmin-landing-pricing.update');
Route::get('/apps-superadmin-impersonate', [SuperAdminImpersonationController::class, 'index'])->name('superadmin.impersonation.index');
Route::post('/apps-superadmin-impersonate', [SuperAdminImpersonationController::class, 'store'])->name('superadmin.impersonation.store');
Route::get('/apps-superadmin-impersonate/leave', [SuperAdminImpersonationController::class, 'leave'])
    ->middleware('signed')
    ->name('superadmin.impersonation.leave');
Route::get('/apps-travel-agents', [TravelAgentController::class, 'index'])->name('travel-agents.index');
Route::post('/apps-travel-agents/{travelAgent}/connect', [TravelAgentController::class, 'connect'])->name('travel-agents.connect');
Route::post('/apps-travel-agents/{travelAgent}/test', [TravelAgentController::class, 'testConnection'])->name('travel-agents.test');
Route::post('/apps-travel-agents/{travelAgent}/disconnect', [TravelAgentController::class, 'disconnect'])->name('travel-agents.disconnect');
Route::post('/apps-travel-agents/retry-failed-sync', [TravelAgentController::class, 'retryFailedOutbound'])->name('travel-agents.retry-failed-sync');
Route::get('/apps-channel-sync-logs', [ChannelSyncLogController::class, 'index'])->name('channel-sync-logs.index');
Route::get('/apps-operations-resources', [OperationsResourceController::class, 'index'])->name('operations-resources.index');
Route::post('/apps-operations-resources', [OperationsResourceController::class, 'store'])->name('operations-resources.store');
Route::post('/apps-operations-resources/{resource}/update', [OperationsResourceController::class, 'update'])->name('operations-resources.update');
Route::post('/apps-operations-resources/{resource}/delete', [OperationsResourceController::class, 'destroy'])->name('operations-resources.destroy');
Route::post('/apps-operations-resources/{resource}/block-out', [OperationsResourceController::class, 'blockOut'])->name('operations-resources.block-out');
Route::get('/apps-tours', [TourController::class, 'index'])->name('tours.index');
Route::post('/apps-tours', [TourController::class, 'store'])->name('tours.store');
Route::post('/apps-tours/{tour}/update', [TourController::class, 'update'])->name('tours.update');
Route::post('/apps-tours/{tour}/archive', [TourController::class, 'archive'])->name('tours.archive');
Route::get('/apps-tenant-categories', [TenantCategoryController::class, 'index'])->name('tenant-categories.index');
Route::post('/apps-tenant-categories', [TenantCategoryController::class, 'update'])->name('tenant-categories.update');
Route::get('/apps-tour-day-capacities', [TourDayCapacityController::class, 'index'])->name('tour-day-capacities.index');
Route::post('/apps-tour-day-capacities', [TourDayCapacityController::class, 'store'])->name('tour-day-capacities.store');
Route::post('/apps-tour-day-capacities/{capacity}/delete', [TourDayCapacityController::class, 'destroy'])->name('tour-day-capacities.destroy');

Route::get('{any}', [App\Http\Controllers\HomeController::class, 'index'])->name('index');
