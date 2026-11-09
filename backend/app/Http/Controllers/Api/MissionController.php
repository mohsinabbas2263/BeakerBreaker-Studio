<?php

namespace App\Http\Controllers\Api;

use App\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissionController extends Controller
{
    /** FR-07.3: the student's own three missions for today (SR-08: no user id taken). */
    public function index(Request $request, MissionService $missions): JsonResponse
    {
        return response()->json($missions->forToday($request->user()));
    }
}
