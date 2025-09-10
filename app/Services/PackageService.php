<?php

namespace App\Services;

use Illuminate\Support\Str;

use App\Traits\FileManagerTrait;

class PackageService
{
    use FileManagerTrait;

    public function getSlug(object $request): string
    {
        return Str::slug($request['title'][array_search('en', $request['lang'])]);
    }

    public function getAddData(object $request): array
    {
        return [
            'title' => $request['title'][array_search('en', $request['lang'])],
            'slug' => $this->getSlug($request),
            'person' => $request['person'],
            'color' => $request['color'],
            'description' => $request['description'][array_search('en', $request['lang'])]
        ];
    }

    public function getUpdateData(object $request, object $data): array
    {
        return [
            'title' => $request['title'][array_search('en', $request['lang'])],
            'person' => $request['person'],
            'color' => $request['color'],
            'description' => $request['description'][array_search('en', $request['lang'])]
        ];
    }
}