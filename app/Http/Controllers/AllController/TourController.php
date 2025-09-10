<?php

namespace App\Http\Controllers\AllController;

use App\Contracts\Repositories\TourAndTravelRepositoryInterface;
use App\Contracts\Repositories\TourCabManageRepositoryInterface;
use App\Contracts\Repositories\TourCabRepositoryInterface;
use App\Contracts\Repositories\TourDriverManageRepositoryInterface;
use App\Contracts\Repositories\TourOrderRepositoryInterface;
use App\Contracts\Repositories\TourTypeRepositoryInterface;
use App\Contracts\Repositories\TourVisitPlaceRepositoryInterface;
use App\Contracts\Repositories\TourVisitRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Enums\ViewPaths\AllPaths\LoginPath;
use App\Enums\ViewPaths\AllPaths\TourPath;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TourAndTravelRequest;
use App\Http\Requests\Admin\TourVisitPlaceRequest;
use App\Http\Requests\Admin\TourVisitRequest;
use App\Http\Requests\Tour\CabRequest;
use App\Http\Requests\Tour\DriverRequest;
use App\Services\TourAndTravelService;
use App\Services\TourVisitService;
use App\Traits\FileManagerTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Expr\Cast\Double;
use SimplePie\Cache\Redis;

class TourController extends Controller
{
    use FileManagerTrait;
    public function __construct(
        private readonly TranslationRepositoryInterface      $translationRepo,
        private readonly TourAndTravelRepositoryInterface  $tourtraveller,
        private readonly TourOrderRepositoryInterface  $tourorder,
        private readonly TourTypeRepositoryInterface  $tourtypeRepo,
        private readonly TourVisitRepositoryInterface  $tourvisitRepo,
        private readonly TourVisitPlaceRepositoryInterface  $tourvisitplac,
        private readonly TourCabManageRepositoryInterface  $tourtravellercabRepo,
        private readonly TourCabRepositoryInterface $tourcabRepo,
        private readonly TourDriverManageRepositoryInterface  $tourtravellerdriverRepo,
    ) {}
    public function index(Request $request)
    {
        $OrderInfo = \App\Models\TourOrder::whereIn('status', [1, 0])->where('refund_status', 0)->where('amount_status', 1)->get();
        $orderStatus = [
            'pending' => \App\Models\TourOrder::whereIn('status', [1, 0])->where(['refund_status' => 0, 'pickup_status' => 0, 'amount_status' => 1, 'drop_status' => 0, 'cab_assign' => 0])
                ->where('pickup_date', '>', \Carbon\Carbon::today()->toDateString())
                ->whereHas('accept', function ($query) {
                    $query->where('tour_order_accept.status', 1);
                })->withCabOrderCheck(auth('tour')->user()->relation_id)->with(['accept'])->count(),
            'confirmed' => $OrderInfo->where('pickup_status', 0)->where('drop_status', 0)->where('cab_assign', auth('tour')->user()->relation_id)->count(),
            'pickup' => $OrderInfo->where('pickup_status', 1)->where('drop_status', 0)->where('cab_assign', auth('tour')->user()->relation_id)->count(),
            'complete' => $OrderInfo->where('pickup_status', 1)->where('drop_status', 1)->where('cab_assign', auth('tour')->user()->relation_id)->count(),
            'canceled' => \App\Models\TourAndTravel::where('id', auth('tour')->user()->relation_id)->first()['cancel_order'],
        ];
        $tourInformation = \App\Models\TourAndTravel::where('id', auth('tour')->user()->relation_id)->first();
        $dashboardData = [
            'totalEarning' => $tourInformation['wallet_amount'],
            'pendingWithdraw' => $tourInformation['withdrawal_pending_amount'],
            "adminCommission" => $tourInformation['admin_commission'],
            "withdrawn" => $tourInformation['withdrawal_amount'],
            'collectedTotalTax' => $tourInformation['gst_amount'],
        ];
        $withdrawalMethods = \App\Models\WithdrawalMethod::where(['is_active' => 1])->get();

        $query = \App\Models\TourOrder::select(\Illuminate\Support\Facades\DB::raw('SUM(final_amount) as y'));
        $types = session()->get('statistics_type') ?? "yearEarn";
        if ($types === 'yearEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("YEAR(pickup_date) as x"))->groupBy(\Illuminate\Support\Facades\DB::raw("YEAR(pickup_date)"));
        } elseif ($types === 'MonthEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(pickup_date, '%Y-%m') as x"))->groupBy(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(pickup_date, '%Y-%m')"));
        } elseif ($types === 'WeekEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("CONCAT('Week ', WEEK(pickup_date), ' of ', DATE_FORMAT(pickup_date, '%M %Y')) as x"))->whereMonth('pickup_date', date('m'))->groupBy(\Illuminate\Support\Facades\DB::raw("YEARWEEK(pickup_date)"));
        } else {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("pickup_date as x"))->groupBy('pickup_date');
        }
        $query->where('cab_assign', auth('tour')->user()->relation_id)
            ->where('drop_status', 1)
            ->where('amount_status', 1)
            ->whereIn('refund_status', [0, 2]);
        $data_query = $query->get();
        $month_amount = [];
        $month_days = [];
        if ($data_query) {
            foreach ($data_query as $ke => $vale) {
                $month_amount[] = $vale['y'];
                $month_days[] = $vale['x'];
            }
        }
        return view(TourPath::DASHBOARD[VIEW], compact('month_amount', 'month_days', 'orderStatus', 'dashboardData', 'withdrawalMethods'));
    }

    public function orderStatistics(Request $request)
    {
        session()->put('statistics_type', $request['type']);
        // $data = \App\Models\TourOrder::select(\Illuminate\Support\Facades\DB::raw('SUM(final_amount) as y'),\Illuminate\Support\Facades\DB::raw('pickup_date as x'))->where('cab_assign',auth('tour')->user()->relation_id)->where('drop_status',1)->where('amount_status',1)->whereIn('refund_status',[0,2])->groupBy('pickup_date')->get();

        $query = \App\Models\TourOrder::select(\Illuminate\Support\Facades\DB::raw('SUM(final_amount) as y'));
        if ($request['type'] === 'yearEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("YEAR(pickup_date) as x"))->groupBy(\Illuminate\Support\Facades\DB::raw("YEAR(pickup_date)"));
        } elseif ($request['type'] === 'MonthEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(pickup_date, '%Y-%m') as x"))->groupBy(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(pickup_date, '%Y-%m')"));
        } elseif ($request['type'] === 'WeekEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("CONCAT('Week ', WEEK(pickup_date), ' of ', DATE_FORMAT(pickup_date, '%M %Y')) as x"))->whereMonth('pickup_date', date('m'))->groupBy(\Illuminate\Support\Facades\DB::raw("YEARWEEK(pickup_date)"));
        } else {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("pickup_date as x"))->groupBy('pickup_date');
        }
        $query->where('cab_assign', auth('tour')->user()->relation_id)
            ->where('drop_status', 1)
            ->where('amount_status', 1)
            ->whereIn('refund_status', [0, 2]);
        $data_query = $query->get();
        $month_amount = [];
        $month_days = [];
        if ($data_query) {
            foreach ($data_query as $ke => $vale) {
                $month_amount[] = $vale['y'];
                $month_days[] = $vale['x'];
            }
        }
        return response()->json(['view' => view('all-views.tour.dashboard.chart', compact('month_amount', 'month_days'))->render()], 200);
    }

    public function profileUpdate(Request $request, $id)
    {
        $getData  = $this->tourtraveller->getFirstWhere(params: ['id' => $id]);
        if (empty($getData)) {
            return back();
        }
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        return view(TourPath::PROFILEUPDATE[VIEW], compact('getData', 'languages', 'defaultLanguage'));
    }

    public function profileEdit(TourAndTravelRequest $request, TourAndTravelService $service, $id)
    {
        $dataArray = $service->getUpdateTourData($request);
        // dd($dataArray);
        $this->tourtraveller->update(id: $id, data: $dataArray);
        $sellers = \App\Models\Seller::where('relation_id', $id)->where('type', 'tour')->first();
        $sellers->f_name = explode(' ', $dataArray['person_name'])[0] ?? $dataArray['person_name'];
        $sellers->l_name =  explode(' ', $dataArray['person_name'])[1] ?? '';
        $sellers->phone = $dataArray['person_phone'];
        if (isset($dataArray['image']) && !empty($dataArray['image'])) {
            $sellers->image = $dataArray['image'];
        }
        $sellers->email = $dataArray['person_email'];

        $sellers->bank_name = $dataArray['bank_name'];
        $sellers->branch = $dataArray['bank_branch'];
        $sellers->ifsc = $dataArray['ifsc_code'];
        $sellers->account_no = $dataArray['account_number'];
        $sellers->holder_name = $dataArray['bank_holder_name'];

        $sellers->update_seller_status = 3;

        if (isset($dataArray['aadhaar_card_image']) && !empty($dataArray['aadhaar_card_image'])) {
            $sellers->aadhar_front_image = $dataArray['aadhaar_card_image'];
        }
        if (isset($dataArray['pan_card_image']) && !empty($dataArray['pan_card_image'])) {
            $sellers->pancard_image = $dataArray['pan_card_image'];
        }
        $sellers->save();
        // $this->translationRepo->update(request: $request, model: 'App\Models\TourAndTravel', id: $id);
        Toastr::success(translate('Tour_&_Traveller_updated_successfully'));
        return redirect()->route(TourPath::DASHBOARD[REDIRECT]);
    }

    public function orderPending(Request $request)
    {
        $pending_order = $this->tourorder->getListWhere(orderBy: ['id' => 'desc'], searchValue: (($request->get('type') == 'ok') ? $request->get('searchValue') : ''), relations: ['userData', 'company', 'Tour', 'accept'], filters: ['amount_status' => 1, 'status' => [0, 1], 'pickup_status' => 0, 'cab_assign_id' => 0, 'refund_status' => 0, 'accept' => 1, 'accept_user' => auth('tour')->user()->relation_id], dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(TourPath::ORDERPENDING[VIEW], compact('pending_order'));
    }

    public function orderCancel(Request $request, $id)
    {
        $getData = $this->tourorder->getFirstWhere(params: ['id' => $id, 'cab_assign' => auth('tour')->user()->relation_id]);
        if ($getData) {
            $this->tourorder->update(id: $id, data: ['cab_assign' => 0, 'traveller_cab_id' => 0, 'traveller_driver_id' => 0, 'on_load' => 0]);
            \App\Models\TourAndTravel::where('id', auth('tour')->user()->relation_id)->update(['cancel_order' =>  \Illuminate\Support\Facades\DB::raw('cancel_order + 1')]);
        }
        return back();
    }

    public function orderConfirm(Request $request)
    {
        $confirm_order = $this->tourorder->getListWhere(orderBy: ['id' => 'desc'], searchValue: (($request->get('type') == 'ok') ? $request->get('searchValue') : ''), relations: ['userData', 'company', 'Tour'], filters: ["assign_status" => ($request->get('assign_status') ?? ''), 'amount_status' => 1, 'status' => [1, 0], 'pickup_status' => 0, 'cab_assign_id' => auth('tour')->user()->relation_id, 'refund_status' => 0], dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(TourPath::ORDERCONFIRM[VIEW], compact('confirm_order'));
    }

    public function orderPickUp(Request $request)
    {
        $pickup_order = $this->tourorder->getListWhere(orderBy: ['id' => 'desc'], searchValue: (($request->get('type') == 'ok') ? $request->get('searchValue') : ''), relations: ['userData', 'company', 'Tour'], filters: ['amount_status' => 1, 'status' => 1, 'pickup_status' => 1, 'drop_status' => 0, 'cab_assign_id' => auth('tour')->user()->relation_id, 'refund_status' => 0], dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(TourPath::ORDERPICKUP[VIEW], compact('pickup_order'));
    }

    public function orderComplete(Request $request)
    {
        $complete_order = $this->tourorder->getListWhere(orderBy: ['id' => 'desc'], searchValue: (($request->get('type') == 'ok') ? $request->get('searchValue') : ''), relations: ['userData', 'company', 'Tour'], filters: ['amount_status' => 1, 'status' => 1, 'pickup_status' => 1, 'drop_status' => 1, 'cab_assign_id' => auth('tour')->user()->relation_id, 'refund_status' => 0], dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(TourPath::ORDERCOMPLETE[VIEW], compact('complete_order'));
    }

    public function orderDetails(Request $request, $id)
    {
        $getData = $this->tourorder->getFirstWhere(params: ['id' => $id], relations: ['userData', 'company', 'Tour']);
        $company_list = $this->tourtraveller->getListWhere(filters: ['id' => auth('tour')->user()->relation_id, 'status' => 1, 'is_approve' => 1]);
        $cabDetails = $this->tourtravellercabRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['traveller_id' => auth('tour')->user()->relation_id], relations: ['Cabs'], dataLimit: "all");
        $travellerDetails = $this->tourtravellerdriverRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['traveller_id' => auth('tour')->user()->relation_id], dataLimit: "all");
        return view(TourPath::ORDERDETAILS[VIEW], compact('getData', 'company_list', 'cabDetails', 'travellerDetails'));
    }

    public function orderAssignAccept(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'cab_id' => 'required',
        ]);
        $check_cab = $this->tourorder->getFirstWhere(params: ['id' => $request->id, 'status' => 1, 'pickup_status' => 0, 'drop_status' => 0, 'cab_assign' => 0]);
        if ($check_cab) {
            $this->tourorder->update(id: $request->id, data: ['status' => 1, 'pickup_status' => 0, 'drop_status' => 0, 'traveller_id' => $request->cab_id, 'cab_assign' => $request->cab_id]);
            Toastr::success('Assign Cab Successfully');
        } else {
            Toastr::error('Already Assign');
        }
        return back();
    }

    public function ordercabdriverAssign(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:tour_order,id',
            'traveller_cab_id' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $cabIds = is_string($value) ? json_decode($value, true) : $value;
                    $cabIds = is_array($cabIds) ?  $cabIds : explode(',', (string) $cabIds);

                    $currentTour = \App\Models\TourOrder::with(['Tour'])->find($request->id);
                    if (!$currentTour) return;

                    if ($currentTour['Tour']['tour_type'] == 'cities_tour') {
                        $bookingPackage = json_decode($currentTour['booking_package'], true);
                        if (is_array($bookingPackage) && $currentTour['Tour']['tour_type'] == 'cities_tour') {
                            foreach ($bookingPackage as $item) {
                                if (isset($item['type']) && ($item['type'] === 'cab' || $item['type'] === 'per_head') && isset($item['qty'])) {
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
                    $days_numberdays = \App\Models\TourVisits::where('id', $currentTour->tour_id)->value('number_of_day');
                    $days_numbernight = \App\Models\TourVisits::where('id', $currentTour->tour_id)->value('number_of_night');
                    if ($days_numberdays > $days_numbernight) {
                        $days = $days_numberdays;
                    } else {
                        $days = $days_numbernight;
                    }
                    $endDate = date('Y-m-d', strtotime("+$days days", strtotime($pickupDate . ' -1 day')));

                    $currentTour->update(['drop_date' => $endDate]);

                    foreach ($cabIds as $cabId) {
                        $overlapping = \App\Models\TourOrder::where('drop_status', 0)
                            ->where('id', '!=', $request->id)
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

                        if ($overlapping) {
                            if ($currentTour['Tour']['use_date'] == 1 || $currentTour['Tour']['use_date'] == 4) {
                                $cabs_data  = \App\Models\TourCabManage::where('id', $cabId)->with(['Cabs'])->first();
                                $getcheckQty = \App\Models\TourOrder::where(function ($query) use ($cabId, $request) {
                                    $query->whereRaw("JSON_CONTAINS(traveller_cab_id, ?)", [json_encode((string) $cabId)])
                                        ->orWhere('id', $request->id);
                                }) //whereRaw("JSON_CONTAINS(traveller_cab_id, ?)", [json_encode((string) $cabId)])
                                    ->where('tour_id', $currentTour['tour_id'])
                                    ->where('pickup_status', 0)
                                    ->where('pickup_date', [$pickupDate])
                                    ->select('booking_package')
                                    ->get()
                                    ->map(function ($tourVisit) {
                                        $packages = json_decode($tourVisit->booking_package, true);
                                        $cabPackage = collect($packages)->firstWhere('type', 'cab');
                                        if (!$cabPackage) {
                                            $cabPackage = collect($packages)->firstWhere('type', 'per_head');
                                        }
                                        return $cabPackage ? (int) $cabPackage['qty'] : 0;
                                    })->sum();
                                if ($cabs_data && $cabs_data['Cabs']['seats'] > 0) {
                                    if ($getcheckQty > $cabs_data['Cabs']['seats']) {
                                        $fail("Cab Name " . $cabs_data['Cabs']['name'] . " is already booked for the given seats. " . ($getcheckQty - $cabs_data['Cabs']['seats']) . " Seats are Not Available.");
                                    }
                                } else {
                                    $fail("Cab Name " . $cabs_data['Cabs']['name'] . " is already booked for the given seats.");
                                }
                            } else {
                                $fail("Cab ID $cabId is already booked for the given date range.");
                            }
                        }
                    }
                },
            ],

            'traveller_driver_id' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    // Ensure value is a string before decoding
                    $driverIds = is_string($value) ? json_decode($value, true) : $value;
                    $driverIds = is_array($driverIds) ? $driverIds : explode(',', (string) $driverIds);

                    $currentTour = \App\Models\TourOrder::with(['Tour'])->find($request->id);
                    if (!$currentTour) return;

                    if ($currentTour['Tour']['tour_type'] == 'cities_tour') {
                        $bookingPackage = json_decode($currentTour['booking_package'], true);
                        if (is_array($bookingPackage) && $currentTour['Tour']['tour_type'] == 'cities_tour') {
                            foreach ($bookingPackage as $item) {
                                if (isset($item['type']) && ($item['type'] === 'cab' || $item['type'] === 'per_head') && isset($item['qty'])) {
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
                    $days_numberdays = \App\Models\TourVisits::where('id', $currentTour->tour_id)->value('number_of_day');
                    $days_numbernight = \App\Models\TourVisits::where('id', $currentTour->tour_id)->value('number_of_night');
                    if ($days_numberdays > $days_numbernight) {
                        $days = $days_numberdays;
                    } else {
                        $days = $days_numbernight;
                    }
                    $endDate = date('Y-m-d', strtotime("+$days days", strtotime($pickupDate . ' -1 day')));

                    foreach ($driverIds as $driverId) {
                        $overlapping = \App\Models\TourOrder::where('drop_status', 0)
                            ->where('id', '!=', $request->id)
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

                        if ($overlapping) {
                            if ($currentTour['Tour']['use_date'] == 1 || $currentTour['Tour']['use_date'] == 4) {
                                $cabIds = is_string($value) ? json_decode($value, true) : $value;
                                $cabIds = is_array($cabIds) ?  $cabIds : explode(',', (string) $cabIds);

                                $cabs_data  = \App\Models\TourCabManage::where('id', $cabIds[0])->with(['Cabs'])->first();
                                $getcheckQty = \App\Models\TourOrder::where(function ($query) use ($cabIds, $request) {
                                    $query->whereRaw("JSON_CONTAINS(traveller_cab_id, ?)", [json_encode((string) $cabIds[0])])
                                        ->orWhere('id', $request->id);
                                })
                                    //whereRaw("JSON_CONTAINS(traveller_cab_id, ?)", [json_encode((string) $cabIds[0])])
                                    ->where('tour_id', $currentTour['tour_id'])->where('pickup_status', 0)
                                    ->where('pickup_date', [$pickupDate])
                                    ->select('booking_package')
                                    ->get()
                                    ->map(function ($tourVisit) {
                                        $packages = json_decode($tourVisit->booking_package, true);
                                        $cabPackage = collect($packages)->firstWhere('type', 'cab');
                                        if (!$cabPackage) {
                                            $cabPackage = collect($packages)->firstWhere('type', 'per_head');
                                        }
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
        \App\Models\TourOrder::where('id', $request->id)->update(['traveller_cab_id' => ($request->traveller_cab_id), 'traveller_driver_id' => ($request->traveller_driver_id)]);

        $tourOrder = \App\Models\TourOrder::where('id', $request->id)->with(['Tour', 'Driver', 'CabsManage'])->withDriverInfo($request->id)->first();

        if ($tourOrder['driver_data'] && json_decode($tourOrder['driver_data'], true)) {
            foreach (json_decode($tourOrder['driver_data'], true) as $kk => $infos) {
                $message_data['driver_name'] = ($infos['name'] ?? ''); //($tourOrder['Driver']['name'] ?? '');
                $message_data['driver_number'] = "+91" . ($infos['phone'] ?? ''); //($tourOrder['Driver']['phone'] ?? '');
                $message_data['vehicle_name'] = (json_decode($tourOrder['Cabs_data'] ?? '[]', true)[$kk]['cab_name'] ?? ''); //($tourOrder['CabsManage']['Cabs']['name'] ?? '');
                $message_data['vehicle_number'] = (json_decode($tourOrder['Cabs_data'] ?? '[]', true)[$kk]['reg_number'] ?? ''); //($tourOrder['CabsManage']['reg_number'] ?? '');
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

        Toastr::success('Assign Cab Successfully');
        return back();
    }

    public function orderReminderMessage(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:tour_order,id',
        ]);
        $tourOrder = \App\Models\TourOrder::where('id', $request->id)->with(['Tour', 'Driver', 'CabsManage'])->withDriverInfo($request->id)->first();

        $driverNames = '';
        $driverNumber = '';
        $VehicelsNames = '';
        $VehicelsNumber = '';
        $pq = 1;
        if ($tourOrder['driver_data'] && json_decode($tourOrder['driver_data'], true)) {
            foreach (json_decode($tourOrder['driver_data'], true) as $kk => $infos) {
                $driverNames .= $pq . ")" . ($infos['name'] ?? '') . "  ";
                $driverNumber .= $pq . ")" . "+91" . ($infos['phone'] ?? '') . "  ";
                $VehicelsNames .= $pq . ")" . (json_decode($tourOrder['Cabs_data'] ?? '[]', true)[$kk]['cab_name'] ?? '') . "  ";
                $VehicelsNumber .= $pq . ")" . (json_decode($tourOrder['Cabs_data'] ?? '[]', true)[$kk]['reg_number'] ?? '') . "  ";
                $pq++;
            }
        }
        $message_data['driver_name'] = $driverNames; //($tourOrder['Driver']['name'] ?? '');
        $message_data['driver_number'] = $driverNumber; //($tourOrder['Driver']['phone'] ?? '');
        $message_data['vehicle_name'] = $VehicelsNames; //($tourOrder['CabsManage']['Cabs']['name'] ?? '');
        $message_data['vehicle_number'] = $VehicelsNumber; //($tourOrder['CabsManage']['reg_number'] ?? '');

        $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
        $message_data['title_name'] = ($tourOrder['Tour']['tour_name'] ?? '');
        $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['pickup_date'] ?? ''));
        $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
        $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
        $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourOrder['Tour']['tour_type'] ?? ''))));
        $message_data['final_amount'] = webCurrencyConverter(amount: (float)($tourOrder['amount'] + $tourOrder['coupon_amount']) ?? 0);
        $message_data['customer_id'] = $tourOrder['user_id'];
        \App\Utils\Helpers::whatsappMessage('tour', 'Reminder of tour date', $message_data);
        Toastr::success(translate('User_Reminder_sent_successfully'));
        return back();
    }
    public function addTour(Request $request)
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $cab_list = \App\Models\TourCab::where('status', 1)->orderBy('id', 'desc')->get();
        $package_list = \App\Models\TourPackage::where('status', 1)->orderBy('id', 'desc')->get();
        $typeList = $this->tourtypeRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['status' => 1], dataLimit: "all");
        $googleMapsApiKey = config('services.google_maps.api_key');
        $travelar_list = \App\Models\TourAndTravel::where('status', 1)->where('id', auth('tour')->user()->relation_id)->where('is_approve', 1)->orderBy('id', 'desc')->get();
        return view(TourPath::ADDTOUR[VIEW], compact('googleMapsApiKey', 'languages', 'defaultLanguage', 'cab_list', 'package_list', 'typeList', 'travelar_list'));
    }

    public function tourSave(TourVisitRequest $request, TourVisitService $service)
    {
        $dataArray = $service->getTourVisitData($request);
        $insert = $this->tourvisitRepo->add(data: $dataArray);
        $this->translationRepo->add(request: $request, model: 'App\Models\TourVisits', id: $insert->id);
        Toastr::success(translate('Tour_Visit_added_successfully'));
        return redirect()->route(TourPath::TOURLIST[REDIRECT]);
    }

    public function tourList(Request $request)
    {
        $getDatalist = $this->tourvisitRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), filters: ['created_id' => [auth('tour')->user()->relation_id, 0]], dataLimit: getWebConfig(name: 'pagination_limit'));
        if (!empty($getDatalist)) {
            foreach ($getDatalist as $key => $value) {
                $getcheckbox  = \App\Models\TourOrderAccept::where('traveller_id', auth('tour')->user()->relation_id)->where('tour_id', $value['id'])->first();
                $getDatalist[$key]['accept_type'] = $getcheckbox['status'] ?? 0;
            }
        }
        return view(TourPath::TOURLIST[VIEW], compact('getDatalist'));
    }

    public function tourView(Request $request)
    {
        $getData = $this->tourvisitRepo->getFirstWhere(params: ['id' => $request['id']]);
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $cab_list = \App\Models\TourCab::where('status', 1)->orderBy('id', 'desc')->get();
        $package_list = \App\Models\TourPackage::where('status', 1)->orderBy('id', 'desc')->get();
        $typeList = $this->tourtypeRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['status' => 1], dataLimit: "all");
        $googleMapsApiKey = config('services.google_maps.api_key');
        $travelar_list = \App\Models\TourAndTravel::where('status', 1)->where('id', auth('tour')->user()->relation_id)->where('is_approve', 1)->orderBy('id', 'desc')->get();
        return view(TourPath::TOURVIEW[VIEW], compact('getData', 'languages', 'defaultLanguage', 'typeList', 'cab_list', 'package_list', 'googleMapsApiKey', 'travelar_list'));
    }

    public function tourUpdate(Request $request)
    {
        $getData = $this->tourvisitRepo->getFirstWhere(params: ['id' => $request['id']]);
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $cab_list = \App\Models\TourCab::where('status', 1)->orderBy('id', 'desc')->get();
        $package_list = \App\Models\TourPackage::where('status', 1)->orderBy('id', 'desc')->get();
        $typeList = $this->tourtypeRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['status' => 1], dataLimit: "all");
        $googleMapsApiKey = config('services.google_maps.api_key');
        $travelar_list = \App\Models\TourAndTravel::where('status', 1)->where('id', auth('tour')->user()->relation_id)->where('is_approve', 1)->orderBy('id', 'desc')->get();
        return view(TourPath::TOURUPDATE[VIEW], compact('getData', 'googleMapsApiKey', 'languages', 'defaultLanguage', 'cab_list', 'package_list', 'typeList', 'travelar_list'));
    }

    public function tourImgDelete($id, $name)
    {
        $getData = $this->tourvisitRepo->getFirstWhere(params: ['id' => $id, 'created_id' => auth('tour')->user()->relation_id]);
        if (!empty($getData)) {
            $dataArray = [];
            if (!empty($getData['image']) && json_decode($getData['image'])) {
                foreach (json_decode($getData['image'], true) as $key => $value) {
                    if ($name == $value) {
                        if (Storage::disk('public')->exists('tour_and_travels/tour_visit/' . $name)) {
                            Storage::disk('public')->delete('tour_and_travels/tour_visit/' . $name);
                        }
                    } else {
                        $dataArray[] = $value;
                    }
                }
            }
            $this->tourvisitRepo->update(id: $id, data: ["image" => json_encode($dataArray)]);
            Toastr::success(translate('Tour_Visit_image_deleted_successfully'));
        } else {
            Toastr::error(translate('Travel_tour_visit_image_will_be_deleted_by_administrator_only'));
        }
        return back();
    }

    public function tourEdit(TourVisitRequest $request, TourVisitService $service)
    {
        $getData = $this->tourvisitRepo->getFirstWhere(params: ['id' => $request['id'], 'created_id' => auth('tour')->user()->relation_id]);
        if (!empty($getData)) {
            $dataArray = $service->getUpdateTourData($request, $getData);
            $this->tourvisitRepo->update(id: $request['id'], data: $dataArray);
            $this->translationRepo->update(request: $request, model: 'App\Models\TourVisits', id: $request['id']);
            Toastr::success(translate('Tour_Visit_updated_successfully'));
        } else {
            Toastr::success(translate('Travel_tour_visit_will_be_updated_by_administrator_only'));
        }
        return redirect()->route(TourPath::TOURLIST[REDIRECT]);
    }
    public function tourDelete(Request $request, TourVisitService $service)
    {
        $getData = $this->tourvisitRepo->getFirstWhere(params: ['id' => $request['id'], 'status' => 0, 'created_id' => auth('tour')->user()->relation_id]);
        if (!empty($getData)) {
            $service->removeimages($getData);
            $this->tourvisitRepo->delete(params: ['id' => $request['id']]);
            Toastr::success(translate('Tour_visit_Deleted_successfully'));
            return back();
        } else {
            Toastr::error(translate('Travel_tour_visit_will_be_deleted_by_administrator_only'));
            return back();
        }
    }

    public function tourDetails(Request $request, $id)
    {
        $name = 'null';
        $getData = $this->tourvisitRepo->getFirstWhere(params: ['id' => $id]);
        $order_list = \App\Models\TourOrder::where('tour_id', $getData['id'])->where('traveller_id', auth('tour')->user()->relation_id)->where('status', '!=', 2)->with(['userData', 'company'])->paginate(10, ['*'], 'page1');
        $refund_list = \App\Models\TourOrder::where('tour_id', $getData['id'])->where('traveller_id', auth('tour')->user()->relation_id)->where('status', 2)->with(['userData', 'company'])->paginate(10, ['*'], 'page2');
        $tour_reviews = \App\Models\TourReviews::where('tour_id', $getData['id'])->with(['userData'])->paginate(10, ['*'], 'page3');

        return view(TourPath::TOUROVERVIEW[VIEW], compact('name', 'getData', 'order_list', 'refund_list', 'tour_reviews'));
    }

    public function tourVisit(Request $request)
    {
        $getData = $this->tourvisitplac->getListWhere(orderBy: ['id' => 'desc'], filters: ['tour_visit_id' => $request['id']], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'));
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $tour_visit_id = $request['id'];
        return view(TourPath::TOURVISITLIST[VIEW], compact('getData', 'languages', 'defaultLanguage', 'tour_visit_id'));
    }

    public function tourVisitStore(TourVisitPlaceRequest $request, TourVisitService $service)
    {
        $getData = $this->tourvisitRepo->getFirstWhere(params: ['id' => $request->tour_visit_id, 'created_id' => auth('tour')->user()->relation_id]);
        if ($getData) {
            $dataArray = $service->getTourVisitPlace($request);
            $insert = $this->tourvisitplac->add(data: $dataArray);
            $this->translationRepo->add(request: $request, model: 'App\Models\TourVisitPlace', id: $insert->id);
            Toastr::success(translate('Tour_Visit_place_added_successfully'));
        } else {
            Toastr::error(translate('Travel_tour_site_will_be_created_by_administrator_only'));
        }
        return redirect()->route(TourPath::TOURVISITLIST[REDIRECT], [$request->tour_visit_id]);
    }

    public function tourVisitUpdate(Request $request)
    {
        $getData = $this->tourvisitplac->getFirstWhere(params: ['id' => $request['id']], relations: ['translations']);
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        return view(TourPath::TOURVISITUPDATE[VIEW], compact('getData', 'languages', 'defaultLanguage'));
    }

    public function tourVisitEdit(TourVisitPlaceRequest $request, TourVisitService $service)
    {
        $getData = $this->tourvisitRepo->getFirstWhere(params: ['id' => $request->tour_visit_id, 'created_id' => auth('tour')->user()->relation_id]);
        if ($getData) {
            $dataArray = $service->getTourVisitPlaceupdate($request, $getData);
            $this->tourvisitplac->update(id: $request['id'], data: $dataArray);
            $this->translationRepo->update(request: $request, model: 'App\Models\TourVisitPlace', id: $request['id']);
            Toastr::success(translate('Tour_Visit_place_updated_successfully'));
        } else {
            Toastr::error(translate('Travel_tour_site_will_be_updated_by_administrator_only'));
        }
        return redirect()->route(TourPath::TOURVISITLIST[REDIRECT], [$request->tour_visit_id]);
    }

    public function TourAccept(Request $request)
    {
        $getData = \App\Models\TourOrderAccept::where('traveller_id', auth('tour')->user()->relation_id)->where('tour_id', $request->tour_id)->first();
        $checkOrder = \App\Models\TourOrder::where('cab_assign', auth('tour')->user()->relation_id)->where('tour_id', $request->tour_id)->where('drop_status', 0)->first();
        // if ($checkOrder) {
        //     return response()->json(['success' => 0, 'message' => translate('There_are_still_some_orders_left_on_this_tour'), 'data' => []], 200);
        // } else {
        $Pending_tour_check =  \App\Models\TourOrder::whereIn('status', [1, 0])->where(['refund_status' => 0, 'pickup_status' => 0, 'amount_status' => 1, 'drop_status' => 0, 'cab_assign' => 0])
            ->where('pickup_date', '>', \Carbon\Carbon::today()->toDateString())
            ->where('tour_id', $request->tour_id)
            ->whereHas('accept', function ($query) {
                $query->where('tour_order_accept.status', 1);
            })->withCabOrderCheck(auth('tour')->user()->relation_id)->with(['accept'])->count();
        if (($request->status ?? 0) == 0 && $Pending_tour_check > 0) {
            return response()->json(['success' => 0, 'message' => translate('All pending orders for this tour must be confirmed first'), 'data' => []], 200);
        } else {
            if (!empty($getData)) {
                $saveData = \App\Models\TourOrderAccept::find($getData['id']);
            } else {
                $saveData = new \App\Models\TourOrderAccept();
            }
            $saveData->tour_id = $request->tour_id;
            $saveData->traveller_id = auth('tour')->user()->relation_id;
            $saveData->status = $request->status;
            $saveData->save();
            return response()->json(['success' => 1, 'message' => translate('status_updated_successfully'), 'data' => $saveData], 200);
        }
        // }
    }

    public function tourVisitImgDelete($id, $name)
    {
        $getData = $this->tourvisitplac->getFirstWhere(params: ['id' => $id], relations: ['TourVisit']);
        if (!empty($getData) && !empty($getData['TourVisit'][0] ?? '') && $getData['TourVisit'][0]['created_id'] == auth('tour')->user()->relation_id) {
            $dataArray = [];
            if (!empty($getData['images']) && json_decode($getData['images'])) {
                foreach (json_decode($getData['images'], true) as $key => $value) {
                    if ($name == $value) {
                        if (Storage::disk('public')->exists('tour_and_travels/tour_visit_place/' . $name)) {
                            Storage::disk('public')->delete('tour_and_travels/tour_visit_place/' . $name);
                        }
                    } else {
                        $dataArray[] = $value;
                    }
                }
            }
            $this->tourvisitplac->update(id: $id, data: ["images" => json_encode($dataArray)]);
            Toastr::success(translate('Tour_Visit_place_deleted_successfully'));
        } else {
            Toastr::error(translate('Travel_tour_site_will_be_deleted_by_administrator_only'));
        }
        return back();
    }

    public function tourVisitDelete(Request $request, TourVisitService $service)
    {
        $getData = $this->tourvisitplac->getFirstWhere(params: ['id' => $request->id], relations: ['TourVisit']);
        if (!empty($getData) && !empty($getData['TourVisit'][0] ?? '') && $getData['TourVisit'][0]['created_id'] == auth('tour')->user()->relation_id) {
            $old_data = $this->tourvisitplac->getFirstWhere(params: ['id' => $request->id]);
            $service->removeplaceimages($old_data);
            $this->tourvisitplac->delete(params: ['id' => $request->id]);
            $this->translationRepo->delete(model: 'App\Models\TourVisitPlace', id: $request->id);
            Toastr::success(translate('Tour_visit_deleted_successfully'));
        } else {
            Toastr::error(translate('Travel_tour_site_will_be_deleted_by_administrator_only'));
        }
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function CabList(Request $request)
    {
        $getData = $this->tourtravellercabRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), filters: ['traveller_id' => auth('tour')->user()->relation_id], relations: ['Cabs'], dataLimit: getWebConfig(name: 'pagination_limit'));
        $cab_list = $this->tourcabRepo->getListWhere(orderBy: ['id' => 'desc'], dataLimit: "all");
        return view(TourPath::CABLIST[VIEW], compact('cab_list', 'getData'));
    }

    public function CabStore(CabRequest $request)
    {
        $dataArray = [
            "traveller_id" => auth('tour')->user()->relation_id,
            "cab_id" => $request['cab_id'],
            "model_number" => $request['model_number'],
            "reg_number" => $request['reg_number'],
            "status" => 0,
            "image" => "",
        ];
        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $dataArray['image'] = time() . '-tourcab' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_cab', $dataArray['image']);
        }
        $this->tourtravellercabRepo->add(data: $dataArray);
        return back();
    }

    public function CabStatusUpdate(Request $request)
    {
        $data['status'] = $request->get('status', 0);
        $this->tourtravellercabRepo->update(id: $request['id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }
    public function CabUpdate(Request $request)
    {
        $traveller_data =  $this->tourtravellercabRepo->getFirstWhere(params: ['id' => $request['id']]);
        $cab_list = $this->tourcabRepo->getListWhere(orderBy: ['id' => 'desc'], dataLimit: "all");
        return view(TourPath::CABUPDATE[VIEW], compact('cab_list', 'traveller_data'));
    }

    public function CabEdit(CabRequest $request)
    {
        $traveller_data =  $this->tourtravellercabRepo->getFirstWhere(params: ['id' => $request['id']]);
        if (empty($traveller_data)) {
            return back();
        }
        $dataArray = [
            "cab_id" => $request['cab_id'],
            "model_number" => $request['model_number'],
            "reg_number" => $request['reg_number'],
        ];
        if ($request->hasFile('image')) {
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_cab/' . $traveller_data['image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_cab/' . $traveller_data['image']);
            }
            $imageFile = $request->file('image');
            $dataArray['image'] = time() . '-tourcab' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_cab', $dataArray['image']);
        }
        $this->tourtravellercabRepo->update(id: $request['id'], data: $dataArray);
        Toastr::success(translate('Traveller_cab_Updated_successfully'));
        return redirect()->route(TourPath::CABLIST[REDIRECT]);
    }
    public function CabTravellerDelete(Request $request)
    {
        $old_data = $this->tourtravellercabRepo->getFirstWhere(params: ['id' => $request['id']]);
        if ($old_data) {
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_cab/' . $old_data['image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_cab/' . $old_data['image']);
            }
            $this->tourtravellercabRepo->delete(params: ['id' => $request['id']]);
            Toastr::success(translate('Traveller_cab_Deleted_successfully'));
            return response()->json(['success' => 1, 'message' => translate('Traveller_cab_Deleted_successfully')], 200);
        } else {
            Toastr::error(translate('Traveller_cab_Deleted_Failed'));
            return response()->json(['success' => 0, 'message' => translate('Traveller_cab_Deleted_Failed')], 400);
        }
    }

    public function CabDriverList(Request $request)
    {
        $getData = $this->tourtravellerdriverRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), filters: ['traveller_id' => auth('tour')->user()->relation_id], dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(TourPath::DRIVERLIST[VIEW], compact('getData'));
    }

    public function DriverStore(DriverRequest $request)
    {
        $dataArray = [
            "traveller_id" => auth('tour')->user()->relation_id,
            "name" => $request['name'],
            "phone" => $request['phone'],
            "email" => ($request['email'] ?? ""),
            "gender" => $request['gender'],
            "dob" => $request['dob'],
            "year_ex" => $request['year_ex'],
            "license_number" => $request['license_number'],
            "pan_number" => $request['pan_number'],
            "aadhar_number" => $request['aadhar_number'],
            "status" => 0,
        ];
        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $dataArray['image'] = time() . '-tourdriver' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_driver', $dataArray['image']);
        }
        if ($request->hasFile('license_image')) {
            $imageFile = $request->file('license_image');
            $dataArray['license_image'] = time() . '-tourdriver' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_driver', $dataArray['license_image']);
        }
        if ($request->hasFile('pan_image')) {
            $imageFile = $request->file('pan_image');
            $dataArray['pan_image'] = time() . '-tourdriver' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_driver', $dataArray['pan_image']);
        }
        if ($request->hasFile('aadhar_image')) {
            $imageFile = $request->file('aadhar_image');
            $dataArray['aadhar_image'] = time() . '-tourdriver' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_driver', $dataArray['aadhar_image']);
        }
        $this->tourtravellerdriverRepo->add(data: $dataArray);
        return back();
    }

    public function DriverStatusUpdate(Request $request)
    {
        $data['status'] = $request->get('status', 0);
        $this->tourtravellerdriverRepo->update(id: $request['id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function DriverDetele(Request $request)
    {
        $old_data = $this->tourtravellerdriverRepo->getFirstWhere(params: ['id' => $request['id']]);
        if ($old_data) {
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['image']);
            }
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['license_image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['license_image']);
            }
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['pan_image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['pan_image']);
            }
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['aadhar_image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['aadhar_image']);
            }
            $this->tourtravellerdriverRepo->delete(params: ['id' => $request['id']]);
            Toastr::success(translate('Traveller_cab_Deleted_successfully'));
            return response()->json(['success' => 1, 'message' => translate('Traveller_cab_Deleted_successfully')], 200);
        } else {
            Toastr::error(translate('Traveller_cab_Deleted_Failed'));
            return response()->json(['success' => 0, 'message' => translate('Traveller_cab_Deleted_Failed')], 400);
        }
    }

    public function DriverUpdate(Request $request)
    {
        $getData = $this->tourtravellerdriverRepo->getFirstWhere(params: ['id' => $request['id']]);
        return view(TourPath::DRIVERUPDATE[VIEW], compact('getData'));
    }
    public function DriverEdit(DriverRequest $request)
    {
        $old_data = $this->tourtravellerdriverRepo->getFirstWhere(params: ['id' => $request['id']]);
        if (empty($old_data)) {
            return back();
        }
        $dataArray = [
            "name" => $request['name'],
            "phone" => $request['phone'],
            "email" => ($request['email'] ?? ""),
            "gender" => $request['gender'],
            "dob" => $request['dob'],
            "year_ex" => $request['year_ex'],
            "license_number" => $request['license_number'],
            "pan_number" => $request['pan_number'],
            "aadhar_number" => $request['aadhar_number'],
        ];
        if ($request->hasFile('image')) {
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['image']);
            }
            $imageFile = $request->file('image');
            $dataArray['image'] = time() . '-tourdriver' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_driver', $dataArray['image']);
        }
        if ($request->hasFile('license_image')) {
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['license_image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['license_image']);
            }
            $imageFile = $request->file('license_image');
            $dataArray['license_image'] = time() . '-tourdriver' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_driver', $dataArray['license_image']);
        }
        if ($request->hasFile('pan_image')) {
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['pan_image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['pan_image']);
            }
            $imageFile = $request->file('pan_image');
            $dataArray['pan_image'] = time() . '-tourdriver' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_driver', $dataArray['pan_image']);
        }
        if ($request->hasFile('aadhar_image')) {
            if (Storage::disk('public')->exists('tour_and_travels/tour_traveller_driver/' . $old_data['aadhar_image'])) {
                Storage::disk('public')->delete('tour_and_travels/tour_traveller_driver/' . $old_data['aadhar_image']);
            }
            $imageFile = $request->file('aadhar_image');
            $dataArray['aadhar_image'] = time() . '-tourdriver' . $imageFile->getClientOriginalName();
            $imageFile->storeAs('public/tour_and_travels/tour_traveller_driver', $dataArray['aadhar_image']);
        }
        $this->tourtravellerdriverRepo->update(id: $request['id'], data: $dataArray);
        Toastr::success(translate('Traveller_driver_Updated_successfully'));
        return redirect()->route(TourPath::DRIVERLIST[REDIRECT]);
    }

    public function withdrawRequests(Request $request)
    {
        $vendorId = auth('tour')->user()->relation_id;
        $withdrawRequests = \App\Models\WithdrawalAmountHistory::where(['vendor_id' => $vendorId, 'type' => "tour"])->with(['Tour', 'TourVisit'])->paginate(10, ['*'], 'page');
        return view(TourPath::WITHDRAW[VIEW], compact('withdrawRequests'));
    }

    public function withdrawRequestadd(Request $request)
    {
        $vendorId = auth('tour')->id();
        // $withdrawMethod = \App\Models\WithdrawalMethod::where('id',$request['withdraw_method'])->first;
        // $wallet = $this->vendorWalletRepo->getFirstWhere(params:['seller_id'=> auth('tour')->id()]);
        // if (($wallet['total_earning']) >= currencyConverter($request['amount']) && $request['amount'] > 1) {
        //     $this->withdrawRequestRepo->add($this->withdrawRequestService->getWithdrawRequestData(
        //         withdrawMethod:$withdrawMethod,
        //         request:$request,
        //         addedBy: 'vendor',
        //         vendorId: $vendorId
        //     ));
        //     $totalEarning = $wallet['total_earning'] - currencyConverter($request['amount']);
        //     $pendingWithdraw = $wallet['pending_withdraw'] + currencyConverter($request['amount']);
        //     $this->vendorWalletRepo->update(
        //         id:$wallet['id'],
        //         data: $this->vendorWalletService->getVendorWalletData(totalEarning:$totalEarning,pendingWithdraw:$pendingWithdraw)
        //     );
        //     Toastr::success(translate('withdraw_request_has_been_sent'));
        // }else{
        //     Toastr::error(translate('invalid_request').'!');
        // }
        return redirect()->back();
    }


    public function TourSupportTicket(Request $request)
    {
        $vendorId = auth('tour')->user()->relation_id;
        $support_list = \App\Models\VendorSupportTicket::where(['created_by' => 'vendor', 'type' => 'tour'])->get();
        $message_list = \App\Models\VendorSupportTicketConv::where(['created_by' => 'vendor', 'type' => 'tour', 'vendor_id' => $vendorId])
            ->when(isset($request['status']) && ($request['status'] != 'all'), function ($query) use ($request) {
                return $query->where('status', $request['status']);
            })->with(['Tour'])->paginate(10, ['*'], 'page');

        return view(TourPath::INBOX[VIEW], compact('message_list', 'support_list'));
    }

    public function TourSupportTicketStore(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|integer|exists:vendor_support_tickets,id',
            'created_by' => 'required|in:admin,vendor',
            'type' => 'required|in:tour',
            'query_title' => 'required',
            'message' => 'required',
        ]);

        $save_ticket = new \App\Models\VendorSupportTicketConv();
        $save_ticket->ticket_id = $request->ticket_id;
        $save_ticket->created_by = $request->created_by;
        $save_ticket->type = $request->type;
        $save_ticket->vendor_id = auth('tour')->user()->relation_id;
        $save_ticket->query_title = $request->query_title;
        $save_ticket->status = 'open';
        $save_ticket->save();

        $ticket_his = new \App\Models\VendorSupportTicketConvHis();
        $ticket_his->ticket_issue_id = $save_ticket->id;
        $ticket_his->sender_type = 'user';
        $ticket_his->message = $request->message;
        $ticket_his->save();
        Toastr::success(translate('ticket_created_successfully'));
        return back();
    }

    public function TourSupportTicketStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:vendor_support_tickets_conv,id',
        ]);
        $ticket_his = \App\Models\VendorSupportTicketConv::find($request->id);
        $ticket_his->status = $request->get('status', 'close');
        $ticket_his->save();
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function TourSupportTicketView(Request $request)
    {

        $supportTicket = \App\Models\VendorSupportTicketConv::with(['Tour', 'conversations'])->find($request->id);
        \App\Models\VendorSupportTicketConvHis::where('ticket_issue_id', $request->id)->update(['read_user_status' => 1]);
        return view(TourPath::INBOXVIEW[VIEW], compact('supportTicket'));
    }

    public function TourSupportTicketReplay(Request $request)
    {
        $request->validate([
            'ticket_issue_id' => 'required|integer|exists:vendor_support_tickets_conv,id',
            "sender_type" => "required|in:admin,user",
            'replay' => "required",
        ]);
        $attachedPaths = [];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/support-ticket', $imageName);
                $attachedPaths[] = $imageName;
            }
        }

        $ticket_his = new \App\Models\VendorSupportTicketConvHis();
        $ticket_his->ticket_issue_id = $request->ticket_issue_id;
        $ticket_his->sender_type = $request->sender_type;
        $ticket_his->message = $request->replay;
        $ticket_his->attached = json_encode($attachedPaths);
        $ticket_his->save();
        Toastr::success(translate('ticket_Added_successfully'));
        return back();
    }

    public function AdminSupportTicket(Request $request)
    {
        $vendorId = auth('tour')->user()->relation_id;
        $support_list = \App\Models\VendorSupportTicket::where(['created_by' => 'admin', 'type' => 'tour'])->get();
        $message_list = \App\Models\VendorSupportTicketConv::where(['created_by' => 'admin', 'type' => 'tour', 'vendor_id' => $vendorId])->with(['Tour'])
            ->when(isset($request['status']) && ($request['status'] != 'all'), function ($query) use ($request) {
                return $query->where('status', $request['status']);
            })->paginate(10, ['*'], 'page');
        return view(TourPath::ADMININBOX[VIEW], compact('message_list', 'support_list'));
    }
    public function FCMUpdates(Request $request)
    {
        request()->session()->put('device_fcm', $request['type']);
        if ($request['type'] == 'owner') {
            \App\Models\Seller::where('id', auth('tour')->id())->update(['cm_firebase_token' => $request['fcm']]);
        }
        return back();
    }
    public function FCMUpdatesdelete(Request $request)
    {
        session()->forget('device_fcm');
        return back();
    }

    public function GetVendorInfo(Request $request)
    {
        $amounts = 0;
        if (!empty($request['tour_order_id'])) {
            $tourOrder = \App\Models\TourOrder::select('amount', 'tour_id')->where('id', $request['tour_order_id'])->with(['Tour'])->first();
            if ($tourOrder) {
                $eventtax = \App\Models\ServiceTax::find(1);
                $gst_amount = 0;
                $admin_commission = 0;
                $final_amount = $tourOrder['amount'];
                if ($eventtax['tour_tax']) {
                    $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                    $final_amount = $final_amount - $gst_amount;
                }
                if ($tourOrder['Tour']['tour_commission'] ?? 0) {
                    $admin_commission = (($final_amount * $tourOrder['Tour']['tour_commission'] ?? 0) / 100);
                    $final_amount = ($final_amount - $admin_commission);
                }
                $admin_commission2 = (($final_amount * 30) / 100);
                $amounts = ($final_amount - $admin_commission2);
            }
        } else {
            $amounts = \App\Models\TourAndTravel::select('wallet_amount')->where('id', $request['id'])->first()['wallet_amount'] ?? 0;
        }
        $tour_data = \App\Models\TourAndTravel::select('bank_holder_name', 'bank_name', 'bank_branch', 'ifsc_code', 'account_number')->where('id', $request['id'])->first();
        if ($tour_data) {
            return response()->json(['success' => 1, 'amount' => $amounts, 'bank_info' => $tour_data, 'message' => "Vendor Withdrawal Info"], 200);
        } else {
            return response()->json(['success' => 0, 'amount' => 0, 'bank_info' => [], 'message' => "Not Found Vendor"], 200);
        }
    }

    public function WithdrawalRequestView(Request $request)
    {
        $vendorId = auth('tour')->user()->relation_id;
        $withdrawRequests = \App\Models\WithdrawalAmountHistory::where(['vendor_id' => $vendorId, 'type' => "tour"])->with(['Tour', 'TourVisit'])->where('id', $request['id'])->first();
        return view('all-views/tour/withdraw/view', compact('withdrawRequests'));
    }
    public function AddWithdrawalRequest(Request $request)
    {
        if ($request['req_amount'] <= $request['wallet_amount']) {
            $withdrawal  =  new \App\Models\WithdrawalAmountHistory();
            $withdrawal->type = "tour";
            $withdrawal->vendor_id = auth('tour')->user()->relation_id;
            $withdrawal->ex_id = (($request->ex_id) ? $request->ex_id : "");
            $withdrawal->holder_name = $request['holder_name'] ?? "";
            $withdrawal->bank_name = $request['bank_name'] ?? "";
            $withdrawal->ifsc_code = $request['ifsc_code'] ?? "";
            $withdrawal->account_number = $request['account_number'] ?? "";
            $withdrawal->upi_code = $request['upi_code'] ?? '';
            $withdrawal->old_wallet_amount = $request['wallet_amount'];
            $withdrawal->req_amount = $request['req_amount'];
            $withdrawal->save();
            if ($request->ex_id) {
                \App\Models\TourOrder::where('id', $request->ex_id)->update(['advance_withdrawal_amount' => $request['req_amount']]);
            } else {
                \App\Models\TourAndTravel::where('id', auth('tour')->user()->relation_id)->update(['withdrawal_pending_amount' => $request['req_amount']]);
            }
            Toastr::success(translate('Payment_request_sent_successfully'));
        } else {
            Toastr::error(translate('Payment_Request_failed'));
        }
        return back();
    }

    public function PasswordChange(Request $request, $id)
    {
        $request->validate([
            'old_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8',
        ]);
        $passwordResetData = \App\Models\Seller::where(['type' => "tour", 'id' => $id])->first();
        if (!$passwordResetData) {
            Toastr::error(translate('invalid_URL'));
            return back()->withInput();
        }
        if (!\Illuminate\Support\Facades\Hash::check($request->old_password, $passwordResetData->password)) {
            Toastr::error(translate('Old password does not match'));
            return back()->withInput();
        }
        $passwordResetData->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $passwordResetData->save();
        if ($passwordResetData) {
            Toastr::success(translate('Password_reset_successfully'));
            return redirect()->route('tour-vendor.dashboard.index');
        } else {
            Toastr::error(translate('invalid_URL'));
            return back()->withInput();
        }
    }
}