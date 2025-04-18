<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;

class SoftdeleteController extends Controller
{
    /** ********************************************************************************************************************
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // if ($request->user()->hasPermissionTo('view All quotes deleted')) {

            $deleted = Quote::onlyTrashed()->with(['tags:name', 'categories:name', 'user'])->get();

            $formattedeleted = $deleted->map(function ($citation) {
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

            return response()->json($formattedeleted);
        // }
        // return response()->json([
        //     "message" => "Vous n'avez pas l'accès de voir les citations supprimer",
        // ], 403);
    }

    /** ********************************************************************************************************************
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /** ********************************************************************************************************************
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()->hasPermissionTo('view quote deleted')) {

            $citation = Quote::onlyTrashed()->with(['tags:name', 'categories:name', 'user'])->findOrFail($id);

            $citation->increment('popularite');

            return response()->json([
                "success"  => true,
                "citation" => [
                    "id"         => $citation->id,
                    "content"    => $citation->content,
                    "user_id"    => $citation->user->name,
                    "popularite" => $citation->popularite,
                    "deleted at" => $citation->deleted_at,
                    "tags"       => $citation->tags->pluck('name'),
                    "categories" => $citation->categories->pluck('name'),
                ],
            ], 200);
        }
        return response()->json([
            "message" => "Vous n'avez pas l'accès de voir un citation supprimer",
        ], 403);
    }

    /** ********************************************************************************************************************
     * Update the specified resource in storage.
     */
    public function restore(Request $request, string $id)
    {
        if ($request->user()->hasPermissionTo('restore quote')) {

            $citation = Quote::withTrashed()->where('id', $id)->restore();
            return response()->json([
                "message" => "Vous avez restouré la citaion avec succès.",
                "quote"   => $citation,
            ], 200);
        }
        return response()->json([
            "message" => "Vous n'avez pas l'accès de restaurer les citations",
        ], 403);

    }

    /** ********************************************************************************************************************
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if ($request->user()->hasPermissionTo('delete definitly quote')) {
            $citation = Quote::withTrashed()->where('id', $id)->forceDelete();
            return response()->json([
                "message" => "Vous avez supprimer définitivement la citaion avec succès.",
                "quote"   => $citation,
            ], 200);
        }
        return response()->json([
            "message" => "Vous n'avez pas l'accès de supprimer définitivement les citations",
        ], 403);
    }
}
