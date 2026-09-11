<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Buy & Answer Quiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-slate-900/80 border border-pink-500/20 backdrop-blur-xl p-8 rounded-2xl shadow-2xl shadow-pink-500/10">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-pink-500 to-rose-400 bg-clip-text text-transparent">
                Buy & Answer
            </h1>
            <p class="text-sm text-slate-400 mt-2">Portal Masuk Admin & Operator</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-400 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                    class="w-full px-4 py-3 bg-slate-950/60 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:border-pink-500 transition-colors">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 bg-slate-950/60 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:border-pink-500 transition-colors">
            </div>

            <button type="submit" 
                class="w-full py-3.5 px-4 bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-500 hover:to-rose-500 text-white font-bold rounded-xl shadow-lg shadow-pink-600/25 transition-all active:scale-[0.98]">
                Masuk Sistem
            </button>
        </form>
    </div>
</body>
</html>