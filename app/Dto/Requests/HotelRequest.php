<?php

namespace App\Dto\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HotelRequest extends FormRequest
{
    public function authorize()
    {
        return true; // hoặc kiểm tra role user
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:hotels,slug,' . $this->hotel,
            'description' => 'nullable|string',
            'listing_category_id' => 'required|exists:listing_categories,id',
            'price_from' => 'required|numeric|min:0',
            'address' => 'nullable|string',
            'location_city' => 'nullable|string',
            'location_country' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'featured_image' => 'nullable|url',
            'max_guests' => 'nullable|integer|min:1',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'sale_off' => 'nullable|numeric|min:0',
            'is_ads' => 'nullable|boolean',
        ];
    }
}
