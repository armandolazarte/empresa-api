<?php

namespace App\Http\Controllers\CommonLaravel;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CommonLaravel\Helpers\GeneralHelper;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{

    function savePreImage(Request $request) {
        $file_headers = get_headers($request->image_url);
        if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return response()->json(['image_saved' => false], 200);
        }
        $name = time().rand(1, 100000).'.webp';
        Storage::disk('public')->put($name, file_get_contents($request->image_url));
        if (env('APP_ENV') == 'local') {
            $name = '/storage/'.$name;
        } else {
            $name = '/public/storage/'.$name;
        }
        return response()->json(['image_saved' => true, 'image_url' => $name], 201);
    }

    function setImage(Request $request, $prop_name) {
        $model_name = GeneralHelper::getModelName($request->model_name);
        
        $name = Storage::disk('public')->put('', $request->image_url);

        if (env('APP_ENV') == 'local') {
            $name = env('APP_URL').'/storage/'.$name;
        } else {
            $name = env('APP_URL').'/public/storage/'.$name;
        }

        $model = $model_name::find($request->id);
        if ($prop_name == 'has_many') {
            $image = Image::create([
                env('IMAGE_URL_PROP_NAME', 'image_url')     => $name,
                'imageable_id'                              => $model->id,
                'imageable_type'                            => $request->model_name,
            ]);
        } else {
            $this->deleteImageProp($request->model_name, $request->id, $prop_name);
            $model->{$prop_name} = $name;
            $model->save();
        }
        
        return response()->json(['model' => $this->fullModel($request->model_name, $request->id)], 200);
    }

    function deleteImageProp($_model_name, $id, $prop_name = 'image_url') {
        $model_name = GeneralHelper::getModelName($_model_name);
        $model = $model_name::find($id);
        if (!is_null($model->{$prop_name})) {
            Self::deleteImage($model->{$prop_name});
            // $storage_name = explode('/', $model->{$prop_name});
            // $storage_name = $storage_name[count($storage_name)-1];
            // Storage::disk('public')->delete($storage_name);
        }
        $model->{$prop_name} = null;
        $model->save();
        return response()->json(['model' => $this->fullModel($_model_name, $id)], 200);
    }

    function deleteImageModel($model_name, $model_id, $image_id) {
        $image = Image::find($image_id);
        Storage::disk('public')->delete($image->image_url);
        $image->delete();
        return response()->json(['model' => $this->fullModel($model_name, $model_id)], 200);
    }

    static function deleteModelImages($model) {
        foreach ($model->getAttributes() as $prop => $_prop) {
            if (substr($prop, 0, 4) == 'foto' || substr($model->{$prop}, 0, 5) == 'image') {
                Self::deleteImage($model->{$prop});
            }
        }
    }

    static function deleteImage($prop_value) {
        $storage_name = explode('/', $prop_value);
        $storage_name = $storage_name[count($storage_name)-1];
        Storage::disk('public')->delete($storage_name);
    }
}
