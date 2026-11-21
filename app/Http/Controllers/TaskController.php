<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RequireMfa;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Services\WorkflowService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $uid = $request->user()->id;
        $mine = fn ($q) => $q->where(fn ($x) => $x->where('assignee_id', $uid)->whereNull('delegated_to_id'))->orWhere('delegated_to_id', $uid);

        $pending = WorkflowStep::with(['instance.document.type', 'instance.document.department', 'instance.version', 'instance.submitter', 'instance.steps.assignee', 'assignee'])
            ->where('action', 'pending')->where($mine)->orderBy('due_at')->get();
        $history = WorkflowStep::with(['instance.document', 'instance.version'])
            ->whereNotIn('action', ['pending', 'waiting', 'skipped'])->where('decided_by', $uid)->latest('decided_at')->limit(20)->get();
        $submitted = \App\Models\WorkflowInstance::with(['document', 'version', 'steps.assignee'])->where('submitted_by', $uid)->latest('id')->limit(15)->get();
        $users = User::whereNotIn('role', ['service', 'auditor', 'it_admin'])->where('id', '!=', $uid)->orderBy('name')->get();

        return view('tasks.index', compact('pending', 'history', 'submitted', 'users'));
    }

    public function decide(Request $request, WorkflowStep $step, WorkflowService $wf)
    {
        $user = $request->user();
        abort_unless($step->action === 'pending' && $step->actorId() === $user->id, 403, 'Tugas ini bukan milik Anda atau sudah diputuskan.');
        $data = $request->validate([
            'decision' => 'required|in:approved,revision,rejected',
            'comment' => 'required_unless:decision,approved|nullable|string|max:2000',
        ], ['comment.required_unless' => 'Alasan/catatan wajib diisi untuk revisi atau penolakan.']);

        $doc = $step->instance->document;
        // AC-04: pengesahan memerlukan MFA
        if ($step->stage === 'approval' && $data['decision'] === 'approved') {
            if (! RequireMfa::isFresh($request)) {
                $request->session()->put('mfa_intended', route('tasks.index'));
                return redirect()->route('mfa.show')->with('warning', 'Pengesahan dokumen memerlukan verifikasi MFA. Setelah verifikasi, klik Setujui kembali.');
            }
            if ($this->acl()->violatesSeparationOfDuties($user, $doc)) {
                return back()->with('error', 'Pemisahan tugas: Anda tidak dapat mengesahkan dokumen material milik sendiri.');
            }
        }

        $wf->decide($step, $user, $data['decision'], $data['comment'] ?? null, $step->stage === 'approval' && RequireMfa::isFresh($request));
        $msg = ['approved' => $step->stage === 'approval' ? 'Dokumen disahkan; versi dikunci dan menunggu publikasi.' : 'Review disetujui, diteruskan ke tahap berikutnya.',
            'revision' => 'Dokumen dikembalikan untuk revisi.', 'rejected' => 'Dokumen ditolak.'][$data['decision']];
        return back()->with('success', $msg);
    }

    public function delegate(Request $request, WorkflowStep $step, WorkflowService $wf)
    {
        abort_unless($step->action === 'pending' && $step->actorId() === $request->user()->id, 403);
        $data = $request->validate(['to_id' => 'required|exists:users,id', 'reason' => 'required|string|max:300']);
        $to = User::findOrFail($data['to_id']);
        if ($this->acl()->violatesSeparationOfDuties($to, $step->instance->document) && $step->stage === 'approval') {
            return back()->with('error', 'Penerima delegasi adalah pembuat dokumen material ini.');
        }
        $wf->delegate($step, $request->user(), $to, $data['reason']);
        return back()->with('success', 'Tugas didelegasikan sementara kepada '.$to->name.'.');
    }
}
