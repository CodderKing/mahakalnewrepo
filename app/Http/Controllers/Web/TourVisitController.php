<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TourCancelResonance;
use App\Models\TourCancelTicket;
use App\Models\TourLeads;
use App\Models\TourOrder;
use App\Models\TourRefundPolicy;
use App\Models\TourReviews;
use App\Models\TourType;
use App\Models\TourVisits;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function App\Utils\payment_gateways;

class TourVisitController extends Controller
{

    public function TourIndex()
    {
        $special_tour = TourVisits::where('tour_type', 'special_tour')->where('status', 1)
            ->where(function ($query) {
                $query->whereIn('use_date', [0, 2, 3, 4])->orWhere(function ($query) {
                    $query->where('use_date', 1)
                        ->whereNotNull('startandend_date')
                        ->whereRaw('? < STR_TO_DATE(SUBSTRING_INDEX(startandend_date, " - ", 1), "%Y-%m-%d")', [date('Y-m-d')]);
                });
            })->groupBy('cities_name')->get();

        $state_name = TourVisits::where('status', 1)->groupBy('state_name')->get();
        $headers = TourType::where('status', 1)->get();
        $getDataAll = TourVisits::where('status', 1)->where(function ($query) {
            $query->whereIn('use_date', [0, 2, 3, 4])->orWhere(function ($query) {
                $query->where('use_date', 1)
                    ->whereNotNull('startandend_date')
                    ->whereRaw('? < STR_TO_DATE(SUBSTRING_INDEX(startandend_date, " - ", 1), "%Y-%m-%d")', [date('Y-m-d')]);
            });
        })->orderBy('id', 'desc')->withCount('TourOrderReview')
            ->withAvg('review', 'star')->withTourCheck()->get();
        return view('web-views.tour.index', compact('getDataAll', 'headers', 'special_tour', 'state_name'));
    }

    public function TourVisit(Request $request, $id = null)
    {
        $customer = User::where('id', auth('customer')->id())->first();
        if (empty($request->leads) || !isset($request->leads)) {
            return redirect()->route('tour.tourvisit', [$id]);
        } elseif (!$customer) {
            Toastr::error('Please Login');
            return redirect()->route('tour.index');
        }
        $getfirst = TourVisits::where('status', 1)->where('slug', $id)->with('TourPlane')->withCount('TourOrderReview')
            ->withAvg('review', 'star')->first();
        $getleads = TourLeads::where('id', $request->leads)->first();
        $getReview = TourReviews::where('status', 1)->where('tour_id', $getfirst['id'] ?? '')->with(['userData']);
        $ratings = [
            'total' => $getReview->avg('star'),
            'list' => $getReview->orderBy('id', 'desc')->limit(10)->get(),
        ];
        $googleMapsApiKey = config('services.google_maps.api_key');
        $payment_gateways_list  = payment_gateways();
        $faqs = \App\Models\FAQ::whereHas('Category', function ($query) {
            $query->where('name', 'tour');
        })->with('Category')->get();
        $id = $getfirst['id'] ?? '';
        return view('web-views.tour.visit', compact('faqs', 'getfirst', 'getleads', 'payment_gateways_list', 'id', 'googleMapsApiKey', 'customer', 'ratings'));
    }

    public function Tourlist($id)
    {
        $getfirst = TourVisits::where('status', 1)->find(base64_decode($id));
        if (empty(base64_decode($id)) || !$getfirst) {
            return back();
        }
        $special_tour = TourVisits::where('tour_type', 'special_tour')->where('status', 1)->where('cities_name', $getfirst['cities_name'])
            ->where(function ($query) {
                $query->whereIn('use_date', [0, 2, 3, 4])->orWhere(function ($query) {
                    $query->where('use_date', 1)
                        ->whereNotNull('startandend_date')
                        ->whereRaw('? < STR_TO_DATE(SUBSTRING_INDEX(startandend_date, " - ", 1), "%Y-%m-%d")', [date('Y-m-d')]);
                });
            })->get();

        $cities_tour = TourVisits::where('tour_type', 'cities_tour')->where('cities_name', $getfirst['cities_name'])->where('status', 1)->get()->groupBy('state_name');
        return view('web-views.tour.cities-tour', compact('cities_tour', 'special_tour'));
    }

