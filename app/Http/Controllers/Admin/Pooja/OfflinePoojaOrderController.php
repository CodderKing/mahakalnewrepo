<?php

namespace App\Http\Controllers\Admin\Pooja;

use App\Events\OrderStatusEvent;
use App\Http\Controllers\Controller;
use App\Models\Astrologer\Astrologer;
use App\Models\OfflineLead;
use App\Models\OfflinepoojaFollowup;
use App\Models\OfflinePoojaOrder;
use App\Traits\PdfGenerator;
use App\Models\ServiceTax;
use App\Models\ServiceTransaction;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\View as PdfView;
use Intervention\Image\Facades\Image;
use App\Models\User;
use Illuminate\Http\Request;

use App\Utils\Helpers;
use App\Jobs\SendWhatsappMessage;
use App\Models\PoojaOffline;
use App\Models\WalletTransaction;

class OfflinePoojaOrderController extends Controller
{
    use PdfGenerator;

    public function orders_list($status, Request $request)
    {
        // dd($request->all());
        if ($status == 'all') {
            // if(empty($request->all()) || $request->pooja_type == 'all'){
            $orders = OfflinePoojaOrder::with('leads')->with('package')->with('offlinePooja')->with('customers')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // } else {
            // $orders = OfflinePoojaOrder::where('type',$request->pooja_type)->with('leads')->with('package')->with('offlinePooja')->with('customers')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // }
        } elseif ($status == 'pending') {
            // if(empty($request->all()) || $request->pooja_type == 'all'){
            $orders = OfflinePoojaOrder::where('status', 0)->whereNull('pandit_assign')->with('leads')->with('package')->with('customers')->with('offlinePooja')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // } else {
            // $orders = OfflinePoojaOrder::where('type',$request->pooja_type)->where('status', 0)->whereNull('pandit_assign')->with('leads')->with('package')->with('customers')->with('offlinePooja')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // }
            // dd($orders);
        } elseif ($status == 'pandit-assigned') {
            // if(empty($request->all()) || $request->pooja_type == 'all'){
            $orders = OfflinePoojaOrder::where('status', 0)->whereNotNull('pandit_assign')->with('leads')->with('package')->with('customers')->with('offlinePooja')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // } else {
            // $orders = OfflinePoojaOrder::where('type',$request->pooja_type)->where('status', 0)->whereNotNull('pandit_assign')->with('leads')->with('package')->with('customers')->with('offlinePooja')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // }
            // dd($orders);
        } elseif ($status == 'completed') {
            // if(empty($request->all()) || $request->pooja_type == 'all'){
            $orders = OfflinePoojaOrder::where('status', 1)->with('leads')->with('package')->with('customers')->with('offlinePooja')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // } else {
            // $orders = OfflinePoojaOrder::where('type',$request->pooja_type)->where('status', 1)->with('leads')->with('package')->with('customers')->with('offlinePooja')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // }
        } elseif ($status == 'canceled') {
            // if(empty($request->all()) || $request->pooja_type == 'all'){
            $orders = OfflinePoojaOrder::where('status', 2)->with('leads')->with('package')->with('customers')->with('offlinePooja')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // } else {
            // $orders = OfflinePoojaOrder::where('type',$request->pooja_type)->where('status', 2)->with('leads')->with('package')->with('customers')->with('offlinePooja')->with('pandit')->orderBy('created_at', 'DESC')->paginate(10);
            // }
        }
        $users = User::all();
        return view('admin-views.pooja.offlinepoojaorder.list', compact('orders', 'users'));
    }

    public function orders_details($orderId)
    {
        $serviceId = OfflinePoojaOrder::select('service_id')->where('order_id', $orderId)->first()->service_id;
        $inHouseAstrologers = Astrologer::select('id', 'name', 'is_pandit_pooja_per_day')
            ->where('primary_skills', 3)
            ->where('type', 'in house')
            ->where('status', 1)
            ->whereRaw("JSON_CONTAINS_PATH(is_pandit_offlinepooja, 'one', '$.\"$serviceId\"')")
            ->get();
        $freelancerAstrologers = Astrologer::select('id', 'name', 'is_pandit_pooja_per_day', 'is_pandit_offlinepooja', 'latitude', 'longitude')
            ->where('primary_skills', 3)
            ->where('type', 'freelancer')
            ->where('status', 1)
            ->whereRaw("JSON_CONTAINS_PATH(is_pandit_offlinepooja, 'one', '$.\"$serviceId\"')")
            ->get();

        $details = OfflinePoojaOrder::where('order_id', $orderId)->with('customers')->with('offlinePooja')->with('leads')->with('package')->with('payments')->with('pandit')->first();

        return view('admin-views.pooja.offlinepoojaorder.details', compact('details', 'inHouseAstrologers', 'freelancerAstrologers'));
    }

