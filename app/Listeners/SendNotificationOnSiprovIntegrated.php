<?php

namespace App\Listeners;

use App\Enums\SmsTemplateEventEnum;
use App\Events\SiprovIntegrated;
use App\Models\SmsTemplate;
use App\Models\Tenant;
use App\Notifications\NotificationDispatcher;

class SendNotificationOnSiprovIntegrated
{
    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(SiprovIntegrated $event): void
    {
        $query = SmsTemplate::where('event', SmsTemplateEventEnum::SiprovIntegrated->value)
            ->where('is_active', true);

        if ($event->tenantId) {
            $query->whereHas('tenants', function ($q) use ($event) {
                $q->where('tenants.id', $event->tenantId);
            });
        } else {
            $query->whereHas('tenants');
        }

        $templates = $query->get();

        if ($templates->isEmpty()) {
            return;
        }

        $data = [
            'tenant_id' => $event->tenantId,
            'nome' => $event->nome,
            'cpf' => $event->cpf,
            'email' => $event->email ?? '',
            'sexo' => $event->sexo ?? '',
            'data_nascimento' => $event->dataNascimento ?? '',
            'cod_plano' => $event->codPlano ?? '',
            'tel' => $event->telefone ?? '',
        ];

        $tenant = Tenant::find($event->tenantId);
        $data['link_pagina'] = $tenant?->url ?? config('app.url');

        foreach ($templates as $template) {
            $this->dispatcher->send($template, $data);
        }
    }
}
