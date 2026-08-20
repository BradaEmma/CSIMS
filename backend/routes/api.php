<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuardController;
use App\Http\Controllers\Api\GuardAssignmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SupervisorDashboardController;
use App\Http\Controllers\Api\RosterController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\IncidentTypeController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DocumentController;

/*
|--------------------------------------------------------------------------
| API VERSION 1
|--------------------------------------------------------------------------
| Everything lives under /api/v1/. Future breaking changes get a /v2/
| group instead of altering these routes, so existing integrations
| (including the eventual frontend) never break silently.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------------------------------
    | PUBLIC ROUTES
    |----------------------------------------------------------------------
    */
    Route::post('/login',  [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    /*
    |----------------------------------------------------------------------
    | AUTHENTICATED ROUTES (all require valid Sanctum token)
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/user', fn(Request $request) => $request->user());

        /*
        |------------------------------------------------------------------
        | NOTIFICATIONS
        |------------------------------------------------------------------
        */
        Route::prefix('notifications')->group(function () {
            Route::get('/', fn(Request $request) =>
                response()->json(['data' => $request->user()->notifications()->paginate(30)])
            );
            Route::get('/unread', fn(Request $request) =>
                response()->json(['data' => $request->user()->unreadNotifications()->get()])
            );
            Route::patch('/{id}/read', function (Request $request, string $id) {
                $notification = $request->user()->notifications()->findOrFail($id);
                $notification->markAsRead();
                return response()->json(['message' => 'Marked as read']);
            });
        });

        /*
        |------------------------------------------------------------------
        | SITES MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('sites')->group(function () {

            Route::middleware('role:admin|supervisor')->group(function () {
                Route::get('/',        [SiteController::class, 'index']);
                Route::get('/{id}',    [SiteController::class, 'show']);
            });

            Route::middleware('role:admin')->group(function () {
                Route::post('/',       [SiteController::class, 'store']);
                Route::put('/{id}',    [SiteController::class, 'update']);
                Route::delete('/{id}', [SiteController::class, 'destroy']);
            });
        });

        /*
        |------------------------------------------------------------------
        | GUARDS MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('guards')->group(function () {

            Route::middleware('role:admin|supervisor')->group(function () {
                Route::get('/',     [GuardController::class, 'index']);
                Route::get('/{id}', [GuardController::class, 'show']);
            });

            Route::middleware('role:admin')->group(function () {
                Route::post('/',        [GuardController::class, 'store']);
                Route::put('/{id}',     [GuardController::class, 'update']);
                Route::delete('/{id}',  [GuardController::class, 'destroy']);
            });
        });

        /*
        |------------------------------------------------------------------
        | GUARD ASSIGNMENTS MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('assignments')->middleware('role:admin|supervisor')->group(function () {
            Route::get('/', [GuardAssignmentController::class, 'index']);

            Route::middleware('role:admin')->group(function () {
                Route::post('/assign',      [GuardAssignmentController::class, 'assign']);
                Route::post('/end/{id}',    [GuardAssignmentController::class, 'endAssignment']);
            });
        });

        /*
        |------------------------------------------------------------------
        | ATTENDANCE MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('attendance')->group(function () {

            Route::post('/check-in',  [AttendanceController::class, 'checkIn']);
            Route::post('/check-out', [AttendanceController::class, 'checkOut']);

            Route::middleware('role:admin|supervisor')->group(function () {
                Route::get('/today',              [AttendanceController::class, 'today']);
                Route::get('/guard/{guardId}',    [AttendanceController::class, 'guardHistory']);
                Route::get('/site/{siteId}',      [AttendanceController::class, 'siteAttendance']);
            });
        });

        /*
        |------------------------------------------------------------------
        | ROSTER MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('roster')->group(function () {

            Route::middleware('role:admin|supervisor')->group(function () {
                Route::get('/',         [RosterController::class, 'index']);
                Route::get('/{date}',   [RosterController::class, 'showDate']);
            });

            Route::middleware('role:admin')->group(function () {
                Route::post('/generate',      [RosterController::class, 'generate']);
                Route::post('/assign-double', [RosterController::class, 'assignDouble']);
                Route::delete('/{id}',        [RosterController::class, 'destroy']);
            });
        });

        /*
        |------------------------------------------------------------------
        | DASHBOARD MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('dashboard')->middleware('role:admin|supervisor')->group(function () {
            Route::get('/live-shift',  [DashboardController::class, 'liveShift']);
            Route::get('/admin',       [DashboardController::class, 'adminSummary']);
            Route::get('/supervisor',  [SupervisorDashboardController::class, 'index']);
        });

        /*
        |------------------------------------------------------------------
        | INCIDENTS MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('incidents')->group(function () {

            Route::post('/',                 [IncidentController::class, 'store']);
            Route::post('/{id}/attachments', [IncidentController::class, 'uploadAttachment']);

            Route::middleware('role:admin|supervisor')->group(function () {
                Route::get('/',               [IncidentController::class, 'index']);
                Route::get('/summary',        [IncidentController::class, 'summary']);
                Route::get('/{id}',           [IncidentController::class, 'show']);
                Route::post('/{id}/resolve',  [IncidentController::class, 'resolve']);
                Route::patch('/{id}/status',  [IncidentController::class, 'updateStatus']);
            });
        });

        /*
        |------------------------------------------------------------------
        | INCIDENT TYPES MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('incident-types')->group(function () {
            Route::get('/', [IncidentTypeController::class, 'index']);

            Route::middleware('role:admin')->group(function () {
                Route::post('/',    [IncidentTypeController::class, 'store']);
                Route::put('/{id}', [IncidentTypeController::class, 'update']);
            });
        });

        /*
        |------------------------------------------------------------------
        | PAYROLL MODULE
        |------------------------------------------------------------------
        | Everything here is admin-only — payroll is sensitive financial data.
        |------------------------------------------------------------------
        */
        Route::prefix('payroll')->middleware('role:admin')->group(function () {
            Route::get('/',                    [PayrollController::class, 'index']);
            Route::post('/generate',           [PayrollController::class, 'generate']);
            Route::post('/generate-bulk',      [PayrollController::class, 'generateBulk']);
            Route::post('/deductions',         [PayrollController::class, 'addDeduction']);
            Route::get('/deduction-types',     [PayrollController::class, 'deductionTypes']);
            Route::post('/deduction-types',    [PayrollController::class, 'storeDeductionType']);
            Route::get('/settings',            [PayrollController::class, 'settings']);
            Route::put('/settings/{key}',      [PayrollController::class, 'updateSetting']);
            Route::get('/{id}',                [PayrollController::class, 'show']);
            Route::patch('/{id}/status',       [PayrollController::class, 'updateStatus']);
        });

        /*
        |------------------------------------------------------------------
        | CLIENTS MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('clients')->group(function () {

            Route::middleware('role:admin|supervisor')->group(function () {
                Route::get('/',     [ClientController::class, 'index']);
                Route::get('/{id}', [ClientController::class, 'show']);
            });

            Route::middleware('role:admin')->group(function () {
                Route::post('/',        [ClientController::class, 'store']);
                Route::put('/{id}',     [ClientController::class, 'update']);
                Route::delete('/{id}',  [ClientController::class, 'destroy']);
            });
        });

        /*
        |------------------------------------------------------------------
        | CONTRACTS MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('contracts')->group(function () {

            Route::middleware('role:admin|supervisor')->group(function () {
                Route::get('/',     [ContractController::class, 'index']);
                Route::get('/{id}', [ContractController::class, 'show']);
            });

            Route::middleware('role:admin')->group(function () {
                Route::post('/',        [ContractController::class, 'store']);
                Route::put('/{id}',     [ContractController::class, 'update']);
                Route::delete('/{id}',  [ContractController::class, 'destroy']);
            });
        });

        /*
        |------------------------------------------------------------------
        | DOCUMENTS MODULE
        |------------------------------------------------------------------
        | Generic file attachment system — works for guards, contracts,
        | and any future entity, via a single polymorphic table.
        |------------------------------------------------------------------
        */
        Route::prefix('documents')->middleware('role:admin|supervisor')->group(function () {
            Route::get('/',        [DocumentController::class, 'index']);
            Route::post('/',       [DocumentController::class, 'store']);
            Route::delete('/{id}', [DocumentController::class, 'destroy']);
        });

        /*
        |------------------------------------------------------------------
        | ADMIN SANDBOX (remove before production)
        |------------------------------------------------------------------
        */
        Route::middleware('role:admin')->get('/admin-test', fn() => response()->json([
            'message' => 'Admin access confirmed',
            'time'    => now()->toDateTimeString(),
        ]));

    });
});