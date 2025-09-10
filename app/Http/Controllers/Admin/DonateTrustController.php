<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\DonateAllTransactionRepositoryInterface;
use App\Contracts\Repositories\DonateCategoryRepositoryInterface;
use App\Contracts\Repositories\DonateLeadsRepositoryInterface;
use App\Contracts\Repositories\DonateTrustAdsRepositoryInterface;
use App\Contracts\Repositories\DonateTrustRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Enums\ViewPaths\Admin\DonateTrustPath;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DonateTrustRequest;
use App\Models\Bank;
use App\Models\DonateLeadFollowup;
use App\Services\DonateTrustService;
use App\Traits\FileManagerTrait;
use App\Utils\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;

class DonateTrustController extends Controller
{

    use FileManagerTrait;
    public function __construct(
        private readonly TranslationRepositoryInterface     $translationRepo,
        private readonly DonateCategoryRepositoryInterface     $donatecategory,
        private readonly DonateTrustRepositoryInterface     $donateTrust,
        private readonly DonateAllTransactionRepositoryInterface $donateTrans,
        private readonly DonateTrustAdsRepositoryInterface $donateads,
        private readonly DonateLeadsRepositoryInterface $donateleads,
    ) {}

    public function AddTrust(Request $request)
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $all_categorys = $this->donatecategory->getListWhere(filters: ['status' => 1, 'types' => 'category']);
        $bankList = Bank::where('status', 1)->get();
        return view(DonateTrustPath::ADDTRUST[VIEW], compact('bankList', 'all_categorys', 'defaultLanguage', 'languages'));
    }

    public function StoreTrust(DonateTrustRequest $request, DonateTrustService $service)
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $dataArray = $service->getAddData($request);
            $insert = $this->donateTrust->add(data: $dataArray);
            $Sellers = new \App\Models\Seller();
            $Sellers->f_name = explode(' ', $dataArray['name'])[0] ?? '';
            $nameParts = explode(' ', $dataArray['name'] ?? '');
            $Sellers->l_name = end($nameParts) ?: '';
            $Sellers->phone = $request['member_phone_no'][0] ?? '';
            $Sellers->email = $request['trust_email'];
            $Sellers->image = $dataArray['theme_image'];
            $rawPhone = $request['member_phone_no'][0] ?? '';
            $Sellers->password = bcrypt(preg_replace('/^\+91\s?/', '', $rawPhone));
            $Sellers->status = "pending";
            $Sellers->update_seller_status = 1;
            $Sellers->reupload_doc_status = 1;
            $Sellers->pan_number = $dataArray['pan_card'];
            $Sellers->pancard_image = $dataArray['pan_card_image'];
            $Sellers->bank_name = $request['bank_name'];
            $Sellers->branch = $request['ifsc_code'];
            $Sellers->account_no =  $request['account_no'];
            $Sellers->ifsc = $request['ifsc_code'];
            $Sellers->holder_name =  $request['beneficiary_name'];
            $Sellers->relation_id = $insert->id;
            $Sellers->type = 'trust';
            $Sellers->save();

            $this->translationRepo->add(request: $request, model: 'App\Models\DonateTrust', id: $insert->id);
            Toastr::success(translate('Trust_added_successfully'));
            Helpers::editDeleteLogs('Donate', 'Trust', 'Insert');
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Toastr::error(translate('Something_went_wrong._Please_try_again.'));
        }
        return redirect()->route(DonateTrustPath::ADDTRUSTLIST[REDIRECT]);
    }

    public function TrustList(Request $request)
    {
        $all_trust = $this->donateTrust->getListWhere(relations: ['category'], orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), filters: ['is_approve' => $request->get('is_approve')], dataLimit: getWebConfig(name: 'pagination_limit'));
        $types = 1;
        return view(DonateTrustPath::ADDTRUSTLIST[VIEW], compact('all_trust', 'types'));
    }
    public function TrustApproved(Request $request)
    {
        $all_trust = $this->donateTrust->getListWhere(relations: ['category'], orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), filters: ['is_approve' => 1], dataLimit: getWebConfig(name: 'pagination_limit'));
        $types = 0;
        return view(DonateTrustPath::ADDTRUSTLIST[VIEW], compact('all_trust', 'types'));
    }
    public function TrustPending(Request $request)
    {
        $all_trust = $this->donateTrust->getListWhere(relations: ['category'], orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), filters: ['is_approve' => 0], dataLimit: getWebConfig(name: 'pagination_limit'));
        $types = 0;
        return view(DonateTrustPath::ADDTRUSTLIST[VIEW], compact('all_trust', 'types'));
    }
    public function TrustCanceled(Request $request)
    {
        $all_trust = $this->donateTrust->getListWhere(relations: ['category'], orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), filters: ['is_approve' => 2], dataLimit: getWebConfig(name: 'pagination_limit'));
        $types = 0;
        return view(DonateTrustPath::ADDTRUSTLIST[VIEW], compact('all_trust', 'types'));
    }
    public function TrustStatus(Request $request)
    {
        $data['status'] = $request->get('status', 0);
        $this->donateTrust->update(id: $request['id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function TrustDelete(Request $request, DonateTrustService $service)
    {
        $old_data = $this->donateTrust->getFirstWhere(params: ['id' => $request->get('id')]);
        if (!empty($old_data)) {
            $service->deleteTrustImage($old_data);
        }
        $this->donateTrust->delete(params: ['id' => $request->get('id')]);
        $this->translationRepo->delete(model: 'App\Models\DonateTrust', id: $request->get('id'));
        Toastr::success(translate('Trust_Deleted_successfully'));
        Helpers::editDeleteLogs('Donate', 'Trust', 'Delete');
        return redirect()->route(DonateTrustPath::ADDTRUSTLIST[REDIRECT]);
    }

    public function TrustUpdate(Request $request, $id)
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $all_categorys = $this->donatecategory->getListWhere(filters: ['status' => 1]);
        $old_data = $this->donateTrust->getFirstWhere(params: ['id' => $id], relations: ['translations']);
        $bankList = Bank::where('status', 1)->get();
        return view(DonateTrustPath::ADDTRUSTUPDATE[VIEW], compact('bankList', 'old_data', 'all_categorys', 'defaultLanguage', 'languages'));
    }

    public function TrustUpdateSave(DonateTrustRequest $request, DonateTrustService $service)
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $old_data = $this->donateTrust->getFirstWhere(params: ['id' => $request->get('id')]);
            $dataArray = $service->getUpdateData($request, $old_data);

            $Sellers = \App\Models\Seller::where('relation_id', $request->get('id'))->where('type', 'trust')->first();
            if ($Sellers) {
                $Sellers->f_name = explode(' ', $dataArray['name'])[0] ?? '';
                $nameParts = explode(' ', $dataArray['name'] ?? '');
                $Sellers->l_name = end($nameParts) ?: '';
                $Sellers->phone = $request['member_phone_no'][0] ?? '';
                $Sellers->email = $request['trust_email'];
                if ($request->hasfile('theme_image')) {
                    $Sellers->image = $dataArray['theme_image'];
                }
                $Sellers->pan_number = $dataArray['pan_card'];
                if ($request->hasfile('pan_card_image')) {
                    $Sellers->pancard_image = $dataArray['pan_card_image'];
                }
                $Sellers->bank_name = $request['bank_name'];
                $Sellers->branch = $request['ifsc_code'];
                $Sellers->account_no =  $request['account_no'];
                $Sellers->ifsc = $request['ifsc_code'];
                $Sellers->holder_name =  $request['beneficiary_name'];
                $Sellers->save();
            }
            $this->donateTrust->update(id: $request->get('id'), data: $dataArray);
            $this->translationRepo->update(request: $request, model: 'App\Models\DonateTrust', id: $request->get('id'));
            Toastr::success(translate('Trust_updated_successfully'));
            Helpers::editDeleteLogs('Donate', 'Trust', 'Update');
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Toastr::error(translate('Something_went_wrong._Please_try_again.'));
        }
        return redirect()->route(DonateTrustPath::ADDTRUSTLIST[REDIRECT]);
    }

    public function GalleryImageDelete(Request $request, DonateTrustService $service)
    {
        $old_data = $this->donateTrust->getFirstWhere(params: ['id' => $request->get('id')]);
        if ($old_data) {
            $dataArray = $service->getRemoveImage($request, $old_data);
            $insert = $this->donateTrust->update(id: $request->get('id'), data: $dataArray);
        }
        Toastr::success(translate('Trust_image_Remove_successfully'));
        Helpers::editDeleteLogs('Donate', 'Trust', 'Image Delete');
        return back();
    }

    public function TrustDetails(Request $request, $id)
    {
        $type = $request->get('type');
        if ($type == 'donate_ad') {
            $donate_adstrust_transaction =  $this->donateTrans->getListWhere(filters: ['type' => 'donate_ads', 'trust_id' => $id, 'amount_status' => 1], searchValue: $request->get('searchValue'), relations: ['users']);
        } else {
            $donate_adstrust_transaction =  $this->donateTrans->getListWhere(filters: ['type' => 'donate_ads', 'trust_id' => $id, 'amount_status' => 1], relations: ['users']);
        }
        if ($type == 'donate_trust') {
            $donate_trust_transaction =  $this->donateTrans->getListWhere(filters: ['type' => 'donate_trust', 'trust_id' => $id, 'amount_status' => 1], searchValue: $request->get('searchValue'), relations: ['users']);
        } else {
            $donate_trust_transaction =  $this->donateTrans->getListWhere(filters: ['type' => 'donate_trust', 'trust_id' => $id, 'amount_status' => 1], relations: ['users']);
        }
        if ($type == 'donate_tran') {
            $ads_transaction = $this->donateTrans->getListWhere(filters: ['typeIn' => ['donate_trust', 'donate_ads'], 'trust_id' => $id, 'amount_status' => 1], searchValue: $request->get('searchValue'), relations: ['users']);
        } else {
            $ads_transaction = $this->donateTrans->getListWhere(filters: ['typeIn' => ['donate_trust', 'donate_ads'], 'trust_id' => $id, 'amount_status' => 1], relations: ['users']);
        }
        if ($type == 'trust_tran') {
            $amount_transaction = $this->donateTrans->getListWhere(filters: ['typeIn' => ['withdrawal', 'ad_approval'], 'trust_id' => $id], searchValue: $request->get('searchValue'));
        } else {
            $amount_transaction = $this->donateTrans->getListWhere(filters: ['typeIn' => ['withdrawal', 'ad_approval'], 'trust_id' => $id]);
        }
        if ($type == 'adlist') {
            $ads_list = $this->donateads->getListWhere(filters: ['trust_id' => $id], searchValue: $request->get('searchValue'), relations: ['category', 'Trusts', 'Purpose']);
        } else {
            $ads_list = $this->donateads->getListWhere(filters: ['trust_id' => $id], relations: ['category', 'Trusts', 'Purpose']);
        }
        $trust_data = $this->donateTrust->getFirstWhere(params: ['id' => $id], relations: ['category']);
        return view(DonateTrustPath::TRUSTDETAIL[VIEW], compact('id', 'trust_data', 'type', 'ads_list', 'donate_adstrust_transaction', 'donate_trust_transaction', 'amount_transaction', 'ads_transaction'));
    }

    public function TrustAdminCommission(Request $request, $id)
    {
        $data = ['donate_commission' => $request->get('donate_commission'), 'ad_commission' => $request->get('ad_commission')];
        $this->donateTrust->update(id: $id, data: $data);
        Toastr::success(translate('Trust_Setting_Updated_successfully'));
        return back();
    }

    public function TrustVerifyDocUpload(Request $request, DonateTrustService $service, $id, $status)
    {
        if ($request->isMethod('post')) {
            $dataArray = $service->UploadVerifyDoc($request);
            $this->donateTrust->update(id: $id, data: $dataArray);
            Toastr::success(translate('Trust_Document_Uploaded_successfully'));
            return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
        } elseif ($request->isMethod('get')) {
            $data = ['is_approve' => $status];
            $this->donateTrust->update(id: $id, data: $data);
            Toastr::success(translate('Trust_Status_changed_successfully'));
            return back();
        }
    }
    public function TrustReqApproval(Request $request, $id, $status)
    {
        $trustData = $this->donateTrust->getFirstWhere(params: ['id' => $id]);
        $trustTrans = $this->donateTrans->getFirstWhere(params: ['trust_id' => $id, 'amount_status' => 0, 'type' => 'withdrawal']);
        if (!empty($trustData) && !empty($trustTrans) && ($trustData['trust_req_withdrawal_amount'] == $trustTrans['amount']) && (($trustData['trust_total_amount'] ?? 0) >= $trustTrans['amount'])) {
            if ($status == 1) {
                $UpdateData = [
                    'trust_total_amount' => ($trustData['trust_total_amount'] - $trustTrans['amount']),
                    'trust_total_withdrawal' => ($trustData['trust_total_withdrawal'] + $trustTrans['amount']),
                    'trust_req_withdrawal_amount' => 0,
                ];
                $trustData = $this->donateTrust->update(id: $id, data: $UpdateData);
                $this->donateTrans->update(id: $trustTrans['id'], data: ['amount_status' => 1, 'transaction_id' => 'menual_send']);
                Toastr::success(translate('Request_Approved_successfully'));
            } else {
                $trustData = $this->donateTrust->update(id: $id, data: ['trust_req_withdrawal_amount' => 0]);
                $this->donateTrans->update(id: $trustTrans['id'], data: ['amount_status' => 2, 'transaction_id' => '']);
                Toastr::success(translate('Request_Canceled_successfully'));
            }
        } else {
            $trustData = $this->donateTrust->update(id: $id, data: ['trust_req_withdrawal_amount' => 0]);
            if (isset($trustTrans['id'])) {
                $this->donateTrans->update(id: $trustTrans['id'], data: ['amount_status' => 2, 'transaction_id' => '']);
            }
            Toastr::success(translate('Request_amount_Canceled_successfully'));
        }
        return back();
    }

    public function DonateLeads(Request $request)
    {
        $DonateLeads = $this->donateleads->getListWhere(filters: ['status' => [0, 2]], searchValue: $request->get('searchValue'), relations: ['AdsDonate', 'Trusts', 'users', 'followby'], dataLimit: getWebConfig(name: 'pagination_limit'), orderBy: ['id' => 'desc']);
        return view(DonateTrustPath::LEADS[VIEW], compact('DonateLeads'));
    }

    public function DonateLeadFollowUp(Request $request)
    {
        $follows = [
            'lead_id' => $request->input('lead_id'),
            'message' => $request->input('message'),
            'last_date' => $request->input('last_date'),
            'next_date' => $request->input('next_date'),
            'follow_by' => $request->input('follow_by'),
            'follow_by_id' => $request->input('follow_by_id'),
            'type' => $request->input('type'),

            'customer_id' => $request->input('customer_id'),
            'pooja_id' => $request->input('pooja_id'),

        ];
        DonateLeadFollowup::create($follows);
        //  dd($followStore);
        Toastr::success(translate('lead_follow_up_successfully'));
        return back();
    }

    public function DonateLeadFollowList($id)
    {
        $followlist = DonateLeadFollowup::where('lead_id', $id)->get();
        if ($followlist) {
            return response()->json($followlist);
        } else {
            return response()->json([], 200);
        }
    }
    public function DonateLeadDelete(Request $request, $id)
    {
        $lead = $this->donateleads->getFirstWhere(params: ['id' => $id]);
        if ($lead) {
            $lead->delete();
            Toastr::success(translate('lead_Delete_successfully'));
        } else {
            Toastr::error(translate('lead_Not_found'));
        }
        return back();
    }
    public function DonatedList(Request $request)
    {
        $getDonated  =  $this->donateTrans->getListWhere(filters: ['typeIn' => ['donate_trust', 'donate_ads'], 'amount_status' => 1, 'start_to_end_date' => (($request->get('show') == 'all') ? $request->get('start_to_end_date') : '')], searchValue: (($request->get('show') == 'all') ? $request->get('searchValue') : ''), relations: ['users', 'getTrust', 'adsTrust'], dataLimit: getWebConfig(name: 'pagination_limit'), orderBy: ['id' => 'desc']);
        $gettrust  =  $this->donateTrans->getListWhere(filters: ['groupby_trust' => 1, 'type' => 'donate_trust', 'amount_status' => 1, 'start_to_end_date' => (($request->get('show') == 'trust') ? $request->get('start_to_end_date') : '')], searchValue: (($request->get('show') == "trust") ? $request->get('searchValue') : ''), relations: ['users', 'getTrust', 'adsTrust'], dataLimit: getWebConfig(name: 'pagination_limit'), orderBy: ['id' => 'desc']);
        $getads  =  $this->donateTrans->getListWhere(filters: ['groupby_ads' => 1, 'type' => 'donate_ads', 'amount_status' => 1, 'start_to_end_date' => (($request->get('show') == 'ads') ? $request->get('start_to_end_date') : '')], searchValue: (($request->get('show') == 'ads') ? $request->get('searchValue') : ''), relations: ['users', 'getTrust', 'adsTrust'], dataLimit: getWebConfig(name: 'pagination_limit'), orderBy: ['id' => 'desc']);
        return view(DonateTrustPath::DONATED[VIEW], compact('getDonated', 'gettrust', 'getads'));
    }

    public function DocVerifiedResend(Request $request)
    {
        $id = $request->vendor_id;
        $getvendor = \App\Models\Seller::where("relation_id", $id)->where('type', 'trust')->first();
        $update_seller_status = 0;
        if ($getvendor && json_decode($getvendor['all_doc_info'], true) && $request['arrays']) {
            $doc_encode = json_decode($getvendor['all_doc_info'], true);
            foreach (json_decode($getvendor['all_doc_info'], true) as $key => $value) {
                foreach ($request['arrays'] as $key12 => $value12) {
                    if ($value12['name'] == $key) {
                        $doc_encode[$key] = $value12['value'];
                        if ($update_seller_status == 0 && $value12['value'] == 2) {
                            $update_seller_status = 2;
                        }
                    }
                }
            }

            if ($update_seller_status == 2) {
                $notis = new \App\Models\Notification();
                $notis->sent_by = $id;
                $notis->sent_to = "trust";
                $notis->title = "Profile Review";
                $notis->description = $request['reason'];
                $notis->notification_count = 1;
                $notis->image = '';
                $notis->status = 1;
                $notis->created_at = date('Y-m-d H:i:s');
                $notis->save();
            }
            \App\Models\Seller::where('relation_id', $id)->where('type', 'trust')->update(['all_doc_info' => json_encode($doc_encode), "reupload_doc_status" => (($update_seller_status == 2) ? 2 : 1), 'update_seller_status' => $update_seller_status]);
        } else {
            return response()->json(['status' => 0, 'message' => translate('update_failed')]);
        }
        return response()->json(['message' => translate('vendor_successfully')]);
    }

    public function WithdrawalList()
    {
        $withdrawRequests = \App\Models\WithdrawalAmountHistory::where(['type' => "trust"])->with(['Trust'])->orderBy('id', 'desc')->paginate(10, ['*'], 'page');
        return view("admin-views.donate_management.withdrawal.index", compact('withdrawRequests'));
    }

    public function WithdrawalReqView(Request $request)
    {
        $withdrawRequests = \App\Models\WithdrawalAmountHistory::where(['type' => "trust"])->with(['Trust'])->where('id', $request['id'])->first();
        return view('admin-views.donate_management.withdrawal.view', compact('withdrawRequests'));
    }

    public function WithdrawalReqReject(Request $request)
    {
        $withdrawRequests = \App\Models\WithdrawalAmountHistory::where(['type' => "trust"])->with(['Trust', 'TrustAds'])->where('id', $request['id'])->first();
        if ($withdrawRequests) {
            if ($withdrawRequests['ex_id'] == 0) {
                \App\Models\DonateTrust::where('id', $withdrawRequests['vendor_id'])->update(['trust_req_withdrawal_amount' => 0]);
            } else {
                // \App\Models\DonateAds::where('id', $withdrawRequests['ex_id'])->update(['advance_withdrawal_amount' => 0]);
            }
            \App\Models\WithdrawalAmountHistory::where('id', $request['id'])->update(['status' => 2]);
            Toastr::success('Paymant Request Reject Successfully');
            return back();
        }
        Toastr::success('Payment Request Reject Failed');
        return back();
    }

    public function RazorpaycreateContact(Request $request, $id, $type)
    {
        try {
            $get_Razorpay = \App\Models\Setting::where('key_name', 'razor_pay')->first();

            $RAZORPAY_KEY_ID = '';
            $RAZORPAY_KEY_SECRET = '';
            $RAZORPAY_ACCOUNT_NO = '';
            if ($get_Razorpay['mode'] == 'live') {
                $RAZORPAY_KEY_ID = $get_Razorpay['live_values']['api_key'];
                $RAZORPAY_KEY_SECRET = $get_Razorpay['live_values']['api_secret'];
                $RAZORPAY_ACCOUNT_NO = $get_Razorpay['live_values']['account_number'] ?? '';
            } else {
                $RAZORPAY_KEY_ID = $get_Razorpay['live_values']['api_key'];
                $RAZORPAY_KEY_SECRET = $get_Razorpay['live_values']['api_secret'];
                $RAZORPAY_ACCOUNT_NO = $get_Razorpay['live_values']['account_number'] ?? '';
            }
            $api = new \Razorpay\Api\Api($RAZORPAY_KEY_ID, $RAZORPAY_KEY_SECRET);
            $getWithdrawal_recode = \App\Models\WithdrawalAmountHistory::where(['type' => "trust"])
                ->with(['Trust'])
                ->where('id', $id)
                ->first();
            $email = $getWithdrawal_recode['Trust']['trust_email'] ?? '';
            $contact = json_decode($getWithdrawal_recode['Trust']['memberlist'] ?? '[]', true)[0]['member_phone_no'] ?? '';

            $data = [
                "name" => $getWithdrawal_recode['Trust']['beneficiary_name'] ?? '',
                "email" => $email,
                "contact" => $contact,
                "type" => "vendor"
            ];

            $headers = [
                "Content-Type: application/json",
                "Authorization: Basic " . base64_encode("$RAZORPAY_KEY_ID:$RAZORPAY_KEY_SECRET")
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/contacts");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200 || $httpCode == 201) {
                $contact_data = json_decode($response, true);
            } else {
                // return ["error" => "Failed to create contact", "response" => json_decode($response, true)];
                Toastr::error('Failed to create contact');
                return back();
            }

            // Create fund account
            if ($type == 'bank') {
                $fundAccount = $api->fundAccount->create([
                    "account_type" => "bank_account",
                    "contact_id" => $contact_data['id'],
                    "bank_account" => [
                        "name" => $getWithdrawal_recode['holder_name'],
                        "ifsc" => $getWithdrawal_recode['ifsc_code'],
                        "account_number" => $getWithdrawal_recode['account_number']
                    ]
                ]);
            } elseif ($type == 'manual') {
                if ($getWithdrawal_recode['ex_id'] == 0) {
                    \App\Models\DonateTrust::where('id', $getWithdrawal_recode['vendor_id'])->update([
                        'trust_req_withdrawal_amount' => 0,
                        'trust_total_withdrawal' => \Illuminate\Support\Facades\DB::raw('trust_total_withdrawal + ' . $getWithdrawal_recode['req_amount']),
                        'trust_total_amount' => \Illuminate\Support\Facades\DB::raw('trust_total_amount - ' . $getWithdrawal_recode['req_amount']),
                    ]);
                }
                \App\Models\WithdrawalAmountHistory::where('id', $id)->update([
                    'status' => 1,
                    'transcation_id' => $request['transcation_id'] ?? '',
                    'approval_amount' => $getWithdrawal_recode['req_amount'],
                    'payment_method' => 'manual'
                ]);
                Toastr::success('Payment transferred successfully');
                return back();
            } else {
                $fundAccount = $api->fundAccount->create([
                    "account_type" => "vpa",
                    "contact_id" => $contact_data['id'],
                    "vpa" => [
                        "address" => $getWithdrawal_recode['upi_code']
                    ]
                ]);
            }

            $fund_account_id = $fundAccount['id'];

            $data_fund_tans = [
                'account_number' => $RAZORPAY_ACCOUNT_NO,
                'fund_account_id' => $fund_account_id,
                'amount' => $getWithdrawal_recode['req_amount'],
                'currency' => 'INR',
                'mode' => (($type == 'upi') ? 'UPI' : 'IMPS'),
                'purpose' => 'payout',
                'queue_if_low_balance' => true,
                'reference_id' => 'Payout123',
                'narration' => 'Payment for service',
                "notes" => [
                    "notes_key_1" => "Tea, Earl Grey, Hot",
                    "notes_key_2" => "Tea, Earl Grey… decaf."
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/payouts");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_fund_tans));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200 || $httpCode == 201) {
                if ($getWithdrawal_recode['ex_id'] == 0) {
                    \App\Models\DonateTrust::where('id', $getWithdrawal_recode['vendor_id'])->update([
                        'trust_req_withdrawal_amount' => 0,
                        'trust_total_withdrawal' => \Illuminate\Support\Facades\DB::raw('trust_total_withdrawal + ' . $getWithdrawal_recode['req_amount']),
                        'trust_total_amount' => \Illuminate\Support\Facades\DB::raw('trust_total_amount - ' . $getWithdrawal_recode['req_amount']),
                    ]);
                }
                \App\Models\WithdrawalAmountHistory::where('id', $id)->update([
                    'status' => 1,
                    'approval_amount' => $getWithdrawal_recode['req_amount']
                ]);
                Toastr::success('Payment transferred successfully');
                return back();
            } else {
                if ($getWithdrawal_recode['ex_id'] == 0) {
                    \App\Models\DonateTrust::where('id', $getWithdrawal_recode['vendor_id'])->update(['trust_req_withdrawal_amount' => 0]);
                }
                \App\Models\WithdrawalAmountHistory::where('id', $id)->update(['status' => 2]);
                return ["error" => "Failed to payouts", "response" => json_decode($response, true)];
            }
        } catch (\Exception $e) {
            Toastr::success('Payment transferred failed');
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function ApproveProfileHold(Request $request)
    {
        $getvendor = \App\Models\Seller::where("relation_id", $request->id)->where('type', 'trust')->first();
        if ($request['type'] == 'approved') {
            $getvendor->status = "approved";
            $getvendor->save();
            \App\Models\DonateTrust::where('id', $request->id)->update(['status' => 1]);
            Toastr::success('Profile Approved Success');
        } else {
            $getvendor->status = "hold";
            $getvendor->save();
            \App\Models\DonateTrust::where('id', $request->id)->update(['status' => 0]);
            \App\Models\DonateAds::where('trust_id', $request->id)->update(['status' => 0]);
            Toastr::success('Profile Hold Success');
        }
        return back();
    }

    public function DonatedViewInfo(Request $request)
    {
        $getDonated  =  $this->donateTrans->getFirstWhere(params: ['id' => $request['id']], relations: ['users', 'getTrust', 'adsTrust']);
        return view(DonateTrustPath::DONATEDVIEW[VIEW], compact('getDonated'));
    }
}
