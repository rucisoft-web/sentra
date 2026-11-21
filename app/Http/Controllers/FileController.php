<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\AuditLogger;
use App\Services\DocumentStorage;
use Illuminate\Http\Request;

/** Preview aman ber-watermark & unduhan terkendali — file tidak pernah diakses langsung via URL publik. */
class FileController extends Controller
{
    private function guard(Request $request, Document $document, DocumentVersion $version, string $action): void
    {
        abort_unless($version->document_id === $document->id, 404);
        $this->authorizeDocument($document, $action);
        abort_if(! $version->object_key || $version->status === 'destroyed', 410, 'File telah dimusnahkan.');
        if ($document->classification === 'strictly_confidential' && ! \App\Http\Middleware\RequireMfa::isFresh($request)) {
            abort(403, 'Verifikasi MFA diperlukan untuk dokumen Sangat Rahasia.');
        }
        // versi draft/in-review hanya untuk pemilik, peserta workflow, dan Document Controller
        if (! in_array($version->status, ['final', 'superseded', 'approved'])) {
            $u = $request->user();
            $participant = $version->document->workflows()->whereHas('steps', fn ($q) => $q->where('assignee_id', $u->id)->orWhere('delegated_to_id', $u->id))->exists();
            abort_unless($participant || $u->hasRole('doc_controller') || in_array($u->id, [$document->owner_id, $document->created_by, $version->uploaded_by]),
                403, 'Versi draft hanya untuk pemilik dan reviewer.');
        }
    }

    public function preview(Request $request, Document $document, DocumentVersion $version)
    {
        $this->guard($request, $document, $version, 'view');
        AuditLogger::log('document.previewed', $document, 'Preview '.$version->label(), ['version_id' => $version->id]);
        return view('documents.preview', [
            'document' => $document, 'version' => $version,
            'watermark' => $request->user()->name.' • '.now()->format('d/m/Y H:i').' • '.$request->ip(),
            'canDownload' => $this->acl()->can($request->user(), $document, 'download'),
        ]);
    }

    public function raw(Request $request, Document $document, DocumentVersion $version)
    {
        $this->guard($request, $document, $version, 'view');
        abort_unless($version->isPreviewable(), 415);
        $contents = DocumentStorage::get($version->object_key);
        abort_unless(hash_equals($version->checksum, hash('sha256', $contents)), 500, 'Integritas file gagal diverifikasi (checksum tidak cocok).');
        return response($contents, 200, [
            'Content-Type' => $version->mime,
            'Content-Disposition' => 'inline; filename="preview.'.($version->mime === 'application/pdf' ? 'pdf' : 'bin').'"',
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public function download(Request $request, Document $document, DocumentVersion $version)
    {
        $this->guard($request, $document, $version, 'download');
        $contents = DocumentStorage::get($version->object_key);
        abort_unless(hash_equals($version->checksum, hash('sha256', $contents)), 500, 'Integritas file gagal diverifikasi.');
        AuditLogger::log('document.downloaded', $document, 'Unduh '.$version->label().' — '.$version->original_name,
            ['version_id' => $version->id, 'checksum' => $version->checksum]);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', str_replace('/', '-', $document->code).'_'.$version->label().'_'.$version->original_name);
        return response($contents, 200, [
            'Content-Type' => $version->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
