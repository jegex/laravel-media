<?php

namespace Jegex\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jegex\Media\MediaCollections\Models\Media;

class VaporUploadsController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'bucket' => 'required|string',
            'name' => 'required|string',
            'content_type' => 'required|string',
            'tmp_filename' => 'required|string',
            'model_id' => 'required|integer',
            'model_type' => 'required|string',
            'collection_name' => 'nullable|string',
        ]);

        $modelClass = $request->model_type;
        $model = $modelClass::findOrFail($request->model_id);

        if (method_exists($model, 'authorizeMedia') && ! $model->authorizeMedia('create')) {
            abort(403, 'Unauthorized to add media to this resource');
        }

        if (! Gate::allows('create', [Media::class, $model])) {
            abort(403, 'Unauthorized to add media to this resource');
        }

        $media = new Media;
        $media->model_type = $modelClass;
        $media->model_id = $model->id;
        $media->collection_name = $request->collection_name ?: 'default';
        $media->name = pathinfo($request->name, PATHINFO_FILENAME);
        $media->file_name = $request->name;
        $media->mime_type = $request->content_type;
        $media->disk = config('media.disk_name');
        $media->size = 0;
        $media->uuid = Str::uuid()->toString();

        $media->save();

        return response()->json([
            'media' => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'url' => $media->getUrl(),
            ],
        ], 201);
    }

    public function markAsFinished(int $mediaId): JsonResponse
    {
        $media = Media::findOrFail($mediaId);

        if ($media->model && method_exists($media->model, 'authorizeMedia') && ! $media->model->authorizeMedia('update')) {
            abort(403, 'Unauthorized to modify media on this resource');
        }

        if (! Gate::allows('update', $media)) {
            abort(403, 'Unauthorized to modify this media');
        }

        $media->touch();

        return response()->json([
            'success' => true,
        ]);
    }

    public function getUploadParameters(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'content_type' => 'required|string',
        ]);

        $disk = Storage::disk(config('media.disk_name'));
        $key = config('media.prefix', '').'/'.Str::uuid()->toString().'/'.$request->name;

        return response()->json([
            'key' => $key,
            'url' => $disk->url($key),
            'bucket' => config('filesystems.disks.s3.bucket', ''),
            'name' => $request->name,
            'content_type' => $request->content_type,
            'acl' => 'private',
        ]);
    }
}
