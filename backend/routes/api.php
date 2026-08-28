<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuardController;
use App\Http\Controllers\Api\GuardAssignmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SupervisorDashboardController;
use App\Http\Controllers\Api\RosterController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\IncidentTypeController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ApprovalController;

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
        | POSTS MODULE
        |------------------------------------------------------------------
        | Posts are physical positions/slots within a site, each carrying
        | its own per-shift guard requirement. Nested under sites for
        | listing/creation; flat /posts/{id} for direct show/update/delete.
        |------------------------------------------------------------------
        */
        Route::prefix('sites/{siteId}/posts')->middleware('role:admin|supervisor')->group(function () {
            Route::get('/', [PostController::class, 'index']);

            Route::middleware('role:admin')->group(function () {
                Route::post('/', [PostController::class, 'store']);
            });
        });

        Route::prefix('posts')->group(function () {

            Route::middleware('role:admin|supervisor')->group(function () {
                Route::get('/{id}', [PostController::class, 'show']);
            });

            Route::middleware('role:admin')->group(function () {
                Route::put('/{id}',    [PostController::class, 'update']);
                Route::delete('/{id}', [PostController::class, 'destroy']);
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
        | APPROVALS MODULE
        |------------------------------------------------------------------
        | Generic multi-level approval engine. Any authenticated user can
        | submit/act on a request — the service layer enforces per-level
        | role checks (hasRole($approver_role)) and separation of duties.
        |------------------------------------------------------------------
        */
        Route::prefix('approvals')->group(function () {

            Route::post('/',              [ApprovalController::class, 'submit']);
            Route::get('/pending',        [ApprovalController::class, 'pending']);
            Route::get('/{id}',           [ApprovalController::class, 'show']);
            Route::post('/{id}/approve',  [ApprovalController::class, 'approve']);
            Route::post('/{id}/reject',   [ApprovalController::class, 'reject']);
            Route::post('/{id}/return',   [ApprovalController::class, 'return']);
            Route::post('/{id}/cancel',   [ApprovalController::class, 'cancel']);

            Route::middleware('role:admin')->group(function () {
                Route::get('/', [ApprovalController::class, 'index']);
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

            Route::middleware('role:admin')->group(function () {
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

            Route::middleware('role:admin')->group(function () {
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
        | EMPLOYEES MODULE
        |------------------------------------------------------------------
        */
            Route::prefix('employees')->group(function () {

            Route::middleware('role:admin')->group(function () {
                Route::get('/',     [EmployeeController::class, 'index']);
                Route::get('/{id}', [EmployeeController::class, 'show']);
            });

            Route::middleware('role:admin')->group(function () {
                Route::post('/',        [EmployeeController::class, 'store']);
                Route::put('/{id}',     [EmployeeController::class, 'update']);
                Route::delete('/{id}',  [EmployeeController::class, 'destroy']);
                Route::put('/{id}/link-user', [EmployeeController::class, 'linkUser']);
            });
        });

        /*
        |------------------------------------------------------------------
        | DEPARTMENTS MODULE
        |------------------------------------------------------------------
        */
        Route::prefix('departments')->group(function () {

            Route::middleware('role:admin')->group(function () {
                Route::get('/',     [DepartmentController::class, 'index']);
                Route::get('/{id}', [DepartmentController::class, 'show']);
            });

            Route::middleware('role:admin')->group(function () {
                Route::post('/',        [DepartmentController::class, 'store']);
                Route::put('/{id}',     [DepartmentController::class, 'update']);
                Route::delete('/{id}',  [DepartmentController::class, 'destroy']);
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

        Route::middleware('manager.department:operations')->get('/manager-test-ops', fn() => response()->json([
            'message' => 'Operations manager access confirmed',
        ]));

    });
});