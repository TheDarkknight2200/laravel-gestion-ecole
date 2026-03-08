<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoursRequest;
use App\Http\Requests\UpdateCoursRequest;
use App\Http\Resources\CoursCollection;
use App\Http\Resources\CoursResource;
use App\Models\Cours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoursController extends Controller
{
    public function index(Request $request): CoursCollection
    {
        $perPage = $request->query('per_page', 15);
        $query   = Cours::query();

        if ($request->has('professeur')) {
            $query->where('professeur', 'like', '%'.$request->query('professeur').'%');
        }

        if ($request->query('include') === 'etudiants') {
            $query->with('etudiants');
        }

        return new CoursCollection($query->paginate($perPage));
    }

    public function store(StoreCoursRequest $request): JsonResponse
    {
        $cours = Cours::create($request->validated());

        return (new CoursResource($cours))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Cours $cours): JsonResponse
    {
        if ($request->query('include') === 'etudiants') {
            $cours->load('etudiants');
        }

        return (new CoursResource($cours))->response();
    }

    public function update(UpdateCoursRequest $request, Cours $cours): JsonResponse
    {
        $cours->update($request->validated());

        return (new CoursResource($cours))->response();
    }

    public function destroy(Cours $cours): JsonResponse
    {
        $cours->delete();

        return response()->json(null, 204);
    }
}