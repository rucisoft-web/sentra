<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>SENTRA · Enterprise Document & Governance System</title>
    <meta name="description" content="SENTRA — pusat arsip korporasi resmi: terstruktur, tersistem, tepat sasaran. Repository, workflow persetujuan, legal & compliance, retensi, audit trail.">
    <style>
        .grid-paper{background-image:linear-gradient(#e5eaf1 1px,transparent 1px),linear-gradient(90deg,#e5eaf1 1px,transparent 1px);background-size:28px 28px}
        .tab-folder{position:relative}.tab-folder:before{content:"";position:absolute;top:-10px;left:16px;width:70px;height:12px;border:1.5px solid currentColor;border-bottom:0;border-radius:6px 6px 0 0;background:inherit}
        html{scroll-behavior:smooth}
    </style>
</head>
<body class="bg-white text-slate-800 font-sans antialiased">
<div class="stripe h-1.5"></div>

{{-- NAV --}}
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b-2 border-navy-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-4">
        <a href="#" class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-md bg-navy-700 flex items-center justify-center border-b-4 border-gold-500">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16v4H4zM5 8v12h14V8M10 12h4"/></svg>
            </div>
            <div class="leading-none"><div class="font-bold text-navy-700 tracking-wider">SENTRA</div><div class="text-[10px] text-slate-500 hidden sm:block mt-0.5">Enterprise Document &amp; Governance System</div></div>
        </a>
        <nav class="hidden md:flex items-center gap-6 ml-auto text-sm font-semibold text-slate-600">
            <a href="#masalah" class="hover:text-navy-700">Masalah</a>
            <a href="#penyebab" class="hover:text-navy-700">Penyebab</a>
            <a href="#solusi" class="hover:text-navy-700">Solusi</a>
            <a href="#fitur" class="hover:text-navy-700">Fitur</a>
            <a href="#keamanan" class="hover:text-navy-700">Keamanan</a>
        </nav>
        <div class="ml-auto md:ml-4 flex items-center gap-2">
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary btn-sm sm:!py-2 sm:!px-4">{{ auth()->check() ? 'Buka Dashboard' : 'Masuk Demo' }}</a>
            <button class="md:hidden p-2 border-2 border-slate-200 rounded-md text-navy-700" data-toggle="mnav" aria-label="Menu"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        </div>
    </div>
    <div id="mnav" class="hidden md:hidden border-t-2 border-slate-100 px-4 py-2 grid grid-cols-2 gap-1 text-sm font-semibold text-navy-700">
        @foreach(['masalah'=>'Masalah','penyebab'=>'Penyebab','solusi'=>'Solusi','fitur'=>'Fitur','keamanan'=>'Keamanan','integrasi'=>'Integrasi'] as $id=>$l)<a href="#{{ $id }}" class="px-3 py-2 rounded hover:bg-navy-50" onclick="document.getElementById('mnav').classList.add('hidden')">{{ $l }}</a>@endforeach
    </div>
</header>

{{-- HERO --}}
<section class="grid-paper border-b-2 border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20 grid lg:grid-cols-2 gap-10 items-center">
        <div>
            <div class="inline-flex items-center gap-2 bg-white border-2 border-gold-500 text-gold-600 text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full mb-5">Terstruktur • Tersistem • Tepat Sasaran</div>
            <h1 class="text-4xl sm:text-5xl font-bold text-navy-700 leading-[1.1]">Arsip korporasi yang <span class="relative whitespace-nowrap"><span class="relative z-10">otentik</span><span class="absolute left-0 right-0 bottom-1 h-3 bg-gold-100 -z-0"></span></span>, mudah ditemukan, dan terkendali.</h1>
            <p class="mt-5 text-lg text-slate-600 max-w-xl"><b>SENTRA</b> adalah pusat arsip resmi perusahaan yang menyimpan, mengatur, menelusuri, menyetujui, melindungi dan mempertahankan dokumen sepanjang siklus hidupnya — sehingga setiap keputusan memiliki bukti, setiap kewajiban hukum terpantau, dan arsip tidak lagi bergantung pada individu.</p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="btn btn-primary !px-6 !py-3 !text-base">Coba demo Grand Pasundan</a>
                <a href="#fitur" class="btn btn-outline !px-6 !py-3 !text-base">Lihat fitur</a>
            </div>
            <div class="mt-8 grid grid-cols-3 gap-3 max-w-md">
                @foreach([['≤ 2 menit','temukan dokumen prioritas'],['100%','dokumen ber-owner & klasifikasi'],['0','izin kritis kedaluwarsa']] as [$v,$l])
                    <div class="border-l-4 border-gold-500 pl-3"><div class="text-xl font-bold text-navy-700">{{ $v }}</div><div class="text-[11px] text-slate-500 leading-tight">{{ $l }}</div></div>
                @endforeach
            </div>
        </div>
        {{-- ilustrasi arsip digital --}}
        <div class="relative">
            <div class="absolute -top-3 -left-3 w-full h-full border-2 border-dashed border-gold-400 rounded-xl hidden sm:block"></div>
            <div class="relative card !border-2 !border-navy-700 shadow-[8px_8px_0_#12355b] overflow-hidden">
                <div class="bg-navy-700 text-white px-4 py-2.5 flex items-center gap-2 text-xs"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span><span class="w-2.5 h-2.5 rounded-full bg-gold-400"></span><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span><span class="ml-2 doc-code opacity-80">sentra / repository / legal</span></div>
                <div class="p-4 space-y-3 bg-paper">
                    @foreach([
                        ['GPCH/LEG/SLF/2021/001','Sertifikat Laik Fungsi Gedung Utama','Berlaku','bg-emerald-100 text-emerald-800','25 hari lagi','bg-rose-100 text-rose-800','border-l-gold-500'],
                        ['GPCH/BOD/SKD/2026/014','Keputusan Direksi — Renovasi Ballroom','Rahasia','bg-amber-100 text-amber-800','v1.0 · disahkan','bg-teal-100 text-teal-800','border-l-navy-700'],
                        ['GPCH/ENG/SOP/2026/003','SOP Pemeliharaan Chiller & AHU','Dalam Review','bg-indigo-100 text-indigo-800','v2.1 · menunggu GM','bg-slate-100 text-slate-700','border-l-indigo-600'],
                    ] as [$c,$t,$b1,$c1,$b2,$c2,$bl])
                    <div class="bg-white border-2 border-slate-200 border-l-[5px] {{ $bl }} rounded-lg p-3">
                        <div class="flex flex-wrap gap-1.5 mb-1"><span class="doc-code text-[10.5px] bg-navy-50 text-navy-700 border border-navy-100 rounded px-1.5">{{ $c }}</span><span class="badge {{ $c1 }}">{{ $b1 }}</span><span class="badge {{ $c2 }}">{{ $b2 }}</span></div>
                        <div class="font-semibold text-sm text-slate-800">{{ $t }}</div>
                    </div>
                    @endforeach
                    <div class="bg-white border-2 border-slate-200 rounded-lg p-3 text-[11px]">
                        <div class="font-bold text-navy-700 mb-1.5">Audit trail · hash chain ✓</div>
                        <div class="doc-code text-slate-500 truncate">#4127 Unduh v1.0 — Dewi L. · 9f2c…e81a</div>
                        <div class="doc-code text-slate-500 truncate">#4128 Disahkan (MFA) — Rina K. · 3ab0…77d4</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- MASALAH --}}
<section id="masalah" class="py-16 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="max-w-3xl mb-10">
            <div class="section-title mb-2">Masalah yang sering terjadi</div>
            <h2 class="text-3xl font-bold text-navy-700">Arsip penting ada, tetapi tidak bisa diandalkan saat dibutuhkan.</h2>
            <p class="text-slate-600 mt-3">Di hotel dan perusahaan jasa, dokumen tersebar di lemari, shared drive, email dan aplikasi chat. Akibatnya muncul masalah berulang yang mahal — terutama saat audit, pemeriksaan pemerintah, sengketa, atau pergantian karyawan.</p>
        </div>
        @php
        $problems = [
            ['🔍','Dokumen sulit ditemukan','Mencari akta, kontrak atau notulen butuh berjam-jam hingga berhari-hari; sering harus bertanya ke orang tertentu.'],
            ['📑','Tidak jelas versi mana yang sah','Ada "SOP_final_revisi2_fix.docx" di banyak folder. Staf memakai versi lama yang sudah dicabut.'],
            ['⏰','Izin & kontrak kedaluwarsa tanpa disadari','SLF, sertifikat halal, riksa uji lift atau kontrak vendor lewat masa berlaku — berisiko denda, penutupan, atau gangguan layanan.'],
            ['📋','Keputusan rapat tanpa tindak lanjut','Notulen direksi tersimpan, tetapi siapa PIC-nya, kapan tenggatnya, dan apa buktinya tidak terpantau.'],
            ['🔓','Dokumen rahasia bocor atau terbuka luas','Folder share terbuka untuk semua; anggaran, hasil audit dan data personel bisa dibuka siapa saja — termasuk admin IT.'],
            ['🚪','Arsip hilang saat karyawan resign','Dokumen tersimpan di laptop, email pribadi atau ingatan seseorang. Saat orangnya pergi, buktinya ikut hilang.'],
            ['🧾','Audit lambat & melelahkan','Auditor meminta bukti, tim sibuk mengumpulkan berkas; tidak ada jejak siapa melihat, mengubah atau menyetujui.'],
            ['🗄️','Arsip menumpuk, pemusnahan tak terkendali','Semua disimpan selamanya "untuk jaga-jaga" — atau sebaliknya dibuang tanpa dasar, padahal sedang dibutuhkan untuk perkara.'],
        ];
        @endphp
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($problems as $i => [$icon,$t,$d])
            <div class="card card-accent border-l-rose-500 p-5 hover:-translate-y-0.5 transition">
                <div class="flex items-center justify-between mb-3"><span class="text-2xl">{{ $icon }}</span><span class="doc-code text-xs text-rose-400 font-bold">M-{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span></div>
                <h3 class="font-bold text-slate-800">{{ $t }}</h3>
                <p class="text-sm text-slate-600 mt-1.5">{{ $d }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- PENYEBAB --}}
<section id="penyebab" class="py-16 sm:py-20 bg-paper border-y-2 border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="max-w-3xl mb-10">
            <div class="section-title mb-2">Apa penyebabnya?</div>
            <h2 class="text-3xl font-bold text-navy-700">Bukan karena orangnya — karena tidak ada sistem yang mengikat.</h2>
            <p class="text-slate-600 mt-3">Masalah arsip hampir selalu berakar pada enam penyebab yang saling berkaitan.</p>
        </div>
        @php
        $causes = [
            ['Tidak ada taksonomi & penomoran baku','Setiap departemen menamai dan menyimpan dokumen dengan caranya sendiri; tidak ada metadata wajib, pemilik dan kode unik.','M-01, M-02'],
            ['Penyimpanan tersebar, tanpa sumber kebenaran tunggal','Kertas, shared drive, email, WhatsApp dan laptop pribadi — salinan berlipat tanpa tahu mana yang otentik.','M-01, M-06'],
            ['Tidak ada alur persetujuan & versioning','Dokumen bisa berubah kapan saja; tidak ada status draft–review–sah, tidak ada penguncian versi final.','M-02'],
            ['Tidak ada pemantauan masa berlaku & tenggat','Tanggal berakhir izin dan tenggat tindak lanjut hanya diingat manual atau di spreadsheet yang jarang diperbarui.','M-03, M-04'],
            ['Hak akses tidak dikelola berdasarkan kebutuhan','Akses diberikan per folder secara longgar, tanpa klasifikasi, tanpa masa berlaku, dan admin teknis bisa membaca segalanya.','M-05'],
            ['Tidak ada audit trail & jadwal retensi','Tidak ada catatan siapa melihat/mengubah/mengunduh; tidak ada aturan berapa lama disimpan dan kapan boleh dimusnahkan.','M-07, M-08'],
        ];
        @endphp
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($causes as $i => [$t,$d,$rel])
            <div class="card p-5 relative overflow-hidden">
                <div class="absolute -right-2 -top-4 text-7xl font-bold text-slate-100 select-none">{{ $i+1 }}</div>
                <div class="relative">
                    <div class="w-10 h-1.5 bg-amber-500 rounded mb-3"></div>
                    <h3 class="font-bold text-slate-800">{{ $t }}</h3>
                    <p class="text-sm text-slate-600 mt-1.5">{{ $d }}</p>
                    <div class="text-xs text-slate-400 mt-3">Memicu masalah: <span class="doc-code text-rose-500">{{ $rel }}</span></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- SOLUSI --}}
