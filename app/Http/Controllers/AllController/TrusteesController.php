<?php

namespace App\Http\Controllers\AllController;

use App\Contracts\Repositories\DonateTrustAdsRepositoryInterface;
use App\Contracts\Repositories\DonateTrustRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Enums\ViewPaths\AllPaths\TrusteesPath;
use App\Http\Controllers\Controller;
use App\Models\DonateAllTransaction;
use App\Models\DonateCategory;
use App\Models\DonateTrust;
use App\Models\Seller;
use App\Models\VendorEmployees;
use App\Models\VendorRoles;
use App\Services\DonateTrustAdsService;
use App\Traits\FileManagerTrait;
use App\Utils\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class TrusteesController extends Controller
{
    use FileManagerTrait;
    protected $relationId;
    public function __construct(
        private readonly TranslationRepositoryInterface      $translationRepo,
        private readonly DonateTrustRepositoryInterface  $donateTrustRepo,
        private readonly DonateTrustAdsRepositoryInterface  $donateads,
    ) {
        $this->middleware(function ($request, $next) {
            if (auth('trust')->check()) {
                $this->relationId = auth('trust')->user()->relation_id;
            } elseif (auth('trust_employee')->check()) {
                $this->relationId = auth('trust_employee')->user()->relation_id;
            } else {
                $this->relationId = null;
            }

            return $next($request);
        });
    }

    public function dashboard()
    {
        $tourInformation = DonateTrust::where('id', $this->relationId)->first();
        $dashboardData = [
            'totalEarning' => $tourInformation['trust_total_amount'] ?? 0,
            'pendingWithdraw' => $tourInformation['trust_req_withdrawal_amount'] ?? 0,
            "adminCommission" => $tourInformation['admin_commission'] ?? 0,
            "withdrawn" => $tourInformation['trust_total_withdrawal'] ?? 0,
        ];
        $withdrawalMethods = \App\Models\WithdrawalMethod::where(['is_active' => 1])->get();


        $types = session()->get('statistics_type') ?? "yearEarn";

        $query = \App\Models\DonateAllTransaction::select(\Illuminate\Support\Facades\DB::raw('SUM(final_amount) as y'));
        if ($types === 'yearEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("YEAR(created_at) as x"))->groupBy(\Illuminate\Support\Facades\DB::raw("YEAR(created_at)"));
        } elseif ($types === 'MonthEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(created_at, '%Y-%m') as x"))->groupBy(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(created_at, '%Y-%m')"));
        } elseif ($types === 'WeekEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("CONCAT('Week ', WEEK(created_at), ' of ', DATE_FORMAT(created_at, '%M %Y')) as x"))->whereMonth('created_at', date('m'))->groupBy(\Illuminate\Support\Facades\DB::raw("YEARWEEK(created_at)"));
        } else {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("created_at as x"))->groupBy('created_at');
        }
        $query->where('amount_status', 1)
            ->whereIn('type', ['donate_trust', 'donate_ads'])
            ->where('trust_id', $this->relationId);
        $data_query = $query->get();
        $month_amount = [];
        $month_days = [];
        if ($data_query) {
            foreach ($data_query as $ke => $vale) {
                $month_amount[] = $vale['y'];
                $month_days[] = $vale['x'];
            }
        }

        return view(TrusteesPath::DASHBOARD[VIEW], compact('withdrawalMethods', 'dashboardData', 'month_amount', 'month_days'));
    }

    public function orderStatistics(Request $request)
    {
        session()->put('statistics_type', $request['type']);
        // $data = \App\Models\TourOrder::select(\Illuminate\Support\Facades\DB::raw('SUM(final_amount) as y'),\Illuminate\Support\Facades\DB::raw('pickup_date as x'))->where('cab_assign',auth('tour')->user()->relation_id)->where('drop_status',1)->where('amount_status',1)->whereIn('refund_status',[0,2])->groupBy('pickup_date')->get();

        $query = \App\Models\DonateAllTransaction::select(\Illuminate\Support\Facades\DB::raw('SUM(final_amount) as y'));
        if ($request['type'] === 'yearEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("YEAR(created_at) as x"))->groupBy(\Illuminate\Support\Facades\DB::raw("YEAR(created_at)"));
        } elseif ($request['type'] === 'MonthEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(created_at, '%Y-%m') as x"))->groupBy(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(created_at, '%Y-%m')"));
        } elseif ($request['type'] === 'WeekEarn') {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("CONCAT('Week ', WEEK(created_at), ' of ', DATE_FORMAT(created_at, '%M %Y')) as x"))->whereMonth('created_at', date('m'))->groupBy(\Illuminate\Support\Facades\DB::raw("YEARWEEK(created_at)"));
        } else {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw("created_at as x"))->groupBy('created_at');
        }
        $query->where('amount_status', 1)
            ->whereIn('type', ['donate_trust', 'donate_ads'])
            ->where('trust_id', $this->relationId);
        $data_query = $query->get();
        $month_amount = [];
        $month_days = [];
        if ($data_query) {
            foreach ($data_query as $ke => $vale) {
                $month_amount[] = $vale['y'];
                $month_days[] = $vale['x'];
            }
        }
        return response()->json(['view' => view('all-views.trustees.dashboard.chart', compact('month_amount', 'month_days'))->render()], 200);
    }

    public function AdsAdd(Request $request)
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $all_categorys = DonateCategory::where(['status' => 1, 'type' => "category"])->get();
        $all_purpose = DonateCategory::where(['status' => 1, 'type' => "porpose"])->get();
        $unit_list = ["KG" => "KG", "Gram" => "Gram", "Liter" => "Liter", "Meter" => "Meter", "Centimeter" => "Centimeter", "Inch" => "Inch", "Pound" => "Pound", "Ounce" => "Ounce", "Milliliter" => "Milliliter", "Foot" => "Foot", "Yard" => "Yard", "Mile" => "Mile", "Kilometer" => "Kilometer", "Litre" => "Litre", "Square Meter" => "Square Meter", "Hectare" => "Hectare", "Acre" => "Acre", "Kilowatt" => "Kilowatt", "Watt" => "Watt", "Kilocalorie" => "Kilocalorie", "Calorie" => "Calorie", "Joule" => "Joule", "Pascal" => "Pascal", "Newton" => "Newton", "Pound per Square Inch" => "Pound per Square Inch", "British Thermal Unit" => "British Thermal Unit", "Hertz" => "Hertz", "Kilohertz" => "Kilohertz", "Revolutions per Minute" => "Revolutions per Minute", "Second" => "Second", "Minute" => "Minute", "Hour" => "Hour", "Day" => "Day", "Week" => "Week", "Month" => "Month", "Year" => "Year", "Person" => "Person", "Pieces" => "Pieces"];
        asort($unit_list);
        return view(TrusteesPath::ADSADD[VIEW], compact('all_purpose', 'unit_list', 'all_categorys', 'languages', 'defaultLanguage'));
    }

    public function AdsStore(Request $request, DonateTrustAdsService $service)
    {
        $request->validate([
            'name' => 'required|array',
            'name.*' => 'required|string|min:1',
            'purpose_id' => 'required',
            'set_type' => 'required',
            'description' => 'required|array',
            'description.*' => 'required|string|min:1',
        ]);
        $request['type'] = 'outsite';
        if (\App\Models\DonateTrust::where('id', $this->relationId)->where('status', 1)->where('is_approve', 1)->exists()) {
            $request['category_id'] = \App\Models\DonateTrust::where('id', $this->relationId)->first()['category_id'] ?? 0;
            $request['trust_id'] = $this->relationId;
            $request['admin_commission'] = 5;
            $dataArray  = $service->getAddData($request);
            $insert = $this->donateads->add(data: $dataArray);
            $this->translationRepo->add(request: $request, model: 'App\Models\DonateAds', id: $insert->id);
            Toastr::success(translate('Trust_ads_added_successfully'));
            Helpers::editDeleteLogs('Donate', 'Ads Trust', 'Insert');
        } else {
            Toastr::error(translate('Your_profile_is_not_active,_on_hold,_or_pending._You_can`t_create_a_ads_donation.'));
        }
        return redirect()->route(TrusteesPath::ADSLIST[REDIRECT]);
    }

    public function AdsList(Request $request)
    {
        $ads_list = $this->donateads->getListWhere(orderBy: ['id' => 'desc'], filters: ['is_approve' => $request->get('is_approve'), 'trust_id' => $this->relationId], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(TrusteesPath::ADSLIST[VIEW], compact('ads_list'));
    }

    public function AdsStatusUpdate(Request $request)
    {
        $data['status'] = $request->get('status', 0);
        $this->donateads->update(id: $request['id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function AdsUpdate(Request $request, $id)
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $all_purpose = DonateCategory::where(['status' => 1, 'type' => "porpose"])->get();
        $unit_list = ["KG" => "KG", "Gram" => "Gram", "Liter" => "Liter", "Meter" => "Meter", "Centimeter" => "Centimeter", "Inch" => "Inch", "Pound" => "Pound", "Ounce" => "Ounce", "Milliliter" => "Milliliter", "Foot" => "Foot", "Yard" => "Yard", "Mile" => "Mile", "Kilometer" => "Kilometer", "Litre" => "Litre", "Square Meter" => "Square Meter", "Hectare" => "Hectare", "Acre" => "Acre", "Kilowatt" => "Kilowatt", "Watt" => "Watt", "Kilocalorie" => "Kilocalorie", "Calorie" => "Calorie", "Joule" => "Joule", "Pascal" => "Pascal", "Newton" => "Newton", "Pound per Square Inch" => "Pound per Square Inch", "British Thermal Unit" => "British Thermal Unit", "Hertz" => "Hertz", "Kilohertz" => "Kilohertz", "Revolutions per Minute" => "Revolutions per Minute", "Second" => "Second", "Minute" => "Minute", "Hour" => "Hour", "Day" => "Day", "Week" => "Week", "Month" => "Month", "Year" => "Year", "Person" => "Person", "Pieces" => "Pieces"];
        asort($unit_list);
        $old_data = $this->donateads->getFirstWhere(params: ['id' => $id], relations: ['translations']);
        return view(TrusteesPath::ADSUPDATE[VIEW], compact('old_data', 'unit_list', 'all_purpose', 'defaultLanguage', 'languages'));
    }

    public function AdsUpdateSave(Request $request, DonateTrustAdsService $service)
    {
        $old_data = $this->donateads->getFirstWhere(params: ['id' => $request->get('id')]);
        $request->validate([
            'name' => 'required|array',
            'name.*' => 'required|string|min:1',
            'purpose_id' => 'required',
            'set_type' => 'required',
            'description' => 'required|array',
            'description.*' => 'required|string|min:1',
        ]);
        $request['type'] = 'outsite';
        if (\App\Models\DonateTrust::where('id', $this->relationId)->where('status', 1)->where('is_approve', 1)->exists()) {
            $request['category_id'] = \App\Models\DonateTrust::where('id', $this->relationId)->first()['category_id'] ?? 0;
            $request['trust_id'] = $this->relationId;
            $dataArray  = $service->getUpdateData($request, $old_data);
            $this->donateads->update(id: $request->get('id'), data: $dataArray);
            $this->translationRepo->update(request: $request, model: 'App\Models\DonateAds', id: $request->get('id'));
            Toastr::success(translate('Trust_ads_update_successfully'));
            Helpers::editDeleteLogs('Donate', 'Ads Trust', 'Update');
        } else {
            Toastr::error(translate('Your_profile_is_not_active,_on_hold,_or_pending._You_can`t_create_a_ads_donation.'));
        }
        return redirect()->route(TrusteesPath::ADSLIST[REDIRECT]);
    }

    public function AdsDelete(Request $request, DonateTrustAdsService $service)
    {
        if (DonateAllTransaction::where('ads_id', $request->get('id'))->where('amount_status', 1)->where('type', 'donate_ads')->count() > 0) {
            Toastr::error(translate('This_donation_ad_cannot_be_deleted_because_it_already_has_donors'));
            return response()->json(['success' => 0, 'message' => translate('This_donation_ad_cannot_be_deleted_because_it_already_has_donors')], 200);
        } else {
            $old_data = $this->donateads->getFirstWhere(params: ['id' => $request->get('id')]);
            if (!empty($old_data)) {
                $service->deleteAdsImage($old_data);
            }
            $this->donateads->delete(params: ['id' => $request->get('id')]);
            $this->translationRepo->delete(model: 'App\Models\DonateAds', id: $request->get('id'));
            Toastr::success(translate('Ads_Deleted_successfully'));
            Helpers::editDeleteLogs('Donate', 'Ads Trust', 'Delete');
        }
        return response()->json(['success' => 1, 'message' => translate('Ads_Deleted_successfully')], 200);
    }

    public function AdsDetails(Request $request, $id)
    {
        $old_data = $this->donateads->getFirstWhere(params: ['id' => $id], relations: ['Purpose', 'Trusts', 'category']);
        if ($old_data) {
            $ads_transaction = DonateAllTransaction::where(['type' => 'donate_ads', 'trust_id' => $old_data['trust_id'], 'ads_id' => $id])->with(['users'])->paginate(10);
            return view(TrusteesPath::ADSDETAILS[VIEW], compact('id', 'old_data', 'ads_transaction'));
        } else {
            return redirect()->route(TrusteesPath::ADSLIST[REDIRECT]);
        }
    }

    public function TrustSupportTicket(Request $request)
    {
        $vendorId = $this->relationId;
        $support_list = \App\Models\VendorSupportTicket::where(['created_by' => 'vendor', 'type' => 'trust'])->get();
        $message_list = \App\Models\VendorSupportTicketConv::where(['created_by' => 'vendor', 'type' => 'trust', 'vendor_id' => $vendorId])
            ->when(isset($request['status']) && ($request['status'] != 'all'), function ($query) use ($request) {
                return $query->where('status', $request['status']);
            })->with(['Trust'])->paginate(10, ['*'], 'page');

        return view(TrusteesPath::TRUSTINBOX[VIEW], compact('message_list', 'support_list'));
    }

    public function TrustSupportTicketStore(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|integer|exists:vendor_support_tickets,id',
            'created_by' => 'required|in:admin,vendor',
            'type' => 'required|in:trust',
            'query_title' => 'required',
            'message' => 'required',
        ]);

        $save_ticket = new \App\Models\VendorSupportTicketConv();
        $save_ticket->ticket_id = $request->ticket_id;
        $save_ticket->created_by = $request->created_by;
        $save_ticket->type = $request->type;
        $save_ticket->vendor_id = $this->relationId;
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

    public function TrustSupportTicketStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:vendor_support_tickets_conv,id',
        ]);
        $ticket_his = \App\Models\VendorSupportTicketConv::find($request->id);
        $ticket_his->status = $request->get('status', 'close');
        $ticket_his->save();
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function TrustSupportTicketView(Request $request)
    {
        $supportTicket = \App\Models\VendorSupportTicketConv::with(['Trust', 'conversations'])->find($request->id);
        \App\Models\VendorSupportTicketConvHis::where('ticket_issue_id', $request->id)->update(['read_user_status' => 1]);
        return view(TrusteesPath::TRUSTINBOXVIEW[VIEW], compact('supportTicket'));
    }

    public function TrustSupportTicketReplay(Request $request)
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
        $vendorId = $this->relationId;
        $support_list = \App\Models\VendorSupportTicket::where(['created_by' => 'admin', 'type' => 'trust'])->get();
        $message_list = \App\Models\VendorSupportTicketConv::where(['created_by' => 'admin', 'type' => 'trust', 'vendor_id' => $vendorId])
            ->when(isset($request['status']) && ($request['status'] != 'all'), function ($query) use ($request) {
                return $query->where('status', $request['status']);
            })->with(['Trust'])->paginate(10, ['*'], 'page');
        return view(TrusteesPath::TRUSTADMININBOX[VIEW], compact('message_list', 'support_list'));
    }

    public function withdrawRequests(Request $request)
    {
        $vendorId = $this->relationId;
        $withdrawRequests = \App\Models\WithdrawalAmountHistory::where(['vendor_id' => $vendorId, 'type' => "trust"])->with(['Trust'])->paginate(10, ['*'], 'page');
        return view(TrusteesPath::TRUSTWITHDRAW[VIEW], compact('withdrawRequests'));
    }

    public function GetVendorInfo(Request $request)
    {
        $amounts = \App\Models\DonateTrust::select('trust_total_amount as wallet_amount')->where('id', $request['id'])->first()['wallet_amount'] ?? 0;
        $tour_data = \App\Models\DonateTrust::select('beneficiary_name as bank_holder_name', 'bank_name', 'ifsc_code', 'account_no as account_number')->where('id', $request['id'])->first();
        if ($tour_data) {
            return response()->json(['success' => 1, 'amount' => $amounts, 'bank_info' => $tour_data, 'message' => "Vendor Withdrawal Info"], 200);
        } else {
            return response()->json(['success' => 0, 'amount' => 0, 'bank_info' => [], 'message' => "Not Found Vendor"], 200);
        }
    }

    public function AddWithdrawalRequest(Request $request)
    {
        if (!\App\Models\WithdrawalAmountHistory::where(['vendor_id' => $this->relationId, 'type' => "trust", 'status' => 0])->exists()) {
            if ($request['req_amount'] <= $request['wallet_amount']) {
                $withdrawal  =  new \App\Models\WithdrawalAmountHistory();
                $withdrawal->type = "trust";
                $withdrawal->vendor_id = $this->relationId;
                $withdrawal->ex_id = (($request->ex_id) ? $request->ex_id : "");
                $withdrawal->holder_name = $request['holder_name'] ?? "";
                $withdrawal->bank_name = $request['bank_name'] ?? "";
                $withdrawal->ifsc_code = $request['ifsc_code'] ?? "";
                $withdrawal->account_number = $request['account_number'] ?? "";
                $withdrawal->upi_code = $request['upi_code'] ?? '';
                $withdrawal->old_wallet_amount = $request['wallet_amount'];
                $withdrawal->req_amount = $request['req_amount'];
                $withdrawal->save();
                // if ($request->ex_id) {
                //     \App\Models\TourOrder::where('id', $request->ex_id)->update(['advance_withdrawal_amount' => $request['req_amount']]);
                // } else {
                \App\Models\DonateTrust::where('id', $this->relationId)->update(['trust_req_withdrawal_amount' => $request['req_amount']]);
                // }
                Toastr::success(translate('Payment_request_sent_successfully'));
            } else {
                Toastr::error(translate('Payment_Request_failed'));
            }
        } else {
            Toastr::error(translate('A_payment_request_has_already_been_sent'));
        }
        return back();
    }

    public function DonationHistory(Request $request)
    {
        if ($request->type) {
            $ads_transaction = DonateAllTransaction::where('type', $request->type)->where('amount_status', 1)->where('trust_id', $this->relationId)->with(['users', 'getTrust', 'adsTrust'])->paginate(10);
        } else {
            $ads_transaction = DonateAllTransaction::whereIn('type', ['donate_ads', 'donate_trust'])->where('amount_status', 1)->where('trust_id', $this->relationId)->with(['users', 'getTrust', 'adsTrust'])->paginate(10);
        }
        return view("all-views.trustees.ads.history", compact('ads_transaction'));
    }

    public function WithdrawalRequestView(Request $request)
    {
        $vendorId = $this->relationId;
        $withdrawRequests = \App\Models\WithdrawalAmountHistory::where(['vendor_id' => $vendorId, 'type' => "trust"])->with(['Trust'])->where('id', $request['id'])->first();
        return view('all-views/trustees/withdraw/view', compact('withdrawRequests'));
    }

    public function profileUpdate(Request $request, $id)
    {
        $getData  = $this->donateTrustRepo->getFirstWhere(params: ['id' => $id], relations: ['category']);
        if (empty($getData)) {
            return back();
        }
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $categoryList = \App\Models\DonateCategory::where('status', 1)->where('type', 'category')->get();
        $vendor = \App\Models\Seller::where('relation_id', $this->relationId)->where('type', 'trust')->first();
        return view(TrusteesPath::PROFILEUPDATE[VIEW], compact('vendor', 'categoryList', 'getData', 'languages', 'defaultLanguage'));
    }

    public function profileEdit(Request $request, $id)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'password' => [
                'required',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)(?!.*\s).{8,}$/',
                'same:confirm_password',
            ],
            'confirm_password' => 'required',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->get('password')[0] ?? 'Unknown error'], 200);
        }

        if (auth('trust')->check()) {
            $seller = Seller::where('id', auth('trust')->id())->where(['relation_id' => $id, 'type' => 'trust'])->first();
        } elseif (auth('trust_employee')->check()) {
            $seller = VendorEmployees::where('id', auth('trust_employee')->id())->where(['relation_id' => $id, 'type' => 'trust'])->first();
        }
        if (!$seller) {
            return response()->json(['message' => 'Seller not found'], 404);
        }
        $seller->password = bcrypt($request->password);
        $seller->save();
        return response()->json(['message' => translate('password_updated_successfully')]);
    }

    public function FCMUpdates(Request $request)
    {
        request()->session()->put('device_fcm', $request['type']);
        if ($request['type'] == 'owner') {
            \App\Models\Seller::where('id', auth('trust')->id())->update(['cm_firebase_token' => $request['fcm']]);
        }
        return back();
    }

    public function profileUpdate2(Request $request, DonateTrustAdsService $service)
    {
        $checkData = \App\Models\Seller::where('relation_id', $this->relationId)->where('type', 'trust')->first();
        if (empty($checkData['all_doc_info'])) {
            $getUniqueArray = [
                'name' => 2,
                'trust_name' => 2,
                "trust_category" => 2,
                'trust_email' => 2,
                'full_address' => 2,
                'description' => 2,
                "members" => 2,
                "website_link" => 2,
                'user_image' => 2,
                "gallery_image" => 2,
                'pan_card' => 2,
                'pan_card_image' => 2,
                'trust_pan_card' => 2,
                'trust_pan_card_image' => 2,
                'twelve_a_certificate' => 2,
                'eighty_g_certificate' => 2,
                'niti_aayog_certificate' => 2,
                'csr_certificate' => 2,
                'e_anudhan_certificate' => 2,
                'frc_certificate' => 2,
                'bank_name' => 2,
                'beneficiary_name' => 2,
                'ifsc_code' => 2,
                'account_type' => 2,
                'account_no' => 2,
                'cancelled_cheque_image' => 2,
                'twelve_a_number' => 2,
                'eighty_g_number' => 2,
                'niti_aayog_number' => 2,
                'csr_number' => 2,
                'e_anudhan_number' => 2,
                'frc_number' => 2,
            ];
            \App\Models\Seller::where('relation_id', $this->relationId)->where('type', 'trust')->update(["all_doc_info" => json_encode($getUniqueArray)]);
        }
        $vendor = \App\Models\Seller::where('relation_id', $this->relationId)->where('type', 'trust')->first();
        $organizerData = DonateTrust::where('id', $this->relationId)->first();
        $allData = $service->ReCorrectTrustData($request, $organizerData, $vendor);

        \App\Models\Seller::where('relation_id', $this->relationId)->where('type', 'trust')->update($allData['vendor']);
        \App\Models\DonateTrust::where('id', $this->relationId)->update($allData['trust']);
        return response()->json(['message' => $request->all(), 'status' => 1, 'data' => []], 200);
    }

    public function DeleteImage(Request $request, $id, $name)
    {
        $getData = DonateTrust::where('id', $id)->first();
        $vendor = \App\Models\Seller::where('relation_id', $id)->where('type', 'trust')->first();
        $check_validate = json_decode($vendor['all_doc_info'] ?? '[]', true);
        if ($check_validate['gallery_image'] == 2 || $check_validate['gallery_image'] == 0) {
            if ($getData && $getData->gallery_image) {
                $images = [];
                $galleryImages = json_decode($getData->gallery_image, true);

                foreach ($galleryImages as $photo) {
                    if ($photo === $name) {
                        $imagePath = storage_path('app/public/donate/trust/' . $name);
                        if (\Illuminate\Support\Facades\File::exists($imagePath)) {
                            \Illuminate\Support\Facades\File::delete($imagePath);
                        }
                    } else {
                        $images[] = $photo;
                    }
                }
                $getData->gallery_image = json_encode($images);
                $getData->save();
            }
            Toastr::success('Image deleted successfully!');
            return back()->with('success', 'Image deleted successfully!');
        } else {
            Toastr::error('Image deleted Failed!');
            return back()->with('success', 'Image deleted Failed!');
        }
    }

    public function AddEmployee(Request $request)
    {
        $roleList = VendorRoles::where('type', 'trust')->get();
        return view(TrusteesPath::ADDEMPLOYEE[VIEW], compact('roleList'));
    }

    public function StoreEmployee(Request $request)
    {
        $request->validate([
            'identify_number' => 'required|unique:vendor_employee,identify_number',
            'name' => 'required',
            'email' => 'required|unique:vendor_employee,email|unique:sellers,email',
            'em_phone' => 'required|unique:vendor_employee,phone|unique:sellers,phone',
            'password' => 'required',
            'emp_role_id' => 'required',
        ]);

        $employee = new VendorEmployees();
        $employee->identify_number = $request['identify_number'];
        $employee->name = $request['name'];
        $employee->type = 'trust';
        $employee->phone = $request['em_phone'];
        $employee->email = $request['email'];
        $employee->emp_role_id = $request['emp_role_id'];
        $employee->password = bcrypt($request['password']);
        if ($request['image']) {
            $fileName = $imageName = time() . '_' . uniqid() . '.' . $request['image']->getClientOriginalExtension();
            if (!\Illuminate\Support\Facades\Storage::disk('public')->exists('event/employee')) {
                \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('event/employee');
            }
            \Illuminate\Support\Facades\Storage::disk('public')->put('event/employee/' . $fileName, file_get_contents($request['image']));
            $employee->image = $imageName;
        }
        $employee->relation_id = $this->relationId;
        $employee->save();
        Toastr::success(translate('Trustees_Employee_added_successfully'));
        return redirect()->route(TrusteesPath::EMPLOYEELIST[REDIRECT]);
    }

    public function EmployeeList(Request $request)
    {
        $getData = VendorEmployees::where('type', 'trust')->where('relation_id', $this->relationId)->paginate(getWebConfig(name: 'pagination_limit'));
        return view(TrusteesPath::EMPLOYEELIST[VIEW], compact('getData'));
    }

    public function EmployeeStatusUpdate(Request $request)
    {
        $data = VendorEmployees::where('type', 'trust')->where('id', $request['id'])->first();
        $data->status = $request->get('status', 0);
        $data->save();
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }
    public function Employeedelete(Request $request)
    {
        $old_data = VendorEmployees::where('type', 'trust')->where('id', $request['id'])->where('relation_id', $this->relationId)->first();
        if ($old_data) {
            $filePath = "event/employee/" . $old_data['image'];
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);
            }
            $old_data->delete();
            Toastr::success(translate('Employee_Deleted_successfully'));
            return response()->json(['success' => 1, 'message' => translate('Employee_deleted_successfully')], 200);
        } else {
            Toastr::error(translate('Employee_Deleted_Failed'));
            return response()->json(['success' => 0, 'message' => translate('Not_found_data')], 400);
        }
    }

    public function EmployeeEdit(Request $request)
    {
        $old_data = VendorEmployees::where('type', 'trust')->where('id', $request['id'])->where('relation_id', $this->relationId)->first();
        if ($old_data) {
            $roleList = VendorRoles::where('type', 'trust')->get();
            return view(TrusteesPath::EMPLOYEEUPDATE[VIEW], compact('roleList', 'old_data'));
        }
        return redirect()->route(TrusteesPath::EMPLOYEELIST[REDIRECT]);
    }

    public function EmployeeUpdate(Request $request, $id)
    {
        $request->validate([
            'identify_number' => 'required|unique:vendor_employee,identify_number,' . $id,
            'name'            => 'required',
            'email'           => 'required|unique:vendor_employee,email,' . $id . ',id|unique:sellers,email',
            'em_phone'        => 'required|unique:vendor_employee,phone,' . $id . ',id|unique:sellers,phone',
            'emp_role_id'     => 'required',
        ]);

        $employee = VendorEmployees::where('id', $id)->where('relation_id', $this->relationId)->first();
        $employee->identify_number = $request['identify_number'];
        $employee->name = $request['name'];
        $employee->phone = $request['em_phone'];
        $employee->email = $request['email'];
        $employee->emp_role_id = $request['emp_role_id'];
        if ($request['image']) {
            $filePath = "event/employee/" . $employee->image;
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);
            }
            $fileName = $imageName = time() . '_' . uniqid() . '.' . $request['image']->getClientOriginalExtension();
            if (!\Illuminate\Support\Facades\Storage::disk('public')->exists('event/employee')) {
                \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('event/employee');
            }
            \Illuminate\Support\Facades\Storage::disk('public')->put('event/employee/' . $fileName, file_get_contents($request['image']));
            $employee->image = $imageName;
        }
        $employee->save();
        Toastr::success(translate('Trustees_Employee_updated_successfully'));
        return redirect()->route(TrusteesPath::EMPLOYEELIST[REDIRECT]);
    }

    public function CheckEmailPhone(Request $request)
    {
        $query = VendorEmployees::where($request['type'], $request['value']);
        if ($request['status'] == 1) {
            $query->where('id', '!=', $request['id']);
        }
        $getData = $query->first();
        if ($getData) {
            return response()->json(['success' => 1, 'message' => "Data Find", 'data' => $getData], 200);
        } else {
            $sellercheck = Seller::where('email', $request['value'])->orWhere('phone', $request['value'])->first();
            if ($sellercheck) {
                return response()->json(['success' => 1, 'message' => "Data Find", 'data' => $getData], 200);
            }
            return response()->json(['success' => 0, 'message' => 'Not Found'], 200);
        }
    }
}
