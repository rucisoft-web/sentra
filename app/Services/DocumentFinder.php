<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** M09 Search & discovery — full-text + filter, hasil difilter sesuai izin (server-side). */
class DocumentFinder
{
    public const FILTERS = ['q', 'module', 'category_id', 'type_id', 'department_id', 'status', 'classification', 'owner_id', 'from', 'to', 'entity_id', 'expiring'];

    public function __construct(private AccessControl $acl) {}

    public function search(User $u, array $f): Collection
    {
        $q = Document::query()->with(['type', 'category', 'department', 'owner', 'entity', 'acl', 'currentVersion']);

        if ($term = trim($f['q'] ?? '')) {
            $words = array_filter(preg_split('/\s+/', $term));
            foreach ($words as $w) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $w).'%';
                $q->where(function ($x) use ($like) {
                    $x->where('code', 'like', $like)->orWhere('title', 'like', $like)
                        ->orWhere('description', 'like', $like)->orWhere('tags', 'like', $like)
                        ->orWhere('counterparty', 'like', $like)
                        ->orWhereHas('versions', fn ($v) => $v->where('content_text', 'like', $like));
                });
            }
        }
        if (! empty($f['module'])) $q->whereHas('category', fn ($c) => $c->where('module', $f['module']));
        foreach (['category_id', 'type_id', 'department_id', 'status', 'classification', 'owner_id', 'entity_id'] as $k) {
            if (! empty($f[$k])) $q->where($k, $f[$k]);
        }
        if (! empty($f['from'])) $q->whereDate('effective_at', '>=', $f['from']);
        if (! empty($f['to'])) $q->whereDate('effective_at', '<=', $f['to']);
        if (! empty($f['expiring'])) $q->whereNotNull('expires_at')->whereDate('expires_at', '<=', today()->addDays((int) $f['expiring']));
        if (empty($f['status'])) $q->where('status', '!=', 'disposed');

        $q->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")->orderByDesc('updated_at');

        return $q->limit(1000)->get()
            ->filter(fn (Document $d) => $this->acl->canDiscover($u, $d))
            ->map(function (Document $d) use ($u) {
                $d->setAttribute('can_open', $this->acl->can($u, $d));
                return $d;
            })->values();
    }

    public static function paginate(Collection $items, int $perPage = 20): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        return new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page,
            ['path' => request()->url(), 'query' => request()->query()]);
    }
}
