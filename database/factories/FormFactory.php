<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        $statuses = [
            Form::STATUS_RASCUNHO,
            Form::STATUS_ATIVO,
            Form::STATUS_PAUSADO,
            Form::STATUS_ENCERRADO,
        ];

        $status = $this->faker->randomElement($statuses);

        // Datas baseadas no status
        $publishedAt = null;
        $expiresAt = null;

        switch ($status) {
            case Form::STATUS_ATIVO:
                $publishedAt = $this->faker->dateTimeBetween('-1 month', 'now');
                $expiresAt = $this->faker->optional(0.3)->dateTimeBetween('+1 week', '+3 months');
                break;

            case Form::STATUS_PAUSADO:
                $publishedAt = $this->faker->dateTimeBetween('-2 months', '-1 month');
                break;

            case Form::STATUS_ENCERRADO:
                $publishedAt = $this->faker->dateTimeBetween('-3 months', '-2 months');
                $expiresAt = $this->faker->dateTimeBetween('-1 month', 'now');
                break;

            case Form::STATUS_RASCUNHO:
            default:
                $publishedAt = null;
                $expiresAt = null;
                break;
        }

        $title = $this->faker->sentence(3);

        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->randomNumber(4),
            'description' => $this->faker->paragraph(2),
            'status' => $status,
            'is_public' => $this->faker->boolean(30), // 30% chance de ser público
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
            'response_limit' => $this->faker->optional(0.5)->numberBetween(10, 1000),
            'settings' => [
                'theme' => $this->faker->randomElement(['default', 'dark', 'colorful']),
                'allow_multiple' => $this->faker->boolean(20),
                'show_progress' => $this->faker->boolean(80),
            ],
            'responses_count' => $this->faker->numberBetween(0, 500),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Estado: Formulário público
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Estado: Formulário privado
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Estado: Rascunho
     */
    public function rascunho(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Form::STATUS_RASCUNHO,
            'published_at' => null,
            'expires_at' => null,
        ]);
    }

    /**
     * Estado: Ativo
     */
    public function ativo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Form::STATUS_ATIVO,
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Estado: Pausado
     */
    public function pausado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Form::STATUS_PAUSADO,
            'published_at' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
        ]);
    }

    /**
     * Estado: Encerrado
     */
    public function encerrado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Form::STATUS_ENCERRADO,
            'published_at' => $this->faker->dateTimeBetween('-3 months', '-2 months'),
            'expires_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
