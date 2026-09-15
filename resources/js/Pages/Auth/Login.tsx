import { useForm, Link, Head } from '@inertiajs/react';
import { Building2, Loader2 } from 'lucide-react';

interface Props {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Sign in" />
            <div className="min-h-screen bg-stone-950 flex items-center justify-center px-4">
                <div className="w-full max-w-sm">
                    {/* Logo */}
                    <div className="flex items-center gap-2 justify-center mb-8">
                        <div className="w-9 h-9 bg-amber-500 rounded-xl flex items-center justify-center">
                            <Building2 size={18} className="text-stone-950" />
                        </div>
                        <span className="text-xl font-semibold text-stone-100">Nexus</span>
                    </div>

                    <div className="bg-stone-900 border border-stone-800 rounded-2xl p-8">
                        <h1 className="text-xl font-semibold text-stone-100 mb-1">Welcome back</h1>
                        <p className="text-sm text-stone-400 mb-6">Sign in to your account</p>

                        {status && (
                            <div className="mb-4 px-3 py-2.5 bg-emerald-500/10 border border-emerald-500/20
                                text-emerald-400 text-sm rounded-lg">
                                {status}
                            </div>
                        )}

                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label className="block text-sm text-stone-300 mb-1.5" htmlFor="email">
                                    Email address
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="w-full bg-stone-800 border border-stone-700 rounded-xl px-4 py-2.5
                                        text-sm text-stone-100 placeholder-stone-500 focus:outline-none
                                        focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors"
                                    placeholder="you@company.com"
                                    autoComplete="email"
                                    required
                                />
                                {errors.email && (
                                    <p className="mt-1 text-xs text-red-400">{errors.email}</p>
                                )}
                            </div>

                            <div>
                                <div className="flex items-center justify-between mb-1.5">
                                    <label className="text-sm text-stone-300" htmlFor="password">
                                        Password
                                    </label>
                                    {canResetPassword && (
                                        <Link
                                            href="/forgot-password"
                                            className="text-xs text-amber-400 hover:text-amber-300 transition-colors"
                                        >
                                            Forgot password?
                                        </Link>
                                    )}
                                </div>
                                <input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="w-full bg-stone-800 border border-stone-700 rounded-xl px-4 py-2.5
                                        text-sm text-stone-100 placeholder-stone-500 focus:outline-none
                                        focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors"
                                    placeholder="••••••••"
                                    autoComplete="current-password"
                                    required
                                />
                                {errors.password && (
                                    <p className="mt-1 text-xs text-red-400">{errors.password}</p>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="accent-amber-500"
                                />
                                <label htmlFor="remember" className="text-sm text-stone-400 cursor-pointer">
                                    Keep me signed in
                                </label>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-amber-500 text-stone-950 font-semibold py-2.5 rounded-xl
                                    hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed
                                    transition-colors text-sm flex items-center justify-center gap-2 mt-2"
                            >
                                {processing && <Loader2 size={15} className="animate-spin" />}
                                Sign in
                            </button>
                        </form>

                        <p className="text-sm text-stone-500 text-center mt-6">
                            New to Nexus?{' '}
                            <Link href="/register" className="text-amber-400 hover:text-amber-300 transition-colors">
                                Create an account
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
