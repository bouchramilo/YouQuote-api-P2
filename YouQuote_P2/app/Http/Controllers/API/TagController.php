<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     return response()->json([
    //         'message'   => 'Voici les Tags disponibles pour ce moment',
    //         'Tags' => Tag::all(),
    //     ], 200);
    // }


    public function index(Request $request)
    {
        try {
            // Check permissions (uncomment when ready)
            // if (!$request->user()->hasPermissionTo('view categories')) {
            //     return response()->json([
            //         'message' => 'Unauthorized: You do not have permission to view categories',
            //     ], 403);
            // }

            $tags = Tag::all();

            if ($tags->isEmpty()) {
                return response()->json([
                    'message' => 'No tags available at this time',
                    'tags' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Available tags retrieved successfully',
                'data' => $tags,
                'count' => $tags->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tags',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // if (! $request->user()) {
        //     return response()->json([
        //         "message" => "L'utilisateur n'est pas authentifié",
        //     ], 401);
        // }

        // if ($request->user()->hasPermissionTo('create tags'))
        // {

            $fields = $request->validate([
                'name' => 'required|max:255',
            ]);

            $tag = Tag::create([
                'name' => $fields['name'],
            ]);

            return response()->json([
                "message"            => "Vous avez créé un tag avec succès",
                "nouvelle tag" => $tag,
            ], 200);

        // }

        // return response()->json([
        //     "message" => "Vous n'avez pas l'accès pour créer des tags",
        // ], 403);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
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

        if ($request->user()->hasPermissionTo('edit tags')) {

            $fields = $request->validate([
                'name' => 'required|max:255',
            ]);

            $tag = Tag::findOrFail($id);

            $tag->update([
                'name' => $fields['name'],
            ]);

            return response()->json([
                "message"            => "Vous avez modifié une tag avec succès",
                "tag modifiée" => $tag,
            ], 200);
        }

        return response()->json([
            "message" => "Vous n'avez pas l'accès pour modifier des tags",
        ], 403);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {

        if ($request->user()->hasPermissionTo('delete tags')) {

            $tag = Tag::findOrFail($id);
            $delete   = $tag->delete();

            if (! $delete) {
                return response()->json(['message' => 'La suppression n\'est pas effectue'], 500);
            }
            return response()->json(['message' => 'La suppression a été bien effectue', 'tag' => $tag], 200);
        }
        return response()->json([
            'message' => 'Vous n\'avez pas l\'accès de supprimer des tags'
        ], 403);
    }
}
