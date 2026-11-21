<?php

use App\Http\Controllers;
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::get('/', [Controllers\LandingController::class, 'index'])->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Controllers\AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [Controllers\AuthController::class, 'logout'])->name('logout');
    Route::get('/mfa', [Controllers\MfaController::class, 'show'])->name('mfa.show');
    Route::post('/mfa', [Controllers\MfaController::class, 'verify'])->middleware('throttle:10,1')->name('mfa.verify');

    Route::get('/dashboard', [Controllers\DashboardController::class, 'index'])->name('dashboard');

    // M01 Repository, M08 Versioning
    Route::get('/documents', [Controllers\DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [Controllers\DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [Controllers\DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [Controllers\DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/edit', [Controllers\DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/documents/{document}', [Controllers\DocumentController::class, 'update'])->name('documents.update');
    Route::get('/documents/{document}/versions/{version}/preview', [Controllers\FileController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/versions/{version}/raw', [Controllers\FileController::class, 'raw'])->name('documents.raw');
    Route::get('/documents/{document}/versions/{version}/download', [Controllers\FileController::class, 'download'])->name('documents.download');
    Route::post('/documents/{document}/versions', [Controllers\DocumentController::class, 'uploadVersion'])->name('documents.versions.store');
    Route::get('/documents/{document}/compare', [Controllers\DocumentController::class, 'compare'])->name('documents.compare');
    Route::post('/documents/{document}/checkout', [Controllers\DocumentController::class, 'checkout'])->name('documents.checkout');
    Route::post('/documents/{document}/release', [Controllers\DocumentController::class, 'release'])->name('documents.release');
    Route::post('/documents/{document}/submit', [Controllers\DocumentController::class, 'submit'])->name('documents.submit');
    Route::post('/documents/{document}/publish', [Controllers\DocumentController::class, 'publish'])->middleware('mfa')->name('documents.publish');
    Route::post('/documents/{document}/archive', [Controllers\DocumentController::class, 'archive'])->name('documents.archive');
    Route::post('/documents/{document}/acl', [Controllers\DocumentController::class, 'addAcl'])->name('documents.acl.store');
    Route::delete('/documents/{document}/acl/{acl}', [Controllers\DocumentController::class, 'removeAcl'])->name('documents.acl.destroy');
    Route::post('/documents/{document}/relations', [Controllers\DocumentController::class, 'addRelation'])->name('documents.relations.store');
    Route::post('/documents/{document}/links', [Controllers\DocumentController::class, 'addLink'])->name('documents.links.store');
    Route::post('/documents/{document}/acknowledge', [Controllers\DocumentController::class, 'acknowledge'])->name('documents.acknowledge');

    // M07 Workflow
    Route::get('/tasks', [Controllers\TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks/{step}/decide', [Controllers\TaskController::class, 'decide'])->name('tasks.decide');
    Route::post('/tasks/{step}/delegate', [Controllers\TaskController::class, 'delegate'])->name('tasks.delegate');

    // M09 Search
    Route::get('/search', [Controllers\SearchController::class, 'index'])->name('search');
    Route::post('/search/saved', [Controllers\SearchController::class, 'save'])->name('search.save');
    Route::delete('/search/saved/{saved}', [Controllers\SearchController::class, 'destroy'])->name('search.destroy');

    // M02 / M03 / M05 / M06 modul domain
    Route::get('/legal', [Controllers\ModuleController::class, 'legal'])->name('modules.legal');
    Route::get('/policies', [Controllers\ModuleController::class, 'policies'])->name('modules.policies');
    Route::get('/policies/{document}/acknowledgements', [Controllers\ModuleController::class, 'acknowledgements'])->name('modules.acknowledgements');
    Route::get('/module/{module}', [Controllers\ModuleController::class, 'module'])->name('modules.show');

    // M04 Board & management records
    Route::get('/meetings', [Controllers\MeetingController::class, 'index'])->name('meetings.index');
    Route::get('/meetings/{meeting}', [Controllers\MeetingController::class, 'show'])->name('meetings.show');
    Route::get('/action-items', [Controllers\MeetingController::class, 'actionItems'])->name('action-items.index');
    Route::put('/action-items/{item}', [Controllers\MeetingController::class, 'updateActionItem'])->name('action-items.update');

    // M10 Retention & records
    Route::get('/retention', [Controllers\RetentionController::class, 'index'])->name('retention.index');
    Route::post('/documents/{document}/holds', [Controllers\RetentionController::class, 'placeHold'])->name('holds.store');
    Route::post('/holds/{hold}/release', [Controllers\RetentionController::class, 'releaseHold'])->name('holds.release');
    Route::post('/documents/{document}/disposal', [Controllers\RetentionController::class, 'requestDisposal'])->name('disposal.store');
    Route::post('/disposal/{disposal}/approve', [Controllers\RetentionController::class, 'approveDisposal'])->middleware('mfa')->name('disposal.approve');
    Route::post('/disposal/{disposal}/reject', [Controllers\RetentionController::class, 'rejectDisposal'])->name('disposal.reject');
    Route::post('/disposal/{disposal}/execute', [Controllers\RetentionController::class, 'executeDisposal'])->middleware('mfa')->name('disposal.execute');
    Route::get('/disposal/{disposal}/certificate', [Controllers\RetentionController::class, 'certificate'])->name('disposal.certificate');

    // Permintaan akses
    Route::get('/access-requests', [Controllers\AccessRequestController::class, 'index'])->name('access-requests.index');
    Route::post('/documents/{document}/access-requests', [Controllers\AccessRequestController::class, 'store'])->name('access-requests.store');
    Route::post('/access-requests/{accessRequest}/decide', [Controllers\AccessRequestController::class, 'decide'])->name('access-requests.decide');

    // M11 Audit, M12 Reports
    Route::get('/audit', [Controllers\AuditController::class, 'index'])->name('audit.index');
    Route::post('/audit/verify', [Controllers\AuditController::class, 'verify'])->name('audit.verify');
    Route::get('/reports', [Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [Controllers\ReportController::class, 'export'])->middleware('mfa')->name('reports.export');

    // Tautan dari sistem lain (Asset Care / CRM / BSC / VHP) — AC-10
    Route::get('/link/{system}/{record}', [Controllers\LinkController::class, 'resolve'])->name('link.resolve');

    Route::get('/notifications', [Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [Controllers\NotificationController::class, 'readAll'])->name('notifications.read');

    // M13 Administration
    Route::prefix('admin')->name('admin.')->middleware('mfa')->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/token', [Admin\UserController::class, 'token'])->name('users.token');
        Route::get('/master', [Admin\MasterController::class, 'index'])->name('master.index');
        Route::post('/master/{kind}', [Admin\MasterController::class, 'store'])->name('master.store');
        Route::get('/integrations', [Admin\MasterController::class, 'integrations'])->name('integrations');
    });
});
