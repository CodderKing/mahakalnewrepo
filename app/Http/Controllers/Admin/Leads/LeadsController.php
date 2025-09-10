<?php

namespace App\Http\Controllers\Admin\Leads;

use App\Models\Chadhava;
use App\Models\Chadhava_orders;
use App\Models\Followsup;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use App\Models\Leads;
use App\Models\OfflineLead;
use App\Models\Package;
use App\Models\ProductCompare;
use App\Models\Service;
use App\Models\Service_order;
use App\Models\User;
use App\Models\Vippooja;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use App\Traits\FileManagerTrait;
use App\Utils\CartManager;
use App\Utils\Helpers;
use DateTime;
use DateTimeZone;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeadsController
{

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View Index function is the starting point of a controller
     * Index function is the starting point of a controller
     */
    
    public function getLeadsData(Request $request)
    {
        $LeadsList = Leads::with(['vippooja', 'service', 'chadhava'])->orderBy('id', 'desc')->get();
        $data = [
        'pooja' => [
            'complete' => Leads::where('type', 'pooja')->where('payment_status', 'complete')->count(),
            'pending'  => Leads::where('type', 'pooja')->where('payment_status', 'pending')->count(),
            'failed'   => Leads::where('type', 'pooja')->where('payment_status', 'failed')->count(),
            'total'   => Leads::where('type', 'pooja')->count(),
        ],
        'vip' => [
            'complete' => Leads::where('type', 'vip')->where('payment_status', 'complete')->count(),
            'pending'  => Leads::where('type', 'vip')->where('payment_status', 'pending')->count(),
            'failed'   => Leads::where('type', 'vip')->where('payment_status', 'failed')->count(),
            'total'   => Leads::where('type', 'vip')->count(),
        ],
        'anushthan' => [
            'complete' => Leads::where('type', 'anushthan')->where('payment_status', 'complete')->count(),
            'pending'  => Leads::where('type', 'anushthan')->where('payment_status', 'pending')->count(),
            'failed'   => Leads::where('type', 'anushthan')->where('payment_status', 'failed')->count(),
            'total'   => Leads::where('type', 'anushthan')->count(),
        ],
        'counselling' => [
            'complete' => Leads::where('type', 'counselling')->where('payment_status', 'complete')->count(),
            'pending'  => Leads::where('type', 'counselling')->where('payment_status', 'pending')->count(),
            'failed'   => Leads::where('type', 'counselling')->where('payment_status', 'failed')->count(),
            'total'   => Leads::where('type', 'counselling')->count(),
        ],
        'chadhava' => [
            'complete' => Leads::where('type', 'chadhava')->where('payment_status', 'complete')->count(),
            'pending'  => Leads::where('type', 'chadhava')->where('payment_status', 'pending')->count(),
            'failed'   => Leads::where('type', 'chadhava')->where('payment_status', 'failed')->count(),
            'total'   => Leads::where('type', 'chadhava')->count(),
        ],
    ];


        return view('admin-views.leads.lead-list', compact('LeadsList','data'));
    }

    public function leadsList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('searchValue') ?? '';
        $service_ids = $request->get('service_id') ?? '';
        $serviceTypes = $request->get('serviceType') ?? '';
        $start_date = $request->get('start_date') ?? '';
        $end_date = $request->get('end_date') ?? '';

        // Base Query (Keep as Query Builder, no ->get() yet)
        $query = Leads::with(['vippooja.orderDetails','service.orderDetails','chadhava.chadhava_order']);
        // Search Filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('package_name', 'like', "%{$searchValue}%")
                    ->orWhere('person_name', 'like', "%{$searchValue}%")
                    ->orWhere('type', 'like', "%{$searchValue}%")
                    ->orWhere('person_phone', 'like', "%{$searchValue}%")
                    ->orWhere('booking_date', 'like', "%{$searchValue}%")
                    ->orWhere('payment_status', 'like', "%{$searchValue}%")
                    ->orWhere('order_id', 'like', "%{$searchValue}%")
                    ->orWhereHas('service', function ($q2) use ($searchValue) {
                        $q2->where('name', 'like', "%{$searchValue}%");
                    })->orWhereHas('vippooja', function ($q3) use ($searchValue) {
                        $q3->where('name', 'like', "%{$searchValue}%");
                    })->orWhereHas('chadhava', function ($q4) use ($searchValue) {
                        $q4->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }
        // Service Filter (if applied)
        if (!empty($service_ids)) {
            $query->where(function ($q) use ($service_ids) {
                $q->orWhere('payment_status', $service_ids);
            });
        }
        if (!empty($serviceTypes)) {
            $query->where(function ($q) use ($serviceTypes) {
                $q->orWhere('type', $serviceTypes);
            });
        }
        if ($request->has('search_text') && $request->search_text != '') {
            $query->where(function ($q) use ($request) {
                $q->where('person_name', 'like', '%' . $request->search_text . '%')
                ->orWhere('name', 'like', '%' . $request->search_text . '%')
                ->orWhere('payment_status', 'like', '%' . $request->search_text . '%')
                ->orWhere('person_phone', 'like', '%' . $request->search_text . '%')
                ->orWhere('type', 'like', '%' . $request->search_text . '%')
                ->orWhere('package_name', 'like', '%' . $request->search_text . '%')
                ->orWhere('order_id', 'like', '%' . $request->search_text . '%')
                ->orWhere('platform', 'like', '%' . $request->search_text . '%');
            });
        }

        if ($request->has('date') && $request->date != '') {
            $query->whereDate('booking_date', $request->date);
        }
        $recordsTotal = Leads::count();

        $recordsFiltered = $query->count();
        $data = $query->orderBy('id', 'desc')->skip($start)
        ->take($length)
        ->get();
        $formattedData = $data->map(function ($item, $key) use ($start) {
        $serviceName = '';
        if ($item->type == 'pooja' || $item->type == 'counselling') {
            $serviceName = optional($item->service)->name;
        } elseif ($item->type == 'vip' || $item->type == 'anushthan') {
            $serviceName = optional($item->vippooja)->name;
        } elseif ($item->type == 'chadhava') {
            $serviceName = optional($item->chadhava)->name;
        }

        $date = ($item->type == 'counselling') ? date('d M,Y', strtotime($item->created_at)) : date('d M,Y', strtotime($item->booking_date));
        $orderDate = date('d M,Y H:i:s', strtotime($item->created_at));
        // Common data assignment
        $orderId = optional($item)->order_id;
        $serviceType = optional($item)->type;
        $platform = optional($item)->platform;
        $wallet = optional($item)->via_wallet;
        $online = optional($item)->via_online;
        $coupon = optional($item)->coupon_amount;
        $amount = optional($item)->final_amount;
        $status = optional($item)->payment_status;
        $packagePrice = optional($item)->package_price;
        $whatsappHit = optional($item)->whatsapp_hit;
        $customerID = optional($item)->customer_id;
        $serviceID = optional($item)->service_id;
        $personName = optional($item)->person_name;
        $personPhone = optional($item)->person_phone;
        $leadId = optional($item)->leadno;
        $statusBadge = '';
        if ($status == 'pending') {
            $statusBadge = '<span class="badge badge-warning">Pending</span>';
        } elseif ($status == 'Complete') {
            $statusBadge = '<span class="badge badge-success">Complete</span>';
        } elseif ($status == 'failed') {
            $statusBadge = '<span class="badge badge-danger">Failed</span>';
        } else {
            $statusBadge = '<span class="badge badge-success">'.e($status).'</span>';
        }
        return [
            'id' => $start + $key + 1,
            'useinfo' =>
                '<strong>Name:</strong> ' . e($item->person_name) . '<br>' .
                ($item->person_phone ? '<strong>Person phone:</strong> ' . e($item->person_phone) . '<br>' : '') .
                '<strong>No Person:</strong> ' . e($item->noperson) . '<br>',
            'date' => $date,
            'order_created' => $orderDate,
            'order_id' => $orderId,
            'service_name' => $serviceName,
            'service_type' => $serviceType,
            'package_price' => '₹' . $packagePrice,
            'wallet' => '₹' . $wallet,
            'online' => '₹' . $online,
            'coupon' => '₹' . $coupon,
            'amount' => '₹' . $amount,
            'platform' => $platform,
            'status' => $statusBadge,
            'payment_status' => $status,
            'whatsapp_hit' => $whatsappHit,
            'customer_id' => $customerID,
            'service_id' => $serviceID,
            'person_name' => $personName,
            'person_phone' => $personPhone,
            'leadno' => $leadId,
        ];
        });

    
        // dd($formattedData);
        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $formattedData
        ]);
    }

    public function add_NewLead(){
        $service = Service::where('status',1)->where('product_type','pooja')->get();
        $counslling = Service::where('status',1)->where('product_type','counselling')->get();
        $vipanushthan = Vippooja::where('status',1)->get();
        $chadhava = Chadhava::where('status',1)->get();
        return view('admin-views.leads.add-lead-generate', compact('service','counslling','vipanushthan','chadhava'));
    }

    public function getServicesByType(Request $request)
    {
        $type = $request->input('type');
        switch ($type) {
            case 'pooja':
                $services = Service::where('product_type', 'pooja')->where('status',1)->get();
                break;

            case 'vip':
                $services = Vippooja::where('is_anushthan', 0)->where('status',1)->get();
                break;

            case 'anushthan':
                $services = Vippooja::where('is_anushthan', 1)->where('status',1)->get();
                break;

            case 'chadhava':
                $services = Chadhava::where('status',1)->get();
                break;

            default:
                $services = collect(); 
                break;
        }
        $servicesHtml = view('admin-views.leads.service_select', compact('services', 'type'))->render();

        return response()->json([
            'services_html' => $servicesHtml
        ]);
    }

    // Leads the Customer Get and Register now
    public function UesetoCheck($no)
    {
        $user = User::where('phone', $no)->first();
        if ($user) {
            return response()->json(['status' => 200, 'user' => $user]);
        }
        return response()->json(['status' => 400]);
    }
    public function getPackagesByService(Request $request)
    {
        $type = $request->type;
        $serviceId = $request->input('service_id');

        if ($type === 'pooja') {
            $service = Service::find($serviceId);
        } elseif ($type === 'vip' || $type === 'anushthan') {
            $service = VipPooja::find($serviceId);
        } elseif ($type === 'chadhava') {
            $service = Chadhava::find($serviceId);
        } else {
            return response()->json([
                'packages_html' => '<p class="text-danger">Invalid service type.</p>'
            ], 400);
        }

        if (!$service) {
            return response()->json([
                'packages_html' => '<p class="text-danger">Service not found.</p>'
            ], 404);
        }

        $packageList = json_decode($service->packages_id, true); 
        
        if (!is_array($packageList)) {
            return response()->json([
                'packages_html' => '<p class="text-danger">Invalid package data.</p>'
            ], 400);
        }

        $packageIds = collect($packageList)->pluck('package_id')->toArray();

        $packages = Package::whereIn('id', $packageIds)
            ->when(in_array($type, ['pooja', 'vip', 'anushthan']), function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->get();

        $packages = $packages->map(function ($pkg) use ($packageList) {
            $match = collect($packageList)->firstWhere('package_id', $pkg->id);
            $pkg->custom_price = $match['package_price'] ?? $pkg->package_price;
            return $pkg;
        });
        $packagesHtml = view('admin-views.leads.package_select', compact('packages'))->render();

        return response()->json([
            'packages_html' => $packagesHtml,
        ]);
    }

    public function AddNewGenerate_leads(Request $request){
     
        if ($request->type === 'pooja') {
            $servicedata = Service::where('id', $request->service_id)->where('product_type', 'pooja')->first();
        } elseif ($request->type === 'vip') {
            $servicedata = Vippooja::where('id', $request->service_id)->first();
        } elseif ($request->type === 'anushthan') {
            $servicedata = Vippooja::where('id', $request->service_id)->first();
        }
        if (!$servicedata) {
            return redirect()->back()->with('error', 'Puja not found.');
        }

        $personName = $request->input('person_name');
        $personPhone = $request->input('phone');
        $nameParts = explode(' ', $personName);
        $firstName = $nameParts[0];
        $lastName = implode(' ', array_slice($nameParts, 1));

        if ($personPhone == '') {
            return redirect()->to('/');
        }

        $customerId = null;

        $userExists = User::where('phone', $request->phone)->exists();
        if (!$userExists) {
            $user = User::create([
                'name'     => $personName,
                'f_name'   => $firstName,
                'l_name'   => $lastName,
                'phone'    => $personPhone,
                'email'    => 'user@mahakal.com',
                'password' => bcrypt('12345678'),
                'verify_otp' =>1,
            ]);
            $customerId = $user->id;
            $data = ['customer_id' => $customerId];
            Helpers::whatsappMessage('whatsapp', 'Welcome Message', $data);
        }else {
            $user = User::where('phone', $request->phone)->first();
            $customerId = $user->id;
        }
       
        if ($request->type == 'pooja') {
            $orderData = Service_order::select('id')->latest()->first();
            $orderId = !empty($orderData['id']) 
                ? 'PJ' . (100000 + $orderData['id'] + 1)
                : 'PJ' . (100001);
        } elseif ($request->type == 'vip') {
            $orderData = Service_order::select('id')->latest()->first();
            $orderId = !empty($orderData['id']) 
                ? 'VPJ' . (100000 + $orderData['id'] + 1)
                : 'VPJ' . (100001);
        } elseif ($request->type == 'anushthan') {
            $orderData = Service_order::select('id')->latest()->first();
            $orderId = !empty($orderData['id']) 
                ? 'APJ' . (100000 + $orderData['id'] + 1)
                : 'APJ' . (100001);
        }
     
        $Lead_new = [
            'service_id'    => $request->service_id,
            'type'          => $request->type,
            'package_id'    => $request->selected_package_id,
            'product_id'    => $servicedata->product_id,
            'package_price' => $request->package_price,
            'package_name'  =>  $request->package_title,
            'noperson'      => $request->package_person,
            'person_phone'  => $request->phone,
            'person_name'   => $request->person_name,
            'booking_date'  => $request->booking_date,
            'customer_id'   => $customerId, 
            'platform'      => $request->platform, 
            'payment_status'=> 'pending', 
            'final_amount'=> $request->package_price, 
            'order_id'  => $orderId,
        ];
        $leads = Leads::create($Lead_new);
        $insertedRowId = $leads->id;

        // Map type to prefix
        $typePrefixes = [
            'pooja'     => 'PJ',
            'vip'       => 'VP',
            'anushthan' => 'AN',
        ];
        $prefix = $typePrefixes[$request->type];
        if (!empty($insertedRowId)) {
            $leadno = $prefix . (100 + $insertedRowId + 1);
        } else {
            $leadno = $prefix . '101';
        }

        Leads::where('id', $insertedRowId)->update(['leadno' => $leadno]);
        $serviceOrderData = Leads::find($leads->id);

        if (!$serviceOrderData) {
            return; 
        }

        $existingServiceOrder = Service_order::where('order_id', $serviceOrderData->order_id)->first();

        $serviceOrderAdd = [
            'order_id'         => $serviceOrderData->order_id,
            'customer_id'      => $serviceOrderData->customer_id,
            'service_id'       => $serviceOrderData->service_id,
            'type'             => $serviceOrderData->type,
            'leads_id'         => $serviceOrderData->id,
            'package_id'       => $serviceOrderData->package_id,
            'package_price'    => $serviceOrderData->package_price,
            'booking_date'     => $serviceOrderData->booking_date,
            'wallet_amount'    => $serviceOrderData->via_wallet ?? 0,
            'transection_amount' => $serviceOrderData->final_amount,
            'pay_amount'       => $serviceOrderData->final_amount,
        ];
    
        Service_order::create($serviceOrderAdd);
        $data = [
            'pooja' => [
                'complete' => Leads::where('type', 'pooja')->where('payment_status', 'complete')->count(),
                'pending'  => Leads::where('type', 'pooja')->where('payment_status', 'pending')->count(),
                'failed'   => Leads::where('type', 'pooja')->where('payment_status', 'failed')->count(),
            ],
            'vip' => [
                'complete' => Leads::where('type', 'vip')->where('payment_status', 'complete')->count(),
                'pending'  => Leads::where('type', 'vip')->where('payment_status', 'pending')->count(),
                'failed'   => Leads::where('type', 'vip')->where('payment_status', 'failed')->count(),
            ],
            'anushthan' => [
                'complete' => Leads::where('type', 'anushthan')->where('payment_status', 'complete')->count(),
                'pending'  => Leads::where('type', 'anushthan')->where('payment_status', 'pending')->count(),
                'failed'   => Leads::where('type', 'anushthan')->where('payment_status', 'failed')->count(),
            ],
            'counselling' => [
                'complete' => Leads::where('type', 'counselling')->where('payment_status', 'complete')->count(),
                'pending'  => Leads::where('type', 'counselling')->where('payment_status', 'pending')->count(),
                'failed'   => Leads::where('type', 'counselling')->where('payment_status', 'failed')->count(),
            ],
            'chadhava' => [
                'complete' => Leads::where('type', 'chadhava')->where('payment_status', 'complete')->count(),
                'pending'  => Leads::where('type', 'chadhava')->where('payment_status', 'pending')->count(),
                'failed'   => Leads::where('type', 'chadhava')->where('payment_status', 'failed')->count(),
            ],
        ];

        return view('admin-views.leads.lead-list',compact('data'));

    }
    // Leads manage the All Order
    
    public function lead_list(Request $request)
    {
        if ($request->has('searchValue')) {
            $leads = Leads::where('person_name', 'like', '%' . $request->searchValue . '%')->where('status', 1)->where('type', 'pooja')->with('service')->orderBy('created_at', 'DESC')->paginate(10);
        } else {
            $leads = Leads::where('status', 1)->where('type', 'pooja')->with('service', 'followby')->orderBy('created_at', 'DESC')->paginate(10);
        }
        // dd($leads);
        return view('admin-views.pooja.lead.list', compact('leads'));
    }
    public function lead_delete($id, Request $request)
    {
        $lead = Leads::where('leadno', $id)->first();
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
            'customer_id' => $request->input('customer_id'),
            'pooja_id' => $request->input('pooja_id'),
            'lead_id' => $request->input('lead_id'),
            'type' => $request->input('type'),
            'follow_by' => $request->input('follow_by'),
            'follow_by_id' => $request->input('follow_by_id'),
            'last_date' => $request->input('last_date'),
            'message' => $request->input('message'),
            'next_date' => $request->input('next_date'),
        ];
        Followsup::create($follows);
        //  dd($followStore);
        Toastr::success(translate('lead_follow_up_successfully'));
        return back();
    }
    public function getFollowList($id)
    {
        $followlist = Followsup::where('lead_id', $id)->get();
        // dd($followlist);
        return response()->json($followlist);
    }
     public function send_whatsapp_leads($id)
    {
        $lead = Leads::where('id', $id)->first();
        $poojaName = Service::where('status', 1)->where('id', $lead->service_id)->first();
        $customer = User::where('is_active', 1)->where('phone', $lead->person_phone)->first();

        if ($lead) {
            $message_data = [
                'service_name' => $poojaName->name,
                'puja_venue' => $poojaName->pooja_venue,
                'type' => 'text-with-media',
                'attachment' => asset('/storage/app/public/pooja/thumbnail/' . $poojaName->thumbnail),
                'puja' => 'Puja',
                'link' => 'mahakal.com/epooja/' . $poojaName->slug,
                'customer_id' => ($customer->id ?? ""),
            ];

            // dd($message_data);
            $messages =  Helpers::whatsappMessage('whatsapp', 'Lead Message', $message_data);
            Leads::where('id', $id)->increment('whatsapp_hit');
            Toastr::success(translate('message_sent_successfully'));
        } else {
            Toastr::error(translate('lead_Not_found'));
        }
        return back();
    }
    public function offlineLeads()
    {
        $offlineLeads = OfflineLead::with(['offlinePooja'])->orderBy('id', 'desc')->get();

        return view('admin-views.leads.offline-pooja-leads', compact('offlineLeads'));
    }

    public function offlineLeadsList()
    {
        $offlineLeads = OfflineLead::with('offlinePooja')->orderBy('id', 'desc')->get()->map(function ($lead) {
            return [
                'id' => $lead->id,
                'person_name' => $lead->person_name,
                'person_phone' => $lead->person_phone,
                'platform' => $lead->platform,
                'pooja_name' => $lead->offlinePooja->name ?? '-',
                'order_created' => $lead->created_at,
                'order_id' => $lead->order_id,
                'package_name' => $lead->package_name,
                'package_main_price' => $lead->package_main_price,
                'package_price' => $lead->package_price,
                'payment_status' => $lead->payment_status,
                'payment_type' => $lead->payment_type,
                'via_wallet' => $lead->via_wallet,
                'via_online' => $lead->via_online,
                'coupon_amount' => $lead->coupon_amount,
                'final_amount' => $lead->final_amount,
                'remain_amount' => $lead->remain_amount,
            ];
        });
        
        return response()->json([
            'status' => true,
            'data' => $offlineLeads
        ]);
    }
   
}