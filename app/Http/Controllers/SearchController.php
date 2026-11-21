<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DocumentFinder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, DocumentFinder $finder)
    {
        $filters = array_filter($request->only(DocumentFinder::FILTERS), fn ($v) => $v !== null && $v !== '');
        $results = null; $elapsed = null;
        if ($filters) {
            $t = microtime(true);
            $all = $finder->search($request->user(), $filters);
            $elapsed = round((microtime(true) - $t) * 1000);
            $results = DocumentFinder::paginate($all, 15);
            if (! $request->has('page')) {
                AuditLogger::log('search', null, 'Pencarian: '.($filters['q'] ?? '(filter)'), ['filters' => $filters, 'results' => $all->count(), 'ms' => $elapsed]);
            }
        }
        return view('search.index', [
            'filters' => $filters, 'results' => $results, 'elapsed' => $elapsed,
            'saved' => SavedSearch::where('user_id', $request->user()->id)->latest()->get(),
            'categories' => DocumentCategory::orderBy('name')->get(), 'types' => DocumentType::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'owners' => User::whereNotIn('role', ['service', 'auditor'])->orderBy('name')->get(),
        ]);
    }

    public function save(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        SavedSearch::create(['user_id' => $request->user()->id, 'name' => $request->name,
            'query' => array_filter($request->only(DocumentFinder::FILTERS))]);
        return back()->with('success', 'Pencarian disimpan.');
    }

    public function destroy(Request $request, SavedSearch $saved)
    {
        abort_unless($saved->user_id === $request->user()->id, 403);
        $saved->delete();
        return back()->with('success', 'Pencarian tersimpan dihapus.');
    }
}
