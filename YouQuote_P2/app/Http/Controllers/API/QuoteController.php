<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuoteController extends Controller
{
    /** ************************************************************************************************************************
     * Display a listing of the resource.
     */
    public function index()
    {
        $quotes = Quote::where("is_valide", true)->with(['tags:name', 'categories:name', 'user'])->get();

        $citations = $quotes->map(function ($citation) {
            return [
                "citation" => [
                    "id"         => $citation->id,
                    "content"    => $citation->content,
                    "user_id"    => $citation->user->name,
                    "popularite" => $citation->popularite,
                    "deleted_at" => $citation->deleted_at,
                    "tags"       => $citation->tags->pluck('name'),
                    "categories" => $citation->categories->pluck('name'),
                ],
            ];
        });

        return response()->json($citations);
    }

    /** ************************************************************************************************************************
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->user()->hasPermissionTo('create quote')) {

            $validator = Validator::make($request->all(), [
                "content"      => "required|string",
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
                'content'  => $request->content,
                'user_id'  => $request->user()->id,
                'nbr_mots' => str_word_count($request->content),
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

    /** ************************************************************************************************************************
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $citation = Quote::with(['tags:name', 'categories:name', 'user'])->findOrFail($id);

        $citation->increment('popularite');

        return response()->json([
            "success"  => true,
            "citation" => [
                "id"         => $citation->id,
                "content"    => $citation->content,
                "user_id"    => $citation->user->name,
                "popularite" => $citation->popularite,
                "tags"       => $citation->tags->pluck('name'),
                "categories" => $citation->categories->pluck('name'),
            ],
        ], 200);
    }

    /** ************************************************************************************************************************
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

    /** ************************************************************************************************************************
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {

        $user     = auth()->user();
        $citation = Quote::find($id);
        if(! $citation){
            return response()->json(['message' => 'Aucune citation Trouvée'], 404);
        }

        if (! $user->hasRole('Admin') && $user->id !== $citation->user_id) {
            return response()->json(['message' => 'Accès refusé, Vous ne peux pas supprimer cette citation !'], 403);
        }

        $record = Quote::find($id);
        $record->delete();
        return response()->json(['message' => 'La suppresseion a été effectué avec succès '], 200);
    }

    // ************************************************************************************************************************
// valider les quotes récement créer
    public function validateQuote(Request $request, string $id)
    {
        $user = $request->user();

        $citation = Quote::find($id);

        if (!$citation) {
            return response()->json([
                'message' => 'Citation non trouvée.',
            ], 404);
        }

        if ($user->hasPermissionTo('validate quote')) {
            $citation->is_valide = true;
            $citation->save();

            return response()->json([
                'message' => 'Vous avez validé la citation.',
                'citation' => $citation,
            ], 200);
        }

        return response()->json([
            'message' => 'Vous n\'avez pas le droit de valider la citation.',
        ], 403);
    }
}
