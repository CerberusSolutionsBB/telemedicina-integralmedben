<?php

namespace App\Http\Services\Question;

use App\Models\Question;

class QuestionService
{
    public function __construct(
        private AssignTenantQuestionService $assignTenantQuestionService,
        private GetAllQuestionsService $getAllQuestionsService,
        private CreateQuestionService $createQuestionService,
        private UpdateQuestionService $updateQuestionService,
        private DeleteQuestionService $deleteQuestionService
    ) {}

    public function assignTenantQuestion(string $tenantId, array $questions)
    {
        return $this->assignTenantQuestionService->execute($tenantId, $questions);
    }

    public function getAllQuestions()
    {
        return $this->getAllQuestionsService->execute();
    }

    public function createQuestion(array $data)
    {
        return $this->createQuestionService->execute($data);
    }

    public function updateQuestion(Question $question, array $data)
    {
        return $this->updateQuestionService->execute($question, $data);
    }

    public function deleteQuestion(Question $question)
    {
        return $this->deleteQuestionService->execute($question);
    }
}
