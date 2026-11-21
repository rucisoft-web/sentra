<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditMandate;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentLink;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\RetentionPolicy;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterController extends Controller
{
    public function index()
    {
        $this->requireRole('it_admin', 'doc_controller');
        return view('admin.master', [
            'entities' => Entity::withCount('departments')->get(),
            'departments' => Department::with(['entity', 'head'])->withCount('users')->orderBy('entity_id')->orderBy('name')->get(),
            'categories' => DocumentCategory::with('retentionPolicy')->withCount('types')->orderBy('module')->get(),
            'types' => DocumentType::with('category')->orderBy('category_id')->get(),
            'policies' => RetentionPolicy::withCount('documents')->orderBy('code')->get(),
            'mandates' => AuditMandate::with(['auditor', 'grantor'])->latest()->get(),
            'auditors' => User::where('role', 'auditor')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $kind)
    {
        $this->requireRole('doc_controller', 'it_admin');
        $created = match ($kind) {
            'department' => Department::create($request->validate([
                'entity_id' => 'required|exists:entities,id', 'code' => 'required|string|max:10', 'name' => 'required|string|max:120',
                'head_user_id' => 'nullable|exists:users,id'])),
            'type' => DocumentType::create($request->validate([
                'category_id' => 'required|exists:document_categories,id', 'code' => 'required|string|max:10|unique:document_types,code',
                'name' => 'required|string|max:120', 'review_cycle_months' => 'nullable|integer|min:1|max:120',
            ]) + ['requires_expiry' => $request->boolean('requires_expiry'), 'requires_review' => $request->boolean('requires_review'),
                'is_material' => $request->boolean('is_material'), 'requires_acknowledgement' => $request->boolean('requires_acknowledgement')]),
            'retention' => RetentionPolicy::create($request->validate([
                'code' => 'required|string|max:20|unique:retention_policies,code', 'name' => 'required|string|max:120',
                'category' => 'required|string|max:120', 'trigger' => ['required', Rule::in(array_keys(RetentionPolicy::TRIGGERS))],
                'period_years' => 'nullable|integer|min:1|max:100', 'disposal_action' => ['required', Rule::in(array_keys(RetentionPolicy::ACTIONS))],
                'legal_basis' => 'nullable|string|max:500'])),
            'mandate' => AuditMandate::create($request->validate([
                'auditor_id' => 'required|exists:users,id', 'mandate_number' => 'required|string|max:60', 'purpose' => 'required|string|max:255',
                'category_ids' => 'nullable|array', 'department_ids' => 'nullable|array',
                'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at|before:'.now()->addYear()->toDateString(),
            ]) + ['granted_by' => $request->user()->id]),
            default => abort(404),
        };
        AuditLogger::log('admin.master_updated', $created, 'Master data '.$kind.' ditambahkan');
        return back()->with('success', 'Data master tersimpan.');
    }

    public function integrations()
    {
        $this->requireRole('it_admin', 'doc_controller');
        return view('admin.integrations', [
            'services' => User::where('role', 'service')->get(),
            'linkStats' => DocumentLink::selectRaw('related_system, count(*) as total')->groupBy('related_system')->pluck('total', 'related_system'),
            'samples' => DocumentLink::with('document')->latest()->limit(12)->get(),
        ]);
    }
}
