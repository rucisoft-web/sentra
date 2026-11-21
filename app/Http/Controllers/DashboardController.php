<?php

namespace App\Http\Controllers;

use App\Models\Acknowledgement;
use App\Models\ActionItem;
use App\Models\AuditEvent;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Services\DocumentFinder;
use Illuminate\Http\Request;

/** M12 Dashboard — AC-09: dokumen overdue, status tindak lanjut, penanggung jawab. */
class DashboardController extends Controller
{
    public function index(Request $request, DocumentFinder $finder)
    {
        $u = $request->user();
        $visible = $finder->search($u, [])->where('can_open', true);
        $published = $visible->where('status', 'published');

        $myTasks = WorkflowStep::with(['instance.document', 'instance.version'])->where('action', 'pending')
            ->where(fn ($q) => $q->where(fn ($x) => $x->where('assignee_id', $u->id)->whereNull('delegated_to_id'))->orWhere('delegated_to_id', $u->id))
            ->orderBy('due_at')->get();

        $expiring = $published->filter(fn ($d) => $d->expires_at && $d->daysToExpiry() <= 90)->sortBy('expires_at')->values();
        $reviewOverdue = $published->filter(fn ($d) => $d->isReviewOverdue())->sortBy('review_at')->values();

        $myActions = ActionItem::with('meeting')->where('pic_id', $u->id)->where('status', '!=', 'done')->orderBy('due_at')->get();

        $ackPending = $published->filter(fn ($d) => $d->type->requires_acknowledgement)
            ->reject(fn ($d) => Acknowledgement::where('document_version_id', $d->current_version_id)->where('user_id', $u->id)->exists())->values();

        $executive = $u->hasRole('owner', 'bod_gm', 'doc_controller');
        $exec = [];
        if ($executive) {
            $all = Document::with(['type', 'owner', 'department'])->where('status', '!=', 'disposed')->get();
            $allPublished = $all->where('status', 'published');
            $exec = [
                'total' => $all->count(),
                'published' => $allPublished->count(),
                'in_workflow' => $all->whereIn('status', ['submitted', 'under_review', 'approved'])->count(),
                'expired' => $allPublished->filter(fn ($d) => $d->expires_at && $d->daysToExpiry() < 0)->count(),
                'expiring90' => $allPublished->filter(fn ($d) => $d->expires_at && $d->daysToExpiry() >= 0 && $d->daysToExpiry() <= 90)->count(),
                'review_overdue' => $allPublished->filter->isReviewOverdue()->count(),
                'incomplete' => $allPublished->filter(fn ($d) => $d->missingMetadata())->count(),
                'pending_steps' => WorkflowStep::with(['instance.document', 'assignee'])->where('action', 'pending')->orderBy('due_at')->get(),
                'overdue_actions' => ActionItem::with(['pic.department', 'meeting'])->where('status', '!=', 'done')->whereDate('due_at', '<', today())->orderBy('due_at')->get(),
                'open_actions' => ActionItem::where('status', '!=', 'done')->count(),
                'by_dept' => $all->groupBy(fn ($d) => $d->department->name)->map->count()->sortDesc(),
                'by_class' => $all->groupBy('classification')->map->count(),
                'owner_coverage' => $allPublished->count() ? round($allPublished->filter(fn ($d) => $d->owner_id && $d->classification)->count() / $allPublished->count() * 100) : 0,
                'expired_list' => $allPublished->filter(fn ($d) => $d->expires_at && $d->daysToExpiry() <= 30)->sortBy('expires_at')->take(8),
                'review_list' => $allPublished->filter->isReviewOverdue()->sortBy('review_at')->take(8),
            ];
        }

        $recent = AuditEvent::with('actor')->where('actor_id', $u->id)->latest('id')->limit(6)->get();

        return view('dashboard', compact('myTasks', 'expiring', 'reviewOverdue', 'myActions', 'ackPending', 'executive', 'exec', 'recent', 'visible'));
    }
}
