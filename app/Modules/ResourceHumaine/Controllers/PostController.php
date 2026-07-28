<?php

namespace App\Modules\ResourceHumaine\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ResourceHumaine\Models\Post;
use App\Modules\ResourceHumaine\Requests\PostRequest;
use App\Modules\ResourceHumaine\Resources\PostResource;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $posts = Post::with(['employees', 'createdBy', 'updatedBy'])
            ->withCount('employees')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            PostResource::collection($posts),
            "Liste des postes chargee avec succes."
        );
    }

    public function show(Post $post)
    {
        return $this->successResponse(
            new PostResource($post->load(['employees', 'createdBy', 'updatedBy'])->loadCount('employees')),
            "Poste charge avec succes."
        );
    }

    public function store(PostRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $post = Post::create($data)->load(['createdBy', 'updatedBy'])->loadCount('employees');

        logActivity("Creation d'un nouveau poste", $post->toArray(), $post);

        return $this->successResponse(
            new PostResource($post),
            "Poste cree avec succes."
        );
    }

    public function update(PostRequest $request, Post $post)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $oldPost = $post->replicate()->fill($post->getAttributes());

        $post->update($data);
        $post->load(['createdBy', 'updatedBy'])->loadCount('employees');

        logActivity("Mise a jour d'un poste", [
            'oldPost' => $oldPost->toArray(),
            'newPost' => $post->toArray(),
        ], $post);

        return $this->successResponse(
            new PostResource($post),
            "Poste mis a jour avec succes."
        );
    }

    public function destroy(Post $post)
    {
        $post->loadCount('employees');

        logActivity("Suppression d'un poste", $post->toArray(), $post);

        $post->delete();

        return $this->noContentSuccessResponse("Poste supprime avec succes.");
    }
}
