<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------- Master data (M13) ----------
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained();
            $table->string('code', 10);
            $table->string('name');
            $table->unsignedBigInteger('head_user_id')->nullable();
            $table->timestamps();
            $table->unique(['entity_id', 'code']);
        });

        Schema::create('retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('category');
            $table->string('trigger', 30); // effective, expiry, superseded, created
            $table->unsignedSmallInteger('period_years')->nullable(); // null = permanen
            $table->string('disposal_action', 30); // destroy, review, permanent
            $table->text('legal_basis')->nullable();
            $table->timestamps();
        });

        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('module', 20)->index(); // legal, policy, board, finance, marketing, hr, operations
            $table->foreignId('retention_policy_id')->nullable()->constrained();
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('document_categories');
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->boolean('requires_expiry')->default(false);
            $table->boolean('requires_review')->default(false);
            $table->boolean('is_material')->default(false);
            $table->boolean('requires_acknowledgement')->default(false);
            $table->unsignedSmallInteger('review_cycle_months')->nullable();
            $table->timestamps();
        });

        // ---------- Repository (M01, M02, M08) ----------
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('entity_id')->constrained();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('category_id')->constrained('document_categories');
            $table->foreignId('type_id')->constrained('document_types');
            $table->string('classification', 30)->index();
            $table->foreignId('owner_id')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->date('effective_at')->nullable();
            $table->date('review_at')->nullable()->index();
            $table->date('expires_at')->nullable()->index();
            $table->foreignId('retention_policy_id')->nullable()->constrained();
            $table->date('retention_until')->nullable();
            $table->string('counterparty')->nullable();
            $table->text('obligations')->nullable();
            $table->decimal('contract_value', 18, 2)->nullable();
            $table->string('tags')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users');
            $table->timestamp('checked_out_at')->nullable();
            $table->foreignId('superseded_by_id')->nullable()->constrained('documents');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('disposed_at')->nullable();
            $table->timestamps();
            $table->unique(['entity_id', 'code']); // AC-01: nomor unik per entitas
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('major')->default(0);
            $table->unsignedSmallInteger('minor')->default(1);
            $table->string('status', 20)->default('draft'); // draft, in_review, approved, final, superseded, rejected, destroyed
            $table->string('object_key')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable(); // SHA-256
            $table->text('change_note')->nullable();
            $table->longText('content_text')->nullable(); // indeks teks / hasil OCR
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('locked')->default(false);
            $table->timestamps();
            $table->unique(['document_id', 'major', 'minor']);
        });

        Schema::create('document_acl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type', 20); // user, department, role
            $table->string('subject_id', 50);
            $table->string('permission', 20); // view, download, edit, deny
            $table->timestamp('expires_at')->nullable();
            $table->string('conditions')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('document_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('relation_type', 30); // renewal_of, references, amends, attachment_of, evidence_for
            $table->timestamps();
        });

        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('related_system', 30); // asset_care, ruci_bsc, crm_gpch, vhp
            $table->string('external_record_id', 80);
            $table->string('relation_type', 40);
            $table->string('label')->nullable();
            $table->timestamps();
            $table->index(['related_system', 'external_record_id']);
        });

        Schema::create('document_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('threshold_days');
            $table->date('expires_at');
            $table->timestamp('sent_at');
            $table->unique(['document_id', 'threshold_days', 'expires_at']);
        });

        Schema::create('acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->timestamp('acknowledged_at');
            $table->unique(['document_version_id', 'user_id']);
        });

        // ---------- Workflow (M07) ----------
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_version_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 10)->default('serial'); // serial, parallel
            $table->string('status', 20)->default('in_progress'); // in_progress, approved, revision, rejected, cancelled
            $table->foreignId('submitted_by')->constrained('users');
            $table->text('note')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('stage', 20); // review, approval
            $table->foreignId('assignee_id')->constrained('users');
            $table->foreignId('delegated_to_id')->nullable()->constrained('users');
            $table->string('action', 20)->default('waiting'); // waiting, pending, approved, revision, rejected, skipped
            $table->timestamp('due_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->text('comment')->nullable();
            $table->boolean('mfa_verified')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();
        });

        // ---------- Board & management records (M04) ----------
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained();
            $table->string('number', 60)->unique();
            $table->string('type', 30); // rups, bod, management, department
            $table->string('title');
            $table->dateTime('meeting_at');
            $table->string('location')->nullable();
            $table->foreignId('chair_id')->constrained('users');
            $table->foreignId('secretary_id')->nullable()->constrained('users');
            $table->text('agenda')->nullable();
            $table->text('attendees')->nullable();
            $table->string('classification', 30)->default('confidential');
            $table->foreignId('minutes_document_id')->nullable()->constrained('documents');
            $table->timestamps();
        });

        Schema::create('meeting_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->text('decision');
            $table->timestamps();
        });

        Schema::create('action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('decision_id')->nullable()->constrained('meeting_decisions')->nullOnDelete();
            $table->string('title');
            $table->foreignId('pic_id')->constrained('users');
            $table->date('due_at');
            $table->string('status', 20)->default('open'); // open, in_progress, done
            $table->foreignId('evidence_document_id')->nullable()->constrained('documents');
            $table->text('evidence_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // ---------- Retention & records (M10) ----------
        Schema::create('legal_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->string('case_reference')->nullable();
            $table->foreignId('hold_owner_id')->constrained('users');
            $table->timestamp('placed_at');
            $table->date('release_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users');
            $table->text('release_note')->nullable();
            $table->timestamps();
        });

        Schema::create('disposal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained();
            $table->foreignId('requested_by')->constrained('users');
            $table->text('reason');
            $table->string('method', 40)->default('Penghapusan aman digital');
            $table->string('status', 20)->default('pending'); // pending, first_approved, approved, rejected, executed
            $table->foreignId('first_approver_id')->nullable()->constrained('users');
            $table->timestamp('first_approved_at')->nullable();
            $table->foreignId('second_approver_id')->nullable()->constrained('users');
            $table->timestamp('second_approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->text('rejection_note')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users');
            $table->string('certificate_number')->nullable()->unique();
            $table->json('manifest')->nullable();
            $table->timestamps();
        });

        // ---------- Access requests & auditor mandates (M11) ----------
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('permission', 20)->default('view');
            $table->text('justification');
            $table->date('requested_until');
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->string('decision', 20)->default('pending'); // pending, approved, rejected
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditor_id')->constrained('users');
            $table->string('mandate_number', 60);
            $table->string('purpose');
            $table->json('category_ids')->nullable();
            $table->json('department_ids')->nullable();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->foreignId('granted_by')->constrained('users');
            $table->timestamps();
        });

        // ---------- Audit trail (M11) — append-only, hash-chained ----------
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->string('event', 40)->index();
            $table->string('resource_type', 40)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('description')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('device')->nullable();
            $table->string('before_hash', 64)->nullable();
            $table->string('after_hash', 64)->nullable();
            $table->json('meta')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->string('prev_hash', 64)->nullable();
            $table->string('hash', 64);
            $table->timestamp('created_at')->index();
            $table->index(['resource_type', 'resource_id']);
        });

        // ---------- Pendukung ----------
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('query');
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->string('level', 20)->default('info');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'app_notifications', 'saved_searches', 'audit_events', 'audit_mandates', 'access_requests',
            'disposal_requests', 'legal_holds', 'action_items', 'meeting_decisions', 'meetings',
            'workflow_steps', 'workflow_instances', 'acknowledgements', 'document_reminders',
            'document_links', 'document_relations', 'document_acl', 'document_versions', 'documents',
            'document_types', 'document_categories', 'retention_policies', 'departments', 'entities',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
