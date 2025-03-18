<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Quote::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->user()->hasPermissionTo('create quote')) {

            $validator = Validator::make($request->all(), [
                "content"      => "required|string",
                "popularite"   => "required|integer",
                "categories"   => "nullable|array",
                "categories.*" => "exists:categories,id",
                "tags"         => "nullable|array",
                "tags.*"       => "exists:tags,id",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "error" => $validator->errors()->first(),
                ], 400);
            }

            $citation = Quote::create([
                'content'    => $request->content,
                'user_id'    => $request->user()->id,
                'popularite' => $request->popularite,
                'nbr_mots'   => str_word_count($request->content),
            ]);

            if ($request->has('categories')) {
                $citation->categories()->attach($request->categories);
            }

            if ($request->has('tags')) {
                $citation->tags()->attach($request->tags);
            }

            return response()->json([
                "success"  => true,
                "citation" => $citation->load('categories', 'tags'),
            ], 201);
        }

        return response()->json([
            "message" => "Vous n'avez pas l'accès pour créer des nouvelles citations",
        ], 403);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {

        $citation             = Quote::findOrFail($id);
        $citation->popularite = $citation->popularite + 1;
        $citation->save();
        return response()->json([
            "success"  => true,
            "citation" => $citation,
        ], 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user     = auth()->user();
        $citation = Quote::findOrFail($id);

        if (! $user->hasRole('Admin') && $user->id !== $citation->user_id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'content'      => 'sometimes|string',
            'tags'         => 'sometimes|array',
            'tags.*'       => 'exists:tags,id',
            'categories'   => 'sometimes|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $citation->update($validated);

        if ($request->has('tags')) {
            $citation->tags()->sync($request->tags);
        }

        if ($request->has('categories')) {
            $citation->categories()->sync($request->categories);
        }

        return response()->json(['message' => 'Citation mise à jour avec succès', 'citation' => $citation], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
