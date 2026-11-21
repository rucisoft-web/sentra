<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;

/** Format nomor: ENTITAS/DEPT/JENIS/TAHUN/NNN — SRS §2 taksonomi perusahaan–departemen–jenis–tahun–nomor. */
class DocumentNumbering
{
    public static function next(Entity $entity, Department $dept, DocumentType $type, ?int $year = null): string
    {
        $year ??= (int) date('Y');
        $prefix = "{$entity->code}/{$dept->code}/{$type->code}/{$year}/";
        $last = Document::where('entity_id', $entity->id)->where('code', 'like', $prefix.'%')
            ->pluck('code')->map(fn ($c) => (int) substr($c, strlen($prefix)))->max() ?? 0;
        return $prefix.str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
    }
}