    public function orders_assign_pandit($orderId, Request $request)
    {
        $pandit = OfflinePoojaOrder::where('order_id', $orderId)->where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->update(['pandit_assign' => $request->pandit_id]);
        if ($pandit) {

            //Whatsapp
            $userInfo = \App\Models\User::where('id', ($pandit->customer_id ?? ""))->first();
            $service_name = \App\Models\PoojaOffline::where('id', $request->service_id)->first();
            $panditInfo = Astrologer::find($request->pandit_id);
            $poojaOrder = OfflinePoojaOrder::where('order_id', $orderId)
                ->where('service_id', $request->service_id)
                ->where('booking_date', $request->booking_date)
                ->first();
            $bookingDetails = $poojaOrder;
            $message_data = [
                'service_name' => $service_name['name'],
                'orderId' => $orderId,
                'customer_id' => $poojaOrder->customer_id,
                'pandit_name' => $panditInfo->name ?? '',
            ];
            $messages =  Helpers::whatsappMessage('offlinepooja', 'Pandit Assign', $message_data);

            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $data['type'] = 'pooja';
                $data['email'] = $userInfo['email'];
                $data['subject'] = 'Confirmation of pay remain amount';
                $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offlinepooja-pandit', compact('userInfo', 'service_name', 'bookingDetails', 'panditInfo'))->render();

                Helpers::emailSendMessage($data);
            }
            Toastr::success(translate('pandit_assigned'));
            return back();
        }
        Toastr::error(translate('an_error_occured'));
        return back();
    }

    // Single certificated
    public function orders_status($orderId, Request $request)
    {
        $pooja = OfflinePoojaOrder::where('order_id', $orderId)->where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->where('package_id', $request->package_id)->with(['offlinePooja', 'customers', 'payments'])->first();
        if (!$pooja) {
            Toastr::error(translate('order_not_found'));
            return back();
        }
        $commission = 0;
        $tax = ServiceTax::first();
        if ($request->order_status == 1) {
            $certificate = Image::make(public_path('assets/back-end/img/certificate/format/certificate-format.png'));
            $certificate->text(@ucwords($pooja['customers']['f_name']) . ' ' . @ucwords($pooja['customers']['l_name']), 950, 630, function ($font) {
                $font->file(public_path('fonts/Roboto-Bold.ttf'));
                $font->size(100);
                $font->color('#ffffff');
                $font->align('center');
                $font->valign('top');
            });
            $serviceName = wordwrap($pooja['offlinePooja']['name'], 65, "\n", false);
            $certificate->text($serviceName, 500, 815, function ($font) {
                $font->file(public_path('fonts/Roboto-Black.ttf'));
                $font->size(40);
                $font->color('#ffffff');
                $font->align('left');
                $font->valign('top');
            });
            $certificate->text(date('d/m/Y', strtotime($pooja['created_at'])), 830, 994, function ($font) {
                $font->file(public_path('fonts/Roboto-Black.ttf'));
                $font->size(40);
                $font->color('#ffffff');
                $font->align('center');
                $font->valign('top');
            });
            $certificatePath = 'assets/back-end/img/certificate/offlinepooja/' . $pooja['order_id'] . '.jpg';
            $certificate->save(public_path($certificatePath));
            OfflinePoojaOrder::where('order_id', $orderId)->update(['pooja_certificate' => $pooja['order_id'] . '.jpg']);
            $astrologer = Astrologer::where('id', $pooja['pandit_assign'])->first();
            if ($astrologer) {
                foreach (json_decode($astrologer['is_pandit_offlinepooja_commission']) as $key => $value) {
                    if ($key == $pooja['offlinePooja']['id']) {
                        $commission = $value;
                    }
                }
            }
            $transaction = new ServiceTransaction();
            $transaction->astro_id = $pooja['pandit_assign'];
            $transaction->type = 'offlinepooja';
            $transaction->order_id = $pooja['order_id'];
            $transaction->txn_id = !empty($pooja['wallet_translation_id']) ? $pooja['wallet_translation_id'] : $pooja['payment_id'];

            $transaction->amount = $pooja['pay_amount'];
            $transaction->commission = $commission;
            $transaction->tax = $tax['offline_pooja'] ?? 0;
            $transaction->save();
            OfflinePoojaOrder::where('order_id', $orderId)->update([
                'status' => $request->order_status,
                'is_edited' => $request->order_status,
                'pooja_certificate' => $certificatePath,
                'order_completed' => now(),
            ]);
            $userInfo = \App\Models\User::where('id', $pooja->customer_id)->first();
            $service_name = \App\Models\PoojaOffline::where('id', ($pooja['service_id'] ?? ""))->first();
            $bookingDetails = \App\Models\OfflinePoojaOrder::where('service_id', ($pooja['service_id'] ?? ""))
                ->where('customer_id', ($pooja['customer_id'] ?? ""))
                ->where('order_id', ($orderId ?? ""))
                ->first();

            $messageData = [
                'service_name' => $service_name->name,
                'customer_id' => $pooja->customer_id,
                'venue_address' => $pooja->venue_address,
                'attachment' => asset('public/' . $certificatePath),
                'type' => 'text-with-media',
                'orderId' => $pooja->order_id,
                'amount' => webCurrencyConverter((float) ($pooja->pay_amount ?? 0)),
                'booking_date' => date('d-m-Y', strtotime($pooja->booking_date)),
            ];
            SendWhatsappMessage::dispatch('offlinepooja', 'Completed', $messageData);

            // send email

            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {

                $data = [
                    'type' => 'pooja',
                    'email' => $userInfo->email,
                    'subject' => 'Puja Completed',
                    'htmlContent' =>
                    \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offline-pooja-complete', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                ];

                Helpers::emailSendMessage($data);
            }
            // dd($transaction);
            $order = OfflinePoojaOrder::where('order_id', $pooja->order_id)->where('status', '1')->with(['customer'])->first();
            event(new OrderStatusEvent(key: 'offlinepuja_1', type: 'offlinepuja', order: $order));
            Toastr::success(translate('status_changed_successfully'));
            return redirect()->route('admin.offlinepooja.order.list', ['status' => 'completed']);
        }
        Toastr::success(translate('an_error_occurred'));
        return redirect()->back();
    }

    public function cancel_poojas($orderId, Request $request)
    {
        $cancelOrder = OfflinePoojaOrder::where('order_id', $orderId)->update(['order_canceled_reason' => $request->cancel_reason, 'order_canceled' => now(), 'status' => 2, 'is_edited' => 1, 'refund_status' => 1, 'canceled_by' => 'admin', 'refund_amount' => $request->refund_amount]);
        if ($cancelOrder) {
            $walletBal = User::where('id', $request->customer_id)->get()->value('wallet_balance');
            $currentBal = $walletBal + $request->refund_amount;
            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $request->customer_id;
            $wallet_transaction->transaction_id = \Str::uuid();
            $wallet_transaction->reference = 'offline pooja order payment';
            $wallet_transaction->transaction_type = 'offline_pooja_order_place';
            $wallet_transaction->credit = $request->refund_amount;
            $wallet_transaction->balance = $currentBal;
            $wallet_transaction->save();
            User::where('id', $request->customer_id)->update(['wallet_balance' => $currentBal]);
        }
        Toastr::success(translate('offline_pooja_cancel_successfully'));
        return redirect()->route('admin.offlinepooja.order.list', ['status' => 'canceled']);
    }

    public function refund_amount($orderId)
    {
        $orderData = OfflinePoojaOrder::where('order_id', $orderId)->first();
        $refund = OfflinePoojaOrder::where('order_id', $orderId)->update(['refund_status' => 1]);
        if ($refund) {
            $prevWalletAmt = User::where('id', $orderData['customer_id'])->value('wallet_balance');
            $newWalletAmt = $prevWalletAmt + $orderData['refund_amount'];
            User::where('id', $orderData['customer_id'])->update(['wallet_balance' => $newWalletAmt]);
            Toastr::success(translate('amount_refunded_to_customer_wallet_successfully'));
            return redirect()->back();
        }
        Toastr::success(translate('an_error_occurred'));
        return redirect()->back();
    }

    public function orders_generate_invoice($orderId)
    {
        $companyPhone = getWebConfig(name: 'company_phone');
        $companyEmail = getWebConfig(name: 'company_email');
        $companyName = getWebConfig(name: 'company_name');
        $companyWebLogo = getWebConfig(name: 'company_web_logo');
        $details = OfflinePoojaOrder::where('order_id', $orderId)->with('customers')->with('offlinePooja')->with('leads')->with('package')->with('payments')->first();
        $mpdf_view = PdfView::make('admin-views.pooja.offlinepoojaorder.invoice', compact('details', 'companyPhone', 'companyEmail', 'companyName', 'companyWebLogo'));
        $this->generatePdf($mpdf_view, 'order_invoice_', $details['order_id']);
    }

    public function lead_list(Request $request)
    {
        if ($request->has('searchValue')) {
            $leads = OfflineLead::where('person_name', 'like', '%' . $request->searchValue . '%')->where('status', 1)->with('offlinePooja', 'followBy')->orderBy('created_at', 'DESC')->paginate(10);
        } else {
            $leads = OfflineLead::where('status', 1)->with('offlinePooja', 'followBy')->orderBy('created_at', 'DESC')->paginate(10);
        }
        // dd($leads);
        return view('admin-views.pooja.offlinepoojalead.list', compact('leads'));
    }

    public function lead_delete($id, Request $request)
    {
        $lead = OfflineLead::where('id', $id)->first();
        if ($lead) {
            $lead->delete();
            Toastr::success(translate('lead_Delete_successfully'));
        } else {
            Toastr::error(translate('lead_Not_found'));
        }
        return back();
    }

    public function followup_store(Request $request)
    {
        $follows = [
            'customer_name' => $request->input('customer_id'),
            'pooja_id' => $request->input('pooja_id'),
            'lead_id' => $request->input('lead_id'),
            'follow_by' => $request->input('follow_by'),
            'follow_by_id' => $request->input('follow_by_id'),
            'last_date' => $request->input('last_date'),
            'message' => $request->input('message'),
            'next_date' => $request->input('next_date'),
        ];
        OfflinepoojaFollowup::create($follows);
        //  dd($followStore);
        Toastr::success(translate('lead_follow_up_successfully'));
        return back();
    }
    public function getFollowList($id)
    {
        $followlist = OfflinepoojaFollowup::where('lead_id', $id)->get();
        return response()->json($followlist);
    }

    public function checked_order()
    {
        $offlinepooja = OfflinePoojaOrder::where('checked', 0)->update(['checked' => 1]);
        if ($offlinepooja) {
            return response()->json(['status' => 200]);
        }
        return response()->json(['status' => 400]);
    }

    public function send_whatsapp_leads($id)
    {
        $lead = OfflineLead::where('id', $id)->first();
        $poojaName = PoojaOffline::where('status', 1)->where('id', $lead->pooja_id)->first();
        $customer = User::where('is_active', 1)->where('phone', $lead->person_phone)->first();

        if ($lead) {
            $message_data = [
                'service_name' => $poojaName->name,
                'type' => 'text-with-media',
                'attachment' => asset('/storage/app/public/offlinepooja/thumbnail/' . $poojaName->thumbnail),
                'link' => 'mahakal.com/offline/pooja/detail/' . $poojaName->slug,
                'customer_id' => ($customer->id ?? ""),
            ];

            $messages =  Helpers::whatsappMessage('offlinepooja', 'Lead Message', $message_data);
            OfflineLead::where('id', $id)->increment('whatsapp_hit');
            Toastr::success(translate('message_sent_successfully'));
        } else {
            Toastr::error(translate('lead_Not_found'));
        }
        return back();
    }
}