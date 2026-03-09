<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin — Kridi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#0CF232', dark: '#09C72A' },
                        navy: '#0B1E2D',
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">
    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-navy mb-4">
            <div class="w-8 h-8 rounded-lg bg-brand flex items-center justify-center">
                <svg class="w-5 h-5 text-navy" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1"/><path d="M16 12H3m13 0-4-4m4 4-4 4"/></svg>
            </div>
        </div>
        <h1 class="text-2xl font-bold text-navy">Kridi Admin</h1>
        <p class="text-gray-500 text-sm mt-1">Connectez-vous pour gérer la plateforme</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        @if($errors->any())
            <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-brand focus:border-brand transition text-sm"
                       placeholder="admin@kridi.ma">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-brand focus:border-brand transition text-sm"
                       placeholder="••••••••">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember"
                       class="rounded border-gray-300 text-brand focus:ring-brand">
                <label for="remember" class="text-sm text-gray-600">Se souvenir de moi</label>
            </div>

            <button type="submit"
                    class="w-full py-3 px-4 bg-brand hover:bg-brand-dark text-navy font-semibold rounded-xl transition shadow-sm">
                Se connecter
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">&copy; {{ date('Y') }} Kridi — Tous droits réservés</p>
</div>

</body>
</html>
