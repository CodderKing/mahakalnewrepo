<?php

namespace App\Services;

use Illuminate\Support\Str;

use App\Traits\FileManagerTrait;

class BhagwanService
{
    use FileManagerTrait;


    public function getProcessedImages(object $request): array
    {
        $imageNames = [];
        if ($request->file('images')) {
            foreach ($request->file('images') as $image) {
                $images = $this->upload(dir: 'bhagwan/', format: 'webp', image: $image);
                $imageNames[] = $images;
                if ($request->has('images_active') && $request->has('images') && count($request['images']) > 0) {
                    $imageNames[] = [
                        'image_name' => $images,
                    ];
                }
            }
        }
        return [
            'image_names' => $imageNames ?? []
        ];
    }

    public function getProcessedUpdateImages(object $request, object $service): array
    {
        $serviceImages = json_decode($service->images);
        $colorImageArray = [];
        if ($request->has('images_active') && $request->has('images') && count($request->images) > 0) {
            $dbColorImage = $service->images ? json_decode($service->images, true) : [];
            if (!$dbColorImage) {
                foreach ($serviceImages as $image) {
                    $dbColorImage[] = [
                        'image_name' => $image,
                    ];
                }
            }
        }

        if ($request->file('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = $this->upload(dir: 'bhagwan/', format: 'webp', image: $image);
                $serviceImages[] = $imageName;
                if ($request->has('images_active') && $request->has('images') && count($request->images) > 0) {
                    $colorImageArray[] = [
                        'image_name' => $imageName,
                    ];
                }
            }
        }

        return [
            'image_names' => $serviceImages ?? []
        ];
    }

    public function getAddData(object $request): array
    {
        $processedImages = $this->getProcessedImages(request: $request);

        return [
            'week' => $request->input('week'),
            'name' => $request['name'][array_search('en', $request['lang'])],
            'images' => json_encode($processedImages['image_names']),
            'thumbnail' => $this->upload(dir: 'bhagwan/thumbnail/', format: 'png', image: $request['image']),
            'status' => 1,
        ];
    }

    public function getUpdateData(object $request, object $data): array
    {
        $processedImages = $this->getProcessedUpdateImages(request: $request, service: $data);

        $dataArray = [
            'week' => $request->input('week'),
            'name' => $request->name[array_search('en', $request['lang'])],
            'images' => json_encode($processedImages['image_names']),
        ];

        if ($request->hasFile('image')) {
            $dataArray['thumbnail'] = $this->update(
                dir: 'bhagwan/thumbnail/',
                oldImage: $data['thumbnail'],
                format: 'webp',
                image: $request->file('image'),
                fileType: 'image'
            );
        }

        return $dataArray;
    }


    public function deleteImages(object $service): bool
    {
        if (!is_null($service['images'])) {
            $images = json_decode($service['images']);
            if (!is_null($images)) {
                foreach ($images as $image) {
                    $this->delete(filePath: '/bhagwan/' . $image);
                }
            }
        }
        $this->delete(filePath: '/bhagwan/thumbnail/' . $service['thumbnail']);
        return true;
    }

    public function deleteImage(object $request, object $service): array
    {
        $existingImages = json_decode($service['images'], true);
        $updatedImages = [];

        foreach ($existingImages as $image) {
            if ($image != $request['name']) {
                $updatedImages[] = $image;
            } else {

                $this->delete(filePath: 'bhagwan/' . $image);
            }
        }
        return [
            'images' => $updatedImages
        ];
    }

    public function deleteAllImage(object $data): bool
    {
        if (!empty($data['images'])) {
            $images = json_decode($data['images'], true);
            if (is_array($images)) {
                foreach ($images as $image) {
                    $this->delete('bhagwan/' . $image);
                }
            } else {
                $this->delete('bhagwan/' . $data['images']);
            }
        }

        if (!empty($data['thumbnail'])) {
            $this->delete('bhagwan/thumbnail/' . $data['thumbnail']);
        }

        if (!empty($data['event_image'])) {
            $this->delete('bhagwan/event-img/' . $data['event_image']);
        }

        return true;
    }
}