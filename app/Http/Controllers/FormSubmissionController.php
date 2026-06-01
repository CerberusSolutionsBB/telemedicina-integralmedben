<?php

namespace App\Http\Controllers;

use App\Http\Services\FormSubmission\FormSubmissionService;
use App\Models\FormSubmission;
use App\Models\Question;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class FormSubmissionController extends Controller
{
    public function __construct(private FormSubmissionService $formSubmissionService) {}

    public function index()
    {
        $submissions = $this->formSubmissionService->getSubmissions();
        $tenant = Tenant::find(tenant('id'));

        return Inertia::render('FormSubmission/Index', [
            'submissions' => $submissions,
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
        ]);
    }

    public function show(FormSubmission $submission)
    {
        $submission = $this->formSubmissionService->getSubmissionDetails($submission);

        return Inertia::render('FormSubmissions/Show', [
            'submission' => $submission,
        ]);
    }

    public function reportPdf()
    {
        $submissions = FormSubmission::with('answers')->latest()->get();

        $questionIds = $submissions->pluck('answers.*.question_id')->flatten()->unique();
        $questions = Question::whereIn('id', $questionIds)->get();

        $submissions->each(function ($submission) use ($questions) {
            $submission->answers->each(function ($answer) use ($questions) {
                $answer->question = $questions->firstWhere('id', $answer->question_id);
            });
        });

        $tenant = Tenant::find(tenant('id'));
        $logoBase64 = null;
        if ($tenant->photo_path) {
            $absolutePath = base_path('storage/app/public/'.$tenant->photo_path);
            if (file_exists($absolutePath)) {
                $logoBase64 = 'data:'.mime_content_type($absolutePath).';base64,'.base64_encode(file_get_contents($absolutePath));
            }
        }

        $pdf = Pdf::loadView('pdf.submissions-report', [
            'submissions' => $submissions,
            'questions' => $questions,
            'tenant' => $tenant,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-cadastros.pdf');
    }

    public function downloadPdf(FormSubmission $submission)
    {
        $submission = $this->formSubmissionService->getSubmissionDetails($submission);

        $tenant = Tenant::find(tenant('id'));
        $logoBase64 = null;
        if ($tenant->photo_path) {
            $absolutePath = base_path('storage/app/public/'.$tenant->photo_path);
            if (file_exists($absolutePath)) {
                $logoBase64 = 'data:'.mime_content_type($absolutePath).';base64,'.base64_encode(file_get_contents($absolutePath));
            }
        }

        $pdf = Pdf::loadView('pdf.submission', [
            'submission' => $submission,
            'tenant' => $tenant,
            'logoBase64' => $logoBase64,
        ]);

        return $pdf->download('submissao-'.$submission->id.'.pdf');
    }
}
