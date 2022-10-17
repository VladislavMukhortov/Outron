<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Media;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    public function remove(Hotel $hotel, Media $media): JsonResponse
    {
        $model = $media->model_type::find($media->model_id);
        $model->deleteMedia($media->id);

        return response()->json([
            'message' => 'Вы успешно удалили картинку.',
        ]);
    }
}
