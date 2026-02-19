<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - Login</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 min-h-screen flex items-center justify-center p-4">
    <div class="glass-effect rounded-2xl shadow-2xl w-full max-w-[400px] overflow-hidden border border-white/50 relative">
        <!-- Decorative Elements -->
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-blue-500 to-indigo-600"></div>

        <div class="p-8 md:p-10">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <!-- <div class="inline-flex justify-center mb-6 bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="StockMin Logo" class="h-12 w-auto object-contain">
                </div> -->
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Welcome</h1>
                <p class="text-slate-500 text-sm mt-2">Enter your credentials to access the system</p>
            </div>  

            <!-- Error Messages -->
            @if ($errors->any())
                <div class="bg-rose-50 border border-rose-100 text-rose-600 px-4 py-3 rounded-lg text-sm mb-6 flex items-center shadow-sm">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Account tidak valid, silahkan coba lagi.</span>
                </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf
                
                <div class="space-y-1.5">
                    <label for="username" class="block text-sm font-semibold text-slate-700">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input type="text" name="username" id="username" 
                               class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-200 text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200 bg-slate-50/50"
                               placeholder="e.g. whc" value="{{ old('username') }}" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input type="password" name="password" id="password" 
                               class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-200 text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200 bg-slate-50/50"
                               placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 transition duration-150">
                        <span class="ml-2 text-sm text-slate-600 font-medium select-none">Remember me</span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-2.5 rounded-lg shadow-lg hover:shadow-indigo-500/30 transform transition-all duration-200 active:scale-[0.98]">
                    Sign In
                </button>
            </form>

            <!-- Guest Mode Button -->
            <div class="mt-4 pt-4 border-t border-slate-200">
                <form action="{{ route('guest.login') }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg border border-slate-300 transform transition-all duration-200 active:scale-[0.98] flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Guest Mode (View Only)
                    </button>
                </form>
                <p class="text-xs text-slate-500 text-center mt-2">Akses terbatas hanya untuk melihat data</p>
            </div>
        </div>
        
        <div class="py-4 bg-slate-50 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-400 font-medium">© {{ date('Y') }} Stock Management System</p>
        </div>
    </div>
</body>
</html>