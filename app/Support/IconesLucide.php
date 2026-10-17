<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Ícones do lucide (lucide-vue-next) usados em cadastros com ícone (serviços, convênios).
 * Qualquer ícone do lucide é aceito; os sugeridos aparecem primeiro no seletor e têm
 * rótulo em português. O front tem a mesma lista em Composables/Icone/sugeridos.js.
 */
class IconesLucide
{
    /** Nome do componente lucide => rótulo em português. */
    public const SUGERIDOS = [
        'Stethoscope' => 'Estetoscópio',
        'HeartPulse' => 'Coração',
        'Activity' => 'Atividade',
        'Video' => 'Teleconsulta',
        'Phone' => 'Telefone',
        'MessageCircle' => 'Mensagem',
        'Pill' => 'Medicamento',
        'Syringe' => 'Vacina',
        'Ambulance' => 'Ambulância',
        'Hospital' => 'Hospital',
        'Brain' => 'Saúde mental',
        'Baby' => 'Pediatria',
        'Eye' => 'Oftalmologia',
        'Smile' => 'Odontologia',
        'Bone' => 'Ortopedia',
        'Microscope' => 'Exames',
        'Thermometer' => 'Temperatura',
        'Dumbbell' => 'Atividade física',
        'Apple' => 'Nutrição',
        'ShieldCheck' => 'Proteção',
        'Calendar' => 'Agendamento',
        'ClipboardList' => 'Lista',
        'FileText' => 'Documento',
        'Users' => 'Família',
    ];

    /** Formato do nome de um componente lucide (ex.: HeartPulse, CalendarCheck2). */
    public const REGRA = 'regex:/^[A-Z][A-Za-z0-9]*$/';

    /**
     * Rótulo do ícone: o sugerido em português ou o nome separado ("HeartPulse" -> "Heart Pulse").
     */
    public static function rotulo(?string $icone): ?string
    {
        if (! $icone) {
            return null;
        }

        return self::SUGERIDOS[$icone] ?? Str::headline($icone);
    }

    /**
     * Sugeridos no formato de opções do front.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function opcoes(): array
    {
        return collect(self::SUGERIDOS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all();
    }
}