    public function TourLeads(Request $request)
    {
        $userfind = User::where('phone', ($request->input('person_phone') ?? ""))->first();
        if ($userfind) {
            \Illuminate\Support\Facades\Auth::guard('customer')->loginUsingId($userfind['id']);
        } else {
            $user = new User();
            $user->phone = $request->input('person_phone');
            $user->name = $request->input('person_name');
            $user->f_name = (explode(" ", $request->input('person_name'))[0] ?? "");
            $user->l_name = (explode(" ", $request->input('person_name'))[1] ?? "");
            $user->email = $request->input('person_phone');
            $user->password =  bcrypt('12345678');
            $user->save();
            \Illuminate\Support\Facades\Auth::guard('customer')->loginUsingId($user->id);

            $data = [
                'customer_id' => ($user->id ?? "")
            ];
            \App\Utils\Helpers::whatsappMessage('whatsapp', 'Welcome Message', $data);
        }
        if (auth('customer')->check()) {
            $leads = new TourLeads();
            $leads->tour_id = $request->tour_id ?? 0;
            $leads->package_id = $request->package_id;
            $leads->user_id = auth('customer')->id();
            $leads->amount = $request->amount;;
            $leads->status = 1;
            $leads->save();
            $tourSlugs = TourVisits::where('id', $request->tour_id)->first();
            // return redirect()->route('tour.tour-booking', [base64_encode(json_encode(['id' => $request->tour_id, 'leads' => $leads->id]))]);
            return redirect()->route('tour.tour-booking', ['id' => $tourSlugs['slug'], 'leads' => $leads->id]);
        } else {
            Toastr::error('Please Login');
        }
        return back();
    }

    public function TourBooking(Request $request, $id)
    {
        if (!auth('customer')->check()) {
            Toastr::error('Please Login');
            return back();
        }
        $getfirst = TourVisits::where('status', 1)->where('slug', $id)->with('TourPlane')->first();
        $getleads = TourLeads::where('id', $request->leads)->first();
        $customer = User::where('id', auth('customer')->id())->first();
        $payment_gateways_list  = payment_gateways();
        $googleMapsApiKey = config('services.google_maps.api_key');
        $id = $getfirst['id'];
        return view('web-views.tour.tour-booking', compact('getfirst', 'customer', 'googleMapsApiKey', 'id', 'getleads', 'payment_gateways_list'));
    }

    public function TourBookingSuccess(Request $request, $id)
    {
        $getfirst = TourVisits::where('status', 1)->where('slug', $id)->with('TourPlane')->first();
        return view('web-views.tour.tour-pay-success', compact('getfirst'));
    }


    public function TourViewDetails(Request $request, $id)
    {
        $tourOrders = \App\Models\TourOrder::where('id', $id)->with(['Tour', 'userData', 'company'])->first();
        if (!$tourOrders) {
            return back();
        }
        return view('web-views.users-profile.tour-order-details', compact('tourOrders'));
    }

