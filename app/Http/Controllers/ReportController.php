<?php

namespace App\Http\Controllers;

use App\Models\CentralPatient;
use App\Models\Question;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $planQuestion = Question::where('role', 'plan')->first();
        $cityQuestion = Question::where('role', 'city')->first();

        $planQuestionId = $planQuestion?->id;
        $cityQuestionId = $cityQuestion?->id;

        $planOptions = $planQuestion?->options ?? [];

        $cityOptions = $cityQuestionId
            ? DB::table('central_patient_answers')
                ->where('question_id', $cityQuestionId)
                ->whereNotNull('answer')
                ->where('answer', '!=', '')
                ->distinct()
                ->orderBy('answer')
                ->pluck('answer')
                ->values()
            : collect();

        $tenants = Tenant::orderBy('id')->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]);

        // Filtros
        $year     = $request->integer('year', now()->year);
        $month    = $request->input('month');
        $plan     = $request->input('plan');
        $city     = $request->input('city');
        $tenantId = $request->input('tenant_id');

        // Scope reutilizável com todos os filtros
        $applyFilters = function ($q) use ($year, $month, $plan, $city, $tenantId, $planQuestionId, $cityQuestionId) {
            $q->whereYear('central_patients.created_at', $year)
              ->when($month,    fn ($q) => $q->whereMonth('central_patients.created_at', $month))
              ->when($tenantId, fn ($q) => $q->where('central_patients.tenant_id', $tenantId))
              ->when($plan && $planQuestionId, fn ($q) =>
                  $q->whereHas('answers', fn ($a) =>
                      $a->where('question_id', $planQuestionId)->where('answer', $plan)
                  )
              )
              ->when($city && $cityQuestionId, fn ($q) =>
                  $q->whereHas('answers', fn ($a) =>
                      $a->where('question_id', $cityQuestionId)->where('answer', 'like', "%{$city}%")
                  )
              );
        };

        // Totais por mês
        $byMonth = CentralPatient::query()
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total')
            ->tap($applyFilters)
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at), MONTH(created_at)')
            ->get();

        // Totais por plano
        $byPlan = $planQuestionId
            ? DB::table('central_patient_answers as a')
                ->join('central_patients as cp', 'cp.id', '=', 'a.central_patient_id')
                ->where('a.question_id', $planQuestionId)
                ->whereYear('cp.created_at', $year)
                ->when($month,    fn ($q) => $q->whereMonth('cp.created_at', $month))
                ->when($tenantId, fn ($q) => $q->where('cp.tenant_id', $tenantId))
                ->when($city && $cityQuestionId, fn ($q) =>
                    $q->whereExists(fn ($sub) =>
                        $sub->from('central_patient_answers')
                            ->whereColumn('central_patient_id', 'cp.id')
                            ->where('question_id', $cityQuestionId)
                            ->where('answer', 'like', "%{$city}%")
                    )
                )
                ->select('a.answer as plan', DB::raw('COUNT(*) as total'))
                ->groupBy('a.answer')
                ->orderByDesc('total')
                ->get()
            : collect();

        // Totais por cidade
        $byCity = $cityQuestionId
            ? DB::table('central_patient_answers as a')
                ->join('central_patients as cp', 'cp.id', '=', 'a.central_patient_id')
                ->where('a.question_id', $cityQuestionId)
                ->whereYear('cp.created_at', $year)
                ->when($month,    fn ($q) => $q->whereMonth('cp.created_at', $month))
                ->when($tenantId, fn ($q) => $q->where('cp.tenant_id', $tenantId))
                ->when($plan && $planQuestionId, fn ($q) =>
                    $q->whereExists(fn ($sub) =>
                        $sub->from('central_patient_answers')
                            ->whereColumn('central_patient_id', 'cp.id')
                            ->where('question_id', $planQuestionId)
                            ->where('answer', $plan)
                    )
                )
                ->select('a.answer as city', DB::raw('COUNT(*) as total'))
                ->groupBy('a.answer')
                ->orderByDesc('total')
                ->get()
            : collect();

        // Totais por credenciado
        $byTenant = CentralPatient::query()
            ->selectRaw('tenant_id, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->when($month,    fn ($q) => $q->whereMonth('created_at', $month))
            ->when($plan && $planQuestionId, fn ($q) =>
                $q->whereHas('answers', fn ($a) =>
                    $a->where('question_id', $planQuestionId)->where('answer', $plan)
                )
            )
            ->when($city && $cityQuestionId, fn ($q) =>
                $q->whereHas('answers', fn ($a) =>
                    $a->where('question_id', $cityQuestionId)->where('answer', 'like', "%{$city}%")
                )
            )
            ->groupBy('tenant_id')
            ->orderByDesc('total')
            ->get();

        $patients = CentralPatient::query()
            ->select('central_patients.*')
            ->tap($applyFilters)
            ->with(['answers' => fn ($q) =>
                $q->whereIn('question_id', array_filter([$planQuestionId, $cityQuestionId]))
            ])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Report/Index', [
            'patients'       => $patients,
            'byMonth'        => $byMonth,
            'byPlan'         => $byPlan,
            'byCity'         => $byCity,
            'byTenant'       => $byTenant,
            'planOptions'    => $planOptions,
            'cityOptions'    => $cityOptions,
            'tenants'        => $tenants,
            'filters'        => $request->only('year', 'month', 'plan', 'city', 'tenant_id'),
            'years'          => range(now()->year, max(2024, now()->year - 3)),
            'planQuestionId' => $planQuestionId,
            'cityQuestionId' => $cityQuestionId,
        ]);
    }
}
