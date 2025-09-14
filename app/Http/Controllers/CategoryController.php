<?php

namespace App\Http\Controllers;

use App\Models\ListingCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // GET /api/categories
    public function index()
    {
        return response()->json(ListingCategory::all(['id', 'name', 'description']));
    }

    // POST /api/categories
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        ListingCategory::create($data);

        return response()->json(['message' => 'Category created']);
    }
}
