<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Quote;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuoteController extends Controller
{
    /** ************************************************************************************************************************
     * Display a listing of the resource.
     */

    public function index()
    {
        // Chargement des relations avec une syntaxe plus propre
        $quotes = Quote::with(['tags:id,name', 'categories:id,name', 'user:id,name'])
            ->where("is_valide", true)
            ->get();

        // Transformation des données avec une syntaxe plus concise
        $formattedQuotes = $quotes->map(function ($quote) {
            return [
                "id"         => $quote->id,
                "content"    => $quote->content,
                "author"     => $quote->user->name,
                "popularity" => $quote->popularite,
                "is_valide"  => $quote->is_valide,
                "is_deleted" => ! is_null($quote->deleted_at),
                "tags"       => $quote->tags->pluck('name')->all(),
                "categories" => $quote->categories->pluck('name')->all(),
                "created_at" => $quote->created_at->toISOString(),
                "updated_at" => $quote->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'data' => $formattedQuotes,
            'meta' => [
                'count'  => $quotes->count(),
                'status' => 'success',
            ],
        ]);
    }
    /** ************************************************************************************************************************
     * Display a listing of the resource.
     */

    public function quoteNonValide()
    {
        // Chargement des relations avec une syntaxe plus propre
        $quotes = Quote::with(['tags:id,name', 'categories:id,name', 'user:id,name'])
            ->where("is_valide", false)
            ->get();

        // Transformation des données avec une syntaxe plus concise
        $formattedQuotes = $quotes->map(function ($quote) {
            return [
                "id"         => $quote->id,
                "content"    => $quote->content,
                "author"     => $quote->user->name,
                "popularity" => $quote->popularite,
                "is_valide"  => $quote->is_valide,
                "is_deleted" => ! is_null($quote->deleted_at),
                "tags"       => $quote->tags->pluck('name')->all(),
                "categories" => $quote->categories->pluck('name')->all(),
                "created_at" => $quote->created_at->toISOString(),
                "updated_at" => $quote->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'data' => $formattedQuotes,
            'meta' => [
                'count'  => $quotes->count(),
                'status' => 'success',
            ],
        ]);
    }

    /** ************************************************************************************************************************
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // if ($request->user()->hasPermissionTo('create quote')) {

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
            'user_id'  => $request->user_id,
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
        // }

        // return response()->json([
        //     "message" => "Vous n'avez pas l'accès pour créer des nouvelles citations",
        // ], 403);
    }

    /** ************************************************************************************************************************
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $citation = Quote::with(['tags:name', 'categories:name', 'user'])->findOrFail($id);

        $citation->increment('popularite');

        return response()->json([
            // "success"  => true,
            // "citation" => [
            "id"         => $citation->id,
            "content"    => $citation->content,
            "user"       => $citation->user->name,
            "created_at" => date_format($citation->created_at, 'd M Y'),
            "popularite" => $citation->popularite,
            "tags"       => $citation->tags->pluck('name'),
            "categories" => $citation->categories->pluck('name'),
            // ],
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

        $role    = $request->role;
        $user_id = $request->user_id;

        $citation = Quote::findOrFail($id);

        if (! $role === "Admin" || $user_id !== $citation->user_id) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $citation->delete();
        return response()->json(['message' => 'Suppression réussie']);
    }

    // ************************************************************************************************************************
    // valider les quotes récement créer
    public function validateQuote(Request $request, string $id)
    {
        // $user = $request->user();
        $role    = $request->role;
        $user_id = $request->user_id;


        $citation = Quote::find($id);

        if (! $citation) {
            return response()->json([
                'message' => 'Citation non trouvée.',
            ], 404);
        }

        if ($role === "Admin") {
        $citation->is_valide = true;
        $citation->save();

        return response()->json([
            'message'  => 'Vous avez validé la citation.',
            'citation' => $citation,
        ], 200);
        }

        return response()->json([
            'message' => 'Vous n\'avez pas le droit de valider la citation.',
        ], 403);
    }

    // ************************************************************************************************************************
    public function searchByCategory(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $categoryId = $request->category_id;

        $quotes = Quote::whereHas('categories', function ($query) use ($categoryId) {
            $query->where('categories.id', $categoryId);
        })->with(['tags', 'categories'])
            ->get();

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

        return response()->json([
            'message' => 'Les Citations avec le category :   ' . $categoryId . '  -  ' . Category::find($categoryId)->name,
            'quotes'  => $citations,
        ]);
    }

    // ************************************************************************************************************************
    public function searchByTag(Request $request)
    {
        $request->validate([
            'tag_id' => 'required|integer|exists:tags,id',
        ]);

        $tagId = $request->tag_id;

        $quotes = Quote::whereHas('tags', function ($query) use ($tagId) {
            $query->where('tags.id', $tagId);
        })->with(['tags', 'categories'])
            ->get();

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

        return response()->json([
            'message' => 'Les Citations avec le tag :   ' . $tagId . '  -  ' . Tag::find($tagId)->name,
            'quotes'  => $citations,
        ]);
    }

    // ***************************************************************************************************************************
    public function random(Request $request)
    {
        try {
            $count = $request->route('count', 1);

            if (! is_numeric($count) || $count < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le paramètre count doit être un nombre supérieur ou égal à 1',
                    'data'    => null,
                ], 400);
            }

            $citations = Quote::with(['user', 'tags', 'categories'])
                ->inRandomOrder()
                ->take((int) $count)
                ->get();

            if ($citations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune citation trouvée',
                    'data'    => null,
                ], 404);
            }

            $formattedCitations = $citations->map(function ($quote) {
                return [
                    'id'         => $quote->id,
                    'content'    => $quote->content,
                    'user'       => $quote->user ? $quote->user->name : null,
                    'popularite' => $quote->popularite,
                    'created_at' => $quote->created_at->format('Y-m-d H:i:s'),
                    'tags'       => $quote->tags->pluck('name'),
                    'categories' => $quote->categories->pluck('name'),
                    // 'likes_count' => $quote->likes_count ?? 0,
                    // 'views_count' => $quote->views_count ?? 0,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => $count == 1 ? 'Citation aléatoire récupérée' : 'Citations aléatoires récupérées',
                'count'   => $citations->count(),
                'data'    => $count == 1 ? $formattedCitations->first() : $formattedCitations,
            ]);

        } catch (\Exception $e) {
            // Log de l'erreur
            \Log::error('Erreur dans QuoteController@random: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Une erreur interne est survenue',
                'error'   => config('app.debug') ? $e->getMessage() : null,
                'data'    => null,
            ], 500);
        }
    }

    // ***************************************************************************************************************************

    public function filterByLength(Request $request)
    {
        try {
            $min = $request->input('min') ? $request->input('min') : 0;
            $max = $request->input('max') ? $request->input('max') : 1000;

            $citations = Quote::where('nbr_mots', ">=", $min)->where('nbr_mots', "<=", $max)->get();

            if ($citations->isEmpty()) {
                return response()->json(['message' => 'Aucune citation trouvée'], 404);
            }

            return response()->json($citations, 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erreur interne du serveur',
            ], 500);
        }
    }

    // ***************************************************************************************************************************

    //  public function popularite()
    //  {
    //     $quotes = Quote::orderBy('popularite', 'desc')->take(6)->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $quotes
    //     ]);
    //  }

    public function popularite()
    {
        $quotes = Quote::with(['user', 'categories', 'tags'])
            ->orderBy('popularite', 'desc')
            ->take(6)
            ->get()
            ->map(function ($quote) {
                return [
                    'id'         => $quote->id,
                    'content'    => $quote->content,
                    'popularite' => $quote->popularite,
                    'user'       => $quote->user ? $quote->user->name : null,
                    'categories' => $quote->categories->pluck('name'),
                    'tags'       => $quote->tags->pluck('name'),
                    'created_at' => $quote->created_at,
                    'updated_at' => $quote->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $quotes,
        ]);
    }

    // ***************************************************************************************************************************

}
