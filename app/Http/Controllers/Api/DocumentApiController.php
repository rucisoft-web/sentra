<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentLink;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * API integrasi read-only metadata. Service account TIDAK menerima isi file — hanya metadata
 * dokumen berlaku non-rahasia dan deep link; pengguna membuka file di SENTRA sesuai izinnya sendiri.
 */
class DocumentApiController extends Controller
{
    private function base()
    {
        return Document::with(['type', 'department', 'currentVersion', 'entity'])
            ->where('status', 'published')->whereIn('classification', ['public_internal', 'department']);
    }

    private function present(Document $d): array
    {
        return [
            'id' => $d->id, 'code' => $d->code, 'title' => $d->title, 'entity' => $d->entity->code,
            'department' => $d->department->name, 'type' => $d->type->name, 'classification' => $d->classification,
            'status' => $d->status, 'effective_at' => $d->effective_at?->toDateString(), 'expires_at' => $d->expires_at?->toDateString(),
            'version' => $d->currentVersion?->label(), 'sha256' => $d->currentVersion?->checksum,
            'url' => route('documents.show', $d),
        ];
    }

    public function index(Request $request)
    {
        $q = $this->base();
        if ($s = $request->query('q')) $q->where(fn ($x) => $x->where('title', 'like', "%$s%")->orWhere('code', 'like', "%$s%"));
        return ['data' => $q->limit(100)->get()->map(fn ($d) => $this->present($d))];
    }

    public function show(string $id)
    {
        $d = $this->base()->where(fn ($q) => $q->where('id', $id)->orWhere('code', $id))->firstOrFail();
        return ['data' => $this->present($d)];
    }

    public function links(string $system, string $record)
    {
        $links = DocumentLink::with('document')->where('related_system', $system)->where('external_record_id', $record)->get();
        return ['data' => $links->map(fn ($l) => [
            'code' => $l->document->code, 'relation' => $l->relation_type,
            // judul rahasia tidak dibocorkan lewat API
            'title' => in_array($l->document->classification, ['public_internal', 'department']) ? $l->document->title : '[Terbatas]',
            'classification' => $l->document->classification, 'url' => route('documents.show', $l->document),
        ]), 'deep_link' => route('link.resolve', [$system, $record])];
    }

    public function storeLink(Request $request)
    {
        $data = $request->validate([
            'document_code' => 'required|exists:documents,code',
            'related_system' => ['required', Rule::in(array_keys(DocumentLink::SYSTEMS))],
            'external_record_id' => 'required|string|max:80', 'relation_type' => 'required|string|max:40',
        ]);
        $doc = Document::where('code', $data['document_code'])->firstOrFail();
        $link = DocumentLink::firstOrCreate(['document_id' => $doc->id, 'related_system' => $data['related_system'],
            'external_record_id' => $data['external_record_id']], ['relation_type' => $data['relation_type']]);
        return response()->json(['data' => ['id' => $link->id, 'deep_link' => route('link.resolve', [$link->related_system, $link->external_record_id])]], 201);
    }
}
