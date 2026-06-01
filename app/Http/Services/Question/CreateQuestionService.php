<?php

namespace App\Http\Services\Question;

use App\Models\Question;
use Illuminate\Support\Str;

class CreateQuestionService
{
    public function execute(array $data)
    {
        $options = null;

        if ($data['type'] === 'option' && isset($data['options'])) {
            $options = collect($data['options'])->map(fn ($label, $index) => [
                'label' => $label,
                'value' => Str::slug($label, '_'),
                'order' => $index,
            ])->values()->toArray();
        }

        return Question::create([
            'title' => $data['title'],
            'type' => $data['type'],
            'options' => $options,
            'is_required' => $data['is_required'] ?? false,
            'is_unique' => $data['is_unique'] ?? false,
        ]);
    }
}
