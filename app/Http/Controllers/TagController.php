<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Hotel;
use Illuminate\Http\Request;

class TagController extends Controller
{
    // GET /api/tags
    public function index()
    {
        return response()->json(Tag::all(['id', 'name', 'description']));
    }

    // POST /api/tags
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Tag::create($data);

        return response()->json(['message' => 'Tag created']);
    }

    // POST /api/hotels/{id}/tags
    public function attachToHotel(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);

        $data = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        $hotel->tags()->syncWithoutDetaching($data['tag_ids']);

        return response()->json(['message' => 'Tags attached']);
    }
}
