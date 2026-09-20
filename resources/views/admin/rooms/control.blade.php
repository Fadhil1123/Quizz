<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Live Control Center - {{ $room->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center bg-slate-900 border border-slate-800 p-4 rounded-2xl">
            <div>
                <a href="{{ route('admin.rooms.index') }}" class="text-pink-400 text-xs hover:underline">← Dashboard Room</a>
                <h1 class="text-2xl font-extrabold text-pink-500">{{ $room->name }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <form action="/admin/rooms/{{ $room->id }}/finish" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengakhiri kuis ini?')">
                    @csrf
                    <button class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-full uppercase transition-all">
                        Selesaikan Kuis 🏆
                    </button>
                </form>
                <span class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-bold text-xs rounded-full uppercase">Live Control</span>
            </div>
        </div>

        @if(session('success'))
            <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-400 text-xs">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-400 text-xs">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Kolom Kiri & Tengah: Soal Aktif & Eksekusi Jawaban -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- BUNGKUSAN ID UTAMA UNTUK DYNAMIC REALTIME UPDATE -->
                <div id="quiz-control-panel" class="bg-slate-900 border border-pink-500/30 p-6 rounded-2xl space-y-4 shadow-xl shadow-pink-500/5">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-pink-400">Soal Aktif di Panggung</h2>
                        @if($activeQuestion)
                            <div class="px-3 py-1 bg-amber-500/20 border border-amber-500/40 text-amber-300 font-mono font-black text-sm rounded-full animate-pulse">
                                ⏱️ Sisa Waktu: <span id="admin-timer-countdown">--</span>s
                            </div>
                        @endif
                    </div>
                    
                    @if($activeQuestion)
                        <div class="space-y-3">
                            <div class="flex justify-between items-start">
                                <p class="text-lg font-medium text-slate-100">{{ $activeQuestion->masterQuestion->question_text }}</p>
                                <span class="px-3 py-1 bg-pink-500/20 text-pink-400 font-extrabold text-sm rounded-full">{{ $activeQuestion->masterQuestion->price }} Pts</span>
                            </div>
                            
                            <!-- KUNCI JAWABAN (HANYA MUNCUL DI ADMIN) -->
                            <div class="p-3 bg-slate-950 border border-emerald-500/30 rounded-xl">
                                <span class="text-xs text-emerald-400 font-bold block">KUNCI JAWABAN (PRIVAT ADMIN):</span>
                                <span class="text-emerald-300 font-mono text-base font-bold">{{ $activeQuestion->masterQuestion->answer_key }}</span>
                            </div>
                        </div>

                        <!-- Panel Eksekusi Tim -->
                        <div class="pt-4 border-t border-slate-800 space-y-3">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xs font-bold text-slate-400 uppercase">
                                    Fase Saat Ini: <span class="text-pink-400 font-extrabold uppercase">{{ $activeQuestion->timer_phase }}</span>
                                </h3>
                                
                                <!-- JIKA FASE MENJAWAB: Tombol Pembeli Jawab SALAH -->
                                @if($activeQuestion->timer_phase === 'menjawab')
                                    <form action="/admin/rooms/{{ $room->id }}/action" method="POST">
                                        @csrf
                                        <input type="hidden" name="action_type" value="BUY_WRONG">
                                        <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                        <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                        <button class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-lg shadow transition-all">
                                            ❌ Pembeli Jawab SALAH (Alihkan ke Operan)
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($room->teams as $team)
                                    <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 flex justify-between items-center">
                                        <div>
                                            <div class="font-bold text-sm">{{ $team->name }}</div>
                                            <div class="text-xs text-pink-400 font-bold">{{ $team->current_score }} Pts</div>
                                        </div>
                                        <div class="flex gap-1">
                                            @if($activeQuestion->timer_phase === 'papar')
                                                <!-- Tombol Beli (-1x) -->
                                                <form action="/admin/rooms/{{ $room->id }}/action" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="action_type" value="BUY">
                                                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                                                    <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                                    <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                                    <button class="px-2.5 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-lg text-xs font-bold hover:bg-amber-500/30">Beli (-1x)</button>
                                                </form>
                                            @elseif($activeQuestion->timer_phase === 'menjawab' && $activeQuestion->buyer_team_id == $team->id)
                                                <!-- Tombol Benar Pembeli (+2x) -->
                                                <form action="/admin/rooms/{{ $room->id }}/action" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="action_type" value="BUY_CORRECT">
                                                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                                                    <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                                    <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                                    <button class="px-2.5 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-lg text-xs font-bold hover:bg-emerald-500/30">Benar (+2x)</button>
                                                </form>
                                            @elseif($activeQuestion->timer_phase === 'operan' && $activeQuestion->buyer_team_id != $team->id)
                                                <!-- Tombol Operan BENAR (+1x) -->
                                                <form action="/admin/rooms/{{ $room->id }}/action" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="action_type" value="PASS_CORRECT">
                                                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                                                    <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                                    <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                                    <button class="px-2.5 py-1 bg-blue-500/20 text-blue-300 border border-blue-500/30 rounded-lg text-xs font-bold hover:bg-blue-500/30">Operan BENAR (+1x)</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Tombol Emergency Reset Timer (Hanya muncul saat ada soal aktif) -->
                            @if($activeQuestion)
                                <form action="{{ route('admin.rooms.reset-timer', $room->id) }}" method="POST" class="pt-2" onsubmit="return confirm('Apakah Anda yakin ingin mereset timer soal ini kembali ke awal (180s)?')">
                                    @csrf
                                    <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                    <button class="w-full py-2 bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30 rounded-xl text-xs font-bold transition-all">
                                        🔄 Reset Timer Soal Ke Awal (180s)
                                    </button>
                                </form>
                            @endif

                            <!-- Tombol Tutup / Hangus -->
                            <form action="/admin/rooms/{{ $room->id }}/action" method="POST" class="pt-2">
                                @csrf
                                <input type="hidden" name="action_type" value="CLOSE">
                                <input type="hidden" name="question_id" value="{{ $activeQuestion->masterQuestion->id }}">
                                <input type="hidden" name="room_question_id" value="{{ $activeQuestion->id }}">
                                <button class="w-full py-2 bg-slate-800 text-slate-400 hover:text-white rounded-xl text-xs font-bold transition-all">Tutup / Hanguskan Soal Ini</button>
                            </form>
                        </div>
                    @else
                        <div class="p-8 text-center text-slate-500 bg-slate-950/50 rounded-xl border border-slate-800">
                            Belum ada soal aktif di panggung. Silakan pilih soal dari daftar di bawah!
                        </div>
                    @endif
                </div>

                <!-- Repositori Soal Room -->
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                    <h2 class="text-sm font-bold text-slate-300">Daftar Soal Ruangan Ini</h2>
                    <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                        @foreach($room->roomQuestions as $rq)
                            <div class="flex justify-between items-center p-3 bg-slate-950 border border-slate-800 rounded-xl">
                                <div>
                                    <span class="text-xs px-2 py-0.5 rounded font-bold uppercase 
                                        {{ $rq->status === 'active' ? 'bg-pink-500/20 text-pink-400' : ($rq->status === 'closed' ? 'bg-slate-800 text-slate-500' : 'bg-emerald-500/10 text-emerald-400') }}">
                                        {{ $rq->status }}
                                    </span>
                                    <p class="text-sm font-medium mt-1">{{ $rq->masterQuestion->question_text }}</p>
                                </div>
                                @if($rq->status === 'unused')
                                    <form action="/admin/rooms/{{ $room->id }}/select-question" method="POST">
                                        @csrf
                                        <input type="hidden" name="room_question_id" value="{{ $rq->id }}">
                                        <button class="px-3 py-1.5 bg-pink-600 hover:bg-pink-500 text-white font-bold text-xs rounded-lg transition-all">Tampilkan Soal</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Live Leaderboard -->
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4 h-fit">
                <h2 class="text-sm font-bold text-pink-400 uppercase tracking-wider">Live Leaderboard</h2>
                <div class="space-y-3">
                    @foreach($room->teams->sortByDesc('current_score') as $index => $team)
                        <div class="flex justify-between items-center p-3 bg-slate-950 border border-slate-800/80 rounded-xl">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-400">{{ $index + 1 }}</span>
                                <span class="font-bold text-sm">{{ $team->name }}</span>
                            </div>
                            <span class="font-black text-pink-400">{{ $team->current_score }} Pts</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Script Real-time Sync Admin Panel -->
    <script>
        const roomId = "{{ $room->id }}";
        const apiEndpoint = `/api/rooms/${roomId}/state`;
        let currentPhase = "{{ $activeQuestion ? $activeQuestion->timer_phase : 'none' }}";

        async function syncAdminPanel() {
            try {
                const response = await fetch(apiEndpoint);
                if (!response.ok) return;
                const data = await response.json();

                const activeQ = data.active_question;
                const newPhase = activeQ ? activeQ.timer_phase : 'none';

                // 1. UPDATE TIMER DETIK SECARA REALTIME TANPA RELOAD
                const adminTimerEl = document.getElementById('admin-timer-countdown');
                if (adminTimerEl && activeQ) {
                    adminTimerEl.innerText = activeQ.remaining_seconds;
                }

                // 2. JIKA FASE BERUBAH (papar -> menjawab -> operan -> none/closed), RELOAD HALAMAN SANGAT CEPAT UNTUK UPDATE TOMBOL
                if (newPhase !== currentPhase) {
                    currentPhase = newPhase;
                    window.location.reload();
                }

            } catch (error) {
                console.error("Error syncing admin panel:", error);
            }
        }

        // Polling 300ms untuk deteksi instan
        setInterval(syncAdminPanel, 300);
    </script>
</body>
</html>