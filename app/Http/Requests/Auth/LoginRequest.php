<?php
namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     * Mensagens de validação dos campos (formato, obrigatoriedade)
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'O campo email é obrigatório.',
            'email.email'       => 'Por favor, insira um email válido (ex: seu@email.com).',
            'password.required' => 'O campo senha é obrigatório.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     * Nomes amigáveis dos campos nas mensagens de erro
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email'    => 'email',
            'password' => 'senha',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * Autenticação com mensagens de erro personalizadas
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // Verifica se o email existe para dar mensagem mais específica
            $userExists = \App\Models\User::where('email', $this->input('email'))->exists();

            if (! $userExists) {
                throw ValidationException::withMessages([
                    'email' => 'Não encontramos uma conta com este email. Verifique se digitou corretamente ou cadastre-se.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => 'Senha incorreta. Tente novamente ou clique em "Esqueceu a senha?" para recuperar.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     * Proteção contra tentativas excessivas com mensagem clara
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());
        $minutes = ceil($seconds / 60);

        $timeMessage = $minutes > 1
            ? "aproximadamente {$minutes} minutos"
            : "alguns segundos";

        throw ValidationException::withMessages([
            'email' => "Muitas tentativas de login. Por segurança, aguarde {$timeMessage} antes de tentar novamente.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}