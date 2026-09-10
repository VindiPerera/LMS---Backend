<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBroadcastPush;
use App\Models\AdminAuditLog;
use App\Models\Broadcast;
use App\Services\FirestoreUserDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Bulk push messaging — push-only (no email/SMS channel), matching the
 * client's confirmed scope. Sending is queued (see SendBroadcastPush); this
 * controller only creates the Broadcast row and dispatches the job.
 */
class BroadcastController extends Controller
{
    public function __construct(private readonly FirestoreUserDirectory $directory)
    {
    }

    public function index(): View
    {
        $broadcasts = Broadcast::with('admin')->latest()->paginate(20);

        return view('admin.broadcasts.index', ['broadcasts' => $broadcasts]);
    }

    public function create(): View
    {
        return view('admin.broadcasts.create');
    }

    /**
     * Recipient-count preview for the confirmation step — an XHR-free page
     * reload with the same form values, not a separate JS-driven endpoint.
     */
    public function preview(Request $request): View
    {
        $data = $this->validated($request);
        $filters = $this->audienceFilters($data);

        return view('admin.broadcasts.confirm', [
            'data' => $data,
            'filters' => $filters,
            'recipientEstimate' => $this->estimateRecipients($filters),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $filters = $this->audienceFilters($data);

        $broadcast = Broadcast::create([
            'admin_id' => $this->admin()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'audience_filters' => $filters,
            'recipient_estimate' => $this->estimateRecipients($filters),
            'status' => 'pending',
        ]);

        SendBroadcastPush::dispatch($broadcast->id);

        AdminAuditLog::recordFor($this->admin(), 'broadcast.sent', 'broadcast', (string) $broadcast->id, [
            'audience_filters' => $filters,
            'recipient_estimate' => $broadcast->recipient_estimate,
        ]);

        return redirect()->route('admin.broadcasts.index')
            ->with('status', 'Broadcast queued — sending in the background.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'audience_type' => ['required', Rule::in(['everyone', 'role', 'is_vip', 'native_lang', 'learning_lang'])],
            'audience_value' => ['required_unless:audience_type,everyone', 'nullable', 'string', 'max:255'],
        ]);
    }

    private function audienceFilters(array $data): array
    {
        if ($data['audience_type'] === 'everyone') {
            return [];
        }

        return [$data['audience_type'] => $data['audience_value']];
    }

    /**
     * Every broadcast now posts a FaceTalk chat message to every matched
     * Firestore user (push is a subset — only those with a saved token) —
     * see SendBroadcastPush — so the estimate is the Firestore match count
     * for both "everyone" and a filtered audience alike.
     */
    private function estimateRecipients(array $filters): int
    {
        return $this->directory->count($filters);
    }

    private function admin(): \App\Models\Admin
    {
        return Auth::guard('admin')->user();
    }
}