    public function TourCancelTicket(Request $request)
    {
        $getData = TourCancelTicket::where('order_id', $request->order_id)->first();
        if ($getData) {
            return response()->json(['status' => 0, 'message' => 'No Found', 'recode' => 0, 'data' => []], 200);
        }
        $ticket = new TourCancelTicket();
        $ticket->user_id = $request->user_id;
        $ticket->order_id = $request->order_id;
        $ticket->message = $request->msg;
        $ticket->status = 1; //0
        $ticket->save();

        User::where('id', $request->user_id)->update(['wallet_balance' => DB::raw('wallet_balance + ' . ($request->amount ?? 0))]);

        $wallet_transaction = new \App\Models\WalletTransaction();
        $wallet_transaction->user_id = $request->user_id;
        $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
        $wallet_transaction->reference = 'tour order refund';
        $wallet_transaction->transaction_type = 'tour_order_refund';
        $wallet_transaction->balance = User::where('id', $request->user_id)->first()['wallet_balance'];
        $wallet_transaction->credit = ($request->amount ?? 0);
        $wallet_transaction->save();
        $get_tourOrder = TourOrder::where('id', ($request->order_id ?? ''))->first();
        TourOrder::where('id', $request->order_id)->update(['refund_status' => 1, 'status' => 2, 'refound_id' => "wallet", 'refund_date' => date("Y-m-d H:i:s"), 'refund_amount' => ($request->amount ?? 0), 'cab_assign' => 0, 'traveller_id' => ($get_tourOrder['cab_assign'] ?? 0), 'refund_query_id' => $ticket->id]);
        $tourOrder = TourOrder::where('id', ($request->order_id ?? ''))->with(['Tour'])->first();
        $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
        $message_data['title_name'] = ($tourOrder['Tour']['tour_name'] ?? '');
        $message_data['booking_date'] = ($tourOrder['pickup_date'] ?? '');
        $message_data['time'] = ($tourOrder['Tour']['pickup_time'] ?? '');
        $message_data['place_name'] = ($tourOrder['Tour']['pickup_address'] ?? '');
        $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourOrder['Tour']['tour_type'] ?? ''))));
        $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
        $message_data['customer_id'] =  $request->user_id;
        \App\Utils\Helpers::whatsappMessage('tour', 'Tour Canceled', $message_data);

        return response()->json(['status' => 1, 'message' => 'added successfully', 'recode' => 1, 'data' => []], 200);
    }

    public function TourCancelResonance(Request $request)
    {
        $getData = new TourCancelResonance();
        $getData->type = $request->type;
        $getData->ticket_id = TourOrder::where('id', $request->order_id)->first()['refund_query_id'] ?? "";
        $getData->msg = $request->msg;
        $getData->save();
        return response()->json(['status' => 1, 'message' => 'added successfully', 'recode' => 1, 'data' => []], 200);
    }

    public function TourReviews(Request $request)
    {
        if (!auth('customer')->check()) {
            Toastr::error('Please Login');
            return back();
        }
        $getReview = TourReviews::where('tour_id', ($request['tour_id'] ?? ''))->where('user_id', auth('customer')->id())->where('order_id', $request->order_id)->first();
        if (!$getReview || $getReview['is_edited'] == 0) {
            if (!$getReview) {
                $review = new TourReviews();
            } else {
                $review = TourReviews::find($getReview['id']);
            }
            $review->order_id = $request->order_id;
            $review->user_id = auth('customer')->id();
            $review->tour_id = $request->tour_id;
            $review->star = $request->rating;
            $review->comment = $request->comment;
            $review->status = 1;
            $review->is_edited = 1;
            $review->save();
            Toastr::success('Comment added successfully');
        } else {
            Toastr::error('Comment has already been added');
        }
        return back();
    }


    public function TourInvoice(Request $request, $id)
    {
        $tourOrders = \App\Models\TourOrder::where('id', $id)->with(['Tour', 'userData', 'company'])->first();
        $refund_policy = TourRefundPolicy::where('status', 1)->where('type', ($tourOrders['Tour']['tour_type'] ?? ""))->orderBy('id', 'asc')->get();
        $mpdf_view  = \Illuminate\Support\Facades\View::make('web-views.tour.paid-invoice', compact('tourOrders', 'refund_policy'));
        \App\Utils\Helpers::gen_mpdf($mpdf_view, 'tour_order_', $id);
    }


    public function ChangePlaneBooking(Request $request, $lead, $id)
    {
        TourLeads::where('id', $lead)->update(['package_id' => $id, 'amount' => 0]);
        $getleads = TourLeads::where('id', $lead)->first();
        $tourSlugs = TourVisits::where('id', $getleads['tour_id'])->first();
        // return redirect()->route('tour.tour-booking', [base64_encode(json_encode(['id' => $getleads['tour_id'], 'leads' => $lead]))]);
        return redirect()->route('tour.tour-booking', ['id' => $tourSlugs['slug'], 'lead' => $lead]);
    }

    public function TourBookingTabs(Request $request)
    {
        $getfirst = TourVisits::where('id', $request->id)->first();

        $transportUsePrice = 0;
        $transportUseTax = 0;
        $transportUsePercent = 0;


        $getInfo = [];
        $new_add_key_exchage = 0;
        if (!empty($request->item)) {
            foreach ($request->item as $key => $value) {
                if ($value['type'] == 'cab') {
                    $gstget = \App\Models\ServiceTax::first()['tour_tax'] ?? 1;
                    $newGst = (($value['price'] * $gstget) / 100);
                    $newprice = $value['price'] - $newGst;
                    $tourPackages =  \App\Models\TourCab::where('id', $value['id'])->first();
                    $images = getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . $tourPackages['image'], type: 'backend-product');
                    $getInfo[] =   ['name' => $tourPackages['name'], 'seats' => $tourPackages['seats'], 'image' => $images, 'qty' => $value['qty'], 'price' => $newprice, 'id' => $tourPackages['id'], 'type' => "cab"];
                    $getInfo[] =   ['name' => '', 'seats' => '', 'image' => '', 'qty' => '', 'price' => ($newGst / 2), 'id' => $value['id'], 'type' => 'cgst', 'title' => "CGST (" . ($gstget / 2) . "%)"];
                    $getInfo[] =   ['name' => '', 'seats' => '', 'image' => '', 'qty' => '', 'price' => ($newGst / 2), 'id' => $value['id'], 'type' => 'sgst', 'title' => "SGST (" . ($gstget / 2) . "%)"];
                    $tourPackages = [];
                } elseif ($value['type'] == 'ex_distance' && ($value['id'] == 0)) {
                    $tourPackages = [];
                    if ($value['qty'] > 20) {
                        $get_ex_packages =   ['name' => '', 'seats' => '', 'image' => '', 'qty' => $value['qty'], 'price' => $value['price'], 'id' => 0, 'type' => $value['type'], 'title' => "Ex Distance Charge"];
                        if (isset($value['ExChargeAmount']) && $value['ExChargeAmount'] > 0) {
                            $get_ex_packages['price'] = $value['ExChargeAmount'] + $value['price'];
                        }
                        $gstget = \App\Models\ServiceTax::first()['tour_transport_tax'] ?? 1;
                        $transportUsePercent = \App\Models\ServiceTax::first()['tour_tax'] ?? 1;
                        $newGst = ((($get_ex_packages['price']) * $gstget) / 100);
                        $newprice = ($get_ex_packages['price']) - $newGst;
                        foreach ($getInfo as $k => $info) {
                            if ($info['type'] === 'cgst') {
                                $getInfo[$k]['price'] = $getInfo[$k]['price'] + ($newGst / 2);
                                $getInfo[$k]['title'] = str_replace(($transportUsePercent / 2), (($transportUsePercent / 2) + ($gstget / 2)), $getInfo[$k]['title']);
                            } elseif ($info['type'] === 'sgst') {
                                $getInfo[$k]['price'] = $getInfo[$k]['price'] + ($newGst / 2);
                                $getInfo[$k]['title'] = str_replace(($transportUsePercent / 2), (($transportUsePercent / 2) + ($gstget / 2)), $getInfo[$k]['title']);
                            }
                        }
                        $getInfo[] =  $get_ex_packages;
                    }
                } elseif ($value['type'] == 'route' && ($value['id'] == 0)) {
                    $getInfo[] =   ['name' => '', 'seats' => '', 'image' => '', 'qty' => 0, 'price' => $value['price'], 'id' => 0, 'type' => $value['type']];
                } else if ($value['type'] == 'per_head') {
                    $tourPackages = [];
                    $gstget = $transportUsePercent = \App\Models\ServiceTax::first()['tour_tax'] ?? 1;
                    $newGst = $transportUseTax = (($value['price'] * $gstget) / 100);
                    $newprice = $transportUsePrice = $value['price'] - $newGst;

                    $getInfo[] =   ['name' => '', 'seats' => '', 'image' => '', 'qty' => $value['qty'], 'price' => $newprice, 'id' => $value['id'], 'type' => $value['type'], 'title' => "person"];
                    $getInfo[] =   ['name' => '', 'seats' => '', 'image' => '', 'qty' => '', 'price' => ($newGst / 2), 'id' => $value['id'], 'type' => 'cgst', 'title' => "CGST (" . ($gstget / 2) . "%)"];
                    $getInfo[] =   ['name' => '', 'seats' => '', 'image' => '', 'qty' => '', 'price' => ($newGst / 2), 'id' => $value['id'], 'type' => 'sgst', 'title' => "SGST (" . ($gstget / 2) . "%)"];
                } elseif ($value['type'] == 'transport' && ($value['price'] > 0)) {
                    $tourPackages = [];
                    $gstget = \App\Models\ServiceTax::first()['tour_transport_tax'] ?? 1;
                    $newGst = ((($value['price']) * $gstget) / 100);
                    $newprice = ($value['price']) - $newGst;
                    $getInfo[] =   ['name' => '', 'seats' => '', 'image' => '', 'qty' => $value['qty'], 'price' => $newprice, 'id' => 0, 'type' => $value['type'], 'title' => "Ex Transport"];
                    foreach ($getInfo as $k => $info) {
                        if ($info['type'] === 'cgst') {
                            $getInfo[$k]['price'] = $getInfo[$k]['price'] + ($newGst / 2);
                            $getInfo[$k]['title'] = str_replace(($transportUsePercent / 2), (($transportUsePercent / 2) + ($gstget / 2)), $getInfo[$k]['title']);
                        } elseif ($info['type'] === 'sgst') {
                            $getInfo[$k]['price'] = $getInfo[$k]['price'] + ($newGst / 2);
                            $getInfo[$k]['title'] = str_replace(($transportUsePercent / 2), (($transportUsePercent / 2) + ($gstget / 2)), $getInfo[$k]['title']);
                        }
                    }
                } else {
                    $tourPackages = \App\Models\TourPackage::where('id', $value['id'])->first();
                    $images = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                }
                if ($tourPackages) {
                    $getInfo[] =   ['name' => $tourPackages['name'], 'seats' => $tourPackages['seats'], 'image' => $images, 'qty' => $value['qty'], 'price' => $value['price'], 'id' => $tourPackages['id'], 'type' => (($value['type'] == 'cab') ? "cab" : "other")];
                }
            }
        }
        return response()->json(['status' => 1, 'message' => 'added successfully', 'recode' => 1, 'data' => $getInfo], 200);
    }

    public function TravellersCab(Request $request, $id)
    {
        $tourvisit = \App\Models\TourVisits::where('created_id', $id)->where('status', 1)->get();
        $travellerinfo = \App\Models\TourAndTravel::where('id', $id)->where('is_approve', 1)->where('status', 1)->first();
        if (!$tourvisit || empty($travellerinfo)) {
            return back();
        }

        return view('web-views.tour.traveller-cab-list', compact('tourvisit', 'travellerinfo'));
    }

    public function TourVisitLeads(Request $request, $id)
    {
        $getfirst = TourVisits::where('status', 1)->where('slug', $id)->with('TourPlane')->withCount('TourOrderReview')->withAvg('review', 'star')->first();
        if (!$getfirst) {
            return redirect()->to('/');
        }
        return view('web-views.tour.tour-visit-lead', compact('getfirst'));
    }

    public function VisitLeads(Request $request, $id)
    {
        $userfind = User::where('phone', ($request->input('person_phone') ?? ""))->first();
        if ($userfind) {
            \Illuminate\Support\Facades\Auth::guard('customer')->loginUsingId($userfind['id']);
        } else {
            $user = new User();
            $user->phone = $request->input('person_phone');
            $user->name = $request->input('person_name');
            $user->f_name = (explode(" ", $request->input('person_name'))[0] ?? "");
            $user->l_name = (explode(" ", $request->input('person_name'))[1] ?? "");
            $user->email = $request->input('person_phone');
            $user->password =  bcrypt('12345678');
            $user->save();
            \Illuminate\Support\Facades\Auth::guard('customer')->loginUsingId($user->id);

            $data = [
                'customer_id' => ($user->id ?? "")
            ];
            \App\Utils\Helpers::whatsappMessage('whatsapp', 'Welcome Message', $data);
        }
        if (auth('customer')->check()) {
            $leads = new TourLeads();
            $leads->tour_id = $request->tour_id ?? 0;
            $leads->package_id = $request->package_id ?? 0;
            $leads->user_id = auth('customer')->id();
            $leads->amount = $request->amount ?? 0;
            $leads->status = 1;
            $leads->save();
            $tourSlugs = TourVisits::where('id', $request->tour_id)->first();
            return redirect()->route('tour.tour-visit-id', ['id' => $tourSlugs['slug'], 'leads' => $leads->id]);
        } else {
            Toastr::error('Please Login');
        }
        return back();
    }
    public function test_push_noti()
    {
        $userData = User::where('id', auth('customer')->id())->first();
        $title = "Tour Booking Confirmation";
        $body = "Your tour has been successfully booked. Stay tuned for more updates.";
        $tokens = $userData['cm_firebase_token'];
        $data = [
            'title' => 'tour_booking',
            "description" => "des",
            "image" => "",
            'order_id' => 12,
            "type" => "order",
            "link" => route('tour.index'),
        ];
        $response = \App\Utils\Helpers::send_push_notif_to_device1($tokens, $data);
    }

    public function RelatedOrderViews(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'type' => 'required',
            'user_id' => 'required_if:type,vendor|nullable',
            'tour_id' => 'required',
            'order_id' => 'required',
        ], [
            'type.required' => 'Type is Request',
            'user_id.required' => 'vendor Id is Empty!',
            'tour_id.required' => 'Tour Id is Empty!',
            'order_id.required' => 'Order Id is Empty!',
        ]);

        if ($validator->fails()) {
            return response()->json(["status" => 0, "message" => "", 'errors' => \App\Utils\Helpers::error_processor($validator), 'data' => []], 403);
        }
        $pickupDate = '';
        $getData = [];
        if ($request['type'] == 'vendor') {
            $pickupDate = \App\Models\TourOrder::where('tour_id', $request['tour_id'])
                ->where('id', $request['order_id'])
                ->where('cab_assign', $request['user_id'])
                ->value('pickup_date');
            $getData = \App\Models\TourOrder::where('tour_id', $request['tour_id'])
                ->where('cab_assign', $request['user_id'])
                ->when($pickupDate, function ($query) use ($pickupDate) {
                    $query->where('pickup_date', $pickupDate);
                })
                ->with(["Tour"])
                ->get();
            if ($getData) {
                foreach ($getData as $kk => $val) {
                    $getCab_josn  = TourOrder::where('tour_id', $request['tour_id'])->WithDriverInfo($val['id'])->first();
                    $getData[$kk]['driver_data']  = $getCab_josn['driver_data'] ?? '';
                    $getData[$kk]['Cabs_data']  = $getCab_josn['Cabs_data'] ?? '';
                }
            }
        } else {
            $pickupDate = \App\Models\TourOrder::where('tour_id', $request['tour_id'])
                ->where('id', $request['order_id'])
                ->value('pickup_date');
            $getData = \App\Models\TourOrder::where('tour_id', $request['tour_id'])
                ->when($pickupDate, function ($query) use ($pickupDate) {
                    $query->where('pickup_date', $pickupDate);
                })
                ->with(["Tour"])
                ->get();
            if ($getData) {
                foreach ($getData as $kk => $val) {
                    $getCab_josn  = TourOrder::where('tour_id', $request['tour_id'])->WithDriverInfo($val['id'])->first();
                    $getData[$kk]['driver_data']  = $getCab_josn['driver_data'] ?? '';
                    $getData[$kk]['Cabs_data']  = $getCab_josn['Cabs_data'] ?? '';
                }
            }
        }

        $html = "<table id='tourOrdersTable2' class='display'>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>person Qty</th>
                                    <th>Time</th>
                                    <th>Cabs Info</th>
                                    <th>Driver Info</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>";
        if ($getData) {
            $k_index = 1;
            foreach ($getData as $kk => $val) {
                $html .= "<tr>
                                    <td>" . ($k_index) . "</td>
                                    <td>";
                $packages = json_decode($val['booking_package'] ?? '[]', true);
                $cabPackage = collect($packages)->firstWhere('type', 'cab');
                $html .= $cabPackage ? (int) $cabPackage['qty'] : 0;
                $html .= "</td>
                                    <td>
                                    <span>Date : " . (date('d M,Y', strtotime($val['pickup_date'] ?? ''))) . "</span><br>
                                    <span>Time : " . ($val['pickup_time'] ?? '') . "</span><br>
                                    <span>Location : " . ($val['pickup_address'] ?? '') . "</span>
                                    </td>
                                    <td>";
                if ($val['Cabs_data'] && json_decode($val['Cabs_data'])) {
                    foreach (json_decode($val['Cabs_data'], true) as $inc) {
                        $html .= "<span>Cab Name: " . ($inc['cab_name'] ?? '') . "</span><br>";
                        $html .= "<span>model Number: " . ($inc['model_number'] ?? '') . "</span><br>";
                        $html .= "<span>reg Number: " . ($inc['reg_number'] ?? '') . "</span><br>";
                    }
                }
                $html .= "</td>
                                    <td>";
                if ($val['driver_data'] && json_decode($val['driver_data'])) {
                    foreach (json_decode($val['driver_data'], true) as $inc) {
                        $html .= "<span>Name: " . ($inc['name'] ?? '') . "</span><br>";
                        $html .= "<span>Phone No: " . ($inc['phone'] ?? '') . "</span><br>";
                        $html .= "<span>email: " . ($inc['email'] ?? '') . "</span><br>";
                    }
                }
                $html .= "</td><td>";
                if ($val['pickup_status'] == 1 && $val['drop_status'] == 0) {
                    $html .= "<span>PickUp</span>";
                } else if ($val['pickup_status'] == 1 && $val['drop_status'] == 1) {
                    $html .= "<span>Drop Complate</span>";
                } else {
                    $html .= "<span>Pending</span>";
                }
                $html .= "</td>
                                </tr>";
                $k_index++;
            }
        }

        $html .= "</tbody>
                        </table>";
        return response()->json(['status' => 1, 'message' => 'added successfully', 'recode' => 1, 'data' => $html, 'pickupDate' => $pickupDate], 200);
    }
}