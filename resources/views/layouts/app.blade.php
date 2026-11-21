<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>@yield('title', 'Dashboard') · SENTRA</title>
</head>
<body class="bg-paper text-slate-800 font-sans antialiased">
@php
    $u = auth()->user();
    $unread = \App\Models\AppNotification::where('user_id', $u->id)->whereNull('read_at')->count();
    $taskCount = \App\Models\WorkflowStep::where('action','pending')->where(fn($q)=>$q->where(fn($x)=>$x->where('assignee_id',$u->id)->whereNull('delegated_to_id'))->orWhere('delegated_to_id',$u->id))->count();
    $nav = [
        ['Utama', [
            ['dashboard', 'Dashboard', 'M3 12l2-2 7-7 7 7 2 2M5 10v10h5v-6h4v6h5V10', null],
            ['tasks.index', 'Tugas Saya', 'M9 5h10M9 12h10M9 19h10M4 5l1 1 2-2M4 12l1 1 2-2M4 19l1 1 2-2', $taskCount],
            ['search', 'Pencarian', 'M21 21l-5-5m2-5a7 7 0 11-14 0 7 7 0 0114 0z', null],
            ['documents.index', 'Repository', 'M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z', null],
        ]],
        ['Modul', [
            ['modules.legal', 'Legal & Perizinan', 'M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z', null],
            ['modules.policies', 'Kebijakan & SOP', 'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6', null],
            ['meetings.index', 'Rapat & Keputusan', 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14H4V6a1 1 0 011-1z', null],
            ['action-items.index', 'Action Item', 'M5 13l4 4L19 7', null],
            ['modules.show:finance', 'Finance & Tax', 'M12 8c-2 0-3 .9-3 2s1 2 3 2 3 .9 3 2-1 2-3 2m0-8V6m0 10v2M4 4h16v16H4z', null],
            ['modules.show:marketing', 'Marketing & Customer', 'M3 11l15-6v14L3 13v-2zm4 2v5h3', null],
            ['modules.show:hr', 'SDM & Organisasi', 'M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8z', null],
            ['modules.show:operations', 'Operasional & Aset', 'M10 3l1 3h2l1-3 3 2-1 3 2 2 3-1 1 3-3 1v2l3 1-1 3-3-1-2 2 1 3-3 2-1-3h-2l-1 3-3-2 1-3-2-2-3 1-1-3 3-1v-2l-3-1 1-3 3 1 2-2-1-3z', null],
        ]],
        ['Tata Kelola', [
            ['access-requests.index', 'Permintaan Akses', 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.7 5.7L11 17H9v2H7v2H3v-3l6.3-6.3A6 6 0 1121 9z', null],
            ['retention.index', 'Retensi & Legal Hold', 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', null],
            ['reports.index', 'Laporan & KPI', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6h6zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0h6m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14h-6z', null],
            ['audit.index', 'Audit Trail', 'M9 12l2 2 4-4m5.6-4A12 12 0 0112 2.9 12 12 0 013.4 6 12 12 0 0012 21a12 12 0 008.6-15z', null],
        ]],
        ['Administrasi', [
            ['admin.users.index', 'Pengguna & Peran', 'M12 4.4a4 4 0 110 5.3M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.2M13 7a4 4 0 11-8 0 4 4 0 018 0z', null],
            ['admin.master.index', 'Master Data', 'M4 7v10c0 2 3.6 4 8 4s8-2 8-4V7M4 7c0 2 3.6 4 8 4s8-2 8-4M4 7c0-2 3.6-4 8-4s8 2 8 4', null],
            ['admin.integrations', 'Integrasi & API', 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', null],
        ]],
    ];
    $allowed = function ($route) use ($u) {
        return match (true) {
            str_starts_with($route, 'admin.') => $u->hasRole('it_admin','doc_controller'),
            $route === 'retention.index' => $u->hasRole('doc_controller','legal','bod_gm','auditor','owner'),
            $route === 'reports.index' => $u->hasRole('owner','bod_gm','doc_controller','legal','auditor'),
            $route === 'audit.index' => $u->hasRole('doc_controller','it_admin','auditor','bod_gm','legal','owner'),
            default => true,
        };
    };
@endphp

<div class="stripe h-1.5 w-full fixed top-0 z-50"></div>

{{-- Sidebar --}}
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r-2 border-navy-700 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col pt-1.5">
    <div class="px-5 py-4 border-b-2 border-slate-200 flex items-center gap-3">
        <div class="w-10 h-10 rounded-md bg-navy-700 flex items-center justify-center border-b-4 border-gold-500">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16v4H4zM5 8v12h14V8M10 12h4"/></svg>
        </div>
        <div class="min-w-0">
            <div class="font-bold text-navy-700 text-lg leading-none tracking-wide">SENTRA</div>
            <div class="text-[10.5px] text-slate-500 leading-tight mt-1">Enterprise Document &amp; Governance System</div>
        </div>
        <button onclick="toggleNav()" class="lg:hidden ml-auto p-2 text-slate-500" aria-label="Tutup menu">✕</button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-3 text-sm">
        @foreach($nav as [$group, $items])
            @php $visibleItems = collect($items)->filter(fn($i) => $allowed(explode(':', $i[0])[0])); @endphp
            @if($visibleItems->isNotEmpty())
            <div class="px-2 pt-3 pb-1 text-[10.5px] font-bold uppercase tracking-widest text-slate-400">{{ $group }}</div>
            @foreach($visibleItems as [$route, $label, $icon, $count])
                @php
                    [$r, $param] = array_pad(explode(':', $route), 2, null);
                    $url = $param ? route($r, $param) : route($r);
                    $active = $param ? request()->is('module/'.$param) : request()->routeIs($r) || request()->routeIs(str_replace('.index','',$r).'.*');
                @endphp
                <a href="{{ $url }}" class="flex items-center gap-3 px-3 py-2 rounded-md mb-0.5 border-l-4 {{ $active ? 'bg-navy-50 border-gold-500 text-navy-700 font-semibold' : 'border-transparent text-slate-600 hover:bg-slate-50 hover:text-navy-700' }}">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>
                    <span class="flex-1">{{ $label }}</span>
                    @if($count)<span class="badge bg-gold-500 text-white">{{ $count }}</span>@endif
                </a>
            @endforeach
            @endif
        @endforeach
    </nav>
    <div class="border-t-2 border-slate-200 p-4 text-xs text-slate-500">
        <div class="font-semibold text-slate-700">{{ $u->entity?->name }}</div>
        <div>Prinsip 3T: Terstruktur • Tersistem • Tepat Sasaran</div>
    </div>
</aside>
<div id="backdrop" onclick="toggleNav()" class="fixed inset-0 bg-navy-900/40 z-30 hidden lg:hidden"></div>

<div class="lg:pl-72 pt-1.5 min-h-screen flex flex-col">
    {{-- Topbar --}}
    <header class="sticky top-1.5 z-20 bg-white/95 backdrop-blur border-b-2 border-slate-200">
        <div class="flex items-center gap-2 sm:gap-3 px-3 sm:px-6 h-16">
            <button onclick="toggleNav()" class="lg:hidden p-2 -ml-1 rounded-md border-2 border-slate-200 text-navy-700" aria-label="Buka menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <form action="{{ route('search') }}" class="flex-1 max-w-xl">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-5-5m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input name="q" value="{{ request('q') }}" placeholder="Cari nomor, judul, isi dokumen…" class="input !pl-9 !py-2">
                </div>
            </form>
            <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-md border-2 border-slate-200 text-navy-700 hover:border-navy-500" aria-label="Notifikasi">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if($unread)<span class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1">{{ $unread }}</span>@endif
            </a>
            <div class="flex items-center gap-2 pl-1">
                <div class="w-9 h-9 rounded-md bg-gold-100 text-gold-600 border-2 border-gold-400 font-bold flex items-center justify-center text-sm">{{ $u->initials() }}</div>
                <div class="hidden md:block leading-tight">
                    <div class="text-sm font-semibold text-slate-800">{{ $u->name }}</div>
                    <div class="text-[11px] text-slate-500">{{ $u->roleLabel() }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="p-2 text-slate-500 hover:text-rose-600" title="Keluar" aria-label="Keluar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 px-3 sm:px-6 py-5 sm:py-6 max-w-[1400px] w-full">
        @foreach(['success' => 'emerald', 'error' => 'rose', 'warning' => 'amber'] as $k => $c)
            @if(session($k))
                <div class="mb-4 card card-accent border-l-{{ $c }}-600 px-4 py-3 text-sm text-{{ $c }}-900 bg-{{ $c }}-50">{{ session($k) }}</div>
            @endif
        @endforeach
        @if($errors->any())
            <div class="mb-4 card card-accent border-l-rose-600 px-4 py-3 text-sm text-rose-900 bg-rose-50">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @hasSection('header')
        <div class="mb-5 flex flex-col sm:flex-row sm:items-end gap-3 sm:justify-between border-b-2 border-slate-200 pb-4">
            <div class="min-w-0">@yield('header')</div>
            <div class="flex flex-wrap gap-2">@yield('actions')</div>
        </div>
        @endif

        @yield('content')
    </main>
    <footer class="px-6 py-4 text-xs text-slate-400 border-t-2 border-slate-200 bg-white">
        SENTRA · Enterprise Document &amp; Governance System — Grand Pasundan Convention Hotel · Seluruh akses tercatat dalam audit trail.
    </footer>
</div>
{{-- Bilah navigasi bawah untuk smartphone --}}
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t-2 border-navy-700 grid grid-cols-4 text-[11px] font-semibold text-slate-600">
    @foreach([['dashboard','Beranda','M3 12l2-2 7-7 7 7 2 2M5 10v10h5v-6h4v6h5V10'],['tasks.index','Tugas','M9 5h10M9 12h10M9 19h10M4 5l1 1 2-2M4 12l1 1 2-2M4 19l1 1 2-2'],['search','Cari','M21 21l-5-5m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],['documents.index','Arsip','M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z']] as [$r,$l,$i])
        <a href="{{ route($r) }}" class="flex flex-col items-center py-2 gap-0.5 {{ request()->routeIs($r) ? 'text-navy-700 border-t-4 border-gold-500 -mt-0.5' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="{{ $i }}"/></svg>{{ $l }}
            @if($r==='tasks.index' && $taskCount)<span class="absolute ml-8 -mt-1 bg-gold-500 text-white rounded-full px-1.5 text-[10px]">{{ $taskCount }}</span>@endif
        </a>
    @endforeach
</nav>
<div class="h-16 lg:hidden"></div>
<script>
function toggleNav(){document.getElementById('sidebar').classList.toggle('-translate-x-full');document.getElementById('backdrop').classList.toggle('hidden');}
document.querySelectorAll('[data-confirm]').forEach(f=>f.addEventListener('submit',e=>{if(!confirm(f.dataset.confirm))e.preventDefault();}));
document.querySelectorAll('[data-toggle]').forEach(b=>b.addEventListener('click',()=>document.getElementById(b.dataset.toggle).classList.toggle('hidden')));
</script>
@stack('scripts')
</body>
</html>
