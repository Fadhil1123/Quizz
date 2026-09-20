<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Center - {{ $room->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6 select-none">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Top Navbar & Room Info -->
        <div class="flex justify-between items-center bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.rooms.index') }}" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition-all">
                    ← Kembali
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-pink-500/20 text-pink-400 border border-pink-500/30">
                            {{ $room->status }}
                        </span>
                        <h1 class="text-2xl font-black text-white">{{ $room->name }}</h1>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">Ruang Kendali Pertandingan Real-Time</p>
                </div>
            </div>

            <form action="{{ route('admin.rooms.finish', $room->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengakhiri kuis ini?')">
                @csrf
                <button class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl uppercase transition-all shadow-lg shadow-rose-600/20">
                    Selesaikan Kuis 🏆
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-400 text-xs font-bold">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Column 1 & 2: Active Question & Live Controls -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Soal Aktif Card -->
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-6">
                    <div class="flex justify-between items-center border-b border-slate-800 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-pink-400 uppercase tracking-wider">Soal Aktif di Panggung</span>
                            <span id="paused-badge" class="hidden px-2.5 py-0.5 bg-amber-500/20 border border-amber-500/40 text-amber-300 font-bold text-[10px] rounded-full uppercase animate-pulse">
                                ⏸️ PAUSED
                            </span>
                        </div>
                        
                        <!-- DUAL TIMER DISPLAY ADMIN -->
                        <div class="flex items-center gap-4">
                            <!-- Global Timer (3 Menit) -->
                            <div class="text-right">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Waktu Soal</span>
                                <span id="admin-global-timer" class="font-mono text-xl font-black text-pink-400">180s</span>
                            </div>
                            
                            <!-- Sub-Phase Timer (Menjawab/Operan) -->
                            <div id="admin-phase-timer-container" class="text-right border-l border-slate-800 pl-4 hidden">
                                <span id="admin-phase-label" class="text-[10px] font-bold text-amber-400 uppercase block">Fase Menjawab</span>
                                <span id="admin-phase-timer" class="font-mono text-xl font-black text-amber-300">0s</span>
                            </div>
                        </div>
                    </div>

                    @if($activeQuestion)
                        <div class="space-y-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-xs font-mono text-slate-400">ID Soal: #{{ $activeQuestion->masterQuestion->id }}</span>
                                    <h2 class="text-xl font-extrabold text-white mt-1">{{ $activeQuestion->masterQuestion->question_text }}</h2>
                                </div>
                                <span class="px-3 py-1 bg-amber-500/10 border border-amber-500/30 text-amber-400 font-black text-sm rounded-xl">
                                    {{ $activeQuestion->masterQuestion->price }} Pts
                                </span>
                            </div>

                            <div class="p-3 bg-slate-950 border border-slate-800 rounded-xl text-xs space-y-1">
                                <span class="text-slate-400">Kunci Jawaban:</span>
                                <p class="font-bold text-emerald-400">{{ $activeQuestion->masterQuestion->answer_key }}</p>
                            </div>

                            <!-- TOMBOL ACTION FASE SOAL -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <!-- Tombol Beli / Pembeli Utama -->
                                @if(!$activeQuestion->is_bought)
                                    <div class="col-span-2 space-y-2">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase">Pilih Tim Yang Membeli Poin:</label>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            @foreach($room->teams as $team)
                                                <form action="{{ route('admin.rooms.process-action', $room->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="action_type" value="BUY">
                                                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                                                    <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                                    <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                                    <button class="w-full py-2 bg-pink-600/20 hover:bg-pink-600/30 border border-pink-500/30 text-pink-300 font-bold text-xs rounded-xl transition-all">
                                                        🛒 {{ $team->name }} Beli
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <!-- Jika Sudah Dibeli -> Eksekusi Benar / Salah Pembeli -->
                                    @if($activeQuestion->timer_phase === 'menjawab')
                                        <form action="{{ route('admin.rooms.process-action', $room->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="action_type" value="BUY_CORRECT">
                                            <input type="hidden" name="team_id" value="{{ $activeQuestion->buyer_team_id }}">
                                            <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                            <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                            <button class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl shadow transition-all">
                                                ✅ Jawaban Pembeli BENAR
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.rooms.process-action', $room->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="action_type" value="BUY_WRONG">
                                            <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                            <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                            <button class="w-full py-3 bg-rose-600 hover:bg-rose-500 text-white font-black text-xs rounded-xl shadow transition-all">
                                                ❌ Jawaban Pembeli SALAH (Ke Mode Operan)
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                <!-- Mode Operan Rebutan -->
                                @if($activeQuestion->timer_phase === 'operan')
                                    <div class="col-span-2 space-y-2">
                                        <label class="text-[10px] font-bold text-rose-400 uppercase">Pilih Tim Yang Perebut Jawaban Benar:</label>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            @foreach($room->teams as $team)
                                                @if($team->id !==$activeQuestion->buyer_team_id)
                                                    <form action="{{ route('admin.rooms.process-action', $room->id) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="action_type" value="PASS_CORRECT">
                                                        <input type="hidden" name="team_id" value="{{ $team->id }}">
                                                        <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                                        <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                                        <button class="w-full py-2 bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/30 text-emerald-300 font-bold text-xs rounded-xl transition-all">
                                                            ⚡ {{ $team->name }} Benar
                                                        </button>
                                                    </form>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- TOMBOL KONTROL TIMER EMERGENCY (PAUSE, RESUME, RESET, CLOSE) -->
                            <div class="pt-4 border-t border-slate-800 grid grid-cols-3 gap-2">
                                @if(!$activeQuestion->is_paused)
                                    <form action="{{ route('admin.rooms.pause-timer', $room->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                        <button class="w-full py-2 bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/30 text-amber-300 font-bold text-xs rounded-xl transition-all">
                                            ⏸️ Pause Timer
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.rooms.resume-timer', $room->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                        <button class="w-full py-2 bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/30 text-emerald-300 font-bold text-xs rounded-xl transition-all">
                                            ▶️ Resume Timer
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('admin.rooms.reset-timer', $room->id) }}" method="POST" onsubmit="return confirm('Reset timer soal ini kembali ke awal (180s)?')">
                                    @csrf
                                    <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                    <button class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl transition-all">
                                        🔄 Reset (180s)
                                    </button>
                                </form>

                                <form action="{{ route('admin.rooms.process-action', $room->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action_type" value="CLOSE">
                                    <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                    <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                    <button class="w-full py-2 bg-rose-950/40 hover:bg-rose-900/60 border border-rose-500/30 text-rose-300 font-bold text-xs rounded-xl transition-all">
                                        🚫 Tutup Soal
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="p-8 text-center text-slate-500 bg-slate-950/50 rounded-xl border border-slate-800">
                            Belum ada soal aktif di panggung. Silakan pilih soal dari daftar di bawah!
                        </div>
                    @endif
                </div>

                <!-- Daftar Soal Tersedia -->
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                    <h2 class="text-sm font-bold text-pink-400 uppercase tracking-wider">Pilih Soal Untuk Ditamplikan</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-80 overflow-y-auto pr-1">
                        @foreach($room->roomQuestions as $rq)
                            <div class="p-3.5 bg-slate-950 border border-slate-800 rounded-xl flex justify-between items-center">
                                <div>
                                    <span class="text-xs font-mono text-slate-500">Soal #{{ $rq->masterQuestion->id }}</span>
                                    <p class="text-xs font-medium text-slate-200 line-clamp-1">{{ $rq->masterQuestion->question_text }}</p>
                                    <span class="text-[10px] text-amber-400 font-bold">{{ $rq->masterQuestion->price }} Pts</span>
                                </div>

                                @if($rq->status === 'unused')
                                    <form action="{{ route('admin.rooms.select-question', $room->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="room_question_id" value="{{ $rq->id }}">
                                        <button class="px-3 py-1.5 bg-pink-600 hover:bg-pink-500 text-white font-bold text-xs rounded-lg shadow transition-all">
                                            Tampilkan 🚀
                                        </button>
                                    </form>
                                @else
                                    <span class="px-2.5 py-1 bg-slate-800 text-slate-500 font-bold text-[10px] rounded-lg uppercase">
                                        {{ $rq->status }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Column 3: Papan Skor Tim -->
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4 h-fit">
                <h2 class="text-sm font-bold text-pink-400 uppercase tracking-wider border-b border-slate-800 pb-3">Papan Skor Peserta</h2>
                <div class="space-y-3">
                    @foreach($room->teams as $team)
                        <div class="p-4 bg-slate-950 border border-slate-800 rounded-xl flex justify-between items-center">
                            <div>
                                <span class="font-bold text-slate-200 text-sm block">{{ $team->name }}</span>
                                <span class="text-xs font-black text-pink-400">{{ $team->current_score }} Pts</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    <!-- SCRIPT HYBRID POLLING + INTERPOLATION COUNTDOWN -->
    <script>
        const roomId = "{{ $room->id }}";
        const apiEndpoint = `/api/rooms/${roomId}/state`;
        
        let localGlobalRem = 0;
        let localPhaseRem = 0;
        let isPaused = false;
        let currentPhase = "{{ $activeQuestion ? $activeQuestion->timer_phase : 'none' }}";

        function formatSeconds(value) {
            const seconds = Number.parseInt(String(value).replace(/s/gi, ''), 10);
            return `${Number.isFinite(seconds) ? Math.max(0, seconds) : 0}s`;
        }

        // Local Interpolation Countdown (Run per 1000ms)
        setInterval(() => {
            if (!isPaused) {
                if (localGlobalRem > 0) {
                    localGlobalRem--;
                    document.getElementById('admin-global-timer').innerText = formatSeconds(localGlobalRem);
                }
                if (localPhaseRem > 0) {
                    localPhaseRem--;
                    document.getElementById('admin-phase-timer').innerText = formatSeconds(localPhaseRem);
                }
            }
        }, 1000);

        // Fetch State Polling (Run per 1000ms untuk Re-sync)
        async function syncAdminPanel() {
            try {
                const response = await fetch(apiEndpoint);
                if (!response.ok) return;
                const data = await response.json();

                const activeQ = data.active_question;
                
                if (activeQ) {
                    isPaused = activeQ.is_paused;
                    localGlobalRem = activeQ.global_remaining_seconds;
                    localPhaseRem = activeQ.phase_remaining_seconds;

                    // UI Update Timer
                    document.getElementById('admin-global-timer').innerText = formatSeconds(localGlobalRem);
                    
                    const phaseContainer = document.getElementById('admin-phase-timer-container');
                    const phaseLabel = document.getElementById('admin-phase-label');
                    const phaseTimer = document.getElementById('admin-phase-timer');

                    if (activeQ.timer_phase === 'menjawab' || activeQ.timer_phase === 'operan') {
                        phaseContainer.classList.remove('hidden');
                        phaseLabel.innerText = activeQ.timer_phase === 'menjawab' ? 'Waktu Menjawab' : 'Mode Operan';
                        phaseTimer.innerText = formatSeconds(localPhaseRem);
                    } else {
                        phaseContainer.classList.add('hidden');
                    }

                    // Badge Paused
                    const pausedBadge = document.getElementById('paused-badge');
                    if (isPaused) {
                        pausedBadge.classList.remove('hidden');
                    } else {
                        pausedBadge.classList.add('hidden');
                    }

                    // Reload jika terjadi pergantian fase dari server
                    if (activeQ.timer_phase !== currentPhase) {
                        currentPhase = activeQ.timer_phase;
                        window.location.reload();
                    }
                } else if (currentPhase !== 'none') {
                    currentPhase = 'none';
                    window.location.reload();
                }

            } catch (error) {
                console.error("Error syncing admin panel:", error);
            }
        }

        function submitAdminAction(event, form) {
            event.preventDefault();

            const submitButton = form.querySelector('button[type="submit"]') || form.querySelector('button');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.dataset.originalText = submitButton.innerText;
                submitButton.innerText = 'Memproses...';
                submitButton.classList.add('opacity-60', 'cursor-wait');
            }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' },
            })
                .then(response => {
                    if (!response.ok) throw new Error('Request gagal diproses.');
                    return response.json();
                })
                .then(() => window.location.reload())
                .catch(error => {
                    console.error('Error executing admin action:', error);
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerText = submitButton.dataset.originalText;
                        submitButton.classList.remove('opacity-60', 'cursor-wait');
                    }
                });
        }

        document.querySelectorAll('form[action*="/action"], form[action*="/pause-timer"], form[action*="/resume-timer"], form[action*="/reset-timer"]')
            .forEach(form => form.addEventListener('submit', event => submitAdminAction(event, form)));

        setInterval(syncAdminPanel, 1000);
        syncAdminPanel();
    </script>
</body>
</html>