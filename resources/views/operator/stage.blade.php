<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stage Display - {{ $room->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between p-8 select-none">
    
    <!-- Header -->
    <div class="flex justify-between items-center border-b border-slate-800/80 pb-6">
        <div>
            <span class="text-xs font-bold text-pink-500 tracking-widest uppercase">STAGE DISPLAY</span>
            <h1 class="text-3xl font-black tracking-tight text-white mt-1">{{ $room->name }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-mono text-slate-400 uppercase tracking-wider">LIVE REFRESH</span>
        </div>
    </div>

    <!-- Main Section -->
    <div class="my-auto py-8">
        <div id="question-card" class="bg-slate-900/80 border-2 border-slate-800 p-10 rounded-3xl shadow-2xl backdrop-blur-xl transition-all duration-500">
            <div id="question-content" class="space-y-6">
                <div class="flex justify-between items-center">
                    <span id="phase-badge" class="px-4 py-1.5 bg-pink-500/20 text-pink-400 border border-pink-500/30 text-sm font-black rounded-full uppercase">Soal Aktif</span>
                    <div class="flex items-center gap-4">
                        <div id="timer-badge" class="hidden px-5 py-2 bg-amber-500/20 border border-amber-500/40 text-amber-300 font-mono font-black text-2xl rounded-full animate-pulse">
                            ⏱️ <span id="timer-seconds">0</span>s
                        </div>
                        <span id="question-price" class="text-3xl font-black text-amber-400">--- Pts</span>
                    </div>
                </div>
                <div id="question-text">
                    <p class="text-3xl md:text-5xl font-bold leading-tight text-slate-100">Menunggu soal ditampilkan oleh Admin...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottombar: Leaderboard -->
    <div class="border-t border-slate-800/80 pt-6">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Papan Skor Peserta</h2>
        <div id="leaderboard-container" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
    </div>

    <script>
        const roomId = "{{ $room->id }}";
        const apiEndpoint = `/api/rooms/${roomId}/state`;

        async function fetchRoomState() {
            try {
                const response = await fetch(apiEndpoint);
                if (!response.ok) return;
                
                const data = await response.json();

                if (data.status === 'success') {
                    // UTAMA: Cek jika status room FINISHED -> Langsung tampilkan Pemenang!
                    if (data.room && data.room.status === 'finished') {
                        renderWinnerOverlay(data.leaderboard || []);
                        return;
                    }

                    renderQuestion(data.active_question);
                    renderLeaderboard(data.leaderboard || []);
                }
            } catch (error) {
                console.error("Error fetching state:", error);
            }
        }

        function renderQuestion(activeQuestion) {
            const textEl = document.getElementById('question-text');
            const priceEl = document.getElementById('question-price');
            const cardEl = document.getElementById('question-card');
            const timerBadge = document.getElementById('timer-badge');
            const timerSeconds = document.getElementById('timer-seconds');
            const phaseBadge = document.getElementById('phase-badge');

            if (activeQuestion) {
                priceEl.innerText = `${activeQuestion.price} Pts`;
                
                if (timerBadge && timerSeconds) {
                    timerBadge.classList.remove('hidden');
                    timerSeconds.innerText = activeQuestion.remaining_seconds ?? 0;
                }

                if (activeQuestion.timer_phase === 'papar') {
                    phaseBadge.innerText = "FASE 1: PAPAR SOAL";
                    phaseBadge.className = "px-4 py-1.5 bg-blue-500/20 text-blue-400 border border-blue-500/30 text-sm font-black rounded-full uppercase";
                    textEl.innerHTML = `
                        <div class="py-12 text-center space-y-4">
                            <div class="inline-block px-6 py-2 bg-pink-500/10 border border-pink-500/30 text-pink-400 font-black rounded-full text-lg animate-pulse">
                                SOAL TERSEDIA UNTUK DIBELI
                            </div>
                            <p class="text-slate-400 text-sm">Silakan tim mengajukan pembelian poin untuk membuka teks soal!</p>
                        </div>
                    `;
                    cardEl.className = "bg-slate-900/80 border-2 border-slate-800 p-10 rounded-3xl shadow-2xl backdrop-blur-xl transition-all duration-500";
                } else if (activeQuestion.timer_phase === 'menjawab') {
                    phaseBadge.innerText = "FASE 2: WAKTU MENJAWAB";
                    phaseBadge.className = "px-4 py-1.5 bg-amber-500/20 text-amber-400 border border-amber-500/30 text-sm font-black rounded-full uppercase";
                    textEl.innerHTML = `
                        <div class="space-y-3">
                            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest block">DIBELI OLEH: ${activeQuestion.buyer_team || 'Tim Peserta'}</span>
                            <div class="text-3xl md:text-5xl font-extrabold text-white leading-tight">${activeQuestion.question_text}</div>
                        </div>
                    `;
                    cardEl.className = "bg-pink-950/40 border-2 border-pink-500 p-10 rounded-3xl shadow-2xl backdrop-blur-xl transition-all duration-500 scale-[1.02]";
                } else if (activeQuestion.timer_phase === 'operan') {
                    phaseBadge.innerText = "FASE 3: MODE OPERAN REBUTAN ⚡";
                    phaseBadge.className = "px-4 py-1.5 bg-rose-500/20 text-rose-400 border border-rose-500/30 text-sm font-black rounded-full uppercase animate-bounce";
                    textEl.innerHTML = `
                        <div class="space-y-3">
                            <span class="text-xs font-bold text-rose-400 uppercase tracking-widest block">SIAPA CEPAT DIA DAPAT!</span>
                            <div class="text-3xl md:text-5xl font-extrabold text-white leading-tight">${activeQuestion.question_text}</div>
                        </div>
                    `;
                    cardEl.className = "bg-rose-950/40 border-2 border-rose-500 p-10 rounded-3xl shadow-2xl backdrop-blur-xl transition-all duration-500";
                }
            } else {
                if (timerBadge) timerBadge.classList.add('hidden');
                phaseBadge.innerText = "SOAL AKTIF";
                phaseBadge.className = "px-4 py-1.5 bg-pink-500/20 text-pink-400 border border-pink-500/30 text-sm font-black rounded-full uppercase";
                textEl.innerHTML = `<p class="text-3xl md:text-5xl font-bold leading-tight text-slate-100">Menunggu soal ditampilkan oleh Admin...</p>`;
                priceEl.innerText = "--- Pts";
                cardEl.className = "bg-slate-900/80 border-2 border-slate-800 p-10 rounded-3xl shadow-2xl backdrop-blur-xl transition-all duration-500";
            }
        }

        function renderLeaderboard(teams) {
            const container = document.getElementById('leaderboard-container');
            if (!container) return;
            container.innerHTML = '';

            teams.forEach((team, index) => {
                const isTop1 = index === 0;
                const card = document.createElement('div');
                card.className = `p-4 rounded-2xl border transition-all ${
                    isTop1 ? 'bg-pink-950/30 border-pink-500/50 shadow-lg shadow-pink-500/10' : 'bg-slate-900/60 border-slate-800'
                }`;

                card.innerHTML = `
                    <div class="text-xs font-bold ${isTop1 ? 'text-pink-400' : 'text-slate-400'}">RANK #${index + 1}</div>
                    <div class="text-lg font-extrabold truncate text-slate-100">${team.name}</div>
                    <div class="text-2xl font-black ${isTop1 ? 'text-pink-400' : 'text-slate-300'}">${team.current_score} <span class="text-xs font-normal">Pts</span></div>
                `;
                container.appendChild(card);
            });
        }

        function renderWinnerOverlay(teams) {
            const winner = teams.length > 0 ? teams[0] : null;

            document.body.innerHTML = `
                <div class="min-h-screen bg-slate-950 text-white flex flex-col items-center justify-center p-8 text-center select-none">
                    <div class="text-7xl mb-4 animate-bounce">🏆</div>
                    <span class="px-4 py-1.5 bg-amber-500/20 text-amber-400 border border-amber-500/40 text-xs font-black rounded-full uppercase tracking-widest mb-3">
                        JUARA 1 TURNAMEN KUIS
                    </span>
                    
                    <h1 class="text-6xl md:text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-pink-400 to-amber-500 mb-2 tracking-tight">
                        ${winner ? winner.name : '---'}
                    </h1>
                    
                    <p class="text-2xl font-bold text-slate-300 mb-8">
                        Skor Akhir: <span class="text-pink-400 font-extrabold">${winner ? winner.current_score : 0} Pts</span>
                    </p>

                    <div class="w-full max-w-lg bg-slate-900/90 border border-slate-800 p-6 rounded-3xl shadow-2xl backdrop-blur-xl space-y-3">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-left mb-4 border-b border-slate-800 pb-2">
                            📊 Klasemen Akhir Peserta
                        </h3>
                        
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                            ${teams.map((t, index) => `
                                <div class="flex justify-between items-center p-3.5 bg-slate-950/80 rounded-2xl border ${index === 0 ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-800/80'}">
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-full ${index === 0 ? 'bg-amber-500 text-slate-950' : 'bg-slate-800 text-slate-400'} flex items-center justify-center text-xs font-black">
                                            #${index + 1}
                                        </span>
                                        <span class="font-bold text-sm text-slate-100">${t.name}</span>
                                    </div>
                                    <span class="font-black ${index === 0 ? 'text-amber-400' : 'text-pink-400'} text-base">
                                        ${t.current_score} Pts
                                    </span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
        }

        setInterval(fetchRoomState, 300);
        fetchRoomState();
    </script>
</body>
</html>