<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pengujian acceptance criteria SRS §11 (AC-01 s.d. AC-10) + smoke test seluruh halaman dengan data demo GPCH. */
class SentraAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email): User { return User::where('email', $email)->firstOrFail(); }
    private function doc(string $titleLike): Document { return Document::where('title', 'like', "%{$titleLike}%")->firstOrFail(); }
    private function withMfa() { return $this->withSession(['mfa_verified_at' => now()->timestamp]); }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('SENTRA')->assertSee('Masalah yang sering terjadi')->assertSee('Fitur SENTRA');
        $this->get('/login')->assertOk()->assertSee('Akun demo');
    }

    public function test_every_demo_user_can_open_main_pages(): void
    {
        $pages = ['/dashboard', '/documents', '/search?q=sertifikat', '/legal', '/policies', '/meetings', '/action-items',
            '/access-requests', '/notifications', '/tasks', '/module/finance', '/module/marketing', '/module/hr', '/module/operations'];
        foreach (User::where('role', '!=', 'service')->get()->reject->isAccessExpired() as $u) {
            foreach ($pages as $p) {
                $this->actingAs($u)->get($p)->assertOk();
            }
        }
        foreach (['gm@gpch.test', 'corsec@gpch.test', 'legal@gpch.test', 'auditor@kap-mitra.test'] as $e) {
            foreach (['/retention', '/reports', '/audit'] as $p) $this->actingAs($this->user($e))->get($p)->assertOk();
        }
        $it = $this->user('it@gpch.test');
        foreach (['/admin/users', '/admin/master', '/admin/integrations'] as $p) $this->actingAs($it)->withMfa()->get($p)->assertOk();
        $this->flushSession();
        $this->actingAs($it)->get('/admin/users')->assertRedirect('/mfa');
    }

    public function test_document_detail_pages_render_for_permitted_users(): void
    {
        $gm = $this->user('gm@gpch.test');
        foreach (Document::where('classification', '!=', 'strictly_confidential')->get() as $d) {
            $res = $this->actingAs($gm)->get(route('documents.show', $d));
            $this->assertSame(in_array($d->status, ['draft']) ? 403 : 200, $res->status(), $d->code.' '.$d->status);
        }
        $this->actingAs($gm)->withMfa()->get(route('documents.show', $this->doc('Akuisisi')))->assertOk();
    }

    public function test_ac01_duplicate_number_rejected_within_entity(): void
    {
        $dc = $this->user('corsec@gpch.test');
        $existing = $this->doc('Akta Pendirian PT');
        $type = DocumentType::where('code', 'SOP')->first();
        $this->actingAs($dc)->post('/documents', [
            'code' => $existing->code, 'title' => 'Duplikat', 'description' => 'Uji duplikasi', 'entity_id' => $existing->entity_id,
            'department_id' => $existing->department_id, 'type_id' => $type->id, 'classification' => 'department', 'owner_id' => $dc->id,
            'file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertSessionHasErrors('code');
    }

    public function test_ac02_owner_finds_board_minutes_but_cannot_open(): void
    {
        $owner = $this->user('owner@gpch.test');
        $doc = $this->doc('Notulen Rapat Direksi Oktober');
        $this->actingAs($owner)->get('/search?q=notulen+rapat+direksi')->assertOk()->assertSee($doc->title);
        $this->actingAs($owner)->get(route('documents.show', $doc))->assertOk()->assertSee('Akses terbatas');
        $this->actingAs($owner)->get(route('documents.raw', [$doc, $doc->current_version_id]))->assertForbidden();
        // dokumen Sangat Rahasia di luar whitelist tidak muncul sama sekali
        $this->actingAs($this->user('corsec@gpch.test'))->get('/search?q=akuisisi')->assertDontSee('Kajian Strategis Rencana Akuisisi');
    }

    public function test_ac03_reviewer_returns_for_revision_history_kept(): void
    {
        $chiller = $this->doc('SOP Pemeliharaan Chiller');
        $wf = $chiller->workflows()->first();
        $this->assertSame('revision', $wf->status);
        $this->assertStringContainsString('parameter kualitas air', $wf->steps()->where('action', 'revision')->first()->comment);
    }

    public function test_ac04_approval_requires_mfa_and_final_version_locked(): void
    {
        $gm = $this->user('gm@gpch.test');
        $step = WorkflowStep::where('assignee_id', $gm->id)->where('action', 'pending')->where('stage', 'approval')->firstOrFail();
        $this->actingAs($gm)->post(route('tasks.decide', $step), ['decision' => 'approved'])->assertRedirect('/mfa');
        $this->actingAs($gm)->withMfa()->post(route('tasks.decide', $step), ['decision' => 'approved'])->assertSessionHas('success');
        $version = $step->instance->version->fresh();
        $this->assertTrue($version->locked);
        $this->expectException(\RuntimeException::class);
        $version->update(['checksum' => str_repeat('0', 64)]);
    }

    public function test_ac05_legal_receives_expiry_reminders(): void
    {
        $legal = $this->user('legal@gpch.test');
        $this->assertTrue(\App\Models\AppNotification::where('user_id', $legal->id)->where('title', 'like', '%Sertifikat Laik Fungsi%')->exists());
        $this->actingAs($legal)->get('/legal')->assertOk()->assertSee('Sertifikat Laik Fungsi');
    }

    public function test_ac06_auditor_read_only_time_bound_and_downloads_logged(): void
    {
        $aud = $this->user('auditor@kap-mitra.test');
        $lk = $this->doc('Laporan Keuangan Audited');
        $this->actingAs($aud)->get(route('documents.download', [$lk, $lk->current_version_id]))->assertOk();
        $this->assertDatabaseHas('audit_events', ['actor_id' => $aud->id, 'event' => 'document.downloaded', 'resource_id' => $lk->id]);
        $this->actingAs($aud)->get(route('documents.show', $this->doc('Pedoman Remunerasi')))->assertOk()->assertSee('Akses terbatas');
        $this->actingAs($aud)->get(route('documents.edit', $lk))->assertForbidden();
        // auditor yang mandatnya habis tidak bisa login
        \Illuminate\Support\Facades\Auth::logout();
        $this->post('/login', ['email' => 'sri.audit@gpch.test', 'password' => 'password'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_ac07_admin_cannot_read_confidential_files(): void
    {
        $it = $this->user('it@gpch.test');
        $this->actingAs($it)->get(route('documents.show', $this->doc('Medical Check-up')))->assertForbidden();
        $kontrak = $this->doc('Kontrak Pemeliharaan Lift');
        $this->actingAs($it)->get(route('documents.download', [$kontrak, $kontrak->current_version_id]))->assertForbidden();
    }

    public function test_ac08_disposal_blocked_under_legal_hold(): void
    {
        $dc = $this->user('corsec@gpch.test');
        $doc = $this->doc('Program Loyalitas');
        $this->assertTrue($doc->hasActiveHold());
        $this->actingAs($dc)->post(route('disposal.store', $doc), ['reason' => 'Uji', 'method' => 'Penghapusan aman digital'])->assertSessionHas('error');
        $this->assertSame(0, $doc->disposalRequests()->count());
    }

    public function test_ac09_gm_dashboard_shows_overdue_and_pic(): void
    {
        $this->actingAs($this->user('gm@gpch.test'))->get('/dashboard')->assertOk()
            ->assertSee('Action item lewat tenggat')->assertSee('Jadwalkan riksa uji lift dengan PJK3')->assertSee('Agus Setiawan');
    }

    public function test_ac10_link_from_asset_care_respects_permission(): void
    {
        $this->actingAs($this->user('rizky@gpch.test'))->get('/link/asset_care/AST-ENG-CHL-01')->assertOk()->assertSee('Garansi');
        $this->getJson('/api/v1/links/asset_care/AST-ENG-CHL-01', ['Authorization' => 'Bearer demo-assetcare-token-0123456789'])
            ->assertOk()->assertJsonStructure(['data', 'deep_link']);
        $this->getJson('/api/v1/documents')->assertUnauthorized();
    }

    public function test_audit_chain_is_intact_and_append_only(): void
    {
        $this->assertTrue(AuditLogger::verify()['ok']);
        $this->expectException(\RuntimeException::class);
        \App\Models\AuditEvent::first()->update(['description' => 'diubah']);
    }

    public function test_publish_flow_by_document_controller(): void
    {
        $dc = $this->user('corsec@gpch.test');
        $doc = $this->doc('Laundry Linen & Seragam 2027');
        $this->actingAs($dc)->withMfa()->post(route('documents.publish', $doc), ['effective_at' => today()->toDateString()])->assertSessionHas('success');
        $this->assertSame('published', $doc->fresh()->status);
    }
}