<section id="solusi" class="py-16 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="max-w-3xl mb-10">
            <div class="section-title mb-2">Solusi SENTRA</div>
            <h2 class="text-3xl font-bold text-navy-700">Satu sumber kebenaran untuk dokumen resmi, dengan prinsip 3T.</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-4 mb-12">
            @foreach([
                ['Terstruktur','border-t-navy-700','text-navy-700','Taksonomi perusahaan → departemen → kategori → jenis → tahun → nomor dokumen, lengkap dengan penanggung jawab dan jadwal retensi.'],
                ['Tersistem','border-t-gold-500','text-gold-600','Workflow draft → review → approval → publish → archive/dispose, audit trail, notifikasi otomatis dan kontrol akses terpusat.'],
                ['Tepat Sasaran','border-t-teal-600','text-teal-700','Pencarian cepat, hanya versi terbaru yang sah, kepatuhan terpantau, audit efisien, dan laporan tindakan yang belum selesai.'],
            ] as [$t,$b,$c,$d])
            <div class="card border-t-[6px] {{ $b }} p-6"><h3 class="text-xl font-bold {{ $c }}">{{ $t }}</h3><p class="text-sm text-slate-600 mt-2">{{ $d }}</p></div>
            @endforeach
        </div>

        <div class="section-title mb-4">Dari masalah ke solusi</div>
        @php
        $map = [
            ['Dokumen sulit ditemukan','Pencarian full-text + filter (jenis, PIC, tanggal, status), saved search, folder virtual; hasil langsung disaring sesuai izin.'],
            ['Versi tidak jelas','Versioning major/minor, check-in/check-out, hash SHA-256, versi final terkunci dan otomatis "superseded" saat versi baru terbit.'],
            ['Izin & kontrak kedaluwarsa','Register legal dengan pengingat otomatis H-90/60/30 ke pemilik, kepala departemen & Legal, plus relasi bukti perpanjangan.'],
            ['Keputusan tanpa tindak lanjut','Modul rapat: keputusan, action item, PIC, tenggat dan bukti tindak lanjut wajib; dashboard GM menyorot yang terlambat.'],
            ['Kebocoran dokumen rahasia','RBAC + ABAC + ACL per dokumen, 4 tingkat klasifikasi, deny mengalahkan allow, MFA, watermark, unduhan dibatasi & tercatat.'],
            ['Arsip bergantung individu','Setiap dokumen punya owner, klasifikasi dan lokasi resmi di SENTRA — bukan di laptop atau email pribadi.'],
            ['Audit lambat','Akses auditor read-only berbatas waktu berdasarkan mandat; audit trail append-only berantai hash; laporan KPI siap pakai.'],
            ['Retensi tak terkendali','Jadwal retensi per kategori, legal hold yang memblokir pemusnahan, persetujuan ganda dan berita acara pemusnahan.'],
        ];
        @endphp
        <div class="card overflow-hidden">
            @foreach($map as $i => [$p,$s])
            <div class="grid md:grid-cols-[minmax(0,1fr)_40px_minmax(0,2fr)] items-center gap-2 md:gap-4 px-4 sm:px-5 py-4 {{ $i ? 'border-t-2 border-slate-100' : '' }}">
                <div class="flex items-center gap-2"><span class="doc-code text-xs font-bold text-rose-500">M-{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span><span class="font-semibold text-slate-700">{{ $p }}</span></div>
                <div class="hidden md:flex justify-center text-gold-500 font-bold text-xl">→</div>
                <div class="text-sm text-slate-600 border-l-4 border-emerald-500 pl-3">{{ $s }}</div>
            </div>
            @endforeach
        </div>

        {{-- siklus hidup --}}
        <div class="mt-12">
            <div class="section-title mb-4">Siklus hidup dokumen yang tercatat penuh</div>
            <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm font-semibold">
                @foreach([['Draft','bg-slate-100 text-slate-700 border-slate-300'],['Diajukan','bg-indigo-50 text-indigo-800 border-indigo-300'],['Dalam Review','bg-indigo-50 text-indigo-800 border-indigo-300'],['Disetujui (MFA)','bg-teal-50 text-teal-800 border-teal-300'],['Berlaku','bg-emerald-50 text-emerald-800 border-emerald-400'],['Digantikan','bg-zinc-100 text-zinc-700 border-zinc-300'],['Arsip Inaktif','bg-stone-100 text-stone-700 border-stone-300'],['Layak Musnah','bg-yellow-50 text-yellow-800 border-yellow-300'],['Dimusnahkan + Berita Acara','bg-zinc-800 text-white border-zinc-800']] as $i => [$s,$c])
                    @if($i)<span class="text-gold-500">›</span>@endif<span class="px-3 py-1.5 rounded-md border-2 {{ $c }}">{{ $s }}</span>
                @endforeach
            </div>
            <p class="text-sm text-slate-500 mt-3">Cabang khusus: <b>Perlu Revisi</b>, <b>Ditolak</b>, dan <b>Legal Hold</b>. Setiap transisi mencatat identitas, waktu, alasan dan versi.</p>
        </div>
    </div>
</section>

{{-- FITUR --}}
<section id="fitur" class="py-16 sm:py-20 bg-navy-700 text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-[0.07] grid-paper"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 relative">
        <div class="max-w-3xl mb-10">
            <div class="text-xs font-bold uppercase tracking-widest text-gold-400 mb-2">Fitur SENTRA</div>
            <h2 class="text-3xl font-bold">13 modul untuk seluruh siklus tata kelola dokumen.</h2>
            <p class="text-navy-100 mt-3">Melengkapi GPCH Asset Care, Ruci BSC, CRM GPCH dan VHP — bukan menggantikan sistem transaksi hotel.</p>
        </div>
        @php
        $features = [
            ['M01','Repository & Klasifikasi','Folder virtual, jenis dokumen, nomor unik otomatis per entitas, metadata wajib, relasi antardokumen, tag, preview aman.'],
            ['M02','Legal & Compliance','Akta, NIB, perizinan, sertifikat, kontrak, pihak terkait, kewajiban, tanggal efektif/akhir, pengingat 90/60/30 hari.'],
            ['M03','Kebijakan, SOP & Organisasi','Struktur organisasi berlaku, kebijakan, SOP, instruksi kerja, acknowledgement karyawan dan review periodik.'],
            ['M04','Board & Management Records','Agenda, risalah, keputusan, action item, PIC, tenggat, bukti tindak lanjut, akses khusus.'],
            ['M05','Finance & Tax Archive','Laporan final, bukti pendukung, pajak, anggaran dan evaluasi — tertaut ke VHP & Ruci BSC.'],
            ['M06','Marketing & Customer Archive','Kontrak korporat, proposal, kampanye, evaluasi — data customer master tetap di CRM GPCH.'],
            ['M07','Workflow & e-Approval','Alur serial/paralel, delegasi sementara, SLA & eskalasi, penolakan beralasan, pemisahan tugas.'],
            ['M08','Versioning & Publikasi','Check-in/out, versi major/minor, bandingkan metadata, supersede, tanggal efektif, versi final terkunci.'],
            ['M09','Search & Discovery','Full-text termasuk isi dokumen, filter tanggal/jenis/PIC/status, saved search, hasil sesuai izin.'],
            ['M10','Retensi & Records','Jadwal retensi per kategori, arsip inaktif, legal hold, pemusnahan dengan persetujuan ganda & berita acara.'],
            ['M11','Audit & Keamanan','Log lihat/unduh/ubah/approve/ekspor berantai hash, monitoring anomali, watermark, kontrol unduhan.'],
            ['M12','Dashboard & Laporan','Dokumen kedaluwarsa, review overdue, persetujuan tertunda, arsip tidak lengkap, aktivitas, SLA pencarian, KPI.'],
            ['M13','Administrasi & Integrasi','Master entitas & unit, pengguna & peran, MFA, API & deep link, mandat auditor, ekspor terkendali.'],
        ];
        @endphp
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($features as [$code,$t,$d])
            <div class="bg-white text-slate-800 rounded-lg border-2 border-white p-5 border-b-[5px] border-b-gold-500">
                <div class="doc-code text-xs font-bold text-gold-600 mb-1">{{ $code }}</div>
                <h3 class="font-bold text-navy-700">{{ $t }}</h3>
                <p class="text-sm text-slate-600 mt-1.5">{{ $d }}</p>
            </div>
            @endforeach
            <div class="rounded-lg border-2 border-dashed border-gold-400 p-5 flex flex-col justify-center">
                <div class="font-bold text-gold-400">Responsif di smartphone</div>
                <p class="text-sm text-navy-100 mt-1.5">Setujui dokumen, cek izin yang akan berakhir, dan perbarui action item langsung dari ponsel.</p>
            </div>
        </div>
    </div>
</section>

{{-- KEAMANAN --}}
<section id="keamanan" class="py-16 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 grid lg:grid-cols-2 gap-10">
        <div>
            <div class="section-title mb-2">Keamanan & tata kelola</div>
            <h2 class="text-3xl font-bold text-navy-700">Default deny. Least privilege. Semua tercatat.</h2>
            <p class="text-slate-600 mt-3">Keputusan izin = peran + unit organisasi + entitas hukum + klasifikasi + kepemilikan + status dokumen + masa berlaku akses. Otorisasi dilakukan di server untuk setiap lihat, cari, preview, unduh, API dan ekspor.</p>
            <ul class="mt-6 space-y-3 text-sm">
                @foreach([
                    'Administrator teknis tidak otomatis dapat membaca dokumen rahasia.',
                    'Pembuat tidak dapat menyetujui dokumen material miliknya sendiri.',
                    'MFA ulang untuk pengesahan, dokumen Sangat Rahasia, ekspor, pemusnahan dan administrasi.',
                    'File terenkripsi di storage privat, diverifikasi SHA-256 setiap dibuka — tidak pernah di folder web publik.',
                    'Akses auditor & tamu selalu memiliki tanggal kedaluwarsa; izin ekspor massal terpisah.',
                    'Audit trail append-only berantai hash — manipulasi log dapat dideteksi.',
                ] as $x)
                <li class="flex gap-3"><span class="mt-0.5 w-5 h-5 shrink-0 rounded bg-emerald-600 text-white flex items-center justify-center text-xs">✓</span><span>{{ $x }}</span></li>
                @endforeach
            </ul>
        </div>
        <div>
            <div class="card overflow-hidden !border-2 !border-navy-700">
                <div class="px-5 py-3 bg-navy-50 border-b-2 border-navy-700 section-title">4 tingkat klasifikasi</div>
                @foreach([
                    ['Publik Internal','SOP umum, pengumuman','Pegawai terautentikasi sesuai entitas','border-l-emerald-500','text-emerald-700'],
                    ['Terbatas Departemen','SOP operasional, laporan fungsi','Departemen terkait & atasan berwenang','border-l-sky-500','text-sky-700'],
                    ['Rahasia','Kontrak, anggaran, hasil audit','ACL eksplisit, need-to-know','border-l-amber-500','text-amber-700'],
                    ['Sangat Rahasia','Strategi akuisisi, sengketa','Whitelist individu, MFA ulang, unduhan dibatasi','border-l-rose-600','text-rose-700'],
                ] as [$t,$e,$a,$b,$c])
                <div class="px-5 py-4 border-b border-slate-100 border-l-[6px] {{ $b }}">
                    <div class="font-bold {{ $c }}">{{ $t }}</div>
                    <div class="text-sm text-slate-600">Contoh: {{ $e }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">Akses default: {{ $a }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- INTEGRASI --}}
<section id="integrasi" class="py-16 bg-paper border-y-2 border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="max-w-3xl mb-8">
            <div class="section-title mb-2">Integrasi terkontrol</div>
            <h2 class="text-3xl font-bold text-navy-700">Tautkan, jangan salin.</h2>
            <p class="text-slate-600 mt-3">Dokumen dibuka dari sistem lain melalui deep link dan tetap melewati pemeriksaan izin pengguna — tanpa membuat salinan tidak terkendali.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([['GPCH Asset Care','Garansi, sertifikat & dokumen teknis aset'],['Ruci BSC','Bukti KPI, target & hasil evaluasi'],['CRM GPCH','Kontrak korporat & laporan marketing'],['VHP','Laporan keuangan final & ekspor terjadwal']] as [$s,$d])
                <div class="card p-5 tab-folder text-navy-700 bg-white mt-3"><div class="font-bold">{{ $s }}</div><div class="text-sm text-slate-600 mt-1">{{ $d }}</div></div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 sm:py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="card !border-2 !border-navy-700 shadow-[8px_8px_0_#c8962e] p-6 sm:p-10 grid md:grid-cols-3 gap-6 items-center">
            <div class="md:col-span-2">
                <div class="section-title mb-2">Demo: Grand Pasundan Convention Hotel</div>
                <h2 class="text-2xl sm:text-3xl font-bold text-navy-700">Jelajahi SENTRA dengan data hotel yang realistis.</h2>
                <p class="text-slate-600 mt-2">Perizinan yang akan berakhir, SOP dalam review, keputusan direksi dengan action item, legal hold, usulan pemusnahan, hingga audit trail — masuk sebagai GM, Legal, Document Controller, staf, auditor, atau admin IT untuk melihat perbedaan hak akses.</p>
            </div>
            <div class="flex flex-col gap-2">
                <a href="{{ route('login') }}" class="btn btn-primary !py-3 !text-base">Masuk ke demo</a>
                <div class="text-xs text-slate-500 text-center">Pilih akun demo di halaman masuk · kata sandi <span class="doc-code">password</span></div>
            </div>
        </div>
    </div>
</section>

<footer class="border-t-2 border-navy-700 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row gap-4 justify-between text-sm text-slate-500">
        <div><span class="font-bold text-navy-700 tracking-wider">SENTRA</span> · Enterprise Document &amp; Governance System</div>
        <div>Terstruktur • Tersistem • Tepat Sasaran</div>
    </div>
    <div class="stripe h-1.5"></div>
</footer>
<script>document.querySelectorAll('[data-toggle]').forEach(b=>b.addEventListener('click',()=>document.getElementById(b.dataset.toggle).classList.toggle('hidden')));</script>
</body>
</html>
