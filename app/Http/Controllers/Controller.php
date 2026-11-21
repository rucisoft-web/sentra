<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AccessControl;
use App\Services\AuditLogger;

abstract class Controller
{
    protected function acl(): AccessControl
    {
        return app(AccessControl::class);
    }

    /** Otorisasi server-side untuk setiap akses dokumen (cegah IDOR). */
    protected function authorizeDocument(Document $document, string $action = 'view'): void
    {
        [$ok, $reason] = $this->acl()->decide(auth()->user(), $document, $action);
        if (! $ok) {
            AuditLogger::log('document.access_denied', $document, "Akses {$action} ditolak: {$reason}");
            abort(403, $reason);
        }
    }

    protected function requireRole(string ...$roles): void
    {
        abort_unless(auth()->user()->hasRole(...$roles), 403, 'Peran Anda tidak memiliki izin untuk aksi ini.');
    }
}
