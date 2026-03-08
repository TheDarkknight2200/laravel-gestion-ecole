<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEtudiantRequest;
use App\Http\Requests\UpdateEtudiantRequest;
use App\Http\Resources\EtudiantCollection;
use App\Http\Resources\EtudiantResource;
use App\Models\Etudiant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EtudiantController extends Controller
{
   
    public function index(Request $request): EtudiantCollection
    {
        $perPage = $request->query('per_page', 15);

        $query = Etudiant::query();

        
        if ($request->has('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->where('prenom', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        
        if ($request->query('include') === 'cours') {
            $query->with('cours');
        }

        $etudiants = $query->paginate($perPage);

        return new EtudiantCollection($etudiants);
    }

  
    public function store(StoreEtudiantRequest $request): JsonResponse
    {
        $etudiant = Etudiant::create($request->validated());

        return (new EtudiantResource($etudiant))
            ->response()
            ->setStatusCode(201);
    }

    
    public function show(Request $request, Etudiant $etudiant): JsonResponse
    {
        
        if ($request->query('include') === 'cours') {
            $etudiant->load('cours');
        }

       return (new EtudiantResource($etudiant))->response();
    }

    
    public function update(UpdateEtudiantRequest $request, Etudiant $etudiant): JsonResponse
    {
        $etudiant->update($request->validated());

        return (new EtudiantResource($etudiant))->response();
    }
   
    public function destroy(Etudiant $etudiant): JsonResponse
    {
        $etudiant->delete();

        
        return response()->json(null, 204);
    }

    
    public function attachCours(Request $request, Etudiant $etudiant): JsonResponse
    {
        $request->validate([
            'cours_ids'   => 'required|array|min:1',
            'cours_ids.*' => 'integer|exists:cours,id',
        ]);

        $etudiant->cours()->attach($request->cours_ids);
        $etudiant->load('cours');

        return response()->json([
            'message' => 'Cours ajoutés avec succès.',
            'data'    => new EtudiantResource($etudiant),
        ]);
    }

    
    public function detachCours(Request $request, Etudiant $etudiant): JsonResponse
    {
        $request->validate([
            'cours_ids'   => 'required|array|min:1',
            'cours_ids.*' => 'integer|exists:cours,id',
        ]);

        $etudiant->cours()->detach($request->cours_ids);
        $etudiant->load('cours');

        return response()->json([
            'message' => 'Cours retirés avec succès.',
            'data'    => new EtudiantResource($etudiant),
        ]);
    }

    
    public function syncCours(Request $request, Etudiant $etudiant): JsonResponse
    {
        $request->validate([
            'cours_ids'   => 'required|array',
            'cours_ids.*' => 'integer|exists:cours,id',
        ]);

        $etudiant->cours()->sync($request->cours_ids);
        $etudiant->load('cours');

        return response()->json([
            'message' => 'Liste des cours mise à jour avec succès.',
            'data'    => new EtudiantResource($etudiant),
        ]);
    }
}