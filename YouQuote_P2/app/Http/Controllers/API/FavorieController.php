<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorie;
use App\Models\Like;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavorieController extends Controller
{
    /** ********************************************************************************************************************
     * Display a listing of the resource.
     */
    //  ********************************************************************************************************************
    private function isLiked(string $user_id, string $quote_id): bool
    {
        return Like::where('user_id', $user_id)
            ->where('quote_id', $quote_id)
            ->exists();
    }

    //  ********************************************************************************************************************
    public function index(string $id)
    {
        $myFavorites = Favorie::where('user_id', $id)
            ->with(['quote' => function ($query) {
                $query->with([
                    'tags:id,name',
                    'categories:id,name',
                    'user:id,name',
                    'likes'     => function ($q) {
                        $q->select('quote_id')
                            ->selectRaw('count(*) as total_likes')
                            ->groupBy('quote_id');
                    },
                    'favorites' => function ($q) {
                        $q->select('quote_id')
                            ->selectRaw('count(*) as total_favorites')
                            ->groupBy('quote_id');
                    },
                ]);
            }])
            ->get();

        if ($myFavorites->isEmpty()) {
            return response()->json([
                'message' => 'Aucun favori trouvé pour cet utilisateur.',
                'data'    => [],
                'meta'    => [
                    'count'  => 0,
                    'status' => 'success',
                ],
            ], 200);
        }

        $formattedMyFavorites = $myFavorites->map(function ($favorie) use ($id) {
            if (! $favorie->quote) {
                return null;
            }

            return [
                "citation"  => [
                    "id"              => $favorie->quote->id,
                    "content"         => $favorie->quote->content,
                    "user"            => optional($favorie->quote->user)->name ?? 'Utilisateur inconnu',
                    "user_id"         => optional($favorie->quote->user)->id,
                    "popularite"      => $favorie->quote->popularite,
                    "tags"            => $favorie->quote->tags->pluck('name')->toArray(),
                    "categories"      => $favorie->quote->categories->pluck('name')->toArray(),
                    "likes_count"     => $favorie->quote->likes->first()->total_likes ?? 0,
                    "favorites_count" => $favorie->quote->favorites->first()->total_favorites ?? 0,
                    "is_liked"        => $this->isLiked($id, $favorie->quote->id),
                ],
                "favori_id" => $favorie->id,
                "added_at"  => $favorie->created_at->toISOString(),
            ];
        })->filter()->values();

        return response()->json([
            'data' => $formattedMyFavorites,
            'meta' => [
                'count'  => $formattedMyFavorites->count(),
                'status' => 'success',
            ],
        ], 200);
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

        $existingLike = Favorie::where('user_id', $user->id)
            ->where('quote_id', $quote_id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();

            return response()->json([
                "success"   => true,
                "message"   => "Vous avez retiré votre like de cette citation.",
                "favorites" => false,
                "citation"  => [
                    "id"         => $citation->id,
                    "content"    => $citation->content,
                    "user_id"    => $citation->user->name,
                    "popularite" => $citation->popularite,
                    "tags"       => $citation->tags->pluck('name'),
                    "categories" => $citation->categories->pluck('name'),
                ],
            ], 200);
        }

        $like = Favorie::create([
            'quote_id' => $quote_id,
            'user_id'  => $user->id,
        ]);

        $like->load(['quote.tags', 'quote.categories', 'quote.user']);

        return response()->json([
            "success"   => true,
            "message"   => "Vous avez aimé une citation.",
            "favorites" => true,
            "citation"  => [
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

        $myFavorites = Favorie::where('quote_id', $id)
            ->with(['quote' => function ($query) {
                $query->with([
                    'user:id,name,email',
                ]);
            }])
            ->get();

        if ($myFavorites->isEmpty()) {
            return response()->json([
                'message' => 'Aucun favori trouvé pour cet utilisateur.',
                'data'    => [],
                'meta'    => [
                    'count'  => 0,
                    'status' => 'success',
                ],
            ], 200);
        }

        $formattedMyFavorites = $myFavorites->map(function ($favorie) use ($id) {
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
                "favori_id" => $favorie->id,
                "added_at"  => $favorie->created_at->toISOString(),
            ];
        })->filter()->values();

        return response()->json([
            'data' => $formattedMyFavorites,
            'meta' => [
                'count'  => $formattedMyFavorites->count(),
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
    public function destroy(Request $request, string $id)
    {
        $user_id = $request->user_id;
        $user    = User::findOrFail($user_id);

        $favori = Favorie::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $favori) {
            return response()->json([
                "success" => false,
                "message" => "Favori non trouvé ou vous n'avez pas la permission.",
            ], 404);
        }

        $favori->delete();

        return response()->json([
            "success"   => true,
            "message"   => "Vous avez retiré une citation de vos favoris.",
            "favori_id" => $id,
        ], 200);
    }
}
