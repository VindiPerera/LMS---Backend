<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\FirestoreReportService;
use App\Services\FirestoreUserDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Lists and moderates `reports/{reportId}` — see FirestoreReportService's
 * doc. Per-user report history/counts are shown on UserController::edit's
 * page instead of here; this is the cross-user "everything pending review"
 * view.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly FirestoreReportService $reports,
        private readonly FirestoreUserDirectory $directory,
    ) {
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $result = $this->reports->list($status !== '' ? $status : null, 20, $request->query('after'));

        // One directory lookup per distinct uid on the page, not per row —
        // the same reporter/reported user often repeats across reports.
        $uids = collect($result['reports'])
            ->flatMap(fn ($report) => [$report['reporterId'] ?? null, $report['reportedUserId'] ?? null])
            ->filter()
            ->unique();
        $users = $uids->mapWithKeys(fn ($uid) => [$uid => $this->directory->find($uid)]);

        return view('admin.reports.index', [
            'reports' => $result['reports'],
            'nextCursor' => $result['next_cursor'],
            'status' => $status,
            'users' => $users,
        ]);
    }

    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', FirestoreReportService::STATUSES)],
        ]);

        $this->reports->updateStatus($id, $data['status']);

        AdminAuditLog::recordFor($this->admin(), 'report.status_updated', 'firestore_report', $id, ['status' => $data['status']]);

        return back()->with('status', 'Report status updated.');
    }

    private function admin(): \App\Models\Admin
    {
        return Auth::guard('admin')->user();
    }
}
