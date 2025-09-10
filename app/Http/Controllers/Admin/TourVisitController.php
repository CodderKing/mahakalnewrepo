<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\TourOrderRepositoryInterface;
use App\Contracts\Repositories\TourTypeRepositoryInterface;
use App\Contracts\Repositories\TourVisitPlaceRepositoryInterface;
use App\Contracts\Repositories\TourVisitRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Enums\ViewPaths\Admin\TourVisitPath;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TourVisitPlaceRequest;
use App\Http\Requests\Admin\TourVisitRequest;
use App\Models\TourAndTravel;
use App\Models\TourCab;
use App\Models\TourFollowup;
use App\Models\TourLeads;
use App\Models\TourOrder;
use App\Models\TourPackage;
use App\Models\TourReviews;
use App\Models\TourVisits;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\TourVisitService;
use App\Traits\FileManagerTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class TourVisitController extends Controller
{

    use FileManagerTrait;
    public function __construct(
        private readonly TranslationRepositoryInterface     $translationRepo,
        private readonly TourVisitRepositoryInterface  $tourtraveller,
        private readonly TourVisitPlaceRepositoryInterface  $tourvisitplac,
        private readonly TourOrderRepositoryInterface  $tourorder,
        private readonly TourTypeRepositoryInterface  $tourtypeRepo,
    ) {}

    public function AddTour()
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $package_list = TourPackage::where('status', 1)->orderBy('id', 'desc')->get();
        $cab_list = TourCab::where('status', 1)->orderBy('id', 'desc')->get();
        $travelar_list = TourAndTravel::where('status', 1)->where('is_approve', 1)->orderBy('id', 'desc')->get();
        $typeList = $this->tourtypeRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['status' => 1], dataLimit: "all");
        $googleMapsApiKey = config('services.google_maps.api_key');
        return view(TourVisitPath::ADDTRAVEL[VIEW], compact('cab_list', 'typeList', 'googleMapsApiKey', 'travelar_list', 'package_list', 'languages', 'defaultLanguage'));
    }
    public function SaveTour(TourVisitRequest $request, TourVisitService $service)
    {
        $dataArray = $service->getTourVisitData($request);
        $insert = $this->tourtraveller->add(data: $dataArray);
        $this->translationRepo->add(request: $request, model: 'App\Models\TourVisits', id: $insert->id);
        Toastr::success(translate('Tour_Visit_added_successfully'));
        return redirect()->route(TourVisitPath::TRAVELLIST[REDIRECT]);
    }

    public function TourList(Request $request)
    {
        $getDatalist = $this->tourtraveller->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(TourVisitPath::TRAVELLIST[VIEW], compact('getDatalist'));
    }

    public function StatusUpdate(Request $request)
    {
        $data['status'] = $request->get('status', 0);
        $this->tourtraveller->update(id: $request['id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function TourUpdate(Request $request, $id)
    {
        $getData  = $this->tourtraveller->getFirstWhere(params: ['id' => $id], relations: ['translations']);
        if (empty($getData)) {
            return back();
        }
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $package_list = TourPackage::where('status', 1)->orderBy('id', 'desc')->get();
        $cab_list = TourCab::where('status', 1)->orderBy('id', 'desc')->get();
        $travelar_list = TourAndTravel::where('status', 1)->where('is_approve', 1)->orderBy('id', 'desc')->get();
        $typeList = $this->tourtypeRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['status' => 1], dataLimit: "all");
        $googleMapsApiKey = config('services.google_maps.api_key');
        return view(TourVisitPath::TRAVELUPDATE[VIEW], compact('cab_list', 'typeList', 'package_list', 'travelar_list', 'googleMapsApiKey', 'getData', 'languages', 'defaultLanguage'));
    }

    public function Touredit(TourVisitRequest $request, TourVisitService $service)
    {
        $getData  = $this->tourtraveller->getFirstWhere(params: ['id' => $request->id]);
        $dataArray = $service->getUpdateTourData($request, $getData);
        $insert = $this->tourtraveller->update(id: $request->id, data: $dataArray);
        $this->translationRepo->update(request: $request, model: 'App\Models\TourVisits', id: $request->id);
        Toastr::success(translate('Tour_Visit_updated_successfully'));
        return redirect()->route(TourVisitPath::TRAVELLIST[REDIRECT]);
    }

    public function TourDelete(Request $request, TourVisitService $service, $id)
    {
        $old_data = $this->tourtraveller->getFirstWhere(params: ['id' => $id]);
        $service->removedoc($old_data);
        $this->tourtraveller->delete(params: ['id' => $id]);
        $this->translationRepo->delete(model: 'App\Models\TourVisits', id: $id);
        Toastr::success(translate('Tour_visit_deleted_successfully'));
        return redirect()->route(TourVisitPath::TRAVELLIST[REDIRECT]);
    }

    public function TourView(Request $request, $id)
    {
        $getData  = $this->tourtraveller->getFirstWhere(params: ['id' => $id], relations: ['translations']);
        if (empty($getData)) {
            return back();
        }
        $name = 'null';
        $view_type = 1;
        $order_list = TourOrder::where('tour_id', $getData['id'])->where('status', '!=', 2)->with(['userData', 'company'])->paginate(10, ['*'], 'page1');
        $refund_list = TourOrder::where('tour_id', $getData['id'])->where('status', 2)->with(['userData', 'company'])->paginate(10, ['*'], 'page2');
        $tour_reviews = TourReviews::where('tour_id', $getData['id'])->with(['userData'])->paginate(10, ['*'], 'page3');

        return view(TourVisitPath::TRAVELVIEW[VIEW], compact('getData', 'name', 'view_type', 'order_list', 'refund_list', 'tour_reviews'));
    }

    public function VisitList(Request $request, $id)
    {
        $getData = $this->tourvisitplac->getListWhere(orderBy: ['id' => 'desc'], filters: ['tour_visit_id' => $id], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'));
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $tour_visit_id = $id;
        return view(TourVisitPath::VISIT[VIEW], compact('getData', 'tour_visit_id', 'languages', 'defaultLanguage'));
    }

    public function VisitStore(TourVisitPlaceRequest $request, TourVisitService $service)
    {
        $dataArray = $service->getTourVisitPlace($request);
        $insert = $this->tourvisitplac->add(data: $dataArray);
        $this->translationRepo->add(request: $request, model: 'App\Models\TourVisitPlace', id: $insert->id);
        Toastr::success(translate('Tour_Visit_place_added_successfully'));
        return redirect()->route(TourVisitPath::VISIT[REDIRECT], [$request->tour_visit_id]);
    }
    public function VisitPlaceStatus(Request $request)
    {
        $data['status'] = $request->get('status', 0);
        $this->tourvisitplac->update(id: $request['id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function TourImageRemove(TourVisitService $service, $id, $name)
    {
        $getData  = $this->tourtraveller->getFirstWhere(params: ['id' => $id]);
        if (empty($getData)) {
            return back();
        }
        $dataIMage = $service->ImageRemove($getData, $name);
        $this->tourtraveller->update(id: $id, data: ['image' => json_encode($dataIMage)]);
        return back();
    }

    public function VisitPlaceDelete(Request $request, TourVisitService $service)
    {
        $old_data = $this->tourvisitplac->getFirstWhere(params: ['id' => $request->id]);
        $service->removeimages($old_data);
        $this->tourvisitplac->delete(params: ['id' => $request->id]);
        $this->translationRepo->delete(model: 'App\Models\TourVisitPlace', id: $request->id);
        Toastr::success(translate('Tour_visit_deleted_successfully'));
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
        //return redirect()->route(TourVisitPath::VISIT[REDIRECT],[$old_data['tour_visit_id']]);
    }

    public function TourLeads(Request $request)
    {
        $Tourlist = TourLeads::where('amount_status', 0)->with(['Tour', 'userData', 'followby'])
            ->when($request->searchValue, function ($query) use ($request) {
                $query->where('amount', 'like', "%$request->searchValue%");
                $query->orWhereHas('Tour', function ($q) use ($request) {
                    $q->where('tour_name', 'like', "%$request->searchValue%");
                });
                $query->orWhereHas('userData', function ($q) use ($request) {
                    $q->where('name', 'like', "%$request->searchValue%");
                    $q->orWhere('phone', 'like', "%$request->searchValue%");
                });
            })->orderBy('id', 'desc')->paginate(10);
        return view(TourVisitPath::LEADS[VIEW], compact('Tourlist'));
    }

    public function TourLeadDelete(Request $request, $id)
    {
        $lead = TourLeads::find($id);
        if ($lead) {
            $lead->delete();
            Toastr::success(translate('lead_Delete_successfully'));
        } else {
            Toastr::error(translate('lead_Not_found'));
        }
        return back();
    }

    public function TourLeadsFollow($id)
    {
        $followlist = TourFollowup::where('lead_id', $id)->get();
        if ($followlist) {
            return response()->json($followlist);
        } else {
            return response()->json([], 200);
        }
    }

    public function TourLeadsFollowUp(Request $request)
    {
        $follows = [
            'lead_id' => $request->input('lead_id'),
            'message' => $request->input('message'),
            'last_date' => $request->input('last_date'),
            'next_date' => $request->input('next_date'),
            'follow_by' => $request->input('follow_by'),
            'follow_by_id' => $request->input('follow_by_id'),
        ];
        TourFollowup::create($follows);
        Toastr::success(translate('lead_follow_up_successfully'));
        return back();
    }

    public function CompanyBookingGet(Request $request)
    {
        $complete_order = $this->tourorder->getListWhere(orderBy: ['id' => 'desc'], searchValue: '', relations: ['userData', 'company', 'Tour'], filters: ['amount_status' => 1, 'tour_id' => $request->id, 'refund_status' => 0], dataLimit: 'all');
        if (!empty($complete_order) && count($complete_order) > 0) {
            $array['order_list'] = $complete_order;
            $array['company'] = TourOrder::selectRaw('sum(qty) as qty, SUM(amount) as amount, cab_assign,tour_id')
                ->where(['amount_status' => 1, 'status' => 1, 'tour_id' => $request->id, 'refund_status' => 0])->where('cab_assign', '!=', '0')
                ->with(['company', 'Tour'])
                ->groupBy('cab_assign')
                ->get();
            $array['company_all'] = TourAndTravel::where('is_approve', 1)->where('status', 1)->get();
            return response()->json(['data' => $array, 'status' => 1], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0], 200);
        }
    }

    function CompanyBookingSettlement(Request $request)
    {
        $tour_id = $request->tour_id ?? '';
        $type = $request->type ?? '';
        $getData = TourOrder::where('tour_id', $tour_id)->where('refund_status', 0)->get();
        if ($type == 1 && !empty($tour_id)) {
            if (!empty($getData) && count($getData) > 0) {
                foreach ($getData as $key => $value) {
                    User::where('id', $value['user_id'])->update(['wallet_balance' => \Illuminate\Support\Facades\DB::raw('wallet_balance + ' .  $value['amount'])]);
                    TourOrder::where('id', $value['id'])->update(['status' => 2, 'refound_id' => "wallet", 'refund_status' => 1, 'refund_amount' => $value['amount'], 'refund_date' => date('Y-m-d H:i:s'), 'cab_assign' => 0]);
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $value['user_id'];
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Tour Refund';
                    $wallet_transaction->transaction_type = 'tour_refund';
                    $wallet_transaction->balance = User::where('id', $value['user_id'])->first()['wallet_balance'];
                    $wallet_transaction->credit =  $value['amount'];
                    $wallet_transaction->save();
                }
            }
        } elseif ($type == 2 && !empty($tour_id)) {
            if (isset($request->order_id) && !empty($request->order_id)) {
                foreach ($request->order_id as $order_id) {
                    TourOrder::where('id', $order_id)->where('cab_assign', $request->cab_id)->update(['status' => 1, 'cab_assign' => $request->transfor_cab]);
                }
            } else {
                $getData1 = TourOrder::where('tour_id', $tour_id)->where('cab_assign', $request->cab_id)->where('refund_status', 0)->get();
                if (!empty($getData1) && count($getData1) > 0) {
                    foreach ($getData1 as $key => $value) {
                        TourOrder::where('id', $value['id'])->where('cab_assign', $request->cab_id)->update(['status' => 1, 'cab_assign' => $request->transfor_cab]);
                    }
                }
            }
        } elseif ($type == 3 && !empty($tour_id)) {
            if (isset($request->order_id) && !empty($request->order_id)) {
                foreach ($request->order_id as $order_id) {
                    $orderData = TourOrder::where('id', $order_id)->first();
                    User::where('id', $orderData['user_id'])->update(['wallet_balance' => \Illuminate\Support\Facades\DB::raw('wallet_balance + ' .  ($orderData['amount'] ?? 0))]);
                    TourOrder::where('id', $order_id)->update(['status' => 2, 'refound_id' => "wallet", 'refund_status' => 1, 'refund_amount' => $orderData['amount'], 'refund_date' => date('Y-m-d H:i:s'), 'cab_assign' => 0]);
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $orderData['user_id'];
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Tour Refund';
                    $wallet_transaction->transaction_type = 'tour_refund';
                    $wallet_transaction->balance = User::where('id', $orderData['user_id'])->first()['wallet_balance'];
                    $wallet_transaction->credit =  $orderData['amount'];
                    $wallet_transaction->save();
                }
            } else {
                $getData1 = TourOrder::where('tour_id', $tour_id)->where('cab_assign', $request->cab_id)->where('refund_status', 0)->get();
                if (!empty($getData1) && count($getData1) > 0) {
                    foreach ($getData1 as $key => $value) {
                        User::where('id', $value['user_id'])->update(['wallet_balance' => \Illuminate\Support\Facades\DB::raw('wallet_balance + ' .  $value['amount'])]);
                        TourOrder::where('id', $value['id'])->update(['status' => 2, 'refound_id' => "wallet", 'refund_status' => 1, 'refund_amount' => $value['amount'], 'refund_date' => date('Y-m-d H:i:s'), 'cab_assign' => 0]);
                        $wallet_transaction = new WalletTransaction();
                        $wallet_transaction->user_id = $value['user_id'];
                        $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                        $wallet_transaction->reference = 'Tour Refund';
                        $wallet_transaction->transaction_type = 'tour_refund';
                        $wallet_transaction->balance = User::where('id', $value['user_id'])->first()['wallet_balance'];
                        $wallet_transaction->credit =  $value['amount'];
                        $wallet_transaction->save();
                    }
                }
            }
        }

        Toastr::success(translate('Changes_updated_successfully'));
        return back();
    }

    public function CommentStatusUpdate(Request $request)
    {
        $data['status'] = $request->get('status', 0);
        TourReviews::where('id', $request['id'])->update($data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function CommissionUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tour_commission' => 'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        TourVisits::where('id', $id)->update(['tour_commission' => $request->tour_commission]);
        Toastr::success(translate('Changes_updated_successfully'));
        return back();
    }

    public function VisitPlaceUpdate(Request $request)
    {
        $old_data = $this->tourvisitplac->getFirstWhere(params: ['id' => $request->id], relations: ['translations']);
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        return view('admin-views.tour_and_travels.tour_visit.visit-update', compact('old_data', 'languages', 'defaultLanguage'));
    }

    public function VisitPlaceEdit(TourVisitPlaceRequest $request, TourVisitService $service)
    {
        $old_data = $this->tourvisitplac->getFirstWhere(params: ['id' => $request->id]);
        $dataArray = $service->getTourVisitPlaceupdate($request, $old_data);
        $this->tourvisitplac->update(id: $request->id, data: $dataArray);
        $this->translationRepo->update(request: $request, model: 'App\Models\TourVisitPlace', id: $request->id);
        Toastr::success(translate('Tour_Visit_place_updated_successfully'));
        return redirect()->route(TourVisitPath::VISIT[REDIRECT], [$request->tour_visit_id]);
    }

    public function VisitPlaceImageRemove(TourVisitService $service, $id, $name)
    {
        $getData  = $this->tourvisitplac->getFirstWhere(params: ['id' => $id]);
        if (empty($getData)) {
            return back();
        }
        $dataIMage = $service->VisitImageRemove($getData, $name);
        $this->tourvisitplac->update(id: $id, data: ['images' => json_encode($dataIMage)]);
        return back();
    }
}