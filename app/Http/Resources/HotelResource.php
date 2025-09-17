<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "authorId" => $this->author_id,
            "date" => $this->created_at?->format('M d, Y'),
            "href" => "/stay-detail", // FE cần thì hardcode / hoặc sinh từ slug
            "listingCategoryId" => $this->listing_category_id,
            "title" => $this->title,
            "featuredImage" => $this->featured_image,
            "galleryImgs" => $this->gallery_images ?? [], // nếu có table/field khác thì map ra
            "description" => $this->description,
            "commentCount" => $this->comments?->count() ?? 0,
            "viewCount" => $this->view_count ?? 0,
            "like" => method_exists($this->resource, 'isLikedBy') ? $this->resource->isLikedBy(auth()->user()) : false,
            "address" => $this->address,
            "reviewStart" => $this->reviews()->avg('rating') ?? 0,
            "reviewCount" => $this->reviews()->count(),
            // "reviewStart" => $this->reviews?->avg('rating') ?? 0,
            // "reviewCount" => $this->reviews?->count() ?? 0,
            "price" => $this->price_from,
            "maxGuests" => $this->max_guests,
            "bedrooms" => $this->bedrooms,
            "bathrooms" => $this->bathrooms,
            "saleOff" => $this->sale_off ? "-{$this->sale_off}% hôm nay" : null,
            "isAds" => $this->is_ads,
            "map" => [
                "lat" => $this->latitude,
                "lng" => $this->longitude,
            ],
        ];
    }
}
