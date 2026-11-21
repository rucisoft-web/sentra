<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Document;
use App\Models\Meeting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/** M04 Board & management records */
class MeetingController extends Controller
{
    private function canSeeMeeting($user, Meeting $m): bool
    {
        if ($user->hasRole('owner', 'bod_gm', 'doc_controller')) {
            // Owner tidak otomatis melihat rapat Sangat Rahasia (mis. strategi akuisisi) kecuali di ACL notulennya
            if ($m->classification === 'strictly_confidential' && $m->minutes) return $this->acl()->can($user, $m->minutes);
            return true;
        }
        if ($m->classification === 'public_internal') return true;
        if ($m->actionItems->contains('pic_id', $user->id)) return true;
        return $m->minutes ? $this->acl()->can($user, $m->minutes) : false;
    }

    public function index(Request $request)
    {
        $meetings = Meeting::with(['chair', 'minutes.acl', 'minutes.department', 'actionItems', 'decisions'])->latest('meeting_at')->get()
            ->filter(fn ($m) => $this->canSeeMeeting($request->user(), $m));
        return view('meetings.index', compact('meetings'));
    }

    public function show(Request $request, Meeting $meeting)
    {
        $meeting->load(['chair', 'secretary', 'minutes.acl', 'minutes.department', 'decisions.actionItems.pic', 'actionItems.pic', 'actionItems.evidence']);
        abort_unless($this->canSeeMeeting($request->user(), $meeting), 403, 'Rapat ini memiliki akses khusus.');
        $canOpenMinutes = $meeting->minutes && $this->acl()->can($request->user(), $meeting->minutes);
        return view('meetings.show', compact('meeting', 'canOpenMinutes'));
    }

    public function actionItems(Request $request)
    {
        $user = $request->user();
        $q = ActionItem::with(['pic.department', 'meeting', 'evidence', 'decision']);
        if (! $user->hasRole('owner', 'bod_gm', 'doc_controller')) {
            $q->where(fn ($x) => $x->where('pic_id', $user->id)->orWhereHas('pic', fn ($p) => $p->where('department_id', $user->department_id)));
        }
        if ($s = $request->status) {
            $s === 'overdue' ? $q->where('status', '!=', 'done')->whereDate('due_at', '<', today()) : $q->where('status', $s);
        }
        $items = $q->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")->orderBy('due_at')->get();
        $evidenceDocs = Document::whereIn('status', ['published', 'draft', 'approved'])->orderByDesc('id')->limit(200)->get(['id', 'code', 'title']);
        return view('meetings.action-items', compact('items', 'evidenceDocs'));
    }

    public function updateActionItem(Request $request, ActionItem $item)
    {
        $user = $request->user();
        abort_unless($item->pic_id === $user->id || $user->hasRole('bod_gm', 'doc_controller'), 403);
        $data = $request->validate([
            'status' => 'required|in:open,in_progress,done',
            'evidence_document_id' => 'nullable|exists:documents,id',
            'evidence_note' => 'nullable|string|max:1000',
        ]);
        if ($data['status'] === 'done' && empty($data['evidence_document_id']) && empty($data['evidence_note'])) {
            return back()->with('error', 'Bukti tindak lanjut (dokumen atau catatan) wajib untuk menyelesaikan action item.');
        }
        $item->update($data + ['completed_at' => $data['status'] === 'done' ? now() : null]);
        AuditLogger::log('action_item.updated', $item, 'Action item "'.$item->title.'" → '.ActionItem::STATUSES[$data['status']]);
        return back()->with('success', 'Action item diperbarui.');
    }
}
