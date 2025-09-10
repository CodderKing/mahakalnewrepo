<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ServiceTax;
use App\Utils\Helpers;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class ServiceTaxController extends Controller
{
    public function service_tax_list()
    {
        $tax = ServiceTax::first();
        return view('admin-views.service-tax.list', compact('tax'));
    }

    public function service_tax_update(Request $request)
    {
        $tax = ServiceTax::first();
        $tax->offline_pooja = $request->offline_pooja;
        $tax->online_pooja = $request->online_pooja;
        $tax->consultation = $request->consultation;
        $tax->live_stream = $request->live_stream;
        $tax->call = $request->call;
        $tax->chat = $request->chat;
        $tax->tour_tax = $request->tour_tax ?? '';
        $tax->event_tax = $request->event_tax ?? "";
        if ($tax->save()) {
            Toastr::success(translate('commission_Update'));
            Helpers::editDeleteLogs('Service Tax', 'Service Tax', 'Update');
            return back();
        }
        Toastr::error(translate('an_error_occured'));
        return back();
    }
}
