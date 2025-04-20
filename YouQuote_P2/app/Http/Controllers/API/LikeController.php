<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LikeController extends Controller
{
    // *********************************************************************************************************************
    private function isLiked(string $user_id, string $quote_id): bool
    {
        return Like::where('user_id', $user_id)
                  ->where('quote_id', $quote_id)
                  ->exists();
    }
    /** ********************************************************************************************************************
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        $mylikes = Like::where('user_id', $user->id)
            ->with(['quote.tags', 'quote.categories', 'quote.user'])
            ->get();

        $formattedMylikes = $mylikes->map(function ($like) {
            if (! $like->quote) {
                return null;
            }

            return [
                "citation" => [
                    "id"         => $like->quote->id,
                    "content"    => $like->quote->content,
                    "user"       => $like->quote->user->name,
                    "user_id"    => $like->quote->user->id,
                    "popularite" => $like->quote->popularite,
                    "tags"       => $like->quote->tags->pluck('name'),
                    "categories" => $like->quote->categories->pluck('name'),
                    "is_liked" => isLiked($like->quote->user->id, $like->quote->id),
                ],
            ];
        });

        return response()->json($formattedMylikes);
    }

    /** ********************************************************************************************************************
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user_id  = $request->user_id;
        $user     = User::findOrFail($user_id);
        $quote_id = $request->quote_id;

        $validator = Validator::make($request->all(), [
            'quote_id' => [
                'required',
                'integer',
                'exists:quotes,id',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "error" => $validator->errors()->first(),
            ], 400);
        }

        $citation = Quote::withTrashed()->find($quote_id);

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

        // Vérifier si l'utilisateur a déjà liké cette citation
        $existingLike = Like::where('user_id', $user->id)
            ->where('quote_id', $quote_id)
            ->first();

        if ($existingLike) {
            // Supprimer le like existant (unlike)
            $existingLike->delete();

            return response()->json([
                "success"  => true,
                "message"  => "Vous avez retiré votre like de cette citation.",
                "liked"    => false,
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

        // Créer un nouveau like
        $like = Like::create([
            'quote_id' => $quote_id,
            'user_id'  => $user->id,
        ]);

        $like->load(['quote.tags', 'quote.categories', 'quote.user']);

        return response()->json([
            "success"  => true,
            "message"  => "Vous avez aimé une citation.",
            "liked"    => true,
            "citation" => [
                "id"         => $like->quote->id,
                "content"    => $like->quote->content,
                "user_id"    => $like->quote->user->name,
                "popularite" => $like->quote->popularite,
                "tags"       => $like->quote->tags->pluck('name'),
                "categories" => $like->quote->categories->pluck('name'),
            ],
        ], 201);
    }

    /** ********************************************************************************************************************
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $Likes = Like::where('quote_id', $id)
            ->with(['quote' => function ($query) {
                $query->with([
                    'user:id,name,email',
                ]);
            }])
            ->get();

        if ($Likes->isEmpty()) {
            return response()->json([
                'message' => 'Aucun favori trouvé pour cet utilisateur.',
                'data'    => [],
                'meta'    => [
                    'count'  => 0,
                    'status' => 'success',
                ],
            ], 200);
        }

        $formattedLikes = $Likes->map(function ($favorie) use ($id) {
            if (! $favorie->quote) {
                return null;
            }

            return [
                "citation"  => [
                    "id"      => $favorie->quote->id,
                    "user_name"    => optional($favorie->quote->user)->name ?? 'Utilisateur inconnu',
                    "user_email"    => optional($favorie->quote->user)->email ?? 'Utilisateur inconnu',
                    "user_id" => optional($favorie->quote->user)->id,
                ],
                "like_id" => $favorie->id,
                "added_at"  => $favorie->created_at->toISOString(),
            ];
        })->filter()->values();

        return response()->json([
            'data' => $formattedLikes,
            'meta' => [
                'count'  => $formattedLikes->count(),
                'status' => 'success',
            ],
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
        $like = Like::where('user_id', $user->id)->where('quote_id', $id)->first();
        $like->delete();
        return response()->json([
            "success" => true,
            "message" => "Vous avez retiré un like sur un citation.",
        ], 200);

    }
}
