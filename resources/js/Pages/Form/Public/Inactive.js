// resources/js/Pages/Form/Public/Inactive.tsx
import { Head } from '@inertiajs/react';

interface Props {
    form: {
        title: string;
        status: string;
        statusLabel: string;
        message: string;
        instruction: string;
        canActivate: boolean;
    };
}

export default function Inactive({ form }: Props) {
    // Cores por status
    const statusColors: Record<string, { bg: string; border: string; icon: string }> = {
        rascunho:   { bg: 'bg-gray-50',    border: 'border-gray-300',    icon: 'text-gray-500' },
        pausado:    { bg: 'bg-yellow-50',  border: 'border-yellow-300',  icon: 'text-yellow-600' },
        encerrado:  { bg: 'bg-red-50',     border: 'border-red-300',     icon: 'text-red-600' },
        expirado:   { bg: 'bg-orange-50',  border: 'border-orange-300',  icon: 'text-orange-600' },
        limite_atingido: { bg: 'bg-purple-50', border: 'border-purple-300', icon: 'text-purple-600' },
    };

    const colors = statusColors[form.status] || statusColors.rascunho;

    return (
        <>
            <Head title={`${form.title} - Indisponível`} />

            <div className={`min-h-screen flex items-center justify-center ${colors.bg} px-4 py-12`}>
                <div className={`max-w-lg w-full bg-white rounded-xl shadow-lg border-2 ${colors.border} p-8 text-center`}>
                    {/* Ícone */}
                    <div className={`mx-auto flex items-center justify-center h-16 w-16 rounded-full ${colors.bg} mb-6`}>
                        <svg
                            className={`h-8 w-8 ${colors.icon}`}
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>

                    <h1 className="text-2xl font-bold text-gray-900 mb-2">
                        {form.title}
                    </h1>

                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${colors.bg} ${colors.icon} border ${colors.border}`}>
                        Status: {form.statusLabel.toUpperCase()}
                    </span>

                    <div className={`mt-6 ${colors.bg} border ${colors.border} rounded-lg p-4`}>
                        <p className={`font-medium ${colors.icon} mb-1`}>
                            {form.message}
                        </p>
                        <p className="text-gray-600 text-sm">
                            {form.instruction}
                        </p>
                    </div>

                    {form.canActivate && (
                        <div className="mt-4 text-sm text-gray-500">
                            <p>Entre em contato com o administrador do formulário ou</p>
                            <p className="font-medium text-gray-700">acesse o painel administrativo para ativar.</p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
