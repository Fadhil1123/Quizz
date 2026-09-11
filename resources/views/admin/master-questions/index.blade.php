<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Bank Soal - Buy & Answer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <a href="{{ route('admin.rooms.index') }}" class="text-pink-400 text-sm hover:underline">← Kembali ke Dashboard Room</a>
                <h1 class="text-3xl font-extrabold text-pink-500 mt-1">Master Bank Soal</h1>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-400 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- Form Tambah Soal -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <h2 class="text-lg font-bold mb-4 text-pink-400">+ Tambah Soal Baru</h2>
            <form action="{{ route('admin.master-questions.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Pertanyaan</label>
                    <textarea name="question_text" required rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm focus:border-pink-500 focus:outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Harga (Poin)</label>
                        <input type="number" name="price" step="50" value="100" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm focus:border-pink-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Kunci Jawaban (Privat Admin)</label>
                        <input type="text" name="answer_key" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm focus:border-pink-500 focus:outline-none">
                    </div>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-lg shadow-pink-600/20">Simpan Soal</button>
            </form>
        </div>

        <!-- Tabel Soal -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/50 text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">Pertanyaan</th>
                        <th class="p-4">Harga</th>
                        <th class="p-4">Kunci Jawaban</th>
                        <th class="p-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($questions as $q)
                        <tr class="hover:bg-slate-800/30">
                            <td class="p-4 text-slate-500">#{{ $q->id }}</td>
                            <td class="p-4 font-medium">{{ $q->question_text }}</td>
                            <td class="p-4"><span class="px-2.5 py-1 bg-pink-500/10 border border-pink-500/30 text-pink-400 rounded-full font-bold text-xs">{{ $q->price }} Pts</span></td>
                            <td class="p-4 text-emerald-400 font-mono">{{ $q->answer_key }}</td>
                            <td class="p-4">
                                <form action="{{ route('admin.master-questions.destroy', $q->id) }}" method="POST" onsubmit="return confirm('Hapus soal ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-400 hover:underline text-xs">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">Belum ada soal di Master Bank.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>