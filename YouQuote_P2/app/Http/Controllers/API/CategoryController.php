<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // if ($request->user()->hasPermissionTo('create categories'))
        return response()->json([
            'message'   => 'Voici les cétégories disponibles pour ce moment',
            'categorie' => Category::all(),
        ], 200);
        // }
        // return response()->json([
        //     "message" => "waaaw",
        // ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! $request->user()) {
            return response()->json([
                "message" => "L'utilisateur n'est pas authentifié",
            ], 401);
        }

        if ($request->user()->hasPermissionTo('create categories'))
        {

            $fields = $request->validate([
                'name' => 'required|max:255',
            ]);

            $category = Category::create([
                'name' => $fields['name'],
            ]);

            return response()->json([
                "message"            => "Vous avez créé une catégorie avec succès",
                "nouvelle catégorie" => $category,
            ], 200);

        }

        return response()->json([
            "message" => "Vous n'avez pas l'accès pour créer des catégories",
        ], 403);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (! $request->user()) {
            return response()->json([
                "message" => "L'utilisateur n'est pas authentifié",
            ], 401);
        }

        if ($request->user()->hasPermissionTo('edit categories')) {

            $fields = $request->validate([
                'name' => 'required|max:255',
            ]);

            $categorie = Category::findOrFail($id);

            $categorie->update([
                'name' => $fields['name'],
            ]);

            return response()->json([
                "message"            => "Vous avez modifié une catégorie avec succès",
                "catégorie modifiée" => $categorie,
            ], 200);
        }

        return response()->json([
            "message" => "Vous n'avez pas l'accès pour modifier des catégories",
        ], 403);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if ($request->user()->hasPermissionTo('delete categories')) {

            $category = Category::findOrFail($id);
            $delete   = $category->delete();

            if (! $delete) {
                return response()->json(['message' => 'La suppression n\'est pas effectue'], 500);
            }
            return response()->json(['message' => 'La suppression a été bien effectue', 'category' => $category], 200);
        }
        return response()->json([
            'message' => 'Vous n\'avez pas l\'accès de supprimer des categories'
        ], 403);
    }
}
