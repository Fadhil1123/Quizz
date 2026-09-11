<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stage Display - {{ $room->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between p-8 select-none">
    
    <!-- Topbar Header -->
    <div class="flex justify-between items-center border-b border-slate-800/80 pb-6">
        <div>
            <span class="text-xs font-bold text-pink-500 tracking-widest uppercase">STAGE DISPLAY</span>
            <h1 class="text-3xl font-black tracking-tight text-white mt-1">{{ $room->name }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <span id="sync-status" class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-mono text-slate-400 uppercase tracking-wider">LIVE REFRESH (1s)</span>
        </div>
    </div>

    <!-- Main Section: Big Question Display -->
    <div class="my-auto py-8">
        <div id="question-card" class="bg-slate-900/80 border-2 border-pink-500/30 p-10 rounded-3xl shadow-2xl backdrop-blur-xl transition-all duration-300">
            <div id="question-content" class="space-y-6">
                <div class="flex justify-between items-center">
                    <span class="px-4 py-1.5 bg-pink-500/20 text-pink-400 border border-pink-500/30 text-sm font-black rounded-full uppercase">Soal Aktif</span>
                    <span id="question-price" class="text-3xl font-black text-amber-400">--- Pts</span>
                </div>
                <p id="question-text" class="text-3xl md:text-5xl font-bold leading-tight text-slate-100">
                    Menunggu soal ditampilkan oleh Admin...
                </p>
            </div>
        </div>
    </div>

    <!-- Bottombar: Live Leaderboard Stage -->
    <div class="border-t border-slate-800/80 pt-6">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Papan Skor Peserta</h2>
        <div id="leaderboard-container" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- Tim & Poin di-render via JavaScript -->
        </div>
    </div>

    <!-- Script Short Polling Fetch API -->
    <script>
        const roomId = "{{ $room->id }}";
        const apiEndpoint = `/api/rooms/${roomId}/state`;

        async function fetchRoomState() {
            try {
                const response = await fetch(apiEndpoint);
                const data = await response.json();

                if (data.status === 'success') {
                    renderQuestion(data.active_question);
                    renderLeaderboard(data.leaderboard);
                }
            } catch (error) {
                console.error("Gagal sinkronisasi data stage:", error);
            }
        }

        function renderQuestion(activeQuestion) {
        const textEl = document.getElementById('question-text');
        const priceEl = document.getElementById('question-price');
        const cardEl = document.getElementById('question-card');

        if (activeQuestion) {
            priceEl.innerText = `${activeQuestion.price} Pts`;

            // Pengecekan Fleksibel: mendukung boolean true maupun integer/string 1
            const isBought = activeQuestion.is_bought === true || activeQuestion.is_bought === 1 || activeQuestion.is_bought === "1";

            if (isBought) {
                // TERBUKA (UNBLUR) SAAT SUDAH DIBELI
                textEl.innerHTML = `
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-amber-400 uppercase tracking-widest block">DIBELI OLEH: ${activeQuestion.buyer_team}</span>
                        <div class="text-3xl md:text-5xl font-extrabold text-white leading-tight">${activeQuestion.question_text}</div>
                    </div>
                `;
                cardEl.className = "bg-pink-950/40 border-2 border-pink-500 p-10 rounded-3xl shadow-2xl backdrop-blur-xl transition-all duration-500 scale-[1.02]";
            } else {
                // TERTUTUP SAAT BELUM DIBELI
                textEl.innerHTML = `
                    <div class="py-12 text-center space-y-4">
                        <div class="inline-block px-6 py-2 bg-pink-500/10 border border-pink-500/30 text-pink-400 font-black rounded-full text-lg animate-pulse">
                            SOAL TERSEDIA UNTUK DIBELI
                        </div>
                        <p class="text-slate-400 text-sm">Silakan tim mengajukan pembelian poin untuk membuka teks soal!</p>
                    </div>
                `;
                cardEl.className = "bg-slate-900/80 border-2 border-slate-800 p-10 rounded-3xl shadow-2xl backdrop-blur-xl transition-all duration-500";
            }
        } else {
            textEl.innerText = "Menunggu soal ditampilkan oleh Admin...";
            priceEl.innerText = "--- Pts";
            cardEl.className = "bg-slate-900/80 border-2 border-slate-800 p-10 rounded-3xl shadow-2xl backdrop-blur-xl transition-all duration-500";
        }
    }

        function renderLeaderboard(teams) {
            const container = document.getElementById('leaderboard-container');
            container.innerHTML = '';

            teams.forEach((team, index) => {
                const isTop1 = index === 0;
                const card = document.createElement('div');
                card.className = `p-4 rounded-2xl border transition-all ${
                    isTop1 
                    ? 'bg-pink-950/30 border-pink-500/50 shadow-lg shadow-pink-500/10' 
                    : 'bg-slate-900/60 border-slate-800'
                }`;

                card.innerHTML = `
                    <div class="text-xs font-bold ${isTop1 ? 'text-pink-400' : 'text-slate-400'}">RANK #${index + 1}</div>
                    <div class="text-lg font-extrabold truncate text-slate-100">${team.name}</div>
                    <div class="text-2xl font-black ${isTop1 ? 'text-pink-400' : 'text-slate-300'}">${team.current_score} <span class="text-xs font-normal">Pts</span></div>
                `;
                container.appendChild(card);
            });
        }

        // Jalankan Polling setiap 1000ms (1 Detik)
        setInterval(fetchRoomState, 1000);
        fetchRoomState(); // Panggilan awal
    </script>
</body>
</html>