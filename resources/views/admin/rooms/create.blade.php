<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Room Baru - Buy & Answer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <a href="{{ route('admin.rooms.index') }}" class="text-pink-400 text-sm hover:underline">← Kembali ke Dashboard Room</a>
            <h1 class="text-3xl font-extrabold text-pink-500 mt-1">Buat Room Baru</h1>
        </div>

        @if($errors->any())
            <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-400 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.rooms.store') }}" method="POST" class="space-y-6">
            @csrf
            <!-- 1. Info Ruangan -->
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h2 class="text-lg font-bold text-pink-400">1. Informasi Ruangan</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Nama Ruangan</label>
                        <input type="text" name="name" required placeholder="Contoh: Babak Penyisihan - Ruang 101" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm focus:border-pink-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Modal Awal Poin per Tim</label>
                        <input type="number" name="initial_score" value="1000" step="100" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm focus:border-pink-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- 2. Dinamis Input Tim -->
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-bold text-pink-400">2. Daftar Tim Peserta</h2>
                    <button type="button" id="add-team-btn" class="px-3 py-1.5 bg-pink-500/10 border border-pink-500/30 text-pink-400 font-bold text-xs rounded-lg hover:bg-pink-500/20">
                        + Tambah Tim Baru
                    </button>
                </div>

                <div id="team-container" class="space-y-3">
                    <div class="flex gap-2 items-center team-row">
                        <input type="text" name="teams[]" required placeholder="Nama Tim 1" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm focus:border-pink-500 focus:outline-none">
                        <button type="button" class="remove-team-btn hidden text-rose-400 hover:text-rose-300 px-3 py-2 text-sm">Hapus</button>
                    </div>
                    <div class="flex gap-2 items-center team-row">
                        <input type="text" name="teams[]" required placeholder="Nama Tim 2" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm focus:border-pink-500 focus:outline-none">
                        <button type="button" class="remove-team-btn hidden text-rose-400 hover:text-rose-300 px-3 py-2 text-sm">Hapus</button>
                    </div>
                </div>
            </div>

            <!-- 3. Assign Soal -->
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h2 class="text-lg font-bold text-pink-400">3. Pilih Soal dari Master Bank</h2>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                    @forelse($masterQuestions as $q)
                        <label class="flex items-center justify-between p-3 bg-slate-950 border border-slate-800 rounded-xl cursor-pointer hover:border-pink-500/50">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="questions[]" value="{{ $q->id }}" class="accent-pink-500 w-4 h-4">
                                <span class="text-sm">{{ $q->question_text }}</span>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-1 bg-pink-500/10 text-pink-400 rounded-full">{{ $q->price }} Pts</span>
                        </label>
                    @empty
                        <p class="text-xs text-slate-500">Belum ada soal di Master Bank. <a href="{{ route('admin.master-questions.index') }}" class="text-pink-400 underline">Tambah Soal Dulu</a></p>
                    @endforelse
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-pink-600 to-rose-600 font-bold text-white rounded-xl shadow-lg shadow-pink-600/20">Simpan & Buat Room Kuis</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('team-container');
            const addBtn = document.getElementById('add-team-btn');

            function updateRemoveButtons() {
                const rows = container.querySelectorAll('.team-row');
                rows.forEach((row) => {
                    const removeBtn = row.querySelector('.remove-team-btn');
                    if (rows.length > 2) {
                        removeBtn.classList.remove('hidden');
                    } else {
                        removeBtn.classList.add('hidden');
                    }
                });
            }

            addBtn.addEventListener('click', function() {
                const teamCount = container.querySelectorAll('.team-row').length + 1;
                const newRow = document.createElement('div');
                newRow.className = 'flex gap-2 items-center team-row';
                newRow.innerHTML = `
                    <input type="text" name="teams[]" required placeholder="Nama Tim ${teamCount}" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm focus:border-pink-500 focus:outline-none">
                    <button type="button" class="remove-team-btn text-rose-400 hover:text-rose-300 px-3 py-2 text-sm">Hapus</button>
                `;
                container.appendChild(newRow);
                updateRemoveButtons();
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-team-btn')) {
                    e.target.closest('.team-row').remove();
                    updateRemoveButtons();
                }
            });

            updateRemoveButtons();
        });
    </script>
</body>
</html>