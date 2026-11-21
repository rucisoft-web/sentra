<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/** M11 Audit — log append-only & tamper-evident. IT admin tidak otomatis melihat isi dokumen, hanya log. */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $this->requireRole('doc_controller', 'it_admin', 'auditor', 'bod_gm', 'legal', 'owner');
        $q = AuditEvent::with('actor')->orderByDesc('created_at')->orderByDesc('id');
        if ($request->filled('event')) $q->where('event', $request->event);
        if ($request->filled('actor_id')) $q->where('actor_id', $request->actor_id);
        if ($request->filled('from')) $q->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to')) $q->whereDate('created_at', '<=', $request->to);
        if ($request->filled('q')) $q->where('description', 'like', '%'.$request->q.'%');

        // monitoring anomali sederhana: unduhan berlebih / akses ditolak berulang dalam 7 hari
        $anomalies = AuditEvent::with('actor')->selectRaw('actor_id, event, count(*) as total')
            ->whereIn('event', ['document.downloaded', 'document.access_denied', 'login_failed', 'mfa_failed'])
            ->where('created_at', '>=', now()->subDays(7))->groupBy('actor_id', 'event')
            ->havingRaw('count(*) >= 3')->orderByDesc('total')->get();

        return view('audit.index', [
            'events' => $q->paginate(40)->withQueryString(),
            'users' => User::orderBy('name')->get(), 'anomalies' => $anomalies,
            'verify' => session('verify'),
        ]);
    }

    public function verify()
    {
        $this->requireRole('doc_controller', 'it_admin', 'auditor', 'legal');
        $r = AuditLogger::verify();
        return back()->with('verify', $r);
    }
}
