<?php

namespace Database\Seeders;

use App\Enums\QuestionRoleEnum;
use Illuminate\Database\Seeder;
use App\Models\Question;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'title' => 'Nome Completo',
                'type' => 'text',
                'options' => null,
            ],
            [
                'title' => 'N° Cartão',
                'type' => 'number',
                'options' => null,
            ],
            [
                'title' => 'Plano',
                'type' => 'option',
                'options' => [
                    ['label' => 'Básico', 'value' => 'basico', 'order' => 0],
                    ['label' => 'Intermediário', 'value' => 'intermediario', 'order' => 1],
                    ['label' => 'Premium', 'value' => 'premium', 'order' => 2],
                ],
                'role' => QuestionRoleEnum::Plan->value,
            ],
            [
                'title' => 'CPF',
                'type' => 'text',
                'options' => null,
                'role' => QuestionRoleEnum::Cpf->value,
            ],
            [
                'title' => 'WhatsApp',
                'type' => 'tel',
                'options' => null,
                'role' => QuestionRoleEnum::Tel->value,
            ],
            [
                'title' => 'E-mail',
                'type' => 'email',
                'options' => null,
            ],
            [
                'title' => 'Data de Nascimento',
                'type' => 'date',
                'options' => null,
            ],
        ];

        foreach ($questions as $data) {
            $question = Question::firstOrCreate(
                ['title' => $data['title']],
                [
                    'type' => $data['type'],
                    'options' => $data['options'] ?? null,
                    'role' => $data['role'] ?? null,
                ]
            );

            if (isset($data['role']) && $question->role?->value !== $data['role']) {
                $question->update(['role' => $data['role']]);
            }
        }
    }
}
