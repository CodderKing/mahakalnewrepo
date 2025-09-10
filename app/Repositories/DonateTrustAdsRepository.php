<?php

namespace App\Repositories;

use App\Contracts\Repositories\DonateTrustAdsRepositoryInterface;
use App\Models\DonateAds;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class DonateTrustAdsRepository implements DonateTrustAdsRepositoryInterface
{
    public function __construct(
        private readonly DonateAds  $donateAds,
    ) {}

    public function add(array $data): string|object
    {
        return $this->donateAds->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->donateAds->withoutGlobalScope('translate')->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->donateAds->with($relations)
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                return $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function getListWhere(array $orderBy = [], string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->donateAds->with($relations)
            ->when($searchValue, function ($query) use ($searchValue) {
                $query->where('name', 'like', "%$searchValue%")->orWhere('id', $searchValue);
                $query->orWhere('ads_id', 'like', "%$searchValue%");
                $query->orWhereHas('category', function ($q) use ($searchValue) {
                    $q->where('name', 'like', "%$searchValue%");
                });
                $query->orWhereHas('Trusts', function ($q) use ($searchValue) {
                    $q->where('trust_name', 'like', "%$searchValue%");
                });
                $query->orWhereHas('Purpose', function ($q) use ($searchValue) {
                    $q->where('name', 'like', "%$searchValue%");
                });
            })

            ->when(isset($filters['status']), function ($query) use ($filters) {
                return $query->where('status', $filters['status']);
            })
            ->when(isset($filters['is_approve']), function ($query) use ($filters) {
                return $query->where('is_approve', $filters['is_approve']);
            })
            ->when(isset($filters['type']), function ($query) use ($filters) {
                return $query->where('type', $filters['type']);
            })
            ->when(isset($filters['trust_id']), function ($query) use ($filters) {
                return $query->where('trust_id', $filters['trust_id']);
            })
            ->when(isset($filters['purpura_id']), function ($query) use ($filters) {
                return $query->where('purpose_id', $filters['purpura_id']);
            })
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        $filters += ['searchValue' => $searchValue];
        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit)->appends($filters);
    }

    public function sendMails($email, $subject, $message)
    {
        try {
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function update(string $id, array $data): bool
    {
        return $this->donateAds->where('id', $id)->update($data);
    }


    public function delete(array $params): bool
    {
        $this->donateAds->where($params)->delete();
        return true;
    }
}
