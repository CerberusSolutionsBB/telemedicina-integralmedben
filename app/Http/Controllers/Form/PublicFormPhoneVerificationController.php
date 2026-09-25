<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Rules\TelefoneBrasileiro;
use App\Services\Form\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Envia e confirma o código SMS dos campos de telefone do formulário público.
 */
class PublicFormPhoneVerificationController extends Controller
{
    public function __construct(private PhoneVerificationService $verification) {}

    public function send(Request $request, string $slug): JsonResponse
    {
        [$form, $field] = $this->resolveField($request, $slug);
        $phone = $this->validatedPhone($request, $field);

        // Mesmo critério de tenant usado no envio do formulário (subdomínio)
        $tenantId = str($request->getHost())->before('.')->toString();

        $result = $this->verification->sendCode((string) $form->id, $phone, $tenantId, (string) $request->ip());

        return response()->json($result, $result['sent'] ? 200 : 429);
    }

    public function verify(Request $request, string $slug): JsonResponse
    {
        [$form, $field] = $this->resolveField($request, $slug);
        $phone = $this->validatedPhone($request, $field);

        $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.required' => 'Informe o código recebido por SMS.', 'code.digits' => 'O código deve ter 6 dígitos.']
        );

        $result = $this->verification->verifyCode((string) $form->id, $phone, $request->input('code'));

        return response()->json($result, $result['verified'] ? 200 : 422);
    }

    /**
     * @return array{0: Form, 1: FormField}
     */
    private function resolveField(Request $request, string $slug): array
    {
        $form = Form::where('slug', $slug)->where('is_public', true)->with('fields')->firstOrFail();

        $field = $form->fields->firstWhere('id', (int) $request->input('field_id'));
        abort_unless($field && $field->isPhoneField(), 422, 'Campo de telefone inválido.');

        return [$form, $field];
    }

    private function validatedPhone(Request $request, FormField $field): string
    {
        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);
        $request->validate(['phone' => ['required', new TelefoneBrasileiro($field->label)]]);

        return $request->input('phone');
    }
}
