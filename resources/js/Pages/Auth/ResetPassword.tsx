import { useForm, Link, Head } from '@inertiajs/react';
import { Building2, Loader2 } from 'lucide-react';

interface Props {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/reset-password');
    };

    return (
        <>
            <Head title="Set new password" />
            <div className="min-h-screen bg-stone-950 flex items-center justify-center px-4">
                <div className="w-full max-w-sm">
                    <div className="flex items-center gap-2 justify-center mb-8">
                        <div className="w-9 h-9 bg-amber-500 rounded-xl flex items-center justify-center">
                            <Building2 size={18} className="text-stone-950" />
                        </div>
                        <span className="text-xl font-semibold text-stone-100">Nexus</span>
                    </div>

                    <div className="bg-stone-900 border border-stone-800 rounded-2xl p-8">
                        <h1 className="text-xl font-semibold text-stone-100 mb-1">Set new password</h1>
                        <p className="text-sm text-stone-400 mb-6">
                            Choose a strong password for your account.
                        </p>

                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label className="block text-sm text-stone-300 mb-1.5">Email address</label>
                                <input
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
                                {errors.email && <p className="mt-1 text-xs text-red-400">{errors.email}</p>}
                            </div>

                            <div>
                                <label className="block text-sm text-stone-300 mb-1.5">New password</label>
                                <input
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="w-full bg-stone-800 border border-stone-700 rounded-xl px-4 py-2.5
                                        text-sm text-stone-100 placeholder-stone-500 focus:outline-none
                                        focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors"
                                    placeholder="At least 8 characters"
                                    autoComplete="new-password"
                                    required
                                />
                                {errors.password && <p className="mt-1 text-xs text-red-400">{errors.password}</p>}
                            </div>

                            <div>
                                <label className="block text-sm text-stone-300 mb-1.5">Confirm password</label>
                                <input
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    className="w-full bg-stone-800 border border-stone-700 rounded-xl px-4 py-2.5
                                        text-sm text-stone-100 placeholder-stone-500 focus:outline-none
                                        focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors"
                                    placeholder="Repeat password"
                                    autoComplete="new-password"
                                    required
                                />
                                {errors.password_confirmation && (
                                    <p className="mt-1 text-xs text-red-400">{errors.password_confirmation}</p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-amber-500 text-stone-950 font-semibold py-2.5 rounded-xl
                                    hover:bg-amber-400 disabled:opacity-50 transition-colors text-sm
                                    flex items-center justify-center gap-2 mt-2"
                            >
                                {processing && <Loader2 size={15} className="animate-spin" />}
                                Reset password
                            </button>
                        </form>

                        <p className="text-sm text-stone-500 text-center mt-6">
                            <Link href="/login" className="text-amber-400 hover:text-amber-300 transition-colors">
                                Back to sign in
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
