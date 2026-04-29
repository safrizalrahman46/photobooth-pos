<x-layouts.admin :title="'Login Admin - READY TO PICT'">
    <main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; background: radial-gradient(circle at 20% 20%, #e5e7eb 0%, #f8fafc 45%, #ffffff 100%);">
        <section style="width: 100%; max-width: 440px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08); padding: 28px;">
            <header style="margin-bottom: 18px;">
                <h1 style="margin: 0; color: #0f172a; font-size: 1.5rem; font-weight: 700;">Login Admin</h1>
                <p style="margin: 8px 0 0; color: #475569; font-size: 0.92rem;">Masuk untuk membuka custom dashboard Ready To Pict.</p>
            </header>

            @if ($errors->any())
                <div style="margin-bottom: 14px; border-radius: 12px; border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; padding: 10px 12px; font-size: 0.88rem;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" style="display: grid; gap: 12px;">
                @csrf

                <label for="email" style="display: grid; gap: 6px; color: #334155; font-weight: 600; font-size: 0.9rem;">
                    Email
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        style="height: 44px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; color: #0f172a; background: #ffffff;"
                    >
                </label>

                <label for="password" style="display: grid; gap: 6px; color: #334155; font-weight: 600; font-size: 0.9rem;">
                    Password
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        style="height: 44px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; color: #0f172a; background: #ffffff;"
                    >
                </label>

                <label style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.88rem; margin-top: 2px;">
                    <input type="checkbox" name="remember" value="1">
                    Tetap login
                </label>

                <button
                    type="submit"
                    style="margin-top: 6px; height: 44px; border: 0; border-radius: 10px; background: #1d4ed8; color: #ffffff; font-weight: 600; font-size: 0.95rem; cursor: pointer;"
                >
                    Masuk
                </button>
            </form>
        </section>
    </main>
</x-layouts.admin>
