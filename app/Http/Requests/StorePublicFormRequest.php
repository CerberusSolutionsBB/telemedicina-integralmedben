<?php

namespace App\Http\Requests;

use App\Enums\QuestionRoleEnum;
use App\Models\Question;
use App\Rules\UniqueAnswerRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tenant_id' => 'required',
            'answers' => 'required|array|min:1',
            'answers.*' => 'nullable',
        ];

        $answers = $this->input('answers', []);
        $questionIds = array_keys($answers);

        if (!empty($questionIds)) {
            $uniqueQuestions = Question::whereIn('id', $questionIds)
                ->where(function ($query) {
                    $query->where('is_unique', true)
                          ->orWhere('role', QuestionRoleEnum::Cpf->value);
                })
                ->pluck('id');

            foreach ($uniqueQuestions as $questionId) {
                $rules["answers.{$questionId}"] = [new UniqueAnswerRule($questionId)];
            }
        }

        return $rules;
    }
}
