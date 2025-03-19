<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LikeController extends Controller
{
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
            return [
                "citation" => [
                    "id"         => $like->quote->id,
                    "content"    => $like->quote->content,
                    "user_id"    => $like->quote->user->name,
                    "popularite" => $like->quote->popularite,
                    "tags"       => $like->quote->tags->pluck('name'),
                    "categories" => $like->quote->categories->pluck('name'),
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

        $validator = Validator::make($request->all(), [
            "quote_id" => "exists:categories,id",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "error" => $validator->errors()->first(),
            ], 400);
        }

        $like = Like::create([
            'quote_id' => $request->quote_id,
            'user_id'  => $request->user()->id,
        ]);

        return response()->json([
            "success"  => true,
            "message"  => "Vous avez aimé une citation .",
            "citation" => $like->load("quote"),
        ], 201);
    }

    /** ********************************************************************************************************************
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
