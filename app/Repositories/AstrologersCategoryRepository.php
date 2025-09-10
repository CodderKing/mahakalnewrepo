<?php
namespace App\Repositories;

use App\Contracts\Repositories\AstrologersCategoryRepositoryInterface;
// use App\Contracts\Repositories\AstrologersRepositoryInterface;
use App\Models\Translation;
use App\Models\AstrologerCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class AstrologersCategoryRepository implements AstrologersCategoryRepositoryInterface {

    public function __construct(
        private readonly AstrologerCategory       $categorydata,
        private readonly Translation    $translation
    )
    {
    }


    public function add(array $data): string|object
    {
        return $this->categorydata->create($data);
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->categorydata;

        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit);
    }


    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->categorydata->where($params)->withoutGlobalScopes()->first();
    }


    public function getListWhere(array $orderBy = [], ?string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->categorydata->with($relations)
            ->where($filters)
            ->when(isset($searchValue), function ($query) use ($searchValue) {
                $translation_ids = $this->translation->where('translationable_type', 'App\Models\Astrologer\Skills')
                    ->where('key', 'name')
                    ->where(function ($q) use ($searchValue) {
                        $q->orWhere('value', 'like', "%$searchValue%");
                    })->pluck('translationable_id');
                $query->where('name', 'like', "%$searchValue%")->orWhereIn('id', $translation_ids);
            })
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                return $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        $filters += ['searchValue' =>$searchValue];
        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit)->appends($filters);
    }

    public function update(string $id, array $data): bool
    {
        return $this->categorydata->where('id', $id)->update($data);   
    }

    public function delete(array $params): bool
    {
        $categorydata = $this->categorydata->where(['id'=>$params['id']])->delete();
        $this->translation->where('translationable_type', 'App\Models\Astrologer\Skills')->where('translationable_id', $params['id'])->delete();
        $this->categorydata->where('id', $params['id'])->delete();
        return true;
    }
}