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
            'listingCategoryId' => 'required|exists:listing_categories,id',
            'price' => 'required|numeric|min:0',
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

    //map
    public static function fromArray(array $data): array
    {
        return [
            'title' => $data['title'] ?? null,
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'listing_category_id' => $data['listingCategoryId'] ?? null,
            'price_from' => $data['price'] ?? null,
            'address' => $data['address'] ?? null,
            'location_city' => $data['locationCity'] ?? null,
            'location_country' => $data['locationCountry'] ?? null,
            'latitude' => $data['map']['lat'] ?? null,
            'longitude' => $data['map']['lng'] ?? null,
            'featured_image' => $data['featuredImage'] ?? null,
            'max_guests' => $data['maxGuests'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'bathrooms' => $data['bathrooms'] ?? null,
            'sale_off' => $data['saleOff'] ?? null,
            'is_ads' => $data['isAds'] ?? false,
        ];
    }
}
