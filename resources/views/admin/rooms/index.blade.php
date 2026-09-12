<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Room - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">
    <div class="max-w-6xl mx-auto space-y-6">
        
        <!-- Header Dashboard -->
        <div class="flex justify-between items-center bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <div>
                <span class="text-xs font-bold text-pink-400 uppercase tracking-wider">Admin Panel</span>
                <h1 class="text-3xl font-black text-white mt-1">Daftar Ruangan Pertandingan</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.master-questions.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition-all">
                    📚 Master Bank Soal
                </a>
                <a href="{{ route('admin.rooms.create') }}" class="px-4 py-2 bg-pink-600 hover:bg-pink-500 text-white font-bold text-xs rounded-xl shadow transition-all">
                    + Buat Room Baru
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-400 text-xs font-bold">
                {{ session('success') }}
            </div>
        @endif

        <!-- Grid Daftar Room -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($rooms as $room)
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4 flex flex-col justify-between">
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider
                                {{ $room->status === 'ongoing' ? 'bg-pink-500/20 text-pink-400 border border-pink-500/30' : ($room->status === 'finished' ? 'bg-slate-800 text-slate-500' : 'bg-emerald-500/10 text-emerald-400') }}">
                                {{ $room->status }}
                            </span>
                            <span class="text-xs text-slate-500 font-mono">{{ $room->teams_count ?? $room->teams->count() }} Tim</span>
                        </div>
                        <h2 class="text-xl font-extrabold text-white">{{ $room->name }}</h2>
                    </div>

                    <div class="pt-4 border-t border-slate-800/80 flex gap-2">
                        <a href="{{ route('admin.rooms.control', $room->id) }}" class="w-full text-center py-2.5 bg-pink-600 hover:bg-pink-500 text-white font-bold text-xs rounded-xl shadow transition-all">
                            Masuk Control Center 🎛️
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full p-12 text-center text-slate-500 bg-slate-900/50 rounded-2xl border border-slate-800 space-y-3">
                    <p class="text-sm">Belum ada ruangan pertandingan yang dibuat.</p>
                    <a href="{{ route('admin.rooms.create') }}" class="inline-block px-4 py-2 bg-pink-600 text-white font-bold text-xs rounded-xl">
                        Buat Ruangan Pertama
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>