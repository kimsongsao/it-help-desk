<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    /**
     * List the organization's support teams.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Team::withCount('members')->orderBy('name')->get()->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'members' => $team->members_count,
            ]),
        ]);
    }
}
