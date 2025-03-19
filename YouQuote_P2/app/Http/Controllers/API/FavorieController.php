<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorie;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FavorieController extends Controller
{
    /** ********************************************************************************************************************
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        $myFavorites = Favorie::where('user_id', $user->id)
            ->with(['quote.tags', 'quote.categories', 'quote.user'])
            ->get();

        if ($myFavorites->isEmpty()) {
            return response()->json([
                'message' => 'Aucun favori trouvé pour cet utilisateur.',
            ], 200);
        }

        $formattedMyFavorites = $myFavorites->map(function ($favorie) {
            if (! $favorie->quote) {
                return null;
            }

            return [
                "citation" => [
                    "id"         => $favorie->quote->id,
                    "content"    => $favorie->quote->content,
                    "user_id"    => $favorie->quote->user ? $favorie->quote->user->name : 'Utilisateur inconnu',
                    "popularite" => $favorie->quote->popularite,
                    "tags"       => $favorie->quote->tags ? $favorie->quote->tags->pluck('name') : [],
                    "categories" => $favorie->quote->categories ? $favorie->quote->categories->pluck('name') : [],
                ],
            ];
        })->filter();

        return response()->json($formattedMyFavorites, 200);
    }

    /** ********************************************************************************************************************
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'quote_id' => [
                'required',
                'integer',
                'exists:quotes,id',
                Rule::unique('favories')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
        ], [
            'quote_id.unique' => 'Vous avez déjà ajouté cette citation à vos favoris.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "error" => $validator->errors()->first(),
            ], 400);
        }

        $citation = Quote::withTrashed()->find($request->quote_id);

        if (! $citation) {
            return response()->json([
                "error" => "Cette citation n'existe pas.",
            ], 404);
        }

        if ($citation->deleted_at !== null) {
            return response()->json([
                "error" => "Cette citation a été supprimée.",
            ], 410);
        }

        $favorite = Favorie::create([
            'quote_id' => $request->quote_id,
            'user_id'  => $user->id,
        ]);

        $favorite->load(['quote.tags', 'quote.categories', 'quote.user']);

        return response()->json([
            "success"  => true,
            "message"  => "Vous avez ajouté une citation à vos favoris.",
            "citation" => [
                "id"         => $favorite->quote->id,
                "content"    => $favorite->quote->content,
                "user_id"    => $favorite->quote->user->name,
                "popularite" => $favorite->quote->popularite,
                "tags"       => $favorite->quote->tags->pluck('name'),
                "categories" => $favorite->quote->categories->pluck('name'),
            ],
        ], 201);
    }

    /** ********************************************************************************************************************
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $user     = auth()->user();
        $citation = Quote::findOrFail($id);

        if (! $user->hasRole('Admin') && $user->id !== $citation->user_id) {
            return response()->json(['message' => 'Accès refusé, Vouz n\'avez pas l\'accès de voir les Favories des quotes des autres auteurs'], 403);
        }

        $likes = Favorie::where('quote_id', $id)
            ->with(['user', 'quote'])
            ->get();

        if ($likes->isEmpty()) {
            return response()->json([
                'message' => 'Aucun user ajoute cette citation dans leur favories.',
            ], 404);
        }

        $formattedLikes = $likes->map(function ($like) {
            return [
                'user préféré' => $like->user->name . '  -  ' . $like->user->email,
            ];
        });

        return response()->json([
            'quote'       => $citation->content,
            'Favorie de ' => $formattedLikes,
        ], 200);
    }

    /** ********************************************************************************************************************
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /** ********************************************************************************************************************
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth()->user();
        $like = Favorie::where('user_id', $user->id)->where('quote_id', $id)->first();
        $like->delete();
        return response()->json([
            "success" => true,
            "message" => "Vous avez retiré un quote dans votre favories.",
        ], 200);

    }
}
