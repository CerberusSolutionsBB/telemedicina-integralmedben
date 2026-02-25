<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantQuestionsRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Services\Question\QuestionService;
use App\Models\Question;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function __construct(private QuestionService $questionService) {}

    public function index()
    {
        $questions = $this->questionService->getAllQuestions();

        return Inertia::render('Registros/Index', [
            'questions' => $questions
        ]);
    }

    public function create()
    {
        return Inertia::render('Questions/Create');
    }

    public function store(StoreQuestionRequest $request)
    {
        $data = $request->validated();
        $this->questionService->createQuestion($data);

        return redirect()->route('registros.index')
            ->with('success', 'Pergunta criada com sucesso!');
    }

    public function edit(Question $question)
    {
        return Inertia::render('Questions/Edit', [
            'question' => $question
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question)
    {
        if ($question->isSystem()) {
            abort(403, 'Esta pergunta é essencial para o sistema e não pode ser editada.');
        }

        $data = $request->validated();
        $this->questionService->updateQuestion($question, $data);

        return redirect()->route('registros.index')
            ->with('success', 'Pergunta atualizada com sucesso!');
    }

    public function show(Question $question)
    {
        return Inertia::render('Questions/Show', [
            'question' => $question->load('tenants')
        ]);
    }

    public function destroy(Question $question)
    {
        if ($question->isSystem()) {
            abort(403, 'Esta pergunta é essencial para o sistema e não pode ser excluída.');
        }

        $this->questionService->deleteQuestion($question);

        return redirect()->route('registros.index')
            ->with('success', 'Pergunta deletada com sucesso!');
    }

    public function storeTenantQuestions(StoreTenantQuestionsRequest $request)
    {
        $data = $request->validated();
        $this->questionService->assignTenantQuestion($data['tenant_id'], $data['questions']);

        return redirect()->route('credenciados.index')
            ->with('success', 'Perguntas vinculadas com sucesso!');
    }
}