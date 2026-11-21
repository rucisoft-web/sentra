<?php

namespace App\Http\Controllers;

use App\Models\DocumentLink;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/** AC-10: tautan dari Asset Care / CRM / BSC / VHP membuka dokumen sesuai izin tanpa membuat salinan. */
class LinkController extends Controller
{
    public function resolve(Request $request, string $system, string $record)
    {
        abort_unless(array_key_exists($system, DocumentLink::SYSTEMS), 404);
        $links = DocumentLink::with(['document.type', 'document.department', 'document.acl', 'document.owner'])
            ->where('related_system', $system)->where('external_record_id', $record)->get();
        $items = $links->map(fn ($l) => ['link' => $l, 'can' => $this->acl()->can($request->user(), $l->document),
            'discover' => $this->acl()->canDiscover($request->user(), $l->document)])->filter(fn ($i) => $i['discover']);
        AuditLogger::log('document.viewed', null, 'Membuka tautan '.DocumentLink::SYSTEMS[$system].' #'.$record, ['system' => $system, 'record' => $record]);
        if ($items->count() === 1 && $items->first()['can']) {
            return redirect()->route('documents.show', $items->first()['link']->document);
        }
        return view('documents.links', ['system' => $system, 'record' => $record, 'items' => $items]);
    }
}
