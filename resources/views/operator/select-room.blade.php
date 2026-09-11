<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pilih Room Operator - Buy & Answer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-xl w-full bg-slate-900 border border-slate-800 p-8 rounded-2xl shadow-2xl space-y-6">
        <div class="text-center">
            <h1 class="text-3xl font-extrabold text-pink-500">Operator Portal</h1>
            <p class="text-sm text-slate-400 mt-1">Pilih ruangan kuis untuk ditayangkan di panggung</p>
        </div>

        <div class="space-y-3">
            @forelse($rooms as $room)
                <a href="/operator/stage/{{ $room->id }}" class="flex justify-between items-center p-4 bg-slate-950 border border-slate-800 rounded-xl hover:border-pink-500/50 transition-all">
                    <div>
                        <div class="font-bold text-lg text-slate-100">{{ $room->name }}</div>
                        <span class="text-xs text-pink-400 uppercase font-bold">{{ $room->status }}</span>
                    </div>
                    <span class="px-4 py-2 bg-pink-600 text-white font-bold text-xs rounded-lg">Buka Stage →</span>
                </a>
            @empty
                <p class="text-center text-slate-500 text-sm py-8">Tidak ada ruangan kuis yang sedang aktif.</p>
            @endforelse
        </div>
    </div>
</body>
</html>