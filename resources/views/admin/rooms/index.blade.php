<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Room - Buy & Answer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold text-pink-500">Pilih Ruangan Kuis</h1>
                <p class="text-sm text-slate-400">Pilih ruangan aktif atau buat ruangan kuis baru</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.master-questions.index') }}" class="px-4 py-2.5 border border-pink-500/30 text-pink-400 rounded-xl hover:bg-pink-500/10">Master Bank Soal</a>
                <a href="{{ route('admin.rooms.create') }}" class="px-5 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-lg shadow-pink-600/20">+ Buat Room Baru</a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-400 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($rooms as $room)
                <div class="bg-slate-900 border border-pink-500/20 p-6 rounded-2xl flex flex-col justify-between space-y-4">
                    <div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase border 
                            {{ $room->status === 'ongoing' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400' }}">
                            {{ $room->status }}
                        </span>
                        <h3 class="text-xl font-bold mt-3 text-slate-100">{{ $room->name }}</h3>
                        <p class="text-xs text-slate-400 mt-1">{{ $room->teams->count() }} Tim Bertanding</p>
                    </div>
                    <a href="/admin/rooms/{{ $room->id }}/control" class="block text-center py-2.5 bg-pink-600 hover:bg-pink-500 text-white font-bold rounded-xl transition-all">
                        Masuk Control Panel →
                    </a>
                </div>
            @empty
                <div class="col-span-3 text-center p-12 bg-slate-900/50 rounded-2xl border border-slate-800 text-slate-500">
                    Belum ada ruangan kuis. Silakan buat room baru!
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>