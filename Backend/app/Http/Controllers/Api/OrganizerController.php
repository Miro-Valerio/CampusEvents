<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organizer;
use Illuminate\Http\JsonResponse;

class OrganizerController extends Controller
{
    public function show(Organizer $organizer): JsonResponse
    {
        return response()->json($organizer->load(['events' => fn ($q) => $q->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at')]));
    }
}
