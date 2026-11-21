<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Entity;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $this->requireRole('it_admin', 'doc_controller');
        return view('admin.users', [
            'users' => User::with(['department', 'entity'])->orderBy('role')->orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(), 'entities' => Entity::all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireRole('it_admin');
        $data = $request->validate([
            'name' => 'required|string|max:120', 'email' => 'required|email|unique:users,email', 'position' => 'nullable|string|max:120',
            'role' => ['required', Rule::in(array_keys(User::ROLES))], 'entity_id' => 'required|exists:entities,id',
            'department_id' => 'required|exists:departments,id', 'access_expires_at' => 'nullable|date|after:today',
        ]);
        if (in_array($data['role'], ['auditor']) && empty($data['access_expires_at'])) {
            return back()->with('error', 'Akun auditor/tamu wajib memiliki tanggal kedaluwarsa akses.');
        }
        $pwd = Str::password(12, symbols: false);
        $u = User::create($data + ['password' => $pwd]);
        AuditLogger::log('admin.user_updated', $u, 'Pengguna dibuat: '.$u->email.' ('.$u->role.')');
        return back()->with('success', 'Pengguna dibuat. Kata sandi sementara: '.$pwd.' (sampaikan melalui kanal aman).');
    }

    public function update(Request $request, User $user)
    {
        $this->requireRole('it_admin');
        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::ROLES))], 'department_id' => 'required|exists:departments,id',
            'is_active' => 'boolean', 'can_bulk_export' => 'boolean', 'access_expires_at' => 'nullable|date',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['can_bulk_export'] = $request->boolean('can_bulk_export');
        abort_if($user->id === $request->user()->id && ! $data['is_active'], 422, 'Tidak dapat menonaktifkan diri sendiri.');
        $user->update($data);
        if (! $data['is_active']) \DB::table('sessions')->where('user_id', $user->id)->delete(); // revoke sesi
        AuditLogger::log('admin.user_updated', $user, 'Pengguna diubah: '.json_encode($user->getChanges()));
        return back()->with('success', 'Pengguna diperbarui.');
    }

    public function token(Request $request, User $user)
    {
        $this->requireRole('it_admin');
        abort_unless($user->role === 'service', 422, 'Token API hanya untuk service account.');
        $plain = Str::random(48);
        $user->update(['api_token' => hash('sha256', $plain)]);
        AuditLogger::log('admin.user_updated', $user, 'Token API diterbitkan ulang');
        return back()->with('success', 'Token baru untuk '.$user->name.': '.$plain.' — simpan sekarang, tidak akan ditampilkan lagi.');
    }
}
