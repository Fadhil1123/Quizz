<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Ruangan - Operator Stage</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-center items-center p-6 select-none">
    
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl space-y-6">
        <div class="text-center space-y-2">
            <span class="px-3 py-1 bg-pink-500/10 border border-pink-500/30 text-pink-400 font-bold text-xs rounded-full uppercase">
                Operator Portal
            </span>
            <h1 class="text-3xl font-black text-white">Pilih Ruangan Kuis</h1>
            <p class="text-xs text-slate-400">Pilih ruangan pertandingan yang ingin ditayangkan ke proyektor panggung.</p>
        </div>

        <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
            @forelse($rooms as $room)
                <a href="{{ route('operator.stage', $room->id) }}" 
                class="flex justify-between items-center p-4 bg-slate-950 border border-slate-800 hover:border-pink-500/50 rounded-2xl transition-all group">
                    <div>
                        <div class="font-bold text-slate-100 group-hover:text-pink-400 transition-colors">{{ $room->name }}</div>
                        <div class="text-xs text-slate-500 uppercase font-mono mt-0.5">Status: {{ $room->status }}</div>
                    </div>
                    <span class="px-3 py-1 bg-pink-600 group-hover:bg-pink-500 text-white font-bold text-xs rounded-xl shadow">
                        Buka Stage 
                    </span>
                </a>
            @empty
                <div class="p-4 text-center text-xs text-slate-500 bg-slate-950 rounded-xl border border-slate-800">
                    Belum ada ruangan kuis yang dibuat oleh Admin.
                </div>
            @endforelse
        </div>

        <form action="{{ route('logout') }}" method="POST" class="pt-2">
            @csrf
            <button class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl transition-all">
                Keluar / Logout
            </button>
        </form>
    </div>

</body>
</html>