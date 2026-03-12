<?php

namespace App\Interfaces;

interface ExternalApiInterface
{
    /**
     * Registra um paciente na API externa.
     *
     * @param  array $data  Dados normalizados do paciente (cpf, nome, tel, plan, etc.)
     * @return array        Resposta da API
     * @throws \Exception   Em caso de falha na comunicação
     */
    public function registerPatient(array $data): array;
}
