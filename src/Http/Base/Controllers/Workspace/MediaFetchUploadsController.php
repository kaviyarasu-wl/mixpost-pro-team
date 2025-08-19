<?php

namespace Inovector\Mixpost\Http\Base\Controllers\Workspace;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Http\Base\Resources\MediaResource;
use Inovector\Mixpost\Models\Media;

class MediaFetchUploadsController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $query = Media::query();
        
        // Apply type filtering
        $type = $request->get('type', 'all');
        
        switch ($type) {
            case 'uploaded':
                // Filter media with no source AND no tenor_id (locally uploaded)
                $query->where(function ($q) {
                    $q->whereNull('data')
                      ->orWhere(function ($subQ) {
                          $subQ->whereRaw("JSON_EXTRACT(data, '$.source') IS NULL")
                               ->whereRaw("JSON_EXTRACT(data, '$.tenor_id') IS NULL");
                      });
                });
                break;
                
            case 'stock':
                // Filter media from Unsplash
                $query->whereJsonContains('data->source', 'Unsplash');
                break;
                
            case 'gifs':
                // Filter media with tenor_id
                $query->whereNotNull('data->tenor_id');
                break;
                
            case 'gravity_write':
                // Filter media from GravityWrite
                $query->whereJsonContains('data->source', 'GravityWrite');
                break;
                
            case 'all':
            default:
                // No filtering, return all media
                break;
        }
        
        // Apply keyword search if provided
        if ($keyword = $request->get('keyword')) {
            $query->where('name', 'like', "%{$keyword}%");
        }
        
        $records = $query->latest('created_at')->simplePaginate(30);

        return MediaResource::collection($records);
    }
}
