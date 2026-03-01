<?php 

namespace App\Http\Services\Question;

use App\Models\Question;
use App\Models\Tenant;

class AssignTenantQuestionService
{
    public function execute($tenantId, $questions)
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            throw new \Exception('Tenant não encontrado');
        }

        $systemQuestionIds = Question::whereNotNull('role')->pluck('id')->toArray();

        $merged = array_unique(array_merge($systemQuestionIds, $questions));

        $tenant->questions()->sync($merged);

        return $tenant;
    }
}