<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Site;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * GET /sites/{siteId}/posts
     */
    public function index($siteId)
    {
        $site = Site::findOrFail($siteId);
        return response()->json($site->posts);
    }

    /**
     * POST /sites/{siteId}/posts
     */
    public function store(Request $request, $siteId)
    {
        $site = Site::findOrFail($siteId);

        $validated = $request->validate([
            'name'                     => 'required|string',
            'morning_guards_required'  => 'required|integer|min:0',
            'night_guards_required'    => 'required|integer|min:0',
        ]);

        $post = $site->posts()->create([
            ...$validated,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Post created successfully',
            'data' => $post
        ]);
    }

    /**
     * GET /posts/{id}
     */
    public function show($id)
    {
        $post = Post::findOrFail($id);
        return response()->json($post);
    }

    /**
     * PUT /posts/{id}
     */
    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'name'                     => 'sometimes|string',
            'morning_guards_required'  => 'sometimes|integer|min:0',
            'night_guards_required'    => 'sometimes|integer|min:0',
            'status'                   => 'sometimes|in:active,inactive',
        ]);

        $post->update($validated);

        return response()->json([
            'message' => 'Post updated successfully',
            'data' => $post
        ]);
    }

    /**
     * DELETE /posts/{id}
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully'
        ]);
    }
}