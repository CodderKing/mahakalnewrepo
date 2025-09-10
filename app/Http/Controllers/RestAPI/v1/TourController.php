<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Contracts\Repositories\TourVisitPlaceRepositoryInterface;
use App\Contracts\Repositories\TourVisitRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\TourAndTravel;
use App\Models\TourCabManage;
use App\Models\TourDriverManage;
use App\Models\TourLeads;
use App\Models\TourOrder;
use App\Models\TourReviews;
use App\Models\TourType;
use App\Models\TourVisits;
use App\Models\User;
use App\Traits\FileManagerTrait;
use App\Utils\ImageManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Utils\Helpers;
use App\Services\TourVisitService;
use PhpParser\Node\Expr\Cast\Double;
use SimplePie\Cache\Redis;
use Illuminate\Support\Facades\DB;


class TourController extends Controller
{
    use FileManagerTrait;
    public function __construct(
        private readonly TranslationRepositoryInterface     $translationRepo,
        private readonly TourVisitRepositoryInterface  $tourtraveller,
        private readonly TourVisitPlaceRepositoryInterface  $tourvisitplac,
    ) {}

    public function dashboard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "cab_assign" => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected traveller Id is invalid or inactive.');
                }
            },]
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $arrayList = [];
        $OrderInfo = \App\Models\TourOrder::whereIn('status', [1, 0])->where('refund_status', 0)->where('amount_status', 1)->with(['accept'])->get();
        $arrayList['order'] = [
            // "all_order" => \App\Models\TourOrder::whereIn('status', [1, 0])->where('refund_status', 0)->where('amount_status', 1)->with(['accept'])->whereHas('accept', function ($subQuery) {
            //     $subQuery->where('status', 1);
            // })->count(),
            'pending_order' => \App\Models\TourOrder::whereIn('status', [1, 0])->where(['refund_status' => 0, 'pickup_status' => 0, 'amount_status' => 1, 'drop_status' => 0, 'cab_assign' => 0])
                ->where('pickup_date', '>', \Carbon\Carbon::today()->toDateString())
                ->whereHas('accept', function ($query) use ($request) {
                    $query->where('tour_order_accept.status', 1)->where('traveller_id', $request['cab_assign']);
                })->withCabOrderCheck($request->cab_assign)->with(['accept'])->count(),
            "confirm_order" => $OrderInfo->where('pickup_status', 0)->where('drop_status', 0)->where('cab_assign',  $request['cab_assign'])->count(),
            "pickup_order" => $OrderInfo->where('pickup_status', 1)->where('drop_status', 0)->where('cab_assign',  $request['cab_assign'])->count(),
            "complete_order" => $OrderInfo->where('pickup_status', 1)->where('drop_status', 1)->where('cab_assign',  $request['cab_assign'])->count(),
            'canceled' => \App\Models\TourAndTravel::where('id', $request['cab_assign'])->first()['cancel_order'],
        ];
        $arrayList['order']['all_order'] = (($arrayList['order']['pending_order'] ?? 0) + ($arrayList['order']['confirm_order'] ?? 0) + ($arrayList['order']['pickup_order'] ?? 0) + ($arrayList['order']['complete_order'] ?? 0));

        $tourInformation = \App\Models\TourAndTravel::where('id', $request['cab_assign'])->first();

        $arrayList['wallet'] = [
            "withdrawable_balance" => $tourInformation['wallet_amount'],
            "collected_balance" => $tourInformation['withdrawal_amount'],
            "pending_withdraw" => $tourInformation['withdrawal_pending_amount'],
            "total_admin_commission" => $tourInformation['admin_commission'],
            "total_tax" => $tourInformation['gst_amount'],
        ];
        return response()->json(['status' => 1, "message" => "dashboard", 'data' => $arrayList], 200);
    }

    public function AllCategory()
    {
        $cities_tour = TourType::where('status', 1)->orderBy('id', 'desc')->get();
        if (!empty($cities_tour) && count($cities_tour) > 0) {
            $translation = [];
            foreach ($cities_tour as $key => $value) {
                $hindi_tour = $value->translations()->pluck('value', 'key')->toArray();
                $translation[$key]['slug'] = $value['slug'];
                $translation[$key]['en_name'] = ($value['name'] ?? "");
                $translation[$key]['hi_name'] = ($hindi_tour['name'] ?? "");
            }
            return response()->json(['status' => 1, 'count' => count($translation), 'data' => $translation], 200);
        } else {
            return response()->json(['status' => 0, 'count' => 0, 'data' => []], 200);
        }
    }

    public function AllTour(Request $request)
    {
        $request->validate([
            'special_type' => ['required', function ($attribute, $value, $fail) {
                if (!TourType::where('slug', $value)->where('status', 1)->exists()) {
                    $fail('The selected tour type is invalid or inactive.');
                }
            },],
            "state_name" => ['nullable', function ($attribute, $value, $fail) {
                if (!TourVisits::where('state_name', $value)->where('status', 1)->exists()) {
                    $fail('The selected state name is invalid or inactive.');
                }
            },],
            "cities_name" => ['nullable', function ($attribute, $value, $fail) {
                if (!TourVisits::where('cities_name', $value)->where('status', 1)->exists()) {
                    $fail('The selected citie name is invalid or inactive.');
                }
            },],
        ], [
            'special_type' => 'special Tour type is required!',
        ]);
        if (!empty($request->state_name) && !empty($request->cities_name)) {
            $special_tour = TourVisits::where('tour_type', $request->special_type)->where(['state_name' => $request->state_name, 'cities_name' => $request->cities_name])->where('status', 1)
                ->where(function ($query) {
                    $query->whereIn('use_date', [0, 2, 3, 4])->orWhere(function ($query) {
                        $query->where('use_date', 1)
                            ->whereNotNull('startandend_date')
                            ->whereRaw('? < STR_TO_DATE(SUBSTRING_INDEX(startandend_date, " - ", 1), "%Y-%m-%d")', [date('Y-m-d')]);
                    });
                })->withTourCheck()->get();
            if (!empty($special_tour) && count($special_tour) > 0) {
                foreach ($special_tour as $key => $val) {
                    $hindi_tour = $val->translations()->pluck('value', 'key')->toArray();
                    $cities_tour[$key]['id'] = $val['id'];
                    $cities_tour[$key]['hi_tour_name'] = $hindi_tour['tour_name'];
                    $cities_tour[$key]['en_tour_name'] = $val['tour_name'];
                    $cities_tour[$key]['en_number_of_day'] = ($val['number_of_day'] ?? '');
                    $cities_tour[$key]['hi_number_of_day'] = ($hindi_tour['number_of_day'] ?? '');
                    // $cities_tour[$key]['package_list'] = json_decode($val['package_list'], true);
                    $cities_tour[$key]['pickup_time'] = ($val['pickup_time'] ?? '');
                    $cities_tour[$key]['pickup_location'] = ($val['pickup_location'] ?? '');
                    $cities_tour[$key]['pickup_lat'] = ($val['pickup_lat'] ?? '');
                    $cities_tour[$key]['pickup_long'] = ($val['pickup_long'] ?? '');
                    $cities_tour[$key]['cities_name'] = ($val['cities_name'] ?? '');
                    $cities_tour[$key]['country_name'] = ($val['country_name'] ?? '');
                    $cities_tour[$key]['state_name'] = ($val['state_name'] ?? '');
                    $cabs_lists = [];
                    $p_services = [];
                    if (!empty($val['cab_list_price']) && json_decode($val['cab_list_price'], true)) {
                        foreach (json_decode($val['cab_list_price'], true) as $kk => $val_v) {
                            $cabs_lists[$kk]['price'] = $val_v['price'];
                            $cabs_lists[$kk]['cab_id'] = $val_v['cab_id'];
                            $getCabs = \App\Models\TourCab::where('id', $val_v['cab_id'])->first();
                            $cab_name = ucwords($getCabs['name'] ?? '');
                            $cabs_lists[$kk]['cab_name'] = $cab_name;
                            $cabs_lists[$kk]['seats'] = ($getCabs['seats'] ?? '');
                            $cabs_lists[$kk]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($getCabs['image'] ?? ""), type: 'backend-product');
                            $p_services[] = 'transport';
                        }
                    }
                    $package_lists = [];
                    if (!empty($val['package_list_price']) && json_decode($val['package_list_price'], true)) {
                        foreach (json_decode($val['package_list_price'], true) as $kk => $val_s) {
                            $package_lists[$kk]['price'] = $val_s['pprice'];
                            $package_lists[$kk]['package_id'] = $val_s['package_id'];
                            $getpackage = \App\Models\TourPackage::where('id', $val_s['package_id'])->first();
                            $package_lists[$kk]['package_name'] = ucwords($getpackage['name'] ?? '');
                            $package_lists[$kk]['seats'] = ($getpackage['seats'] ?? '');
                            $package_lists[$kk]['type'] = ($getpackage['type'] ?? '');
                            $package_lists[$kk]['title'] = ($getpackage['title'] ?? '');
                            $package_lists[$kk]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($getpackage['image'] ?? ""), type: 'backend-product');
                            $p_services[] = ($getpackage['type'] ?? '');
                        }
                    }
                    $cities_tour[$key]['cab_list'] = $cabs_lists;
                    $cities_tour[$key]['package_list'] = $package_lists;
                    $cities_tour[$key]['services'] = array_values(array_unique($p_services));
                    $cities_tour[$key]['use_date'] = ($val['use_date'] ?? '');
                    $cities_tour[$key]['date'] = ($val['startandend_date'] ?? '');
                    $cities_tour[$key]['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . ($val['tour_image'] ?? ""), type: 'backend-product');
                }
            }
        } else {
            $cities_tour = [];
            // $special_tour = TourVisits::where('tour_type', 'cities_tour')->where('status', 1)->groupBy('cities_name')->get()->groupBy('state_name');
            $special_tour = TourVisits::where('tour_type', $request->special_type)->where('status', 1)->where(function ($query) {
                $query->whereIn('use_date', [0, 2, 3, 4])->orWhere(function ($query) {
                    $query->where('use_date', 1)
                        ->whereNotNull('startandend_date')
                        ->whereRaw('? < STR_TO_DATE(SUBSTRING_INDEX(startandend_date, " - ", 1), "%Y-%m-%d")', [date('Y-m-d')]);
                });
            })->withTourCheck()->groupBy('cities_name')->get()->groupBy('state_name');
            if (!empty($special_tour) && count($special_tour) > 0) {
                $p = 0;
                foreach ($special_tour as $key => $val) {
                    if (!empty($val) && count($val) > 0) {
                        $q = 0;
                        foreach ($val as $kay => $state) {
                            $hindi_tour = (TourVisits::find($state['id']))->translations()->pluck('value', 'key')->toArray();
                            if ($q == 0) {
                                $cities_tour[$p]['en_state_name'] = $state['state_name'] ?? '';
                                $cities_tour[$p]['hi_state_name'] = $hindi_tour['state_name'] ?? "";
                            }
                            $cities_tour[$p]['list'][$q]['id'] = $state['id'] ?? '';
                            $cities_tour[$p]['list'][$q]['en_cities_name'] = $state['cities_name'];
                            $cities_tour[$p]['list'][$q]['hi_cities_name'] = $hindi_tour['cities_name'] ?? "";
                            $cities_tour[$p]['list'][$q]['hi_tour_name'] = $hindi_tour['tour_name'];
                            $cities_tour[$p]['list'][$q]['en_tour_name'] = $state['tour_name'];
                            $cities_tour[$p]['list'][$q]['en_number_of_day'] = ($state['number_of_day'] ?? '');
                            $cities_tour[$p]['list'][$q]['hi_number_of_day'] = ($hindi_tour['number_of_day'] ?? '');
                            $cabs_lists = [];
                            $p_services = [];
                            if (!empty($state['cab_list_price']) && json_decode($state['cab_list_price'], true)) {
                                foreach (json_decode($state['cab_list_price'], true) as $kk => $val_v) {
                                    $cabs_lists[$kk]['price'] = $val_v['price'];
                                    $cabs_lists[$kk]['cab_id'] = $val_v['cab_id'];
                                    $getCabs = \App\Models\TourCab::where('id', $val_v['cab_id'])->first();
                                    $cab_name = ucwords($getCabs['name'] ?? '');
                                    $cabs_lists[$kk]['cab_name'] = $cab_name;
                                    $cabs_lists[$kk]['seats'] = ($getCabs['seats'] ?? '');
                                    $cabs_lists[$kk]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($getCabs['image'] ?? ""), type: 'backend-product');
                                    $p_services[] = 'transport';
                                }
                            }
                            $package_lists = [];
                            if (!empty($state['package_list_price']) && json_decode($state['package_list_price'], true)) {
                                foreach (json_decode($state['package_list_price'], true) as $kk => $val_s) {
                                    $package_lists[$kk]['price'] = $val_s['pprice'];
                                    $package_lists[$kk]['package_id'] = $val_s['package_id'];
                                    $getpackage = \App\Models\TourPackage::where('id', $val_s['package_id'])->first();
                                    $package_lists[$kk]['package_name'] = ucwords($getpackage['name'] ?? '');
                                    $package_lists[$kk]['seats'] = ($getpackage['seats'] ?? '');
                                    $package_lists[$kk]['type'] = ($getpackage['type'] ?? '');
                                    $package_lists[$kk]['title'] = ($getpackage['title'] ?? '');
                                    $package_lists[$kk]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($getpackage['image'] ?? ""), type: 'backend-product');
                                    $p_services[] = ($getpackage['type'] ?? '');
                                }
                            }
                            $cities_tour[$p]['list'][$q]['cab_list'] = $cabs_lists;
                            $cities_tour[$p]['list'][$q]['package_list'] = $package_lists;
                            $cities_tour[$p]['list'][$q]['services'] = array_values(array_unique($p_services));
                            $cities_tour[$p]['list'][$q]['use_date'] = ($state['use_date'] ?? '');
                            $cities_tour[$p]['list'][$q]['date'] = ($state['startandend_date'] ?? '');
                            $cities_tour[$p]['list'][$q]['pickup_time'] = ($state['pickup_time'] ?? '');
                            $cities_tour[$p]['list'][$q]['pickup_location'] = ($state['pickup_location'] ?? '');
                            $cities_tour[$p]['list'][$q]['pickup_lat'] = ($state['pickup_lat'] ?? '');
                            $cities_tour[$p]['list'][$q]['pickup_long'] = ($state['pickup_long'] ?? '');
                            $cities_tour[$p]['list'][$q]['cities_name'] = ($state['cities_name'] ?? '');
                            $cities_tour[$p]['list'][$q]['country_name'] = ($state['country_name'] ?? '');
                            $cities_tour[$p]['list'][$q]['state_name'] = ($state['state_name'] ?? '');
                            $cities_tour[$p]['list'][$q]['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . ($state['tour_image'] ?? ""), type: 'backend-product');
                            $q++;
                        }
                    }
                    $p++;
                }
            }
        }
        if (!empty($cities_tour) && count($cities_tour) > 0) {
            return response()->json(['status' => 1, 'count' => count($cities_tour), 'data' => $cities_tour], 200);
        } else {
            return response()->json(['status' => 0, 'count' => 0, 'data' => []], 200);
        }
    }
    public function CitiesTour(Request $request)
    {
        $request->validate([
            'special_type' => 'required|in:0,1',
            "cities_name" => "required",
        ], [
            'special_type.required' => 'special Tour type is required!',
            "cities_name.required" => "Cities Name is required!",
        ]);
        $cities_tour = [];
        if ($request->special_type == 1) {
            $special_tour = TourVisits::where('tour_type', 'special_tour')->where('status', 1)->where('cities_name', $request->cities_name)
                ->where(function ($query) {
                    $query->whereIn('use_date', [0, 2, 3, 4])->orWhere(function ($query) {
                        $query->where('use_date', 1)
                            ->whereNotNull('startandend_date')
                            ->whereRaw('? < STR_TO_DATE(SUBSTRING_INDEX(startandend_date, " - ", 1), "%Y-%m-%d")', [date('Y-m-d')]);
                    });
                })->withTourCheck()->get();
            if (!empty($special_tour) && count($special_tour) > 0) {
                foreach ($special_tour as $key => $val) {
                    $hindi_tour = $val->translations()->pluck('value', 'key')->toArray();
                    $cities_tour[$key]['id'] = $val['id'];
                    $cities_tour[$key]['hi_tour_name'] = $hindi_tour['tour_name'];
                    $cities_tour[$key]['en_tour_name'] = $val['tour_name'];
                    $cities_tour[$key]['en_number_of_day'] = ($val['number_of_day'] ?? '');
                    $cities_tour[$key]['hi_number_of_day'] = ($hindi_tour['number_of_day'] ?? '');
                    $cabs_lists = [];
                    $p_services = [];
                    if (!empty($val['cab_list_price']) && json_decode($val['cab_list_price'], true)) {
                        foreach (json_decode($val['cab_list_price'], true) as $kk => $val_c) {
                            $cabs_lists[$kk]['price'] = $val_c['price'];
                            $cabs_lists[$kk]['cab_id'] = $val_c['cab_id'];
                            $getCabs = \App\Models\TourCab::where('id', $val_c['cab_id'])->first();
                            $cab_name = ucwords($getCabs['name'] ?? '');
                            $cabs_lists[$kk]['cab_name'] = $cab_name;
                            $cabs_lists[$kk]['seats'] = ($getCabs['seats'] ?? '');
                            $cabs_lists[$kk]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($getCabs['image'] ?? ""), type: 'backend-product');
                            $p_services[] = 'transport';
                        }
                    }
                    $package_lists = [];
                    if (!empty($val['package_list_price']) && json_decode($val['package_list_price'], true)) {
                        foreach (json_decode($val['package_list_price'], true) as $kk => $val_s) {
                            $package_lists[$kk]['price'] = $val_s['pprice'];
                            $package_lists[$kk]['package_id'] = $val_s['package_id'];
                            $getpackage = \App\Models\TourPackage::where('id', $val_s['package_id'])->first();
                            $package_lists[$kk]['package_name'] = ucwords($getpackage['name'] ?? '');
                            $package_lists[$kk]['seats'] = ($getpackage['seats'] ?? '');
                            $package_lists[$kk]['type'] = ($getpackage['type'] ?? '');
                            $package_lists[$kk]['title'] = ($getpackage['title'] ?? '');
                            $package_lists[$kk]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($getpackage['image'] ?? ""), type: 'backend-product');
                            $p_services[] = ($getpackage['type'] ?? '');
                        }
                    }
                    $cities_tour[$key]['cab_list'] = $cabs_lists;
                    $cities_tour[$key]['package_list'] = $package_lists;
                    $cities_tour[$key]['services'] = array_values(array_unique($p_services));
                    $cities_tour[$key]['use_date'] = ($val['use_date'] ?? '');
                    $cities_tour[$key]['date'] = ($val['startandend_date'] ?? '');
                    $cities_tour[$key]['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $val['tour_image'], type: 'backend-product');
                }
            }
        } else {
            $special_tour = TourVisits::where('tour_type', 'cities_tour')->where('cities_name', $request->cities_name)->where('status', 1)->get();
            if (!empty($special_tour) && count($special_tour) > 0) {
                $p = 0;
                foreach ($special_tour as $key => $val) {
                    $hindi_tour = $val->translations()->pluck('value', 'key')->toArray();
                    $cities_tour[$p]['en_tour_name'] = $val['tour_name'] ?? "";
                    $cities_tour[$p]['en_number_of_day'] = ($val['number_of_day'] ?? '');
                    $cities_tour[$p]['hi_number_of_day'] = ($hindi_tour['number_of_day'] ?? '');
                    $cities_tour[$p]['hi_tour_name'] = $hindi_tour['tour_name'] ?? "";
                    $cities_tour[$p]['use_date'] = ($val['use_date'] ?? '');
                    $cities_tour[$p]['date'] = ($val['startandend_date'] ?? '');
                    // $cities_tour[$p]['package_list'] = json_decode($val['package_list'], true);
                    $cabs_lists = [];
                    $p_services = [];
                    if (!empty($val['cab_list_price']) && json_decode($val['cab_list_price'], true)) {
                        foreach (json_decode($val['cab_list_price'], true) as $kk => $val_p) {
                            $cabs_lists[$kk]['price'] = $val_p['price'];
                            $cabs_lists[$kk]['cab_id'] = $val_p['cab_id'];
                            $getCabs = \App\Models\TourCab::where('id', $val_p['cab_id'])->first();
                            $cab_name = ucwords($getCabs['name'] ?? '');
                            $cabs_lists[$kk]['cab_name'] = $cab_name;
                            $cabs_lists[$kk]['seats'] = ($getCabs['seats'] ?? '');
                            $cabs_lists[$kk]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($getCabs['image'] ?? ""), type: 'backend-product');
                            $p_services[] = 'transport';
                        }
                    }
                    $package_lists = [];
                    if (!empty($val['package_list_price']) && json_decode($val['package_list_price'], true)) {
                        foreach (json_decode($val['package_list_price'], true) as $kk => $val_s) {
                            $package_lists[$kk]['price'] = $val_s['pprice'];
                            $package_lists[$kk]['package_id'] = $val_s['package_id'];
                            $getpackage = \App\Models\TourPackage::where('id', $val_s['package_id'])->first();
                            $package_lists[$kk]['package_name'] = ucwords($getpackage['name'] ?? '');
                            $package_lists[$kk]['seats'] = ($getpackage['seats'] ?? '');
                            $package_lists[$kk]['type'] = ($getpackage['type'] ?? '');
                            $package_lists[$kk]['title'] = ($getpackage['title'] ?? '');
                            $package_lists[$kk]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($getpackage['image'] ?? ""), type: 'backend-product');
                            $p_services[] = ($getpackage['type'] ?? '');
                        }
                    }
                    $cities_tour[$p]['cab_list'] = $cabs_lists;
                    $cities_tour[$p]['package_list'] = $package_lists;
                    $cities_tour[$p]['services'] = array_values(array_unique($p_services));
                    $cities_tour[$p]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $val['tour_image'], type: 'backend-product');
                    $p++;
                }
            }
        }
        if (!empty($cities_tour) && count($cities_tour) > 0) {
            return response()->json(['status' => 1, 'count' => count($cities_tour), 'data' => $cities_tour], 200);
        } else {
            return response()->json(['status' => 0, 'count' => 0, 'data' => []], 200);
        }
    }

    public function TourLeads(Request $request)
    {
        $request->validate([
            'tour_id' =>  ['required', function ($attribute, $value, $fail) {
                if (!TourVisits::where('id', $value)->where('status', 1)->exists()) {
                    $fail('The selected tour is invalid or inactive.');
                }
            },],
            'package_id' => 'required',
            'user_id' => ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            },],
            'amount' => 'required|numeric|min:1',
        ], [
            'tour_id.required' => "tour is required!",
            'package_id.required' => "package is required!",
            'user_id.required' => "user Id is required!",
            'amount.required' => "amount is required!",
        ]);

        $leads = new TourLeads();
        $leads->tour_id = $request->tour_id ?? 0;
        $leads->package_id = $request->package_id;
        $leads->user_id = $request->user_id;
        $leads->amount = $request->amount;
        $leads->status = 1;
        $leads->save();

        return response()->json(['status' => 1, 'data' => ['insert_id' => $leads->id]], 200);
    }

    public function TourById(Request $request)
    {
        $request->validate([
            'tour_id' =>  ['required', function ($attribute, $value, $fail) {
                if (!TourVisits::where('id', $value)->where('status', 1)->exists()) {
                    $fail('The selected tour is invalid or inactive.');
                }
            },],
        ], [
            'tour_id.required' => "tour is required!",
        ]);
        $cities_tour = [];
        $special_tour = TourVisits::where('id', $request->tour_id)->where('status', 1)->first();
        if (!empty($special_tour)) {
            $hindi_tour = $special_tour->translations()->pluck('value', 'key')->toArray();
            $getRecode = ['tour_name', 'description', 'highlights', 'inclusion', "exclusion", 'terms_and_conditions', 'cancellation_policy', 'notes'];
            foreach ($getRecode as $name) {
                $cities_tour['en_' . $name] = $special_tour[$name] ?? "";
                $cities_tour['hi_' . $name] = $hindi_tour[$name] ?? "";
            }
            $cities_tour['use_date'] = ($special_tour['use_date'] ?? '');
            $cities_tour['date'] = ($special_tour['startandend_date'] ?? '');

            $cities_tour['pickup_time'] = ($special_tour['pickup_time'] ?? '');
            $cities_tour['pickup_location'] = ($special_tour['pickup_location'] ?? '');
            $cities_tour['pickup_lat'] = ($special_tour['pickup_lat'] ?? '');
            $cities_tour['pickup_long'] = ($special_tour['pickup_long'] ?? '');
            $cities_tour['cities_name'] = ($special_tour['cities_name'] ?? '');
            $cities_tour['country_name'] = ($special_tour['country_name'] ?? '');
            $cities_tour['state_name'] = ($special_tour['state_name'] ?? '');
            $cities_tour['ex_distance'] = ($special_tour['ex_distance'] ?? 0);

            // $cities_tour['package_list'] = json_decode($special_tour['package_list'], true);
            $cabs_lists = [];
            $p_services = [];
            $tour_package_total_price = 0;
            if ($special_tour['use_date'] == 1) {
                if (!empty($special_tour['cab_list_price']) && json_decode($special_tour['cab_list_price'], true)) {
                    foreach (json_decode($special_tour['cab_list_price'], true) as $kk => $val_p) {
                        $cab_id = $val_p['cab_id'];
                        $getCabs = \App\Models\TourCab::where('id', $cab_id)->first();
                        $hindi_tourcabs = $getCabs->translations()->pluck('value', 'key')->toArray();

                        $available_seats = \App\Models\TourOrder::where('tour_id', $request->tour_id)
                            ->where('amount_status', 1)
                            ->where('status', 1)
                            ->where('available_seat_cab_id', $cab_id)
                            ->sum('qty');

                        if (!isset($cabs_lists[$cab_id])) {
                            $cabs_lists[$cab_id] = [
                                'price' => $val_p['price'],
                                'cab_id' => $cab_id,
                                'en_cab_name' => ucwords($getCabs['name'] ?? ''),
                                'hi_cab_name' => ucwords($hindi_tourcabs['name'] ?? ''),
                                'en_description' => ucwords($getCabs['description'] ?? ''),
                                'hi_description' => ucwords($hindi_tourcabs['description'] ?? ''),
                                'seats' => ($getCabs['seats'] ?? 0),
                                'total_seats' => ($getCabs['seats'] ?? 0),
                                'total_seats_message' => ($getCabs['seats'] ?? 0),
                                'total_booking_seats' => $available_seats,
                                'image' => getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($getCabs['image'] ?? ""), type: 'backend-product'),
                            ];
                        } else {
                            $cabs_lists[$cab_id]['total_seats_message'] = $cabs_lists[$cab_id]['total_seats_message'] . ' + ' . ($getCabs['seats'] ?? 0);
                            $cabs_lists[$cab_id]['total_seats'] += ($getCabs['seats'] ?? 0);
                            $cabs_lists[$cab_id]['total_booking_seats'] += $available_seats;
                        }
                        $p_services[] = 'transport';
                    }
                    $cabs_lists = array_values($cabs_lists);
                }
            } else {
                if (!empty($special_tour['cab_list_price']) && json_decode($special_tour['cab_list_price'], true)) {
                    foreach (json_decode($special_tour['cab_list_price'], true) as $kk => $val_p) {
                        $cabs_lists[$kk]['price'] = $val_p['price'];
                        $cabs_lists[$kk]['cab_id'] = $val_p['cab_id'];
                        $getCabs = \App\Models\TourCab::where('id', $val_p['cab_id'])->first();
                        $hindi_tourcabs = $getCabs->translations()->pluck('value', 'key')->toArray();
                        $cabs_lists[$kk]['en_cab_name'] = ucwords($getCabs['name'] ?? '');
                        $cabs_lists[$kk]['hi_cab_name'] = ucwords($hindi_tourcabs['name'] ?? '');

                        $cabs_lists[$kk]['en_description'] = ucwords($getCabs['description'] ?? '');
                        $cabs_lists[$kk]['hi_description'] = ucwords($hindi_tourcabs['description'] ?? '');
                        $cabs_lists[$kk]['seats'] = ($getCabs['seats'] ?? '');
                        $cabs_lists[$kk]['total_seats'] = 0;
                        $cabs_lists[$kk]['total_seats_message'] = 0;
                        $cabs_lists[$kk]['total_booking_seats'] = 0;
                        $cabs_lists[$kk]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($getCabs['image'] ?? ""), type: 'backend-product');
                        $p_services[] = 'transport';
                    }
                }
            }

            $package_lists = [];
            if (!empty($special_tour['package_list_price']) && json_decode($special_tour['package_list_price'], true)) {
                foreach (json_decode($special_tour['package_list_price'], true) as $kk => $val_s) {
                    $tour_package_total_price += (float)$val_s['pprice'];
                    $package_lists[$kk]['price'] = $val_s['pprice'];
                    $package_lists[$kk]['package_id'] = $val_s['package_id'];
                    $getpackage = \App\Models\TourPackage::where('id', $val_s['package_id'])->first();
                    $package_lists[$kk]['en_package_name'] = ucwords($getpackage['name'] ?? '');
                    $package_lists[$kk]['en_description'] = ucwords($getpackage['description'] ?? '');
                    $hindi_tourpackage = $getpackage->translations()->pluck('value', 'key')->toArray();
                    $package_lists[$kk]['hi_package_name'] = ($hindi_tourpackage['name'] ?? '');
                    $package_lists[$kk]['hi_description'] = ($hindi_tourpackage['description'] ?? '');
                    $package_lists[$kk]['seats'] = ($getpackage['seats'] ?? '');
                    $package_lists[$kk]['type'] = ($getpackage['type'] ?? '');
                    $package_lists[$kk]['title'] = ($getpackage['title'] ?? '');
                    $package_lists[$kk]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($getpackage['image'] ?? ""), type: 'backend-product');
                    $p_services[] = ($getpackage['type'] ?? '');
                    $cities_tour[$getpackage['type'] . '_list'][] = $package_lists[$kk];
                }
            }
            $cities_tour['cab_list'] = $cabs_lists;
            $cities_tour['package_list'] = $package_lists;
            $cities_tour['tour_package_total_price'] = $tour_package_total_price;
            $cities_tour['services'] = array_values(array_unique($p_services));
            $cities_tour['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $special_tour['tour_image'], type: 'backend-product');
            $image_list = [];
            if (!empty($special_tour['image']) && json_decode($special_tour['image'], true)) {
                foreach (json_decode($special_tour['image'], true) as $value) {
                    $image_list[] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $value, type: 'backend-product');
                }
            }
            $cities_tour['image_list'] = $image_list;
            if (!empty($special_tour['time_slot']) && json_decode($special_tour['time_slot'], true)) {
                $cities_tour['time_slot'] = json_decode($special_tour['time_slot'], true);
            } else {
                $cities_tour['time_slot'] = [];
            }

            $cities_tour['itinerary_place'] = [];
            $get_itineraryData = \App\Models\TourVisitPlace::where('tour_visit_id', $request->tour_id)->where('status', 1)->get();
            if ($get_itineraryData) {
                foreach ($get_itineraryData as $key => $value) {
                    $gethindi = $value->translations()->pluck('value', 'key')->toArray();
                    $cities_tour['itinerary_place'][$key]['id'] = $value['id'];
                    $cities_tour['itinerary_place'][$key]['en_name'] = $value['name'];
                    $cities_tour['itinerary_place'][$key]['hi_name'] = $gethindi['name'];
                    $cities_tour['itinerary_place'][$key]['en_time'] = $value['time'];
                    $cities_tour['itinerary_place'][$key]['hi_time'] = $gethindi['time'];

                    $cities_tour['itinerary_place'][$key]['en_description'] = $value['description'];
                    $cities_tour['itinerary_place'][$key]['hi_description'] = $gethindi['description'];

                    $itinerary_image_list = [];
                    if (!empty($value['images']) && json_decode($value['images'], true)) {
                        foreach (json_decode($value['images'], true) as $itn_va) {
                            $itinerary_image_list[] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit_place/' . $itn_va, type: 'backend-product');
                        }
                    }
                    $cities_tour['itinerary_place'][$key]['image'] = $itinerary_image_list;
                }
            }
        }
        if (!empty($cities_tour)) {
            $cities_tour['user_booking_count'] = \App\Models\TourOrder::where('tour_id', $request->tour_id)
                ->where('amount_status', 1)
                ->where('status', 1)
                ->count();
            $getDataUser =    \App\Models\TourOrder::where('tour_id', $request->tour_id)->where('amount_status', 1)->where('status', 1)->with(['userData'])->get();
            $cities_tour['user_profile_image'] = [];
            if ($getDataUser) {
                foreach ($getDataUser as $valimg) {
                    $cities_tour['user_profile_image'][] = getValidImage(path: 'storage/app/public/profile/' . ($value['userData']['image'] ?? ""), type: 'backend-profile');
                }
            }
            return response()->json(['status' => 1, 'count' => 1, 'data' => $cities_tour], 200);
        }
        return response()->json(['status' => 0, 'count' => 0, 'data' => []], 200);
    }

    public function travellerInfo(Request $request)
    {
        $request->validate([
            'tour_id' =>  'required',
        ], [
            'tour_id.required' => 'tour Id is required!',
        ]);
        $special_tour = TourVisits::where('id', $request->tour_id)->where('status', 1)->first();
        $getInfo = TourAndTravel::where('id', $special_tour['created_id'])->where('status', 1)->first();
        if (!empty($special_tour) && !empty($getInfo)) {
            $getData = [];
            if (!empty($special_tour['created_id'])) {
                $hindi_tour = $getInfo->translations()->pluck('value', 'key')->toArray();
                $getData['id'] = ($getInfo['id'] ?? "");
                $getData['en_owner_name'] = ($getInfo['owner_name'] ?? "");
                $getData['hi_owner_name'] = $hindi_tour['owner_name'];
                $getData['en_company_name'] = ($getInfo['company_name'] ?? "");
                $getData['hi_company_name'] = $hindi_tour['company_name'];
                $getData['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/doc/' . ($getInfo['image'] ?? ""), type: 'backend-product');
            }
            return response()->json(['status' => 1, 'count' => 1, 'data' => $getData], 200);
        }
        return response()->json(['status' => 0, 'count' => 0, 'data' => []], 200);
    }

    public function BookingList(Request $request)
    {
        $request->validate([
            'user_id' =>  ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            },],
        ], [
            'user_id.required' => 'User Id is required!',
        ]);
        $bookingList = [];
        if (!empty($request->order_id)) {
            $all_booking = TourOrder::where('user_id', $request->user_id)->where('id', $request->order_id)->with(['Tour'])->first();
            if (!empty($all_booking)) {
                $bookingList['id'] = $all_booking['id'];
                $bookingList['order_id'] = $all_booking['order_id'];
                $bookingList['qty'] = $all_booking['qty'];
                $bookingList['en_tour_name'] = $all_booking['Tour']['tour_name'] ?? '';
                $bookingList['tour_id'] = $all_booking['tour_id'] ?? '';
                $getDatas = TourReviews::where('user_id', $all_booking['user_id'])->where('order_id', $all_booking['id'])->where('tour_id', $all_booking['tour_id'])->first();
                $bookingList['review_status'] = $getDatas['is_edited'] ?? 0;
                $tourTranslation = TourVisits::find($all_booking['tour_id']);
                $hindi_tour = [];
                if ($tourTranslation) {
                    $hindi_tour = $tourTranslation->translations()->pluck('value', 'key')->toArray();
                }
                $bookingList['hi_tour_name'] = $hindi_tour['tour_name'] ?? "";
                $bookingList['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . ($all_booking['Tour']['tour_image'] ?? ''), type: 'backend-product');
                $bookingList['order_id'] = $all_booking['order_id'];
                $bookingList['amount'] = (($all_booking['amount'] ?? 0) + ($all_booking['coupon_amount'] ?? 0));
                $bookingList['coupon_amount'] = $all_booking['coupon_amount'];
                $total_amounts = (((float)$all_booking['amount'] ?? 0) + ((float)$all_booking['coupon_amount'] ?? 0));
                if ($all_booking['part_payment'] == 'part') {
                    $total_amounts += ((float)$all_booking['amount'] ?? 0);
                }
                $bookingList['total_amount'] = $total_amounts;
                $bookingList['remaining_amount'] = (($all_booking['part_payment'] == 'part') ? $all_booking['amount'] : 0);
                $bookingList['paid_amount'] = (((float)$all_booking['amount'] ?? 0) + ((float)$all_booking['coupon_amount'] ?? 0));
                $bookingList['refund_status'] = $all_booking['refund_status'] ?? 0;
                $bookingList['refund_amount'] = $all_booking['refund_amount'] ?? 0;
                $bookingList['pay_amount'] = $all_booking['amount'];
                $booking_arrays = [];
                $tba = 0;
                if (!empty($all_booking['booking_package']) && json_decode($all_booking['booking_package'], true)) {
                    foreach (json_decode($all_booking['booking_package'], true) as $key => $value) {
                        if ($value['type'] == 'cab') {
                            $getCabs = \App\Models\TourCab::where('id', $value['id'])->first();
                            $hindi_tourcabs = [];
                            if ($getCabs) {
                                $hindi_tourcabs = $getCabs->translations()->pluck('value', 'key')->toArray();
                            }
                            $booking_arrays[$tba]['en_name'] = ucwords($getCabs['name'] ?? '');
                            $booking_arrays[$tba]['hi_name'] = ucwords($hindi_tourcabs['name'] ?? '');
                            $booking_arrays[$tba]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($getCabs['image'] ?? ""), type: 'backend-product');
                            $booking_arrays[$tba]['price'] = $value['price'];
                            if (($all_booking['use_date'] == 2 || $all_booking['use_date'] == 3 || $all_booking['use_date'] == 4) && !empty(json_decode($all_booking['Tour']['package_list_price'] ?? '[]', true)) && json_decode($all_booking['Tour']['package_list_price'] ?? '[]', true)) {
                                foreach (json_decode($all_booking['Tour']['package_list_price'] ?? '[]', true) as $pq => $valupq) {
                                    $tba++;
                                    $tourPackages = \App\Models\TourPackage::where('id', $valupq['package_id'])->first();
                                    $hindi_tourpack = [];
                                    if ($tourPackages) {
                                        $hindi_tourpack = $tourPackages->translations()->pluck('value', 'key')->toArray();
                                    }
                                    $booking_arrays[$tba]['en_name'] = ucwords($tourPackages['name'] ?? '');
                                    $booking_arrays[$tba]['hi_name'] = ucwords($hindi_tourpack['name'] ?? '');
                                    $booking_arrays[$tba]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . $tourPackages['image'], type: 'backend-product');
                                    $booking_arrays[$tba]['price'] = 0;
                                    $booking_arrays[$tba]['qty'] = $value['qty'];
                                }
                                // $tba++;
                            }
                        } else if ($value['type'] == 'other' || $value['type'] ==  "foods" || $value['type'] == "hotel") {
                            $tourPackages = \App\Models\TourPackage::where('id', $value['id'])->first();
                            $hindi_tourpack = [];
                            if ($tourPackages) {
                                $hindi_tourpack = $tourPackages->translations()->pluck('value', 'key')->toArray();
                            }
                            $booking_arrays[$tba]['en_name'] = ucwords($tourPackages['name'] ?? '');
                            $booking_arrays[$tba]['hi_name'] = ucwords($hindi_tourpack['name'] ?? '');
                            $booking_arrays[$tba]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                            $booking_arrays[$tba]['price'] = (($all_booking['use_date'] == 0) ? $value['price'] : 0);
                        } else {
                            $booking_arrays[$tba]['en_name'] = str_replace('_', ' ', $value['type']);
                            $booking_arrays[$tba]['hi_name'] = str_replace('_', ' ', $value['type']);
                            $booking_arrays[$tba]['image'] = '';
                            $booking_arrays[$tba]['price'] = $value['price'];
                        }
                        $booking_arrays[$tba]['qty'] = $value['qty'];
                        $tba++;
                    }
                }
                $bookingList['booking_packages'] = $booking_arrays;
                $bookingList['part_payment'] = $all_booking['part_payment'];
                $bookingList['amount_status'] = $all_booking['amount_status'];
                $bookingList['transaction_id'] = $all_booking['transaction_id'];
                $bookingList['refund_status'] = $all_booking['refund_status'];
                $bookingList['pickup_address'] = $all_booking['pickup_address'];
                $bookingList['pickup_date'] = $all_booking['pickup_date'];
                $bookingList['pickup_time'] = $all_booking['pickup_time'];
                $bookingList['pickup_otp'] = $all_booking['pickup_otp'];
                $bookingList['pickup_status'] = $all_booking['pickup_status'];
                $bookingList['drop_opt'] = $all_booking['drop_opt'];
                $bookingList['drop_status'] = $all_booking['drop_status'];
                $bookingList['booking_time'] = $all_booking['created_at'];
                $getSpecial_tour = \App\Models\TourRefundPolicy::where('status', 1)->where('type', $tourOrders['Tour']['tour_type'] ?? '')->orderBy('day', 'desc')->get();
                $Amount_Pay = 0;
                $pickupTimestamp = strtotime($all_booking['pickup_date'] . ' ' . $all_booking['pickup_time']);
                if (!empty($getSpecial_tour) && count($getSpecial_tour) > 0) {
                    foreach ($getSpecial_tour as $val) {
                        $calculatedTimestamp = strtotime("-" . $val['day'] . " hours", $pickupTimestamp);
                        $currentTimestamp = strtotime(now());
                        if ($currentTimestamp <= $calculatedTimestamp) {
                            $Amount_Pay = ($all_booking['amount'] * $val['percentage']) / 100;
                            break;
                        }
                    }
                }
                $bookingList['cancel_refund_amount_given'] = $Amount_Pay;
                $bookingList['invoice_url'] = url('api/v1/tour/tour-order-invoice/' . $all_booking['id']);

                $bookingList['itinerary_place'] = [];
                $get_itineraryData = \App\Models\TourVisitPlace::where('tour_visit_id', $all_booking['tour_id'])->where('status', 1)->get();
                if ($get_itineraryData) {
                    foreach ($get_itineraryData as $key => $value) {
                        $gethindi = $value->translations()->pluck('value', 'key')->toArray();
                        $bookingList['itinerary_place'][$key]['id'] = $value['id'];
                        $bookingList['itinerary_place'][$key]['en_name'] = $value['name'];
                        $bookingList['itinerary_place'][$key]['hi_name'] = $gethindi['name'];
                        $bookingList['itinerary_place'][$key]['en_time'] = $value['time'];
                        $bookingList['itinerary_place'][$key]['hi_time'] = $gethindi['time'];

                        $bookingList['itinerary_place'][$key]['en_description'] = $value['description'];
                        $bookingList['itinerary_place'][$key]['hi_description'] = $gethindi['description'];

                        $itinerary_image_list = [];
                        if (!empty($value['images']) && json_decode($value['images'], true)) {
                            foreach (json_decode($value['images'], true) as $itn_va) {
                                $itinerary_image_list[] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit_place/' . $itn_va, type: 'backend-product');
                            }
                        }
                        $bookingList['itinerary_place'][$key]['image'] = $itinerary_image_list;
                    }
                }
            }
            return response()->json(['status' => 1, 'count' => 1, 'data' => $bookingList], 200);
        } else {
            $all_booking = TourOrder::where('user_id', $request->user_id)->with(['Tour'])->orderBy('id', "desc")->get();
            if (!empty($all_booking) && count($all_booking) > 0) {
                foreach ($all_booking as $key => $value) {
                    $bookingList[$key]['id'] = $value['id'];
                    $bookingList[$key]['order_id'] = $value['order_id'];
                    $bookingList[$key]['qty'] = $value['qty'];
                    $bookingList[$key]['en_tour_name'] = $value['Tour']['tour_name'] ?? '';
                    $tourTranslation = TourVisits::find($value['tour_id']);
                    $hindi_tour = [];
                    if ($tourTranslation) {
                        $hindi_tour = $tourTranslation->translations()->pluck('value', 'key')->toArray();
                    }
                    $bookingList[$key]['hi_tour_name'] = $hindi_tour['tour_name'] ?? "";
                    $bookingList[$key]['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . ($value['Tour']['tour_image'] ?? ''), type: 'backend-product');
                    $bookingList[$key]['order_id'] = $value['order_id'];
                    $bookingList[$key]['amount'] = (($value['amount'] ?? 0) + ($value['coupon_amount'] ?? 0));
                    $bookingList[$key]['coupon_amount'] = $value['coupon_amount'];
                    $bookingList[$key]['pay_amount'] = $value['amount'];
                    $bookingList[$key]['amount_status'] = $value['amount_status'];
                    $bookingList[$key]['transaction_id'] = $value['transaction_id'];
                    $bookingList[$key]['refund_status'] = $value['refund_status'];
                    $bookingList[$key]['pickup_address'] = $value['pickup_address'];
                    $bookingList[$key]['pickup_date'] = $value['pickup_date'];
                    $bookingList[$key]['pickup_time'] = $value['pickup_time'];
                    $bookingList[$key]['pickup_otp'] = $value['pickup_otp'];
                    $bookingList[$key]['pickup_status'] = $value['pickup_status'];
                    $bookingList[$key]['drop_opt'] = $value['drop_opt'];
                    $bookingList[$key]['drop_status'] = $value['drop_status'];
                    $bookingList[$key]['booking_time'] = $value['created_at'];
                    $bookingList[$key]['part_payment'] = $value['part_payment'];
                }
                return response()->json(['status' => 1, 'count' => count($bookingList), 'data' => $bookingList], 200);
            }
        }
        return response()->json(['status' => 0, 'count' => 0, 'data' => $bookingList], 200);
    }

    public function BookingOrderRemmimgPay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            },],
            'order_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourOrder::where('id', $value)->where('user_id', $request['user_id'])->exists()) {
                    $fail('The selected Order id invalid.');
                }
            },],
            'wallet_type' => 'required|in:0,1',
            'payment_amount' => 'required|numeric|min:1',
            'transaction_id' => 'required',
            'online_pay' => 'required_unless:transaction_id,wallet',
        ], [
            'user_id.required' => 'Cab Id is Empty!',
            'order_id.required' => 'Order Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        if ($request->wallet_type == 1 && ($request['online_pay'] ?? 0) > 0) {
            User::where('id', $request->user_id)->update(['wallet_balance' => \Illuminate\Support\Facades\DB::raw('wallet_balance + ' . $request['online_pay'])]);
            $wallet_transaction = new \App\Models\WalletTransaction();
            $wallet_transaction->user_id = $request->user_id;
            $wallet_transaction->transaction_id = (($request->transaction_id) ? $request->transaction_id : \Illuminate\Support\Str::uuid());
            $wallet_transaction->reference = 'add_funds_to_wallet';
            $wallet_transaction->transaction_type = 'add_fund';
            $wallet_transaction->balance = User::where('id', $request->user_id)->first()['wallet_balance'];
            $wallet_transaction->credit = $request['online_pay'];
            $wallet_transaction->save();
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $user = User::find($request->user_id);
            $event_booking = TourOrder::with(['Tour'])->find($request['order_id']);
            if ($event_booking['part_payment'] == 'full') {
                return response()->json(['status' => 0, 'message' => "full Pay Successfully", 'data' => []], 200);
            } elseif ($event_booking['amount'] > $request['payment_amount']) {
                return response()->json(['status' => 0, 'message' => "Please Check Remaining Amount Pay", 'data' => []], 200);
            }
            if ($request->wallet_type == 1) {
                if ($user['wallet_balance'] >= $request['payment_amount']) {
                    User::where('id', $user['id'])->update(['wallet_balance' => \Illuminate\Support\Facades\DB::raw('wallet_balance - ' . $request['payment_amount'])]);

                    $gst_amount = 0;
                    $admin_commission = 0;
                    $final_amount = $request['payment_amount'];
                    $eventtax = \App\Models\ServiceTax::find(1);
                    if ($eventtax['tour_tax']) {
                        $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                        $final_amount = $final_amount - $gst_amount;
                    }
                    if ($event_booking['Tour']['tour_commission']) {
                        $admin_commission = (($final_amount * $event_booking['Tour']['tour_commission']) / 100);
                        $final_amount = ($final_amount - $admin_commission);
                    }
                    TourOrder::where('id', $request['order_id'])->update(['part_payment' => 'full', 'admin_commission' => \Illuminate\Support\Facades\DB::raw('admin_commission + ' . $admin_commission), 'gst_amount' => \Illuminate\Support\Facades\DB::raw('gst_amount + ' . $gst_amount), 'final_amount' => \Illuminate\Support\Facades\DB::raw('final_amount + ' . $final_amount), 'amount' => \Illuminate\Support\Facades\DB::raw('amount + ' . $request['payment_amount'])]);
                    $tourOrder = TourOrder::where('id', ($request['order_id'] ?? ''))->first();
                    $wallet_transaction = new \App\Models\WalletTransaction();
                    $wallet_transaction->user_id = $user['id'];
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Tour order';
                    $wallet_transaction->transaction_type = 'tour_order';
                    $wallet_transaction->balance = User::where('id', $user['id'])->first()['wallet_balance'];
                    $wallet_transaction->debit = $request->payment_amount;
                    $wallet_transaction->save();
                    $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                    $message_data['title_name'] = ($event_booking['Tour']['tour_name'] ?? '');
                    $message_data['booking_date'] = ($tourOrder['pickup_date'] ?? '');
                    $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                    $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                    $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($event_booking['Tour']['tour_type'] ?? ''))));
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
                    $message_data['customer_id'] = $request->user_id;
                    if ($event_booking['Tour']['tour_image']) {
                        $message_data['type'] = 'text-with-media';
                        $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $event_booking['Tour']['tour_image'] ?? '');
                    }
                    $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$request->payment_amount ?? 0);
                    Helpers::whatsappMessage('tour', 'Tour Remaining Pay', $message_data);

                    \Illuminate\Support\Facades\DB::commit();
                    return response()->json(['status' => 1, 'message' => "Remaining amount pay Successfully", 'data' => []], 200);
                } else {
                    return response()->json(['status' => 0, 'message' => 'please wallet Amount Check', 'data' => []], 200);
                }
            } else {
                $eventtax = \App\Models\ServiceTax::find(1);
                $gst_amount = 0;
                $admin_commission = 0;
                $final_amount = $request->payment_amount;
                if ($eventtax['tour_tax']) {
                    $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                    $final_amount = $final_amount - $gst_amount;
                }
                if ($event_booking['Tour']['tour_commission']) {
                    $admin_commission = (($final_amount * $event_booking['Tour']['tour_commission']) / 100);
                    $final_amount = ($final_amount - $admin_commission);
                }
                TourOrder::where('id', $request['order_id'])->update(['part_payment' => 'full', 'admin_commission' => \Illuminate\Support\Facades\DB::raw('admin_commission + ' . $admin_commission), 'gst_amount' => \Illuminate\Support\Facades\DB::raw('gst_amount + ' . $gst_amount), 'final_amount' => \Illuminate\Support\Facades\DB::raw('final_amount + ' . $final_amount), 'amount' => \Illuminate\Support\Facades\DB::raw('amount + ' . $request['payment_amount'])]);
                $tourOrder = TourOrder::where('id', ($request['order_id'] ?? ''))->first();
                if ($request->use_date == 1) {
                    $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                    $message_data['title_name'] = ($event_booking['Tour']['tour_name'] ?? '');
                    $message_data['booking_date'] = ($tourOrder['pickup_date'] ?? '');
                    $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                    $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                    $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($event_booking['Tour']['tour_type'] ?? ''))));
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
                    $message_data['customer_id'] = $request->user_id;
                    if ($event_booking['Tour']['tour_image']) {
                        $message_data['type'] = 'text-with-media';
                        $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $event_booking['Tour']['tour_image'] ?? '');
                    }
                    $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$request->payment_amount ?? 0);
                    Helpers::whatsappMessage('tour', 'Tour Remaining Pay', $message_data);
                } else {
                    $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                    $message_data['title_name'] = ($event_booking['Tour']['tour_name'] ?? '');
                    $message_data['booking_date'] = ($tourOrder['pickup_date'] ?? '');
                    $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                    $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                    $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($event_booking['Tour']['tour_type'] ?? ''))));
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
                    $message_data['customer_id'] = $request->user_id;
                    if ($event_booking['Tour']['tour_image']) {
                        $message_data['type'] = 'text-with-media';
                        $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $event_booking['Tour']['tour_image'] ?? '');
                    }
                    $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$request->payment_amount ?? 0);
                    Helpers::whatsappMessage('tour', 'Tour Remaining Pay', $message_data);
                }
                \Illuminate\Support\Facades\DB::commit();
                return response()->json(['status' => 1, 'message' => 'Remaining Amount pay Successfully', 'data' => []], 200);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['status' => 0, 'message' => 'An error occurred: ' . $e->getMessage(), 'data' => []], 200);
        }
    }

    public function touraddcomment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            }],
            'tour_id' => ['required', function ($attribute, $value, $fail) {
                if (!TourVisits::where('id', $value)->exists()) {
                    $fail('Tour ID does not exist.');
                }
            }],
            'order_id' => 'required',
            'type' => 'required|in:view,add',
        ], [
            'user_id.required' => 'User Id is Empty!',
            'tour_id.required' => 'Tour Id is Empty!',
            'star.required' => 'Star is Empty!',
            'comment.required' => 'Comment is Empty!',
        ]);
        $validator->sometimes('star', 'required|numeric|between:1,5', function ($input) {
            return $input->type === 'add';
        });
        $validator->sometimes('comment', 'required', function ($input) {
            return $input->type === 'add';
        });

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 403);
        }
        $images = '';
        $contact = TourReviews::where('user_id', $request->user_id)->where('order_id', $request->order_id)->where('tour_id', $request->tour_id)->with(['userData'])->first();
        if ($request->type == 'view') {
            $getList['star'] = $contact['star'] ?? '';
            $getList['created_at'] = date('d M,Y h:i A', strtotime($contact['created_at'] ?? ''));
            $getList['comment'] = $contact['comment'] ?? '';
            $getList['is_edited'] = $contact['is_edited'] ?? '';
            $getList['user_name'] = $contact['userData']['name'] ?? '';
            $getList['user_image'] = getValidImage(path: 'storage/app/public/profile/' . ($contact['userData']['image'] ?? ""), type: 'backend-product');
            if (!empty($value['image'])) {
                $getList['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/review/' . $contact['image'], type: 'backend-product');
            }
            return response()->json(['status' => 1, 'message' => 'get Comment', 'recode' => 1, 'data' => $getList, 'errors' => []], 200);
        } else {
            if (!$contact || $contact['is_edited'] == 0) {
                if ($request->file('image')) {
                    $images = ImageManager::upload('tour_and_travels/review/', 'webp', $request->file('image'));
                }
                if ($contact) {
                    $contact = TourReviews::find($contact['id']);
                } else {
                    $contact = new TourReviews();
                }
                $contact->order_id = $request->order_id;
                $contact->user_id = $request->user_id;
                $contact->tour_id = $request->tour_id;
                $contact->status = 1;
                $contact->comment = $request->comment;
                $contact->star = $request->star;
                $contact->is_edited = 1;
                $contact->image = $images;
                $contact->save();
            } else {
                return response()->json(['status' => 0, 'message' => 'You have Already added Comment', 'recode' => 0, 'data' => [], 'errors' => []], 200);
            }
        }
        return response()->json(['status' => 1, 'message' => 'User Add Comment Successfully', 'recode' => 0, 'data' => [], 'errors' => []], 200);
    }

    public function gettourcomment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tour_id' => ['required', function ($attribute, $value, $fail) {
                if (!TourVisits::where('id', $value)->where('status', 1)->exists()) {
                    $fail('Tour ID does not exist.');
                }
            },],
        ], [
            'tour_id.required' => 'Tour Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 403);
        }
        if (!empty($request->user_id) && !empty($request->order_id) && !empty($request->event_id)) {
            $check = TourReviews::where('tour_id', $request->tour_id)->where('order_id', $request->order_id)->where('user_id', $request->user_id)->first();
            return response()->json(['status' => 1, 'message' => 'get Tour Comments', 'data' => $check], 200);
        } else {
            $getData = TourReviews::where(['status' => 1, 'tour_id' => $request->tour_id])->with(['userData'])->orderBy('id', 'desc')->get();
            $getData_stars = TourReviews::where(['status' => 1, 'tour_id' => $request->tour_id])->groupBy('tour_id')->avg('star');
            $getList = [];
            if (!empty($getData) && count($getData) > 0) {
                foreach ($getData as $key => $value) {
                    $getList[$key]['star'] = $value['star'];
                    $getList[$key]['created_at'] = $value['created_at'];
                    $getList[$key]['comment'] = $value['comment'];
                    $getList[$key]['user_name'] = $value['userData']['name'];
                    $getList[$key]['user_image'] = getValidImage(path: 'storage/app/public/profile/' . $value['userData']['image'], type: 'backend-product');
                    if (!empty($value['image'])) {
                        $getList[$key]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/review/' . $value['image'], type: 'backend-product');
                    }
                }
                return response()->json(['status' => 1, 'message' => 'get Tour Comments', 'tour_star' => $getData_stars, 'recode' => count($getData), 'data' => $getList], 200);
            }
        }
        return response()->json(['status' => 0, 'message' => 'No Comment', 'recode' => 0, 'data' => []], 200);
    }


    public function SearchName(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ], [
            'name.required' => 'Product name is required!',
        ]);

        $sellers = TourVisits::where(function ($q) use ($request) {
            $q->orWhere('tour_name', 'like', "%{$request['name']}%");
            $q->orWhere('cities_name', 'like', "%{$request['name']}%");
            $q->orWhere('country_name', 'like', "%{$request['name']}%");
            $q->orWhere('state_name', 'like', "%{$request['name']}%");
        })->where('status', 1)
            ->where(function ($query) {
                $query->whereIn('use_date', [0, 2, 3, 4])->orWhere(function ($query) {
                    $query->where('use_date', 1)
                        ->whereNotNull('startandend_date')
                        ->whereRaw('? < STR_TO_DATE(SUBSTRING_INDEX(startandend_date, " - ", 1), "%Y-%m-%d")', [date('Y-m-d')]);
                });
            })
            ->get();
        if ($request->role == 'web') {
            $recodes = '';
            foreach ($sellers as $product) {
                $recodes .= '<li class="list-group-item px-0 overflow-hidden">
                                <button type="submit" class="search-result-product btn p-0 m-0 search-result-product-button align-items-baseline text-start" 
                                        data-product-name="' . $product['tour_name'] . '" onclick="return $(`.search_ids`).val(`' . ($product['slug'] ?? '') . '`)">
                                    <span><i class="czi-search"></i></span>
                                    <div class="text-truncate">' . $product['tour_name'] . '</div>
                                    <span class="px-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-up-left" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M2 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1H3.707l10.147 10.146a.5.5 0 0 1-.708.708L3 3.707V8.5a.5.5 0 0 1-1 0z"/>
                                        </svg>
                                    </span>
                                </button>
                                
                            </li>';
            }
        } else {
            $recodes = [];
            foreach ($sellers as $ky => $product) {
                $recodes[$ky] = ['id' => $product['id'], 'name' => $product['tour_name']];
            }
        }
        if (!empty($sellers) && count($sellers) > 0) {
            return response()->json(['status' => 1, 'count' => count($sellers), 'data' => $recodes], 200);
        } else {
            return response()->json(['status' => 0, 'count' => 0, 'data' => (($request->role == 'web') ? '' : [])], 400);
        }
    }

    public function TourCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            },],
            'coupon_code' => ['required', function ($attribute, $value, $fail) {
                if (!Coupon::where('code', $value)->where('coupon_type', 'tour_order')->where('status', 1)->whereDate('start_date', '<=', date('Y-m-d'))->whereDate('expire_date', '>=', date('Y-m-d'))->exists()) {
                    $fail('Invalid Coupon Code.');
                }
            }],

            'amount' => 'required|numeric|min:1',
        ], [
            'user_id.required' => 'User Id is Empty!',
            'coupon_code.required' => 'Coupon Code is Empty!',
            'amount.required' => 'Amount is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $couponData = Coupon::where('code', $request->get('coupon_code'))->where('coupon_type', 'tour_order')->where('status', 1)->whereDate('start_date', '<=', date('Y-m-d'))->whereDate('expire_date', '>=', date('Y-m-d'))->first();
        $checkCoupon = TourOrder::where('coupon_id', ($couponData['id'] ?? ""))->where('amount_status', 1)->where('user_id', $request->get('user_id'))->count();
        if (($couponData['limit'] ?? 0) <= $checkCoupon) {
            return response()->json(['status' => 0, 'message' => 'The coupon code has already been used', 'recode' => 0, 'data' => []], 200);
        }
        if ($couponData['customer_id'] != 0 && $couponData['customer_id'] != $request->user_id) {
            return response()->json(['status' => 0, 'message' => 'Invalid Coupon Code', 'recode' => 0, 'data' => []], 200);
        }
        if (($couponData['min_purchase'] > $request->get('amount'))) {
            return response()->json(['status' => 0, 'message' => 'Minimum amount Rs ' . ($couponData['min_purchase']) . ' This coupon is applicable', 'recode' => 0, 'data' => []], 200);
        }
        $coupon_amount = 0;
        $final_amount = $request->get('amount');
        if ($couponData['discount_type'] == 'amount') {
            $coupon_amount = $couponData['discount'];
            $final_amount = ($final_amount - ($couponData['discount'] ?? 0));
        }
        if ($couponData['discount_type'] == 'percentage') {
            $coupon_amount =  round((($final_amount * ($couponData['discount'] ?? 0)) / 100), 2);
            if ($couponData['max_discount'] < $coupon_amount) {
                $coupon_amount =  $couponData['max_discount'];
            }
            $final_amount =  ($final_amount - $coupon_amount);
        }


        return response()->json(['status' => 1, 'message' => 'Successfully Coupon Apply', 'recode' => 1, 'data' => ['coupon_id' => $couponData['id'], 'coupon_amount' => $coupon_amount, 'final_amount' => $final_amount]], 200);
    }

    public function TourGetDistance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tour_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (TourVisits::where('id', $value)->whereNull('lat')->whereNull('long')->exists()) {
                    $fail('The selected Tour ID is invalid or already has latitude and longitude set.');
                }
            },],
            'lat' => "required",
            'long' => "required",
        ], [
            'tour_id.required' => 'Tour Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getTour = TourVisits::where('id', $request->tour_id)->first();
        $ExChargeAmount = [];
        if (!empty($request->cab_id)) {
            $bus_list = json_decode($getTour['cab_list_price'] ?? '[]', true);
            foreach ($bus_list as $cab) {
                if ($cab['cab_id'] == $request->cab_id) {
                    $ExChargeAmount = $cab['exprice'] ?? [];
                    break;
                }
            }
        }
        $unit = 'k';
        $earthRadiusKm = 6371;
        $lat1 = deg2rad($getTour['lat']);
        $long1 = deg2rad($getTour['long']);
        $lat2 = deg2rad($request->lat);
        $long2 = deg2rad($request->long);

        $dLat = $lat2 - $lat1;
        $dLon = $long2 - $long1;

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadiusKm * $c;

        // if ($unit == 'M') {
        //     return $distance * 0.621371;
        // }
        $totalAmount = 0;
        $matched = false;
        if ($ExChargeAmount && is_array($ExChargeAmount)) {
            foreach ($ExChargeAmount as $range) {
                if ($distance >= $range['start'] && $distance <= $range['end']) {
                    $totalAmount = $range['charge'] + $range['driver'];
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $last = end($ExChargeAmount);
                $totalAmount = $last['charge'] + $last['driver'];
            }
        }

        return response()->json(['status' => 1, 'message' => 'Successfully', 'recode' => 1, 'data' => round($distance, 2), 'ExChargeAmount' => $totalAmount], 200);
    }

    public function TourPending(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected traveller Id is invalid or inactive.');
                }
            },],
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $all_booking = \App\Models\TourOrder::whereIn('status', [1, 0])->where(['refund_status' => 0, 'pickup_status' => 0, 'amount_status' => 1, 'drop_status' => 0, 'cab_assign' => 0])
            ->where('pickup_date', '>', \Carbon\Carbon::today()->toDateString())
            ->whereHas('accept', function ($query) use ($request) {
                $query->where('tour_order_accept.status', 1)->where('traveller_id', $request['cab_assign']);
            })->withCabOrderCheck($request->cab_assign)->with(['accept'])->orderBy('id', 'desc')->get();


        if (!empty($all_booking) && count($all_booking) > 0) {
            foreach ($all_booking as $key => $value) {
                $bookingList[$key]['id'] = $value['id'];
                $bookingList[$key]['tour_id'] = $value['tour_id'];
                $bookingList[$key]['order_id'] = $value['order_id'];
                $bookingList[$key]['qty'] = $value['qty'];
                $bookingList[$key]['user_name'] = $value['userData']['name'];
                $bookingList[$key]['user_phone'] = $value['userData']['phone'];
                $bookingList[$key]['user_image'] = getValidImage(path: 'storage/app/public/profile/' . $value['userData']['image'], type: 'backend-product');
                $bookingList[$key]['en_tour_name'] = $value['Tour']['tour_name'];
                $hindi_tour = (TourVisits::find($value['tour_id']))->translations()->pluck('value', 'key')->toArray();
                $bookingList[$key]['hi_tour_name'] = $hindi_tour['tour_name'] ?? "";
                $bookingList[$key]['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $value['Tour']['tour_image'], type: 'backend-product');
                $bookingList[$key]['amount'] = (($value['amount'] ?? 0) + ($value['coupon_amount'] ?? 0));
                $bookingList[$key]['coupon_amount'] = $value['coupon_amount'];
                $bookingList[$key]['pay_amount'] = $value['amount'];
                $bookingList[$key]['amount_status'] = $value['amount_status'];
                $bookingList[$key]['transaction_id'] = $value['transaction_id'];
                $bookingList[$key]['refund_status'] = $value['refund_status'];
                $bookingList[$key]['pickup_address'] = $value['pickup_address'];
                $bookingList[$key]['pickup_date'] = $value['pickup_date'];
                $bookingList[$key]['pickup_time'] = $value['pickup_time'];
                $bookingList[$key]['booking_time'] = $value['created_at'];

                $getdata = TourOrder::where('id', $value['id'])->with(['Tour', 'company', 'Driver', 'CabsManage'])->withDriverInfo($value['id'])->first();

                if ($getdata) {

                    $bookingList[$key]['driver_data']  = json_decode($getdata['driver_data'] ?? '[]') ?? '';
                    $bookingList[$key]['Cabs_data']  = json_decode($getdata['Cabs_data'] ?? '[]') ?? '';

                    $bookingList[$key]['tour_bookings'] = []; //$getdata['booking_package'];

                    $assign_cabs_use_allPackages = 0;
                    $assign_cabs_use_qtys = 0;
                    if (!empty($getdata['booking_package']) && json_decode($getdata['booking_package'], true)) {
                        foreach (json_decode($getdata['booking_package'], true) as $val) {
                            if ($getdata['use_date'] == 0 || ($val['type'] == 'cab' && $getdata['use_date'] == 1) || ($val['type'] != 'ex_distance' && $getdata['use_date'] == 2) || ($val['type'] != 'ex_distance' && $getdata['use_date'] == 3) || ($val['type'] != 'ex_distance' && $getdata['use_date'] == 4)) {
                                if ($val['type'] == 'cab') {
                                    $tourPackages = \App\Models\TourCab::where('id', $val['id'])->first();
                                    $images = getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . $tourPackages['image'], type: 'backend-product');
                                    $assign_cabs_use_allPackages = $val['id'];
                                    $assign_cabs_use_qtys = $val['qty'];
                                } elseif ($val['type'] == 'other' || $val['type'] == 'foods' || $val['type'] == 'hotel') {
                                    $tourPackages = \App\Models\TourPackage::where('id', $val['id'])->first();
                                    $images = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . $tourPackages['image'], type: 'backend-product');
                                } else {
                                    $tourPackages = [];
                                }
                            }
                        }
                    }


                    $ppi = 0;
                    if (!empty($getdata['Tour']['cab_list_price']) && json_decode($getdata['Tour']['cab_list_price'], true)) {
                        foreach (json_decode($getdata['Tour']['cab_list_price'], true) as $p_info) {
                            if ($assign_cabs_use_allPackages == $p_info['cab_id']) {
                                $tourPackages = \App\Models\TourCab::where('id', $p_info['cab_id'])->first();
                                $bookingList[$key]['tour_bookings'][$ppi]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                                $bookingList[$key]['tour_bookings'][$ppi]['name'] =  $tourPackages['name'] ?? "";
                                $bookingList[$key]['tour_bookings'][$ppi]['seats'] = $tourPackages['seats'] ?? "";
                                $bookingList[$key]['tour_bookings'][$ppi]['qty'] = $assign_cabs_use_qtys;
                                $bookingList[$key]['tour_bookings'][$ppi]['amount'] = setCurrencySymbol(amount: usdToDefaultCurrency(amount: ((float)$p_info['price'] ?? 0) * $assign_cabs_use_qtys ?? 1), currencyCode: getCurrencyCode());
                                $ppi++;
                            }
                        }
                    }
                    if (!empty($getdata['Tour']['package_list_price']) && json_decode($getdata['Tour']['package_list_price'], true)) {
                        foreach (json_decode($getdata['Tour']['package_list_price'], true) as $p_info) {
                            $tourPackages = \App\Models\TourPackage::where('id', $p_info['id'])->first();
                            $bookingList[$key]['tour_bookings'][$ppi]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                            $bookingList[$key]['tour_bookings'][$ppi]['name'] = $tourPackages['name'] ?? "";
                            $bookingList[$key]['tour_bookings'][$ppi]['seats'] = "";
                            $bookingList[$key]['tour_bookings'][$ppi]['qty']  = $assign_cabs_use_qtys;
                            $bookingList[$key]['tour_bookings'][$ppi]['amount'] = setCurrencySymbol(amount: usdToDefaultCurrency(amount: ((float)$p_info['pprice'] ?? 0) * $assign_cabs_use_qtys ?? 1), currencyCode: getCurrencyCode());
                            $ppi++;
                        }
                    }
                    $bookingList[$key]['tour_itinerary'] = [];
                    if (isset($getdata['Tour'], $getdata['Tour']['TourPlane'], $getdata['Tour']['TourPlane'][0])) {
                        $vv = 0;
                        foreach ($getdata['Tour']['TourPlane'] as $viisit) {
                            $gethindi = $viisit->translations()->pluck('value', 'key')->toArray();
                            $bookingList[$key]['tour_itinerary'][$vv]['id'] = $viisit['id'];
                            $bookingList[$key]['tour_itinerary'][$vv]['en_name'] = $viisit['name'];
                            $bookingList[$key]['tour_itinerary'][$vv]['hi_name'] = $gethindi['name'];
                            $bookingList[$key]['tour_itinerary'][$vv]['en_time'] = $viisit['time'];
                            $bookingList[$key]['tour_itinerary'][$vv]['hi_time'] = $gethindi['time'];

                            $bookingList[$key]['tour_itinerary'][$vv]['en_description'] = $viisit['description'];
                            $bookingList[$key]['tour_itinerary'][$vv]['hi_description'] = $gethindi['description'];

                            $itinerary_image_list = [];
                            if (!empty($viisit['images']) && json_decode($viisit['images'], true)) {
                                foreach (json_decode($viisit['images'], true) as $itn_va) {
                                    $itinerary_image_list[] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit_place/' . $itn_va, type: 'backend-product');
                                }
                            }
                            $bookingList[$key]['tour_itinerary'][$vv]['image'] = $itinerary_image_list;
                            $vv++;
                        }
                    }

                    $bookingList[$key]['part_payment'] = $getdata['part_payment'];
                    $bookingList[$key]['advance_withdrawal_amount'] = $getdata['advance_withdrawal_amount'];
                    $bookingList[$key]['status'] = $getdata['status'];
                    $bookingList[$key]['refund_amount'] = $getdata['refund_amount'];
                    if (!empty($getdata['company'])) {
                        $bookingList[$key]['traveller_info'] = [
                            'image' => getValidImage(path: 'storage/app/public/tour_and_travels/doc/' . ($getdata['company']['image'] ?? ''), type: 'backend-profile'),
                            'name' => ($getdata['company']['company_name'] ?? ''),
                            'phone' => $getdata['company']['phone_no'],
                            'email' => $getdata['company']['email']
                        ];
                    } else {
                        $bookingList[$key]['traveller_info'] = null;
                    }
                }
            }
            return response()->json(['status' => 1, 'count' => count($bookingList), 'data' => $bookingList], 200);
        } else {
            return response()->json(['status' => 0, 'count' => '0', 'data' => []], 200);
        }
    }

    public function CabTourOrdercancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'order_id' => ['required', function ($attribute, $value, $fail) {
                if (!TourOrder::where('id', $value)->where('refund_status', 0)->whereIn('status', [0, 1])->where('pickup_status', 0)->exists()) {
                    $fail('Order ID does not exist.');
                }
            },],
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
            'order_id.required' => 'Order Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $getData =  TourOrder::where('id', $request->order_id)->where('cab_assign', $request->cab_assign)->with(['userData'])->first();
        if ($getData) {
            TourOrder::where('id', $request->order_id)->where('cab_assign', $request->cab_assign)->update(['cab_assign' => 0]);
            TourAndTravel::where('id', $request->cab_assign)->update(["cancel_order" => DB::raw('cancel_order + ' . 1)]);
            return response()->json(['status' => 1, 'message' => 'Cancel Order', 'data' => []], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found Data', 'data' => []], 200);
        }
    }

    public function TourAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'order_id' => ['required', function ($attribute, $value, $fail) {
                if (!TourOrder::where('id', $value)->where('cab_assign', '=', 0)->exists()) {
                    $fail('The selected Order already assigned.');
                }
            },],
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
            'order_id.required' => 'Order Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        TourOrder::where('id', $request->order_id)->update(['cab_assign' => $request->cab_assign]);
        return response()->json(['status' => 1, 'message' => 'cab assign Successfully', 'data' => []], 200);
    }

    public function VendorTourOrderView(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', function ($attribute, $value, $fail) {
                if (!TourOrder::where('id', $value)->exists()) {
                    $fail('The selected Order id invalid.');
                }
            },],
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
            'order_id.required' => 'Order Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getdata = TourOrder::where('id', $request->order_id)->with(['Tour', 'company', 'Driver', 'CabsManage'])->first();
        $bookingList = [];
        if ($getdata) {
            $bookingList['id'] = $getdata['id'];
            $bookingList['tour_id'] = $getdata['tour_id'];
            $bookingList['order_id'] = $getdata['order_id'];
            $bookingList['qty'] = $getdata['qty'];
            $bookingList['user_name'] = $getdata['userData']['name'];
            $bookingList['user_phone'] = $getdata['userData']['phone'];
            $bookingList['user_image'] = getValidImage(path: 'storage/app/public/profile/' . $getdata['userData']['image'], type: 'backend-profile');
            $bookingList['en_tour_name'] = $getdata['Tour']['tour_name'];
            $hindi_tour = (TourVisits::find($getdata['tour_id']))->translations()->pluck('value', 'key')->toArray();
            $bookingList['hi_tour_name'] = $hindi_tour['tour_name'] ?? "";
            $bookingList['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $getdata['Tour']['tour_image'], type: 'backend-product');
            $bookingList['tour_bookings'] = []; //$getdata['booking_package'];

            $assign_cabs_use_allPackages = 0;
            $assign_cabs_use_qtys = 0;
            if (!empty($getdata['booking_package']) && json_decode($getdata['booking_package'], true)) {
                foreach (json_decode($getdata['booking_package'], true) as $val) {
                    if ($getdata['use_date'] == 0 || ($val['type'] == 'cab' && $getdata['use_date'] == 1) || ($val['type'] != 'ex_distance' && $getdata['use_date'] == 2) || ($val['type'] != 'ex_distance' && $getdata['use_date'] == 3) || ($val['type'] != 'ex_distance' && $getdata['use_date'] == 4)) {
                        if ($val['type'] == 'cab') {
                            $tourPackages = \App\Models\TourCab::where('id', $val['id'])->first();
                            $images = getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . $tourPackages['image'], type: 'backend-product');
                            $assign_cabs_use_allPackages = $val['id'];
                            $assign_cabs_use_qtys = $val['qty'];
                        } elseif ($val['type'] == 'other') {
                            $tourPackages = \App\Models\TourPackage::where('id', $val['id'])->first();
                            $images = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . $tourPackages['image'], type: 'backend-product');
                        } else {
                            $tourPackages = [];
                        }
                    }
                }
            }


            $bookingList['tour_bookings'] = [];
            $ppi = 0;
            if (!empty($getdata['Tour']['cab_list_price']) && json_decode($getdata['Tour']['cab_list_price'], true)) {
                foreach (json_decode($getdata['Tour']['cab_list_price'], true) as $p_info) {
                    if ($assign_cabs_use_allPackages == $p_info['cab_id']) {
                        $tourPackages = \App\Models\TourCab::where('id', $p_info['cab_id'])->first();
                        $bookingList['tour_bookings'][$ppi]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                        $bookingList['tour_bookings'][$ppi]['name'] =  $tourPackages['name'] ?? "";
                        $bookingList['tour_bookings'][$ppi]['seats'] = $tourPackages['seats'] ?? "";
                        $bookingList['tour_bookings'][$ppi]['qty'] = $assign_cabs_use_qtys;
                        $bookingList['tour_bookings'][$ppi]['amount'] = setCurrencySymbol(amount: usdToDefaultCurrency(amount: ((float)$p_info['price'] ?? 0) * $assign_cabs_use_qtys ?? 1), currencyCode: getCurrencyCode());
                        $ppi++;
                    }
                }
            }
            if (!empty($getdata['Tour']['package_list_price']) && json_decode($getdata['Tour']['package_list_price'], true)) {
                foreach (json_decode($getdata['Tour']['package_list_price'], true) as $p_info) {
                    $tourPackages = \App\Models\TourPackage::where('id', $p_info['id'])->first();
                    $bookingList['tour_bookings'][$ppi]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                    $bookingList['tour_bookings'][$ppi]['name'] = $tourPackages['name'] ?? "";
                    $bookingList['tour_bookings'][$ppi]['seats'] = "";
                    $bookingList['tour_bookings'][$ppi]['qty']  = $assign_cabs_use_qtys;
                    $bookingList['tour_bookings'][$ppi]['amount'] = setCurrencySymbol(amount: usdToDefaultCurrency(amount: ((float)$p_info['pprice'] ?? 0) * $assign_cabs_use_qtys ?? 1), currencyCode: getCurrencyCode());
                    $ppi++;
                }
            }
            $bookingList['tour_itinerary'] = [];
            if (isset($getdata['Tour'], $getdata['Tour']['TourPlane'], $getdata['Tour']['TourPlane'][0])) {
                $vv = 0;
                foreach ($getdata['Tour']['TourPlane'] as $viisit) {
                    $gethindi = $viisit->translations()->pluck('value', 'key')->toArray();
                    $bookingList['tour_itinerary'][$vv]['id'] = $viisit['id'];
                    $bookingList['tour_itinerary'][$vv]['en_name'] = $viisit['name'];
                    $bookingList['tour_itinerary'][$vv]['hi_name'] = $gethindi['name'];
                    $bookingList['tour_itinerary'][$vv]['en_time'] = $viisit['time'];
                    $bookingList['tour_itinerary'][$vv]['hi_time'] = $gethindi['time'];

                    $bookingList['tour_itinerary'][$vv]['en_description'] = $viisit['description'];
                    $bookingList['tour_itinerary'][$vv]['hi_description'] = $gethindi['description'];

                    $itinerary_image_list = [];
                    if (!empty($viisit['images']) && json_decode($viisit['images'], true)) {
                        foreach (json_decode($viisit['images'], true) as $itn_va) {
                            $itinerary_image_list[] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit_place/' . $itn_va, type: 'backend-product');
                        }
                    }
                    $bookingList['tour_itinerary'][$vv]['image'] = $itinerary_image_list;
                    $vv++;
                }
            }

            $bookingList['order_id'] = $getdata['order_id'];
            $bookingList['amount'] = (($getdata['amount'] ?? 0) + ($getdata['coupon_amount'] ?? 0));
            $bookingList['coupon_amount'] = $getdata['coupon_amount'];
            $bookingList['pay_amount'] = $getdata['amount'];
            $bookingList['part_payment'] = $getdata['part_payment'];
            $bookingList['advance_withdrawal_amount'] = $getdata['advance_withdrawal_amount'];
            $bookingList['amount_status'] = $getdata['amount_status'];
            $bookingList['transaction_id'] = $getdata['transaction_id'];
            $bookingList['refund_status'] = $getdata['refund_status'];
            $bookingList['pickup_address'] = $getdata['pickup_address'];
            $bookingList['pickup_date'] = $getdata['pickup_date'];
            $bookingList['pickup_time'] = $getdata['pickup_time'];
            $bookingList['booking_time'] = $getdata['created_at'];
            $bookingList['status'] = $getdata['status'];
            $bookingList['refund_status'] = $getdata['refund_status'];
            $bookingList['refund_amount'] = $getdata['refund_amount'];
            if (!empty($getdata['company'])) {
                $bookingList['traveller_info'] = [
                    'image' => getValidImage(path: 'storage/app/public/tour_and_travels/doc/' . ($getdata['company']['image'] ?? ''), type: 'backend-profile'),
                    'name' => ($getdata['company']['company_name'] ?? ''),
                    'phone' => $getdata['company']['phone_no'],
                    'email' => $getdata['company']['email']
                ]; //$value['CabsManage'];
            } else {
                $bookingList['traveller_info'] = [];
            }
            if (!empty($getdata['CabsManage'])) {
                $bookingList['cab_info'] = [
                    'image' => getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_cab/' . ($getdata['CabsManage']['Cabs']['image'] ?? ''), type: 'backend-product'),
                    'name' => ($getdata['CabsManage']['Cabs']['name'] ?? ''),
                    'reg_number' => $getdata['CabsManage']['reg_number'],
                    'model_number' => $getdata['CabsManage']['model_number']
                ]; //$value['CabsManage'];
            } else {
                $bookingList['cab_info'] = [];
            }
            if (!empty($getdata['Driver'])) {
                $bookingList['driver_info'] = [
                    'image' => getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/' . $getdata['Driver']['image'] ?? '', type: 'backend-profile'),
                    'name' => $getdata['Driver']['name'],
                    'phone' => $getdata['Driver']['phone']
                ];
            } else {
                $bookingList['driver_info'] = [];
            }
        }
        return response()->json(['status' => 1, 'message' => 'cab assign Successfully', 'data' => $bookingList], 200);
    }

    public function TourAssignCabDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected traveller Id is invalid or inactive.');
                }
                if (!TourOrder::where('id', $request['order_id'])->where('cab_assign', $value)->where('status', 1)->exists()) {
                    $fail('Please confirm the order first.');
                }
            },],
            'order_id' => 'required|exists:tour_order,id',
            'cab_id' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $cabIds = is_string($value) ? json_decode($value, true) : $value;
                    $cabIds = is_array($cabIds) ?  $cabIds : explode(',', (string) $cabIds);
                    $currentTour = \App\Models\TourOrder::with(['Tour'])->find($request->order_id);
                    if (!$currentTour) return;

                    if ($currentTour['Tour']['tour_type'] == 'cities_tour') {
                        $bookingPackage = json_decode($currentTour['booking_package'], true);
                        if (is_array($bookingPackage) && $currentTour['Tour']['tour_type'] == 'cities_tour') {
                            foreach ($bookingPackage as $item) {
                                if (isset($item['type']) && $item['type'] === 'cab' && isset($item['qty'])) {
                                    $qty_cities = $item['qty'];
                                    break;
                                }
                            }
                        }
                        if ($qty_cities > count($cabIds)) {
                            $fail("Please select a $qty_cities Cabs.");
                        }
                    }

                    $pickupDate = $currentTour->pickup_date;
                    $days = \App\Models\TourVisits::where('id', $currentTour->tour_id)->value('days');
                    $endDate = date('Y-m-d', strtotime("+$days days", strtotime($pickupDate . ' -1 day')));

                    $currentTour->update(['drop_date' => $endDate]);

                    foreach ($cabIds as $cabId) {
                        $overlapping = \App\Models\TourOrder::where('drop_status', 0)
                            ->where('id', '!=', $request->order_id)
                            ->where(function ($query) use ($cabId) {
                                $query->whereJsonContains('traveller_cab_id', (string) $cabId) // JSON case
                                    ->orWhereRaw("FIND_IN_SET(?, traveller_cab_id)", [$cabId]); // Comma-separated case
                            })
                            ->where(function ($query) use ($pickupDate, $endDate) {
                                $query->whereBetween('pickup_date', [$pickupDate, $endDate])
                                    ->orWhereBetween('drop_date', [$pickupDate, $endDate])
                                    ->orWhereRaw('? BETWEEN pickup_date AND drop_date', [$pickupDate])
                                    ->orWhereRaw('? BETWEEN pickup_date AND drop_date', [$endDate]);
                            })
                            ->exists();
                        $cabs_data  = \App\Models\TourCabManage::where('id', $cabId)->with(['Cabs'])->first();
                        $packages_bookings = json_decode($currentTour->booking_package ?? '[]', true);
                        $cabPackage_check = collect($packages_bookings)->firstWhere('type', 'cab');
                        if ((($cabs_data['traveller_id'] ?? "") != $request->cab_assign) || ($cabs_data['cab_id'] ?? "") != ($cabPackage_check['id'] ?? "")) { //satish
                            $fail("Please select a valid cab.");
                        }
                        if ($overlapping) {
                            if ($currentTour['Tour']['use_date'] == 1 || $currentTour['Tour']['use_date'] == 4) {
                                $getcheckQty = \App\Models\TourOrder::where(function ($query) use ($cabId, $request) {
                                    $query->whereRaw("JSON_CONTAINS(traveller_cab_id, ?)", [json_encode((string) $cabId)])
                                        ->orWhere('id', $request->order_id);
                                }) //whereRaw("JSON_CONTAINS(traveller_cab_id, ?)", [json_encode((string) $cabId)])
                                    ->where('tour_id', $currentTour['tour_id'])
                                    ->where('pickup_status', 0)
                                    ->where('pickup_date', [$pickupDate])
                                    ->select('booking_package')
                                    ->get()
                                    ->map(function ($tourVisit) {
                                        $packages = json_decode($tourVisit->booking_package, true);
                                        $cabPackage = collect($packages)->firstWhere('type', 'cab');
                                        return $cabPackage ? (int) $cabPackage['qty'] : 0;
                                    })->sum();
                                if ($cabs_data && $cabs_data['Cabs']['seats'] > 0) {
                                    if ($getcheckQty > $cabs_data['Cabs']['seats']) {
                                        $fail("Cab ID $cabId is already booked for the given seats. Only " . ($getcheckQty - $cabs_data['Cabs']['seats']) . " seats are available.");
                                    }
                                } else {
                                    $fail("Cab ID $cabId is already booked for the given seats. Only " . ($getcheckQty - $cabs_data['Cabs']['seats']) . " seats are available.");
                                }
                            } else {
                                $fail("Cab ID $cabId is already booked for the given date range.");
                            }
                        }
                    }
                },
            ],

            'driver_id' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    // Ensure value is a string before decoding
                    $driverIds = is_string($value) ? json_decode($value, true) : $value;
                    $driverIds = is_array($driverIds) ? $driverIds : explode(',', (string) $driverIds);

                    $currentTour = \App\Models\TourOrder::with(['Tour'])->find($request->order_id);
                    if (!$currentTour) return;

                    if ($currentTour['Tour']['tour_type'] == 'cities_tour') {
                        $bookingPackage = json_decode($currentTour['booking_package'], true);
                        if (is_array($bookingPackage) && $currentTour['Tour']['tour_type'] == 'cities_tour') {
                            foreach ($bookingPackage as $item) {
                                if (isset($item['type']) && $item['type'] === 'cab' && isset($item['qty'])) {
                                    $qty_cities = $item['qty'];
                                    break;
                                }
                            }
                        }
                        if ($qty_cities > count($driverIds)) {
                            $fail("Please select a $qty_cities Driver.");
                        }
                    }

                    $pickupDate = $currentTour->pickup_date;
                    $days = \App\Models\TourVisits::where('id', $currentTour->tour_id)->value('days');
                    $endDate = date('Y-m-d', strtotime("+$days days", strtotime($pickupDate . ' -1 day')));

                    foreach ($driverIds as $driverId) {
                        $overlapping = \App\Models\TourOrder::where('drop_status', 0)
                            ->where('id', '!=', $request->order_id)
                            ->where(function ($query) use ($driverId) {
                                $query->whereJsonContains('traveller_driver_id', (string) $driverId) // JSON case
                                    ->orWhereRaw("FIND_IN_SET(?, traveller_driver_id)", [$driverId]); // Comma-separated case
                            })
                            ->where(function ($query) use ($pickupDate, $endDate) {
                                $query->whereBetween('pickup_date', [$pickupDate, $endDate])
                                    ->orWhereBetween('drop_date', [$pickupDate, $endDate])
                                    ->orWhereRaw('? BETWEEN pickup_date AND drop_date', [$pickupDate])
                                    ->orWhereRaw('? BETWEEN pickup_date AND drop_date', [$endDate]);
                            })
                            ->exists();

                        $driver_data_check  = \App\Models\TourDriverManage::where('id', $driverId)->where('traveller_id', $request->cab_assign)->first();
                        if (!$driver_data_check) {
                            $fail("Please select a valid Driver.");
                        }

                        if ($overlapping) {
                            if ($currentTour['Tour']['use_date'] == 1 || $currentTour['Tour']['use_date'] == 4) {
                                $cabIds = is_string($value) ? json_decode($value, true) : $value;
                                $cabIds = is_array($cabIds) ?  $cabIds : explode(',', (string) $cabIds);

                                $cabs_data  = \App\Models\TourCabManage::where('id', $cabIds[0])->with(['Cabs'])->first();
                                $getcheckQty = \App\Models\TourOrder::where(function ($query) use ($cabIds, $request) {
                                    $query->whereRaw("JSON_CONTAINS(traveller_cab_id, ?)", [json_encode((string) $cabIds[0])])
                                        ->orWhere('id', $request->order_id);
                                })
                                    //whereRaw("JSON_CONTAINS(traveller_cab_id, ?)", [json_encode((string) $cabIds[0])])
                                    ->where('tour_id', $currentTour['tour_id'])->where('pickup_status', 0)
                                    ->where('pickup_date', [$pickupDate])
                                    ->select('booking_package')
                                    ->get()
                                    ->map(function ($tourVisit) {
                                        $packages = json_decode($tourVisit->booking_package, true);
                                        $cabPackage = collect($packages)->firstWhere('type', 'cab');
                                        return $cabPackage ? (int) $cabPackage['qty'] : 0;
                                    })->sum();
                                if ($cabs_data && $cabs_data['Cabs']['seats'] > 0) {
                                    if ($getcheckQty > $cabs_data['Cabs']['seats']) {
                                        $fail("This Cab is full and Driver Already Assign.");
                                        $fail("Driver ID $driverId is already booked for the given seats. Only " . ($getcheckQty - $cabs_data['Cabs']['seats']) . " seats are available.");
                                    }
                                } else {
                                    $fail("Driver ID $driverId is already booked for the given seats. Only " . ($getcheckQty - $cabs_data['Cabs']['seats']) . " seats are available.");
                                }
                            } else {
                                $fail("Driver ID $driverId is already booked for the given date range.");
                            }
                        }
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $save = TourOrder::find($request->order_id);
        $save->traveller_cab_id = $request['cab_id'];
        $save->traveller_driver_id = $request['driver_id'];
        $save->save();

        $tourOrder = TourOrder::where('id', $request->order_id)->with(['Tour', 'Driver', 'CabsManage'])->withDriverInfo($request->order_id)->first();
        if ($tourOrder['driver_data'] && json_decode($tourOrder['driver_data'], true)) {
            foreach (json_decode($tourOrder['driver_data'], true) as $kk => $infos) {
                $message_data['driver_name'] = ($infos['name'] ?? '');
                $message_data['driver_number'] = "+91" . ($infos['phone'] ?? '');
                $message_data['vehicle_name'] = (json_decode($tourOrder['Cabs_data'] ?? '[]', true)[$kk]['cab_name'] ?? '');
                $message_data['vehicle_number'] = (json_decode($tourOrder['Cabs_data'] ?? '[]', true)[$kk]['reg_number'] ?? '');
                $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                $message_data['title_name'] = ($tourOrder['Tour']['tour_name'] ?? '');
                $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['pickup_date'] ?? ''));
                $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourOrder['Tour']['tour_type'] ?? ''))));
                $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
                $message_data['customer_id'] = $tourOrder['user_id'];
                if ($tourOrder['Tour']['tour_image']) {
                    $message_data['type'] = 'text-with-media';
                    $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $tourOrder['Tour']['tour_image'] ?? '');
                }
                $remain_amount = ((!empty($tourOrder['part_payment']) && $tourOrder['part_payment'] == 'part') ? $tourOrder['amount'] : 0);
                $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$remain_amount ?? 0);
                \App\Utils\Helpers::whatsappMessageVendorSend('tour', 'driver_reminder', $message_data);
            }
        }

        if ($save) {
            return response()->json(['status' => 1, 'message' => "Assign cab and Driver", 'count' => '1', 'data' => []], 200);
        } else {
            return response()->json(['status' => 0, 'message' => "Assign Failed", 'count' => '0', 'data' => []], 200);
        }
    }

    public function TourAssignConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            "type" => "required|in:confirm,one,two",
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
            'type.required' => 'Please Choose confirm,one,two',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $booking_query = TourOrder::where('cab_assign', $request->cab_assign)->where('amount_status', 1)->whereIn('refund_status', [0, 3]);
        if ($request->type == "confirm") {
            $booking_query->where('pickup_status', 0)->where('drop_status', 0);
        } elseif ($request->type == "one") {
            $booking_query->where('pickup_status', 1)->where('drop_status', 0);
        } elseif ($request->type == "two") {
            $booking_query->where('pickup_status', 1)->where('drop_status', 1);
        }
        $all_booking = $booking_query->with(['Tour', 'company', "userData", 'Driver', 'CabsManage'])->orderBy('id', 'desc')->get();
        if (!empty($all_booking) && count($all_booking) > 0) {
            foreach ($all_booking as $key => $value) {
                $bookingList[$key]['id'] = $value['id'];
                $bookingList[$key]['tour_id'] = $value['tour_id'];
                $bookingList[$key]['order_id'] = $value['order_id'];
                $bookingList[$key]['qty'] = $value['qty'];
                $bookingList[$key]['user_name'] = $value['userData']['name'];
                $bookingList[$key]['user_phone'] = $value['userData']['phone'];
                $bookingList[$key]['user_image'] = getValidImage(path: 'storage/app/public/profile/' . $value['userData']['image'], type: 'backend-product');
                $bookingList[$key]['en_tour_name'] = $value['Tour']['tour_name'];
                $hindi_tour = (TourVisits::find($value['tour_id']))->translations()->pluck('value', 'key')->toArray();
                $bookingList[$key]['hi_tour_name'] = $hindi_tour['tour_name'] ?? "";
                $bookingList[$key]['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $value['Tour']['tour_image'], type: 'backend-product');
                $bookingList[$key]['order_id'] = $value['order_id'];
                $bookingList[$key]['amount'] = (($value['amount'] ?? 0) + ($value['coupon_amount'] ?? 0));
                $bookingList[$key]['coupon_amount'] = $value['coupon_amount'];
                $bookingList[$key]['pay_amount'] = $value['amount'];
                $bookingList[$key]['amount_status'] = $value['amount_status'];
                $bookingList[$key]['transaction_id'] = $value['transaction_id'];
                $bookingList[$key]['refund_status'] = $value['refund_status'];
                $bookingList[$key]['pickup_address'] = $value['pickup_address'];
                $bookingList[$key]['pickup_date'] = $value['pickup_date'];
                $bookingList[$key]['pickup_time'] = $value['pickup_time'];
                $bookingList[$key]['booking_time'] = $value['created_at'];

                $bookingList[$key]['tour_bookings'] = [];

                $assign_cabs_use_allPackages = 0;
                $assign_cabs_use_qtys = 0;
                if (!empty($value['booking_package']) && json_decode($value['booking_package'], true)) {
                    foreach (json_decode($value['booking_package'], true) as $val) {
                        if ($value['use_date'] == 0 || ($val['type'] == 'cab' && $value['use_date'] == 1) || ($val['type'] != 'ex_distance' && $value['use_date'] == 2) || ($val['type'] != 'ex_distance' && $value['use_date'] == 3) || ($val['type'] != 'ex_distance' && $value['use_date'] == 4)) {
                            if ($val['type'] == 'cab') {
                                $tourPackages = \App\Models\TourCab::where('id', $val['id'])->first();
                                $images = getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . $tourPackages['image'], type: 'backend-product');
                                $assign_cabs_use_allPackages = $val['id'];
                                $assign_cabs_use_qtys = $val['qty'];
                            } elseif ($val['type'] == 'other' || $val['type'] == 'foods' || $val['type'] == 'hotel') {
                                $tourPackages = \App\Models\TourPackage::where('id', $val['id'])->first();
                                $images = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . $tourPackages['image'], type: 'backend-product');
                            } else {
                                $tourPackages = [];
                            }
                        }
                    }
                }


                $bookingList[$key]['tour_bookings'] = [];
                $ppi = 0;
                if (!empty($value['Tour']['cab_list_price']) && json_decode($value['Tour']['cab_list_price'], true)) {
                    foreach (json_decode($value['Tour']['cab_list_price'], true) as $p_info) {
                        if ($assign_cabs_use_allPackages == $p_info['cab_id']) {
                            $tourPackages = \App\Models\TourCab::where('id', $p_info['cab_id'])->first();
                            $bookingList[$key]['tour_bookings'][$ppi]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                            $bookingList[$key]['tour_bookings'][$ppi]['name'] =  $tourPackages['name'] ?? "";
                            $bookingList[$key]['tour_bookings'][$ppi]['seats'] = $tourPackages['seats'] ?? "";
                            $bookingList[$key]['tour_bookings'][$ppi]['qty'] = $assign_cabs_use_qtys;
                            $bookingList[$key]['tour_bookings'][$ppi]['amount'] = setCurrencySymbol(amount: usdToDefaultCurrency(amount: ((float)$p_info['price'] ?? 0) * $assign_cabs_use_qtys ?? 1), currencyCode: getCurrencyCode());
                            $ppi++;
                        }
                    }
                }
                if (!empty($value['Tour']['package_list_price']) && json_decode($value['Tour']['package_list_price'], true)) {
                    foreach (json_decode($value['Tour']['package_list_price'], true) as $p_info) {
                        $tourPackages = \App\Models\TourPackage::where('id', $p_info['id'])->first();
                        $bookingList[$key]['tour_bookings'][$ppi]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                        $bookingList[$key]['tour_bookings'][$ppi]['name'] = $tourPackages['name'] ?? "";
                        $bookingList[$key]['tour_bookings'][$ppi]['seats'] = "";
                        $bookingList[$key]['tour_bookings'][$ppi]['qty']  = $assign_cabs_use_qtys;
                        $bookingList[$key]['tour_bookings'][$ppi]['amount'] = setCurrencySymbol(amount: usdToDefaultCurrency(amount: ((float)$p_info['pprice'] ?? 0) * $assign_cabs_use_qtys ?? 1), currencyCode: getCurrencyCode());
                        $ppi++;
                    }
                }
                $bookingList[$key]['tour_itinerary'] = [];
                if (isset($value['Tour'], $value['Tour']['TourPlane'], $value['Tour']['TourPlane'][0])) {
                    $vv = 0;
                    foreach ($value['Tour']['TourPlane'] as $viisit) {
                        $gethindi = $viisit->translations()->pluck('value', 'key')->toArray();
                        $bookingList[$key]['tour_itinerary'][$vv]['id'] = $viisit['id'];
                        $bookingList[$key]['tour_itinerary'][$vv]['en_name'] = $viisit['name'];
                        $bookingList[$key]['tour_itinerary'][$vv]['hi_name'] = $gethindi['name'];
                        $bookingList[$key]['tour_itinerary'][$vv]['en_time'] = $viisit['time'];
                        $bookingList[$key]['tour_itinerary'][$vv]['hi_time'] = $gethindi['time'];

                        $bookingList[$key]['tour_itinerary'][$vv]['en_description'] = $viisit['description'];
                        $bookingList[$key]['tour_itinerary'][$vv]['hi_description'] = $gethindi['description'];

                        $itinerary_image_list = [];
                        if (!empty($viisit['images']) && json_decode($viisit['images'], true)) {
                            foreach (json_decode($viisit['images'], true) as $itn_va) {
                                $itinerary_image_list[] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit_place/' . $itn_va, type: 'backend-product');
                            }
                        }
                        $bookingList[$key]['tour_itinerary'][$vv]['image'] = $itinerary_image_list;
                        $vv++;
                    }
                }

                $bookingList[$key]['order_id'] = $value['order_id'];
                $bookingList[$key]['amount'] = (($value['amount'] ?? 0) + ($value['coupon_amount'] ?? 0));
                $bookingList[$key]['coupon_amount'] = $value['coupon_amount'];
                $bookingList[$key]['pay_amount'] = $value['amount'];
                $bookingList[$key]['part_payment'] = $value['part_payment'];
                $bookingList[$key]['advance_withdrawal_amount'] = $value['advance_withdrawal_amount'];
                $bookingList[$key]['amount_status'] = $value['amount_status'];
                $bookingList[$key]['transaction_id'] = $value['transaction_id'];

                $total_amounts = (((float)$value['amount'] ?? 0) + ((float)$value['coupon_amount'] ?? 0));
                if ($value['part_payment'] == 'part') {
                    $total_amounts += ((float)$value['amount'] ?? 0);
                }

                $bookingList[$key]['total_amount'] = $total_amounts;
                $bookingList[$key]['remaining_amount'] = (($value['part_payment'] == 'part') ? $value['amount'] : 0);
                $bookingList[$key]['paid_amount'] = (((float)$value['amount'] ?? 0) + ((float)$value['coupon_amount'] ?? 0));


                $bookingList[$key]['pickup_address'] = $value['pickup_address'];
                $bookingList[$key]['pickup_date'] = $value['pickup_date'];
                $bookingList[$key]['pickup_time'] = $value['pickup_time'];
                $bookingList[$key]['booking_time'] = $value['created_at'];
                $bookingList[$key]['status'] = $value['status'];
                $bookingList[$key]['refund_status'] = $value['refund_status'] ?? 0;
                $bookingList[$key]['refund_amount'] = $value['refund_amount'] ?? 0;
                if (!empty($value['company'])) {
                    $bookingList[$key]['traveller_info'] = [
                        'image' => getValidImage(path: 'storage/app/public/tour_and_travels/doc/' . ($value['company']['image'] ?? ''), type: 'backend-profile'),
                        'name' => ($value['company']['company_name'] ?? ''),
                        'phone' => $value['company']['phone_no'],
                        'email' => $value['company']['email']
                    ];
                } else {
                    $bookingList[$key]['traveller_info'] = null;
                }

                $getCabDatas = TourOrder::where('cab_assign', $request->cab_assign)->withDriverInfo($value['id'])->first();
                $bookingList[$key]['driver_data']  = json_decode($getCabDatas['driver_data'] ?? '[]') ?? '';
                $bookingList[$key]['Cabs_data']  = json_decode($getCabDatas['Cabs_data'] ?? '[]') ?? '';
            }
            return response()->json(['status' => 1, 'count' => count($bookingList), 'data' => $bookingList], 200);
        } else {
            return response()->json(['status' => 0, 'count' => '0', 'data' => []], 200);
        }
    }


    public function TourCabView(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            "order_id" => "required",
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
            'order_id.required' => 'Order Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getData =  TourOrder::where('id', $request->order_id)->where('cab_assign', $request->cab_assign)->with(['userData', 'CabsManage', 'Driver'])->first();
        $bookingList = [];
        if (!empty($getData)) {
            $bookingList['id'] = $getData['id'];
            $bookingList['tour_id'] = $getData['tour_id'];
            $bookingList['order_id'] = $getData['order_id'];
            $bookingList['qty'] = $getData['qty'];
            $bookingList['user_name'] = $getData['userData']['name'];
            $bookingList['user_phone'] = $getData['userData']['phone'];
            $bookingList['user_image'] = getValidImage(path: 'storage/app/public/profile/' . $getData['userData']['image'], type: 'backend-product');
            $bookingList['en_tour_name'] = $getData['Tour']['tour_name'];
            $hindi_tour = (TourVisits::find($getData['tour_id']))->translations()->pluck('value', 'key')->toArray();
            $bookingList['hi_tour_name'] = $hindi_tour['tour_name'] ?? "";
            $bookingList['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $getData['Tour']['tour_image'], type: 'backend-product');
            $bookingList['order_id'] = $getData['order_id'];
            $bookingList['amount'] = (($getData['amount'] ?? 0) + ($getData['coupon_amount'] ?? 0));
            $bookingList['coupon_amount'] = $getData['coupon_amount'];
            $bookingList['pay_amount'] = $getData['amount'];
            $bookingList['amount_status'] = $getData['amount_status'];
            $bookingList['transaction_id'] = $getData['transaction_id'];
            $bookingList['refund_status'] = $getData['refund_status'];
            $bookingList['pickup_address'] = $getData['pickup_address'];
            $bookingList['pickup_date'] = $getData['pickup_date'];
            $bookingList['pickup_time'] = $getData['pickup_time'];
            $bookingList['booking_time'] = $getData['created_at'];
            if (!empty($getData['CabsManage'])) {
                $bookingList['cab_info'] = ['name' => ($getData['CabsManage']['Cabs']['name'] ?? ''), 'reg_number' => $getData['CabsManage']['reg_number'], 'model_number' => $getData['CabsManage']['model_number']]; //$value['CabsManage'];
            } else {
                $bookingList['cab_info'] = [];
            }
            if (!empty($getData['Driver'])) {
                $bookingList['driver_info'] = ['name' => $getData['Driver']['name'], 'phone' => $getData['Driver']['phone']];
            } else {
                $bookingList['driver_info'] = [];
            }
            return response()->json(['status' => 1, 'message' => 'Get Recode', 'data' => $bookingList], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found Data', 'data' => []], 200);
        }
    }

    public function TourCabOtpVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected traveller Id is invalid or inactive.');
                }
                if (!TourOrder::where('id', $request['order_id'])->whereRaw("NOT JSON_CONTAINS(traveller_cab_id, '0')")->where('status', 1)->exists()) {
                    $fail('Please provide driver and cab.');
                }
            },],
            "order_id" => "required",
            "otp" => "required",
            "type" => "required|in:one,two",
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
            'type.required' => 'Please Choose one,two',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $getData =  TourOrder::where('id', $request->order_id)->where('cab_assign', $request->cab_assign)->with(['userData'])->first();
        if (!empty($getData)) {
            $deviceToken = $getData['userData']['cm_firebase_token'] ?? '';
            if ($request->type == 'one' && $request->otp == $getData['pickup_otp']) {
                TourOrder::where('id', $request->order_id)->where('cab_assign', $request->cab_assign)->update(['pickup_status' => 1]);
            } elseif ($request->type == 'two' && $request->otp == $getData['drop_opt'] && $getData['pickup_status'] == 1 && $getData['drop_status'] == 0) {
                TourOrder::where('id', $request->order_id)->where('cab_assign', $request->cab_assign)->update(['drop_status' => 1]);
                TourAndTravel::where('id', $request->cab_assign)
                    ->update([
                        'wallet_amount' => \Illuminate\Support\Facades\DB::raw('wallet_amount + ' . ($getData['final_amount'] - $getData['advance_withdrawal_amount'] ?? 0)),
                        'gst_amount' => \Illuminate\Support\Facades\DB::raw('gst_amount + ' . $getData['gst_amount']),
                        'admin_commission' => \Illuminate\Support\Facades\DB::raw('admin_commission + ' . $getData['admin_commission']),
                    ]);

                $tourOrder = TourOrder::where('id', $request->order_id)->first();
                $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                $message_data['title_name'] = ($tourOrder['Tour']['tour_name'] ?? '');
                $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['pickup_date'] ?? ''));
                $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourOrder['Tour']['tour_type'] ?? ''))));
                $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
                $message_data['customer_id'] = $tourOrder['user_id'];
                if ($tourOrder['Tour']['tour_image']) {
                    $message_data['type'] = 'text-with-media';
                    $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $tourOrder['Tour']['tour_image'] ?? '');
                }
                $remain_amount = ((!empty($tourOrder['part_payment']) && $tourOrder['part_payment'] == 'part') ? $tourOrder['amount'] : 0);
                $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$remain_amount ?? 0);
                Helpers::whatsappMessage('tour', 'Completed', $message_data);

                $getOld_pending_req = TourAndTravel::where('id', $request->cab_assign)->first();
                $withdrawal  =  new \App\Models\WithdrawalAmountHistory();
                $withdrawal->type = "tour_order";
                $withdrawal->vendor_id = $tourOrder['cab_assign'];
                $withdrawal->ex_id = ($request->order_id ?? "");
                $withdrawal->old_wallet_amount = $getOld_pending_req['wallet_amount'] ?? 0;
                $withdrawal->req_amount = $tourOrder['amount'] ?? 0;
                $withdrawal->save();
            } else {
                return response()->json(['status' => 0, 'message' => (($getData['drop_status'] == 1) ? "Invalid OTP" : 'Already close Booking'), 'data' => []], 200);
            }
            return response()->json(['status' => 1, 'message' => 'OTP successfully verified', 'data' => []], 200);
        }
        return response()->json(['status' => 0, 'message' => 'Not Found Data', 'data' => []], 200);
    }


    public function CabStoreFcmToken() {}

    public function TourCabOtpSend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            "order_id" => "required",
            "type" => "required|in:one,two",
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
            'type.required' => 'Please Choose one,two',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $getData =  TourOrder::where('id', $request->order_id)->where('cab_assign', $request->cab_assign)->with(['userData'])->first();
        if (!empty($getData)) {

            $deviceToken = $getData['userData']['cm_firebase_token'] ?? '';
            $message_data['customer_id'] = $getData['userData']['id'];
            if ($request->type == 'one') {
                $title = 'PICKUP OTP';
                $message =  "Your ride has arrived! 🚗 Share your Pickup OTP " . $getData['pickup_otp'] . " with the captain to start your journey.Please do not share this OTP with anyone else for your safety, Happy Journey !";
                $message_data['otp'] = $getData['pickup_otp'];
                \App\Utils\Helpers::whatsappMessage('tour', 'pickup otp', $message_data);
            } else {
                $title = 'DROP OTP';
                $message = "You’ve reached your destination! 🎉 Share your Drop OTP " . $getData['drop_opt'] . " with the captain to confirm the trip. Thank you for riding with us! Please do not share this OTP with anyone else for your safety.";
                $message_data['otp'] = $getData['drop_opt'];
                \App\Utils\Helpers::whatsappMessage('tour', 'drop otp', $message_data);
            }
            $web_config  = \App\Models\BusinessSetting::where('type', 'company_fav_icon')->first();
            $data = [
                'title' => $title,
                "description" => $message,
                "image" => theme_asset(path: 'storage/app/public/company') . '/' . $web_config['value'],
                'order_id' => 0,
                "type" => "order",
            ];
            $response = \App\Utils\Helpers::send_push_notif_to_device1($deviceToken, $data);
            return response()->json(['status' => true, 'message' => 'Send Otp', 'data' => $data], 200);
        }
        return response()->json(['status' => false, 'message' => 'Not Found Data', 'data' => []], 200);
    }


    public function CabProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $user = TourAndTravel::where(['id' => $request->cab_assign])->first();
        if (!empty($user)) {
            $user['gst_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/doc/' . $user['gst_image'], type: 'backend-product');
            $user['pan_card_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/doc/' . $user['pan_card_image'], type: 'backend-product');
            $user['aadhaar_card_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/doc/' . $user['aadhaar_card_image'], type: 'backend-product');
            $user['address_proof_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/doc/' . $user['address_proof_image'], type: 'backend-product');
            $user['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/doc/' . $user['image'], type: 'backend-product');

            return response()->json(['status' => 1, 'message' => 'Company Information', 'data' => $user], 200);
        }
        return response()->json(['status' => 0, 'message' => 'Not Found Data', 'data' => []], 200);
    }

    public function CabProfileUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'owner_name' => "required",
            "company_name" => "required",
            "address" => "required",
            "web_site_link" => "required",
            "services" => "required",
            "area_of_operation" => "required",
            "person_address" => "required",
            "person_name" => "required",
            "bank_holder_name" => "required",
            "bank_name" => "required",
            "bank_branch" => "required",
            "ifsc_code" => "required",
            "account_number" => "required",
            'gst_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pan_card_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'aadhaar_card_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'address_proof_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
            "person_name.required" => "Person name filed is Empty",
            "bank_holder_name.required" => "Bank Holder name filed is Empty",
            "bank_name.required" => "Bank name filed is Empty",
            "bank_branch.required" => "Bank branch name filed is Empty",
            "ifsc_code.required" => "ifsc code filed is Empty",
            "account_number.required" => "account number filed is Empty"
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $user = TourAndTravel::where(['id' => $request->cab_assign])->first();
        $user->owner_name = $request->owner_name;
        $user->company_name = $request->company_name;
        $user->address = $request->address;
        $user->web_site_link = $request->web_site_link;
        $user->services = $request->services;
        $user->area_of_operation = $request->area_of_operation;
        $user->person_name = $request->person_name;
        $user->person_address = $request->person_address;
        $user->bank_holder_name = $request->bank_holder_name;
        $user->bank_name = $request->bank_name;
        $user->bank_branch = $request->bank_branch;
        $user->ifsc_code = $request->ifsc_code;
        $user->account_number = $request->account_number;
        if ($request->file('gst_image')) {
            if (!empty($user->gst_image) && \Illuminate\Support\Facades\Storage::exists('tour_and_travels/doc/' . $user->gst_image)) {
                \Illuminate\Support\Facades\Storage::delete('tour_and_travels/doc/' . $user->gst_image);
            }
            $user->gst_image =  ImageManager::upload('tour_and_travels/doc/', 'png', $request['gst_image']);
        }
        if ($request->file('pan_card_image')) {
            if (!empty($user->pan_card_image) && \Illuminate\Support\Facades\Storage::exists('tour_and_travels/doc/' . $user->pan_card_image)) {
                \Illuminate\Support\Facades\Storage::delete('tour_and_travels/doc/' . $user->pan_card_image);
            }
            $user->pan_card_image =  ImageManager::upload('tour_and_travels/doc/', 'png', $request['pan_card_image']);
        }
        if ($request->file('aadhaar_card_image')) {
            if (!empty($user->aadhaar_card_image) && \Illuminate\Support\Facades\Storage::exists('tour_and_travels/doc/' . $user->aadhaar_card_image)) {
                \Illuminate\Support\Facades\Storage::delete('tour_and_travels/doc/' . $user->aadhaar_card_image);
            }
            $user->aadhaar_card_image =  ImageManager::upload('tour_and_travels/doc/', 'png', $request['aadhaar_card_image']);
        }
        if ($request->file('address_proof_image')) {
            if (!empty($user->address_proof_image) && \Illuminate\Support\Facades\Storage::exists('tour_and_travels/doc/' . $user->address_proof_image)) {
                \Illuminate\Support\Facades\Storage::delete('tour_and_travels/doc/' . $user->address_proof_image);
            }
            $user->address_proof_image =  ImageManager::upload('tour_and_travels/doc/', 'png', $request['address_proof_image']);
        }
        if ($request->file('image')) {
            if (!empty($user->image) && \Illuminate\Support\Facades\Storage::exists('tour_and_travels/doc/' . $user->image)) {
                \Illuminate\Support\Facades\Storage::delete('tour_and_travels/doc/' . $user->image);
            }
            $user->image =  ImageManager::upload('tour_and_travels/doc/', 'png', $request['image']);
        }
        $user->save();
        return response()->json(['status' => 1, 'message' => 'update Successfully', 'data' => []], 200);
    }



    public function CabInactiveUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 0)->whereIn('status', [1, 0])->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'owner_name' => 'required',
            'company_name' => 'required',
            'phone_no' => 'required',
            'email' => 'required|email',
            'address' => 'required',
            'web_site_link' => 'required',
            'services' => 'required',
            'area_of_operation' => 'required',
            'person_name' => 'required',
            'person_phone' => 'required',
            'person_email' => 'required|email',
            'person_address' => 'required',
            'bank_holder_name' => 'required',
            'bank_name' => 'required',
            'bank_branch' => 'required',
            'ifsc_code' => 'required',
            'account_number' => 'required',
            'gst_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pan_card_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'aadhaar_card_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'address_proof_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
            'owner_name.required' => 'Owner Name is Empty!',
            'company_name.required' => 'Company Name is Empty!',
            'phone_no.required' => 'phone No is Empty!',
            'email.required' => 'Email is Empty!',
            'address.required' => 'Full Address is Empty!',
            'web_site_link.required' => 'web site Url is Empty!',
            'services.required' => 'services is Empty!',
            'area_of_operation.required' => 'area of operation is Empty!',
            "person_name.required" => "Person name filed is Empty",
            "person_phone.required" => "Person phone filed is Empty",
            "person_email.required" => "Person Email filed is Empty",
            "person_address.required" => "Person Address filed is Empty",
            "bank_holder_name.required" => "Bank Holder name filed is Empty",
            "bank_name.required" => "Bank name filed is Empty",
            "bank_branch.required" => "Bank branch name filed is Empty",
            "ifsc_code.required" => "ifsc code filed is Empty",
            "account_number.required" => "account number filed is Empty"
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $user = TourAndTravel::where(['id' => $request->cab_assign])->first();
        $user->id = $request->cab_assign;
        $user->owner_name = $request->owner_name;
        $user->company_name = $request->company_name;
        $user->phone_no = $request->phone_no;
        $user->email = $request->email;
        $user->address = $request->address;
        $user->web_site_link = $request->web_site_link;
        $user->services = $request->services;
        $user->area_of_operation = $request->area_of_operation;
        $user->person_name = $request->person_name;
        $user->person_phone = $request->person_phone;
        $user->person_email = $request->person_email;
        $user->person_address = $request->person_address;
        $user->bank_holder_name = $request->bank_holder_name;
        $user->bank_name = $request->bank_name;
        $user->bank_branch = $request->bank_branch;
        $user->ifsc_code = $request->ifsc_code;
        $user->account_number = $request->account_number;
        $user->gst_image =  (($request->file('gst_image')) ? ImageManager::upload('tour_and_travels/doc/', 'png', $request['gst_image']) : "");
        $user->pan_card_image = (($request->file('pan_card_image')) ? ImageManager::upload('tour_and_travels/doc/', 'png', $request['pan_card_image']) : "");
        $user->aadhaar_card_image = (($request->file('aadhaar_card_image')) ? ImageManager::upload('tour_and_travels/doc/', 'png', $request['aadhaar_card_image']) : "");
        $user->address_proof_image = (($request->file('address_proof_image')) ? ImageManager::upload('tour_and_travels/doc/', 'png', $request['address_proof_image']) : "");
        $user->image = (($request->file('image')) ? ImageManager::upload('tour_and_travels/doc/', 'png', $request['image']) : "");

        $user->save();
        return response()->json(['status' => 1, 'message' => 'update Successfully', 'data' => []], 200);
    }

    public function TravellerAddCab(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "cab_assign" => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'cab_id' => 'required|integer|exists:tour_cab,id',
            'reg_number' => "required|string|max:15|regex:/^[A-Za-z0-9 ]+$/|unique:tour_traveller_cabs,reg_number",
            'model_number' => 'required|string|max:50',
            'image' => "required|image|mimes:jpeg,png,jpg,gif"
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $save = new \App\Models\TourCabManage();
        $save->traveller_id =  $request['cab_assign'];
        $save->cab_id =  $request['cab_id'];
        $save->model_number =  $request['model_number'];
        $save->reg_number =  $request['reg_number'];
        $save->status =  0;
        if ($request->file('image')) {
            $save->image = imageManager::upload('tour_and_travels/tour_traveller_cab/', 'webp', $request->file('image'));
        }
        $save->save();
        if ($save) {
            return response()->json(['status' => 1, 'message' => 'Tour visit traveller cab added successfully', 'data' => []], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'added Failed', 'data' => []], 200);
        }
    }

    public function TravellerCabList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "cab_assign" => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getData = \App\Models\TourCabManage::where('traveller_id', $request['cab_assign'])->with('Cabs')->get();
        if (!empty($getData) && count($getData) > 0) {
            $getArray = [];
            foreach ($getData as $key => $value) {
                $getArray[$key]['id'] = $value['id'];
                $getArray[$key]['cab_id'] = $value['cab_id'];
                $getArray[$key]['cab_name'] = $value['Cabs']['name'] ?? "";
                $getArray[$key]['model_number'] = $value['model_number'];
                $getArray[$key]['reg_number'] = $value['reg_number'];
                $getArray[$key]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_cab/' . $value['image'], type: 'backend-product');
                $getArray[$key]['status'] = $value['status'];
            }
            return response()->json(['status' => 1, 'message' => 'Tour visit traveller cab get successfully', 'data' => $getArray], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found', 'data' => []], 200);
        }
    }

    public function TravellerCabSingle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "cab_assign" => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected traveller Id is invalid or inactive.');
                }
            },],
            'traveller_cab_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getData = \App\Models\TourCabManage::where('traveller_id', $request['cab_assign'])->where('id', $request['traveller_cab_id'])->first();
        if (!empty($getData)) {
            $getData['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_cab/' . $getData['image'], type: 'backend-product');

            return response()->json(['status' => 1, 'message' => 'Tour visit traveller cab get successfully', 'data' => $getData], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found', 'data' => []], 200);
        }
    }

    public function TravellerCabUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'traveller_id' => 'required|integer|exists:tour_traveller_cabs,id',
            "cab_assign" => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
                if (!\App\Models\TourCabManage::where('traveller_id', $value)->where('id', $request['traveller_id'])->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'cab_id' => 'required|integer|exists:tour_cab,id',
            'reg_number' => "required|string|max:15|regex:/^[A-Za-z0-9 ]+$/|unique:tour_traveller_cabs,reg_number",
            'model_number' => 'required|string|max:50',
            'image' => "image|mimes:jpeg,png,jpg,gif"
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $save = \App\Models\TourCabManage::find($request['traveller_id']);
        $save->cab_id =  $request['cab_id'];
        $save->model_number =  $request['model_number'];
        $save->reg_number =  $request['reg_number'];
        if ($request->file('image')) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_cab/' . $save['image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_cab/' . $save['image']);
            }
            $save->image = imageManager::upload('tour_and_travels/tour_traveller_cab/', 'webp', $request->file('image'));
        }
        $save->save();
        if ($save) {
            return response()->json(['status' => 1, 'message' => 'Tour visit traveller cab updated successfully', 'data' => []], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'updated Failed', 'data' => []], 200);
        }
    }

    public function TravellerCabDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'traveller_id' => 'required|integer|exists:tour_traveller_cabs,id',
            "cab_assign" => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
                if (!\App\Models\TourCabManage::where('traveller_id', $value)->where('id', $request['traveller_id'])->exists()) {
                    $fail('records are invalid.');
                }
                if (\App\Models\TourOrder::where('cab_assign', $value)->where('traveller_cab_id', $request['traveller_id'])->where('drop_status', 0)->exists()) {
                    $fail("This cab is already booked so don't delete the recode.");
                }
            },]
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $old_data = \App\Models\TourCabManage::find($request['traveller_id']);
        if ($old_data) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_cab/' . $old_data['image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_cab/' . $old_data['image']);
            }
            $old_data->delete();
            return response()->json(['success' => 1, 'message' => translate('Traveller_cab_Deleted_successfully')], 200);
        } else {
            return response()->json(['success' => 0, 'message' => translate('Traveller_cab_Deleted_Failed')], 400);
        }
    }

    public function TravellerAddDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "cab_assign" => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'phone' => "required|digits:10|unique:tour_traveller_driver,phone",
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'gender' => 'required|in:male,female,other',
            'dob' => 'required|date|before:-18 years',
            'year_ex' => 'required|integer|min:0',
            'license_number' => "required|string|regex:/^[A-Za-z0-9 ]{1,15}$/|unique:tour_traveller_driver,license_number",
            'pan_number' => "required|string|regex:/^[A-Za-z0-9 ]{1,15}$/|unique:tour_traveller_driver,pan_number",
            'aadhar_number' => "required|string|regex:/^\d{12}$/|unique:tour_traveller_driver,aadhar_number",
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'license_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'pan_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'aadhar_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $save = new \App\Models\TourDriverManage();
        $save->traveller_id =  $request['cab_assign'];
        $save->name =  $request['name'];
        $save->phone =  $request['phone'];
        $save->email =  ($request['email'] ?? '');
        $save->gender =  $request['gender'];
        $save->dob =  $request['dob'];
        $save->year_ex =  $request['year_ex'];
        $save->license_number =  $request['license_number'];
        $save->pan_number =  $request['pan_number'];
        $save->aadhar_number =  $request['aadhar_number'];
        $save->status =  0;

        if ($request->file('image')) {
            $save->image = imageManager::upload('tour_and_travels/tour_traveller_driver/', 'webp', $request->file('image'));
        }
        if ($request->file('license_image')) {
            $save->license_image = imageManager::upload('tour_and_travels/tour_traveller_driver/', 'webp', $request->file('license_image'));
        }
        if ($request->file('pan_image')) {
            $save->pan_image = imageManager::upload('tour_and_travels/tour_traveller_driver/', 'webp', $request->file('pan_image'));
        }
        if ($request->file('aadhar_image')) {
            $save->aadhar_image = imageManager::upload('tour_and_travels/tour_traveller_driver/', 'webp', $request->file('aadhar_image'));
        }
        $save->save();
        if ($save) {
            return response()->json(['status' => 1, 'message' => 'Tour visit traveller driver added successfully', 'data' => []], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'added Failed', 'data' => []], 200);
        }
    }

    public function TravellerDriverList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "cab_assign" => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected traveller Id is invalid or inactive.');
                }
            },],
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getData = \App\Models\TourDriverManage::where('traveller_id', $request['cab_assign'])->get();
        if (!empty($getData) && count($getData) > 0) {
            $getArray = [];
            foreach ($getData as $key => $value) {
                $getArray[$key]['id'] = $value['id'];
                $getArray[$key]['name'] = $value['name'];
                $getArray[$key]['phone'] = $value['phone'] ?? "";
                $getArray[$key]['email'] = $value['email'];
                $getArray[$key]['gender'] = $value['gender'];
                $getArray[$key]['dob'] = date('d M,Y', strtotime($value['dob']));
                $getArray[$key]['year_ex'] = $value['year_ex'];
                $getArray[$key]['license_number'] = $value['license_number'];
                $getArray[$key]['pan_number'] = $value['pan_number'];
                $getArray[$key]['aadhar_number'] = $value['aadhar_number'];
                $getArray[$key]['status'] = $value['status'];
                $getArray[$key]['order_complete'] = $value['order_complete'];
                $getArray[$key]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/' . $value['image'], type: 'backend-product');
                $getArray[$key]['license_image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/' . $value['license_image'], type: 'backend-product');
                $getArray[$key]['pan_image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/' . $value['pan_image'], type: 'backend-product');
                $getArray[$key]['aadhar_image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/' . $value['aadhar_image'], type: 'backend-product');
                $getArray[$key]['status'] = $value['status'];
            }
            return response()->json(['status' => 1, 'message' => 'Tour visit traveller cab get successfully', 'data' => $getArray], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found', 'data' => []], 200);
        }
    }

    public function TravellerDriverSingle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "cab_assign" => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected traveller Id is invalid or inactive.');
                }
            },],
            'traveller_cab_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getData = \App\Models\TourCabManage::where('traveller_id', $request['cab_assign'])->where('id', $request['traveller_cab_id'])->first();
        if (!empty($getData)) {
            $getData['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/' . $getData['image'], type: 'backend-product');
            $getData['license_image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/' . $getData['license_image'], type: 'backend-product');
            $getData['pan_image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/' . $getData['pan_image'], type: 'backend-product');
            $getData['aadhar_image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/' . $getData['aadhar_image'], type: 'backend-product');
            return response()->json(['status' => 1, 'message' => 'Tour visit traveller cab get successfully', 'data' => $getData], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found', 'data' => []], 200);
        }
    }

    public function TravellerDriverUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "cab_assign" => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected traveller Id is invalid or inactive.');
                }
            },],
            "driver_id" => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!\App\Models\TourDriverManage::where('id', $value)->where('traveller_id', $request['cab_assign'])->exists()) {
                    $fail('The selected driver Id is invalid.');
                }
            },],
            'phone' => "required|digits:10|unique:tour_traveller_driver,phone," . $request['driver_id'],
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'gender' => 'required|in:male,female,other',
            'dob' => 'required|date|before:-18 years',
            'year_ex' => 'required|integer|min:0',
            'license_number' => "required|string|regex:/^[A-Za-z0-9 ]{1,15}$/|unique:tour_traveller_driver,license_number," . $request['driver_id'],
            'pan_number' => "required|string|regex:/^[A-Za-z0-9 ]{1,15}$/|unique:tour_traveller_driver,pan_number," . $request['driver_id'],
            'aadhar_number' => "required|string|regex:/^\d{12}$/|unique:tour_traveller_driver,aadhar_number," . $request['driver_id'],
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'license_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'pan_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'aadhar_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $save = \App\Models\TourDriverManage::find($request['driver_id']);
        $save->traveller_id =  $request['cab_assign'];
        $save->name =  $request['name'];
        $save->phone =  $request['phone'];
        $save->email =  ($request['email'] ?? '');
        $save->gender =  $request['gender'];
        $save->dob =  $request['dob'];
        $save->year_ex =  $request['year_ex'];
        $save->license_number =  $request['license_number'];
        $save->pan_number =  $request['pan_number'];
        $save->aadhar_number =  $request['aadhar_number'];
        $save->status =  0;

        if ($request->file('image')) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $save['image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $save['image']);
            }
            $save->image = imageManager::upload('tour_and_travels/tour_traveller_driver/', 'webp', $request->file('image'));
        }
        if ($request->file('license_image')) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $save['license_image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $save['license_image']);
            }
            $save->license_image = imageManager::upload('tour_and_travels/tour_traveller_driver/', 'webp', $request->file('license_image'));
        }
        if ($request->file('pan_image')) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $save['pan_image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $save['pan_image']);
            }
            $save->pan_image = imageManager::upload('tour_and_travels/tour_traveller_driver/', 'webp', $request->file('pan_image'));
        }
        if ($request->file('aadhar_image')) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $save['aadhar_image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $save['aadhar_image']);
            }
            $save->aadhar_image = imageManager::upload('tour_and_travels/tour_traveller_driver/', 'webp', $request->file('aadhar_image'));
        }
        $save->save();
        if ($save) {
            return response()->json(['status' => 1, 'message' => 'Tour visit traveller driver updated successfully', 'data' => []], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'added Failed', 'data' => []], 200);
        }
    }

    public function TravellerDriverDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|integer|exists:tour_traveller_driver,id',
            "cab_assign" => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected traveller Id is invalid or inactive.');
                }
                if (!\App\Models\TourDriverManage::where('traveller_id', $value)->where('id', $request['driver_id'])->exists()) {
                    $fail('records are invalid.');
                }
                if (\App\Models\TourOrder::where('cab_assign', $value)->where('traveller_driver_id', $request['driver_id'])->where('drop_status', 0)->exists()) {
                    $fail("This driver is already booked so don't delete the recode.");
                }
            },]
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $old_data = \App\Models\TourDriverManage::find($request['driver_id']);
        if ($old_data) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['image']);
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['license_image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['license_image']);
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['pan_image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['pan_image']);
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['aadhar_image'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['aadhar_image']);
            }
            $old_data->delete();
            return response()->json(['success' => 1, 'message' => translate('Traveller_driver_Deleted_successfully')], 200);
        } else {
            return response()->json(['success' => 0, 'message' => translate('Traveller_driver_Deleted_Failed')], 400);
        }
    }

    public function GetTypes()
    {
        $getType = \App\Models\TourType::where('status', 1)->orderBy('id', 'desc')->get();
        if (count($getType) > 0) {
            $getData = [];
            foreach ($getType as $key => $value) {
                $getData[$key]['slug'] = $value['slug'];
                $getData[$key]['name'] = $value['name'];
            }
            return response()->json(['status' => 1, 'message' => 'Get Successfully', 'recode' => count($getData), 'data' => $getData], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not found', 'recode' => 0, 'data' => []], 400);
        }
    }
    public function GetCabList()
    {
        $getType = \App\Models\TourCab::where('status', 1)->orderBy('id', 'desc')->get();
        if (count($getType) > 0) {
            $getData = [];
            foreach ($getType as $key => $value) {
                $getData[$key]['id'] = $value['id'];
                $getData[$key]['name'] = $value['name'];
                $getData[$key]['seats'] = $value['seats'];
            }
            return response()->json(['status' => 1, 'message' => 'Get Successfully', 'recode' => count($getData), 'data' => $getData], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not found', 'recode' => 0, 'data' => []], 400);
        }
    }
    public function GetPackageList()
    {
        $getpackage = \App\Models\TourPackage::where('status', 1)->orderBy('id', 'desc')->get();
        if (count($getpackage) > 0) {
            $getData = [];
            foreach ($getpackage as $key => $value) {
                $getData[$key]['id'] = $value['id'];
                $getData[$key]['name'] = $value['name'];
                $getData[$key]['seats'] = $value['title'];
            }
            return response()->json(['status' => 1, 'message' => 'Get Successfully', 'recode' => count($getData), 'data' => $getData], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not found', 'recode' => 0, 'data' => []], 400);
        }
    }

    public function GetLanguageList()
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        if ($languages) {
            return response()->json(['status' => 1, 'message' => 'Get Successfully', 'recode' => count($languages), 'data' => $languages], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not found', 'recode' => 0, 'data' => []], 400);
        }
    }




    public function AddTour(Request $request, TourVisitService $service)
    {
        $validator = Validator::make($request->all(), [
            'tour_name' => 'required|array',
            'tour_name.*' => 'required|string|min:1',
            'tour_type' => 'required',
            'created_id' => 'required',
            'cities_name' => 'required|array',
            'cities_name.*' => 'required|string|min:1',
            'country_name' => 'required|array',
            'country_name.*' => 'required|string|min:1',
            'state_name' => 'required|array',
            'state_name.*' => 'required|string|min:1',
            'lat' => 'required',
            'long' => 'required',
            'ex_distance' => 'required',

            'description' => 'required|array',
            'description.*' => 'required|string|min:1',
            'highlights' => 'required|array',
            'highlights.*' => 'required|string|min:1',
            'inclusion' => 'required|array',
            'inclusion.*' => 'required|string|min:1',
            'exclusion' => 'required|array',
            'exclusion.*' => 'required|string|min:1',
            'terms_and_conditions' => 'required|array',
            'terms_and_conditions.*' => 'required|string|min:1',
            'cancellation_policy' => 'required|array',
            'cancellation_policy.*' => 'required|string|min:1',
            'notes' => 'required|array',
            'notes.*' => 'required|string|min:1',

            'cab_id' => 'required|array',
            'cab_id.*' => 'required|string|min:1',
            'price' => 'required|array',
            'price.*' => 'required|string|min:1',

            'package_id' => 'required|array',
            'package_id.*' => 'required|string|min:1',
            'pprice' => 'required|array',
            'pprice.*' => 'required|string|min:1',
            'use_date' => 'required|in:0,1,2,3,4',
            "startandend_date" => 'required_if:use_date,1',
            "pickup_time" => "required_if:use_date,1",
            "pickup_location" => "required_if:use_date,1,4,2",
            "pickup_lat" => "required_if:use_date,1,4,2",
            "pickup_long" => "required_if:use_date,1,4,2",
            'tour_image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        \Illuminate\Support\Facades\DB::beginTransaction();
        $request['lang'] = getWebConfig(name: 'pnc_language') ?? [];

        try {
            $dataArray = $service->getTourVisitData($request);
            $insert = $this->tourtraveller->add(data: $dataArray);
            $this->translationRepo->add(request: $request, model: 'App\Models\TourVisits', id: $insert->id);

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Tour visit data added successfully.',], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to add tour visit data. Please try again later.', 'error' => $e->getMessage(),], 400);
        }
    }


    public function TourList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getDatalist = $this->tourtraveller->getListWhere(orderBy: ['id' => 'desc'], filters: ['created_id' => [$request['cab_assign'], 0]], dataLimit: 'all');
        if ($getDatalist) {
            $getDatas = [];
            foreach ($getDatalist as $k => $val) {
                $getDatas[$k]['id'] = $val['id'];
                $getDatas[$k]['tour_id'] = $val['tour_id'];
                $getDatas[$k]['tour_name'] = $val['tour_name'];
                $getDatas[$k]['tour_type'] = $val['tour_type'];
                $getcheckbox  = \App\Models\TourOrderAccept::where('traveller_id', $request['cab_assign'])->where('tour_id', $val['id'])->first();
                $getDatas[$k]['accept_type'] = $getcheckbox['status'] ?? 0;
                $getDatas[$k]['create_by'] = (($val['created_id']) ? 'admin' : 'vendor');
                $getDatas[$k]['status'] = $val['status'];
            }
            return response()->json(['status' => 1, 'message' => 'Get Successfully', 'recode' => count($getDatas), 'data' => $getDatas], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not found', 'recode' => 0, 'data' => []], 400);
        }
    }

    public function TourStatusChage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'tour_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourVisits::where('id', $value)->where('created_id', $request->cab_assign)->exists()) {
                    $fail('The selected Tour Id is invalid.');
                }
            },],
            'status' => "required|in:1,0",
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $data['status'] = $request->get('status', 0);
        $this->tourtraveller->update(id: $request['tour_id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }


    public function TourGetId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'tour_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourVisits::where('id', $value)->where('created_id', $request->cab_assign)->exists()) {
                    $fail('The selected Tour Id is invalid.');
                }
            },],
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getDatalist = \App\Models\TourVisits::where('id', $request->tour_id)->first();
        if ($getDatalist) {
            $hindi_tour = $getDatalist->translations()->pluck('value', 'key')->toArray();
            $getDatas['id'] = $getDatalist['id'];
            $getDatas['tour_id'] = $getDatalist['tour_id'];
            $getDatas['en_tour_name'] = $getDatalist['tour_name'];
            $getDatas['hi_tour_name'] = $hindi_tour['tour_name'];
            $getDatas['tour_type'] = $getDatalist['tour_type'];
            $getDatas['en_cities_name'] = $getDatalist['cities_name'];
            $getDatas['hi_cities_name'] = $hindi_tour['cities_name'];
            $getDatas['en_country_name'] = $getDatalist['country_name'];
            $getDatas['hi_country_name'] = $hindi_tour['country_name'];
            $getDatas['en_state_name'] = $getDatalist['state_name'];
            $getDatas['hi_state_name'] = $hindi_tour['state_name'];
            $getDatas['lat'] = $getDatalist['lat'];
            $getDatas['long'] = $getDatalist['long'];
            $getDatas['en_description'] = $getDatalist['description'];
            $getDatas['hi_description'] = $hindi_tour['description'];
            $getDatas['en_highlights'] = $getDatalist['highlights'];
            $getDatas['hi_highlights'] = $hindi_tour['highlights'];
            $getDatas['en_inclusion'] = $getDatalist['inclusion'];
            $getDatas['hi_inclusion'] = $hindi_tour['inclusion'];
            $getDatas['en_exclusion'] = $getDatalist['exclusion'];
            $getDatas['hi_exclusion'] = $hindi_tour['exclusion'];
            $getDatas['en_terms_and_conditions'] = $getDatalist['terms_and_conditions'];
            $getDatas['hi_terms_and_conditions'] = $hindi_tour['terms_and_conditions'];
            $getDatas['en_cancellation_policy'] = $getDatalist['cancellation_policy'];
            $getDatas['hi_cancellation_policy'] = $hindi_tour['cancellation_policy'];
            $getDatas['en_notes'] = $getDatalist['notes'];
            $getDatas['hi_notes'] = $hindi_tour['notes'];
            $getDatas['cab_list_price'] = json_decode($getDatalist['cab_list_price'], true);
            $getDatas['package_list_price'] = json_decode($getDatalist['package_list_price'], true);
            $getDatas['tour_image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $getDatalist['tour_image'], type: 'backend-product');
            $getmutiimage = [];
            if (!empty($getDatalist['image']) && json_decode($getDatalist['image'], true)) {
                foreach (json_decode($getDatalist['image'], true) as $key => $value) {
                    $getmutiimage[] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $value, type: 'backend-product');
                }
            }
            $getDatas['image'] = $getmutiimage;
            $getDatas['use_date'] = $getDatalist['use_date'];

            $getDatas['pickup_time'] = $getDatalist['pickup_time'];
            $getDatas['pickup_location'] = $getDatalist['pickup_location'];
            $getDatas['pickup_lat'] = $getDatalist['pickup_lat'];
            $getDatas['pickup_long'] = $getDatalist['pickup_long'];
            $getDatas['startandend_date'] = $getDatalist['startandend_date'];
            $getDatas['ex_distance'] = $getDatalist['ex_distance'];

            return response()->json(['status' => 1, 'message' => 'Get Successfully', 'recode' => 1, 'data' => $getDatas], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not found', 'recode' => 0, 'data' => []], 400);
        }
    }

    public function TourImageRemove(Request $request, TourVisitService $service)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'tour_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourVisits::where('id', $value)->where('created_id', $request->cab_assign)->exists()) {
                    $fail('The selected Tour Id is invalid.');
                }
            },],
            'image_name' => "required",
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        $getData  = $this->tourtraveller->getFirstWhere(params: ['id' => $request->tour_id]);
        if (empty($getData)) {
            return back();
        }
        $dataIMage = $service->ImageRemove($getData, $request->image_name);
        $this->tourtraveller->update(id: $request->tour_id, data: ['image' => json_encode($dataIMage)]);
        return response()->json(['status' => 1, 'message' => 'image Remove Successfully', 'recode' => 1, 'data' => []], 200);
    }

    public function TourUpdate(Request $request, TourVisitService $service)
    {
        $validator = Validator::make($request->all(), [
            'tour_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourVisits::where('id', $value)->where('created_id', $request->created_id)->exists()) {
                    $fail('The selected Tour Id is invalid.');
                }
            },],
            'tour_name' => 'required|array',
            'tour_name.*' => 'required|string|min:1',
            'tour_type' => 'required',
            'created_id' => 'required',
            'cities_name' => 'required|array',
            'cities_name.*' => 'required|string|min:1',
            'country_name' => 'required|array',
            'country_name.*' => 'required|string|min:1',
            'state_name' => 'required|array',
            'state_name.*' => 'required|string|min:1',
            'lat' => 'required',
            'long' => 'required',
            'ex_distance' => 'required',

            'description' => 'required|array',
            'description.*' => 'required|string|min:1',
            'highlights' => 'required|array',
            'highlights.*' => 'required|string|min:1',
            'inclusion' => 'required|array',
            'inclusion.*' => 'required|string|min:1',
            'exclusion' => 'required|array',
            'exclusion.*' => 'required|string|min:1',
            'terms_and_conditions' => 'required|array',
            'terms_and_conditions.*' => 'required|string|min:1',
            'cancellation_policy' => 'required|array',
            'cancellation_policy.*' => 'required|string|min:1',
            'notes' => 'required|array',
            'notes.*' => 'required|string|min:1',

            'cab_id' => 'required|array',
            'cab_id.*' => 'required|string|min:1',
            'price' => 'required|array',
            'price.*' => 'required|string|min:1',

            'package_id' => 'required|array',
            'package_id.*' => 'required|string|min:1',
            'pprice' => 'required|array',
            'pprice.*' => 'required|string|min:1',
            'use_date' => 'required|in:0,1',
            "startandend_date" => 'required_if:use_date,1',
            "pickup_time" => "required_if:use_date,1",
            "pickup_location" => "required_if:use_date,1,2,4",
            "pickup_lat" => "required_if:use_date,1,2,4",
            "pickup_long" => "required_if:use_date,1,2,4",
            'tour_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }
        \Illuminate\Support\Facades\DB::beginTransaction();
        $request['lang'] = getWebConfig(name: 'pnc_language') ?? [];

        try {
            $dataArray = $service->getTourVisitData($request);
            $this->tourtraveller->update(id: $request->tour_id, data: $dataArray);
            $this->translationRepo->update(request: $request, model: 'App\Models\TourVisits', id: $request->tour_id);

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Tour visit data updated successfully.',], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update tour visit data. Please try again later.', 'error' => $e->getMessage(),], 400);
        }
    }

    public function TourDelete(Request $request, TourVisitService $service)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'tour_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourVisits::where('id', $value)->where('created_id', $request->cab_assign)->exists()) {
                    $fail('The selected Tour Id is invalid.');
                }
            },]
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $getData = $this->tourtraveller->getFirstWhere(params: ['id' => $request['tour_id'], 'status' => 0, 'created_id' => $request['cab_assign']]);
        if (!empty($getData)) {
            $service->removeimages($getData);
            $this->tourtraveller->delete(params: ['id' => $request['tour_id']]);
            return response()->json(['status' => 1, 'message' => translate('Tour_visit_Deleted_successfully'), 'data' => []], 200);
        } else {
            return response()->json(['status' => 0, 'message' => translate('Travel_tour_visit_will_be_deleted_by_administrator_only'), 'data' => []], 200);
        }
    }
    public function TourOrderAccept(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'tour_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourVisits::where('id', $value)->where('created_id', $request->cab_assign)->exists()) {
                    $fail('The selected Tour Id is invalid.');
                }
            },],
            'status' => "required|in:1,0"
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $getData = \App\Models\TourOrderAccept::where('traveller_id', $request['cab_assign'])->where('tour_id', $request['tour_id'])->first();
        $checkOrder = \App\Models\TourOrder::where('cab_assign', $request['cab_assign'])->where('tour_id', $request['tour_id'])->where('drop_status', 0)->first();
        if ($checkOrder) {
            return response()->json(['success' => 0, 'message' => translate('There_are_still_some_orders_left_on_this_tour'), 'data' => []], 200);
        } else {
            if (!empty($getData)) {
                $saveData = \App\Models\TourOrderAccept::find($getData['id']);
            } else {
                $saveData = new \App\Models\TourOrderAccept();
            }
            $saveData->tour_id = $request->tour_id;
            $saveData->traveller_id = $request['cab_assign'];
            $saveData->status = $request['status'];
            $saveData->save();
            return response()->json(['success' => 1, 'message' => translate('status_updated_successfully'), 'data' => $saveData], 200);
        }
    }

    public function NewTopTour()
    {
        $cities_tour = [];
        $special_tour = TourVisits::where('status', 1)->where(function ($query) {
            $query->whereIn('use_date', [0, 2, 3, 4])->orWhere(function ($query) {
                $query->where('use_date', 1)
                    ->whereNotNull('startandend_date')
                    ->whereRaw('? < STR_TO_DATE(SUBSTRING_INDEX(startandend_date, " - ", 1), "%Y-%m-%d")', [date('Y-m-d')]);
            });
        })->withTourCheck()->orderBy('id', 'desc')->limit(10)->get();

        if (!empty($special_tour) && count($special_tour) > 0) {
            $p = 0;
            foreach ($special_tour as $key => $val) {
                $hindi_tour = $val->translations()->pluck('value', 'key')->toArray();
                $cities_tour[$p]['id'] = $val['id'] ?? "";
                $cities_tour[$p]['en_tour_name'] = $val['tour_name'] ?? "";
                $cities_tour[$p]['hi_tour_name'] = $hindi_tour['tour_name'] ?? "";
                $cities_tour[$p]['use_date'] = ($val['use_date'] ?? '');
                $cities_tour[$p]['date'] = ($val['startandend_date'] ?? '');
                $cabs_lists = [];
                $p_services = [];
                if (!empty($val['cab_list_price']) && json_decode($val['cab_list_price'], true)) {
                    foreach (json_decode($val['cab_list_price'], true) as $kk => $val_p) {
                        $cabs_lists[$kk]['price'] = $val_p['price'];
                        $cabs_lists[$kk]['cab_id'] = $val_p['cab_id'];
                        $getCabs = \App\Models\TourCab::where('id', $val_p['cab_id'])->first();
                        $cab_name = ucwords($getCabs['name'] ?? '');
                        $cabs_lists[$kk]['cab_name'] = $cab_name;
                        $cabs_lists[$kk]['seats'] = ($getCabs['seats'] ?? '');
                        $cabs_lists[$kk]['image'] =  getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($getCabs['image'] ?? ""), type: 'backend-product');
                        $p_services[] = 'transport';
                    }
                }
                $package_lists = [];
                if (!empty($val['package_list_price']) && json_decode($val['package_list_price'], true)) {
                    foreach (json_decode($val['package_list_price'], true) as $kk => $val_s) {
                        $package_lists[$kk]['price'] = $val_s['pprice'];
                        $package_lists[$kk]['package_id'] = $val_s['package_id'];
                        $getpackage = \App\Models\TourPackage::where('id', $val_s['package_id'])->first();
                        $package_lists[$kk]['package_name'] = ucwords($getpackage['name'] ?? '');
                        $package_lists[$kk]['seats'] = ($getpackage['seats'] ?? '');
                        $package_lists[$kk]['type'] = ($getpackage['type'] ?? '');
                        $package_lists[$kk]['title'] = ($getpackage['title'] ?? '');
                        $package_lists[$kk]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($getpackage['image'] ?? ""), type: 'backend-product');
                        $p_services[] = ($getpackage['type'] ?? '');
                    }
                }
                $cities_tour[$p]['cab_list'] = $cabs_lists;
                $cities_tour[$p]['package_list'] = $package_lists;
                $cities_tour[$p]['services'] = array_values(array_unique($p_services));
                $cities_tour[$p]['image'] = getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $val['tour_image'], type: 'backend-product');
                $p++;
            }
            return response()->json(['status' => 1, 'count' => count($cities_tour), 'data' => $cities_tour], 200);
        } else {
            return response()->json(['status' => 0, 'count' => 0, 'data' => []], 200);
        }
    }

    public function couponListType(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'type' => 'required|in:event,tour',
        ], [
            'type.required' => 'type is provide!',
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => 0, "message" => "", 'errors' => Helpers::error_processor($validator), 'data' => []], 403);
        }

        if ($request->type == 'event' && !empty(\Illuminate\Support\Facades\Auth::guard('api')->user()->id)) {
            $userId = \Illuminate\Support\Facades\Auth::guard('api')->user()->id;
            $coupons = Coupon::where('status', 1)
                ->where('coupon_type', 'event_order')
                ->whereDate('start_date', '<=', date('Y-m-d'))
                ->whereDate('expire_date', '>=', date('Y-m-d'))
                ->whereRaw('`limit` > (SELECT COUNT(*) FROM `event_orders` WHERE `event_orders`.`coupon_id` = `coupons`.`id`)')
                ->whereNotExists(function ($query) use ($userId) {
                    $query->from('event_orders')
                        ->whereColumn('event_orders.coupon_id', 'coupons.id')
                        ->where('event_orders.user_id', $userId)
                        ->where('event_orders.transaction_status', 1);
                })
                ->get();
        } else if ($request->type == 'tour' && !empty(\Illuminate\Support\Facades\Auth::guard('api')->user()->id)) {
            $userId = \Illuminate\Support\Facades\Auth::guard('api')->user()->id;
            $coupons = Coupon::where('status', 1)
                ->where('coupon_type', 'tour_order')
                ->whereDate('start_date', '<=', date('Y-m-d'))
                ->whereDate('expire_date', '>=', date('Y-m-d'))
                ->whereRaw('`limit` > (SELECT COUNT(*) FROM `tour_order` WHERE `tour_order`.`coupon_id` = `coupons`.`id`)')
                ->whereNotExists(function ($query) use ($userId) {
                    $query->from('tour_order')
                        ->whereColumn('tour_order.coupon_id', 'coupons.id')
                        ->where('tour_order.user_id', $userId)
                        ->where('tour_order.amount_status', 1);
                })
                ->get();
        }
        if ($coupons && count($coupons) > 0) {
            return response()->json(['status' => 1, 'coupons' => $coupons], 200);
        } else {
            return response()->json(['status' => 0, 'coupons' => $coupons], 200);
        }
    }

    public function BookingOrderPolicy(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'user_id' =>  ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            },],
            "order_id" => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourOrder::where('id', $value)->where('user_id', $request->user_id)->whereIn('status', [0, 1])->exists()) {
                    $fail('The selected Order is invalid or Refunded.');
                }
            },],
        ], [
            'user_id.required' => 'User Id is required!',
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => 0, "message" => Helpers::error_processor($validator)[0], 'errors' => Helpers::error_processor($validator), 'data' => []], 403);
        }

        $all_booking = TourOrder::where('user_id', $request->user_id)->where('id', $request->order_id)->with(['Tour'])->first();
        $policy_array = [];
        if (!empty($all_booking['Tour']['tour_type'] ?? '')) {
            $getSpecial_tour = \App\Models\TourRefundPolicy::where('status', 1)->where('type', $all_booking['Tour']['tour_type'])->orderBy('day', 'desc')->get();
            $indexs = 0;
            foreach ($getSpecial_tour as $val) {
                $pickupDate = strtotime($all_booking['pickup_date'] . ' ' . $all_booking['pickup_time'] . ' -' . $val['day'] . ' hours');
                $createdAt = strtotime($all_booking['created_at']);
                if ($pickupDate > $createdAt) {
                    $policy_array[$indexs]['en_message'] = preg_replace('/\{\{\s*\$date\s*\}\}/', '<strong>' . date('d-m-Y h:i A', strtotime($all_booking['pickup_date'] . ' ' . $all_booking['pickup_time'] . ' -' . $val['day'] . ' hours')) . '</strong>', ($val['message'] ?? ''));
                    $hindi_tour =  (TourVisits::find($all_booking['Tour']['id']))->translations()->pluck('value', 'key')->toArray();
                    $policy_array[$indexs]['hi_message'] = preg_replace('/\{\{\s*\$date\s*\}\}/', '<strong>' . date('d-m-Y h:i A', strtotime($all_booking['pickup_date'] . ' ' . $all_booking['pickup_time'] . ' -' . $val['day'] . ' hours')) . '</strong>', ($hindi_tour['message'] ?? ''));
                    $policy_array[$indexs]['percentage'] = $val['percentage'] . "%";
                    $policy_array[$indexs]['amount'] = (($all_booking['amount'] * $val['percentage']) / 100);
                    $policy_array[$indexs]['date'] = date('d-m-Y h:i A', strtotime($all_booking['pickup_date'] . ' ' . $all_booking['pickup_time'] . ' -' . $val['day'] . ' hours'));
                    $indexs++;
                }
            }
        }

        if ($policy_array && count($policy_array) > 0) {
            return response()->json(['status' => 1, 'message' => "", 'data' => $policy_array], 200);
        } else {
            return response()->json(['status' => 0, "message" => "", 'data' => []], 200);
        }
    }

    public function UserTourOrderCancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' =>  ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            },],
            "order_id" => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!TourOrder::where('id', $value)->where('user_id', $request->user_id)->whereIn('status', [0, 1])->exists()) {
                    $fail('The selected Order is invalid or Refunded.');
                }
            },],
            "msg" => "required",
        ], [
            'user_id.required' => 'User Id is required!',
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => 0, "message" => Helpers::error_processor($validator)[0]['message'], 'errors' => Helpers::error_processor($validator), 'data' => []], 403);
        }
        $tourOrder = TourOrder::where('id', ($request->order_id ?? ''))->with(['Tour'])->first();

        $getSpecial_tour = \App\Models\TourRefundPolicy::where('status', 1)->where('type', $tourOrder['Tour']['tour_type'] ?? '')->orderBy('day', 'desc')->get();
        $Amount_Pay = 0;
        $pickupTimestamp = strtotime($tourOrder['pickup_date'] . ' ' . $tourOrder['pickup_time']);
        if (!empty($getSpecial_tour) && count($getSpecial_tour) > 0) {
            foreach ($getSpecial_tour as $val) {
                $calculatedTimestamp = strtotime("-" . $val['day'] . " hours", $pickupTimestamp);
                $currentTimestamp = strtotime(now());
                if ($currentTimestamp <= $calculatedTimestamp) {
                    $Amount_Pay = ($tourOrder['amount'] * $val['percentage']) / 100;
                    break;
                }
            }
        }
        $getData = \App\Models\TourCancelTicket::where('order_id', $request->order_id)->first();
        if ($getData) {
            return response()->json(['status' => 0, 'message' => 'No Found', 'recode' => 0, 'data' => []], 200);
        }
        $ticket = new \App\Models\TourCancelTicket();
        $ticket->user_id = $request->user_id;
        $ticket->order_id = $request->order_id;
        $ticket->message = $request->msg;
        $ticket->status = 1; //0
        $ticket->save();

        User::where('id', $request->user_id)->update(['wallet_balance' => \Illuminate\Support\Facades\DB::raw('wallet_balance + ' . ($Amount_Pay ?? 0))]);

        $wallet_transaction = new \App\Models\WalletTransaction();
        $wallet_transaction->user_id = $request->user_id;
        $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
        $wallet_transaction->reference = 'tour order refund';
        $wallet_transaction->transaction_type = 'tour_order_refund';
        $wallet_transaction->balance = User::where('id', $request->user_id)->first()['wallet_balance'];
        $wallet_transaction->credit = ($Amount_Pay ?? 0);
        $wallet_transaction->save();

        TourOrder::where('id', $request->order_id)->update(['refund_status' => 1, 'status' => 2, 'refound_id' => "wallet", 'refund_amount' => ($Amount_Pay ?? 0), 'cab_assign' => 0, 'traveller_id' => ($tourOrder['cab_assign'] ?? 0), 'refund_date' => date('Y-m-d H:i:s'), 'refund_query_id' => $ticket->id]);

        $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
        $message_data['title_name'] = ($tourOrder['Tour']['tour_name'] ?? '');
        $message_data['booking_date'] = ($tourOrder['pickup_date'] ?? '');
        $message_data['time'] = ($tourOrder['Tour']['pickup_time'] ?? '');
        $message_data['place_name'] = ($tourOrder['Tour']['pickup_address'] ?? '');
        $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourOrder['Tour']['tour_type'] ?? ''))));
        $message_data['final_amount'] = webCurrencyConverter(amount: (float)$Amount_Pay ?? 0);
        $message_data['refund_amount'] = webCurrencyConverter(amount: (float)$Amount_Pay ?? 0);
        $message_data['customer_id'] =  $request->user_id;
        $message_data['reject_reason'] = $request->msg;
        \App\Utils\Helpers::whatsappMessage('tour', 'Tour Canceled', $message_data);
        return response()->json(['status' => 1, "message" => "Refund Successfully", 'data' => []], 200);
    }


    public function TourOrderInvoiceDownload(Request $request, $order_id)
    {
        try {
            $tourOrders = \App\Models\TourOrder::where('id', $order_id)->with(['Tour', 'userData', 'company'])->first();
            if (!$tourOrders) {
                return response()->json([
                    "status" => 0,
                    "message" => "Tour order not found.",
                    "data" => []
                ], 403);
            }
            $refund_policy = \App\Models\TourRefundPolicy::where('status', 1)->where('type', ($tourOrders['Tour']['tour_type'] ?? ""))->orderBy('id', 'asc')->get();
            $mpdf_view  = \Illuminate\Support\Facades\View::make('web-views.tour.paid-invoice', compact('tourOrders', 'refund_policy'));
            \App\Utils\Helpers::gen_mpdf($mpdf_view, 'tour_order_', $order_id);
            return response()->json(["status" => 1, "message" => "Invoice generated successfully."]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => 0,
                "message" => "An error occurred.",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function TravellerWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
            'req_amount' => 'required|numeric',
            'holder_name'     => 'nullable|required_without:upi_code',
            'bank_name'       => 'nullable|required_without:upi_code',
            'ifsc_code'       => 'nullable|required_without:upi_code',
            'account_number'  => 'nullable|required_without:upi_code',
            'upi_code'        => 'nullable|required_without:holder_name,bank_name,ifsc_code,account_number',
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $getOld_pending_req = \App\Models\TourAndTravel::where('id', $request->cab_assign)->first();
        $check_request_old = \App\Models\WithdrawalAmountHistory::where('vendor_id', $request->cab_assign)->where('type', 'tour')->where('status', 0)->first();
        if ($request['req_amount'] > $getOld_pending_req['wallet_amount']) {
            return response()->json(["status" => 0, "message" => "Your wallet balance is not valid"]);
        } elseif ($check_request_old) {
            return response()->json(["status" => 0, "message" => "Your amount request has already been sent. Please wait for further processing."]);
        }
        if ($request['req_amount'] <= $getOld_pending_req['wallet_amount'] && $getOld_pending_req['withdrawal_pending_amount'] <= 0) {
            $withdrawal  =  new \App\Models\WithdrawalAmountHistory();
            $withdrawal->type = "tour";
            $withdrawal->vendor_id = $request->cab_assign;
            $withdrawal->ex_id = (($request->ex_id) ? $request->ex_id : "");
            $withdrawal->holder_name = $request['holder_name'] ?? "";
            $withdrawal->bank_name = $request['bank_name'] ?? "";
            $withdrawal->ifsc_code = $request['ifsc_code'] ?? "";
            $withdrawal->account_number = $request['account_number'] ?? "";
            $withdrawal->upi_code = $request['upi_code'] ?? '';
            $withdrawal->old_wallet_amount = $getOld_pending_req['wallet_amount'] ?? 0;
            $withdrawal->req_amount = $request['req_amount'] ?? 0;
            $withdrawal->save();
            if ($request->ex_id) {
                \App\Models\TourOrder::where('id', $request->ex_id)->update(['advance_withdrawal_amount' => $request['req_amount']]);
            } else {
                \App\Models\TourAndTravel::where('id', $request->cab_assign)->update(['withdrawal_pending_amount' => $request['req_amount']]);
            }
            return response()->json(["status" => 1, "message" => translate('Payment_request_sent_successfully')]);
        } else {
            return response()->json(["status" => 0, "message" => "Your amount request has already been sent. Please wait for further processing."]);
        }
    }

    public function TravellerWithdrawalHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $getdata = \App\Models\WithdrawalAmountHistory::where('vendor_id', $request->cab_assign)->where('type', 'tour')->with(['Tour'])->get();
        if ($getdata && count($getdata) > 0) {
            $show_datas = [];
            $key = 0;
            foreach ($getdata as $value) {
                $show_datas[$key]['id'] = $value['id'];
                $show_datas[$key]['old_wallet_amount'] = $value['old_wallet_amount'];
                $show_datas[$key]['req_amount'] = $value['req_amount'];
                $show_datas[$key]['approval_amount'] = $value['approval_amount'];
                $show_datas[$key]['message'] = $value['message'];
                $show_datas[$key]['status'] = $value['status'];
                $show_datas[$key]['transcation_id'] = $value['transcation_id'];
                $show_datas[$key]['payment_method'] = $value['payment_method'];
                $show_datas[$key]['upi_code'] = $value['upi_code'];
                $show_datas[$key]['bank_name'] = $value['bank_name'];
                $show_datas[$key]['holder_name'] = $value['holder_name'];
                $show_datas[$key]['ifsc_code'] = $value['ifsc_code'];
                $show_datas[$key]['account_number'] = $value['account_number'];
                $show_datas[$key]['created_at'] = $value['created_at'];
                $key++;
            }
            return response()->json(["status" => 1, "message" => "Request Transaction History.", 'data' => $show_datas]);
        } else {
            return response()->json(["status" => 0, "message" => "Not Found Trasaction.", 'data' => []]);
        }
    }

    public function TravellerOrderAmountHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cab_assign' => ['required', function ($attribute, $value, $fail) {
                if (!TourAndTravel::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                    $fail('The selected cab Id is invalid or inactive.');
                }
            },],
        ], [
            'cab_assign.required' => 'Cab Id is Empty!',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => Helpers::error_processor($validator)[0]['message'], 'recode' => 0, 'data' => [], 'errors' => Helpers::error_processor($validator)], 200);
        }

        $getdata = \App\Models\WithdrawalAmountHistory::where('vendor_id', $request->cab_assign)->where('type', 'tour_order')->with(['TourVisit'])->get();
        if ($getdata && count($getdata) > 0) {
            $show_datas = [];
            $key = 0;
            foreach ($getdata as $value) {
                $show_datas[$key]['id'] = $value['id'];
                $show_datas[$key]['old_wallet_amount'] = $value['old_wallet_amount'];
                $show_datas[$key]['available_amount'] = $value['req_amount'];
                $hindi_tour = $value['TourVisit']['Tour']->translations()->pluck('value', 'key')->toArray();
                $show_datas[$key]['tour_id'] = $value['TourVisit']['Tour']['id'] ?? "";
                $show_datas[$key]['en_tour_name'] = $value['TourVisit']['Tour']['tour_name'] ?? "";
                $show_datas[$key]['hi_tour_name'] = $hindi_tour['tour_name'] ?? "";
                $show_datas[$key]['order_id'] = $value['TourVisit']['id'] ?? "";
                $show_datas[$key]['created_at'] = $value['created_at'];
                $key++;
            }
            return response()->json(["status" => 1, "message" => "Request Transaction History.", 'data' => $show_datas]);
        } else {
            return response()->json(["status" => 0, "message" => "Not Found Trasaction.", 'data' => []]);
        }
    }
}
