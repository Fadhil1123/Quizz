<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Room Baru - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex justify-between items-center bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <div>
                <a href="{{ route('admin.rooms.index') }}" class="text-pink-400 text-xs hover:underline">← Kembali ke Dashboard Room</a>
                <h1 class="text-2xl font-black text-white mt-1">Buat Ruangan Pertandingan Baru</h1>
            </div>
        </div>

        @if($errors->any())
            <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-400 text-xs font-bold space-y-1">
                @foreach($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('admin.rooms.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Section 1: Informasi Room -->
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h2 class="text-sm font-bold text-pink-400 uppercase tracking-wider">1. Informasi Ruangan & Modal Awal</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-300 uppercase">Nama Ruangan / Sesi</label>
                        <input type="text" name="name" required placeholder="Contoh: Babak Penyisihan - Ruang A" 
                            value="{{ old('name') }}"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-pink-500">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-300 uppercase">Modal Awal Poin per Tim</label>
                        <input type="number" name="initial_score" value="{{ old('initial_score', 1000) }}" required 
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-pink-500">
                    </div>
                </div>
            </div>

            <!-- Section 2: Input Tim Peserta Dinamis -->
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-sm font-bold text-pink-400 uppercase tracking-wider">2. Tim Peserta</h2>
                        <p class="text-xs text-slate-500">Tambahkan tim yang akan bertanding di ruangan ini.</p>
                    </div>
                    <button type="button" id="btn-add-team" class="px-3 py-1.5 bg-pink-500/20 text-pink-400 border border-pink-500/30 font-bold text-xs rounded-xl hover:bg-pink-500/30 transition-all">
                        + Tambah Tim
                    </button>
                </div>

                <div id="teams-container" class="space-y-3">
                    <!-- Default Minimal 2 Input Tim -->
                    <div class="flex items-center gap-2 team-row">
                        <input type="text" name="teams[]" required placeholder="Nama Tim 1 (misal: Tim Alpha)" 
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-pink-500">
                    </div>
                    <div class="flex items-center gap-2 team-row">
                        <input type="text" name="teams[]" required placeholder="Nama Tim 2 (misal: Tim Beta)" 
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-pink-500">
                        <button type="button" onclick="removeTeamRow(this)" class="px-3 py-3 bg-rose-500/10 text-rose-400 border border-rose-500/20 font-bold text-xs rounded-xl hover:bg-rose-500/20 transition-all">Hapus</button>
                    </div>
                </div>
            </div>

            <!-- Section 3: Pilih Soal dari Master Bank Soal -->
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-sm font-bold text-pink-400 uppercase tracking-wider">3. Pilih Soal dari Bank Soal</h2>
                        <p class="text-xs text-slate-500">Centang soal-soal yang ingin dimasukkan ke ruangan ini.</p>
                    </div>
                    <button type="button" id="btn-select-all" class="text-xs text-pink-400 font-bold hover:underline">
                        Pilih Semua Soal
                    </button>
                </div>

                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    @forelse($masterQuestions ?? [] as $question)
                        <label class="flex items-start gap-3 p-3.5 bg-slate-950 border border-slate-800 rounded-xl hover:border-slate-700 cursor-pointer transition-all">
                            <input type="checkbox" name="questions[]" value="{{ $question->id }}" class="question-checkbox mt-1 w-4 h-4 rounded border-slate-800 text-pink-600 focus:ring-pink-500">
                            <div class="flex-1 justify-between flex items-center">
                                <div>
                                    <p class="text-sm font-medium text-slate-200">{{ $question->question_text }}</p>
                                    <span class="text-xs text-emerald-400 font-mono">Kunci: {{ $question->answer_key }}</span>
                                </div>
                                <span class="px-2.5 py-1 bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-xs rounded-lg">{{ $question->price }} Pts</span>
                            </div>
                        </label>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-500 bg-slate-950 rounded-xl border border-slate-800">
                            Belum ada soal di Master Bank Soal. Silakan buat soal di menu Master Bank Soal terlebih dahulu.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full py-3.5 bg-pink-600 hover:bg-pink-500 text-white font-black text-sm rounded-2xl shadow-xl shadow-pink-600/20 transition-all">
                Simpan & Buat Ruangan Pertandingan 🚀
            </button>
        </form>

    </div>

    <!-- Script Input Tim Dinamis & Select All Checkbox -->
    <script>
        let teamCount = 2;

        document.getElementById('btn-add-team').addEventListener('click', function() {
            teamCount++;
            const container = document.getElementById('teams-container');
            const newRow = document.createElement('div');
            newRow.className = 'flex items-center gap-2 team-row';
            newRow.innerHTML = `
                <input type="text" name="teams[]" required placeholder="Nama Tim ${teamCount}" 
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-pink-500">
                <button type="button" onclick="removeTeamRow(this)" class="px-3 py-3 bg-rose-500/10 text-rose-400 border border-rose-500/20 font-bold text-xs rounded-xl hover:bg-rose-500/20 transition-all">Hapus</button>
            `;
            container.appendChild(newRow);
        });

        function removeTeamRow(btn) {
            const rows = document.querySelectorAll('.team-row');
            if (rows.length > 2) {
                btn.closest('.team-row').remove();
            } else {
                alert('Minimal harus ada 2 tim dalam satu ruangan!');
            }
        }

        // Select All Questions
        let isAllSelected = false;
        document.getElementById('btn-select-all')?.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.question-checkbox');
            isAllSelected = !isAllSelected;
            checkboxes.forEach(cb => cb.checked = isAllSelected);
            this.innerText = isAllSelected ? 'Batal Pilih Semua' : 'Pilih Semua Soal';
        });
    </script>
</body>
</html>