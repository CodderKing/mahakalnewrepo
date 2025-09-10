@extends('layouts.back-end.app')

@section('title', translate('Passenger Company Details'))
@push('css_or_js')
<style>
   .rainbow {
      background-color: #343A40;
      border-radius: 4px;
      color: #000;
      cursor: pointer;
      padding: 8px 16px;
   }

   .rainbow-1 {
      background-image: linear-gradient(359deg, #90e979d9 13%, #f8f8f8 54%, #ebd859 103%);
      animation: slidebg 5s linear infinite;
   }

   @keyframes slidebg {
      to {
         background-position: 20vw;
      }
   }
</style>
@endpush

@section('content')
<div class="content container-fluid">
   <div class="mb-3">
      <h2 class="h1 mb-0 d-flex gap-2">
         <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/') }}" alt="">
         {{ translate('Passenger Company Details') }}
      </h2>
   </div>
   <div class="row">
      <div class="card w-100">
         <div class="card-body">
            <ul class="nav nav-tabs w-fit-content mb-4">
               <li class="nav-item text-capitalize">
                  <a class="nav-link {{ (($name == 'null')?'active':'') }}" id="overview-tab" data-toggle="tab" href="#overview-content">
                     {{ translate('overview') }}
                  </a>
               </li>
               <li class="nav-item text-capitalize">
                  <a class="nav-link {{ (($name == 'cab')?'active':'') }}" id="Cab-list-tab" data-toggle="tab" href="#Cab-list-content">
                     {{ translate('cab_list') }}
                  </a>
               </li>
               <li class="nav-item text-capitalize">
                  <a class="nav-link {{ (($name == 'driver')?'active':'') }}" id="driver-tab" data-toggle="tab" href="#driver-list-content">
                     {{ translate('driver_list') }}
                  </a>
               </li>
               <li class="nav-item text-capitalize">
                  <a class="nav-link {{ (($name == 'transation')?'active':'') }}" id="transation-tab" data-toggle="tab" href="#transation-content-withdrawal">
                     {{ translate('transation') }}
                  </a>
               </li>
               <li class="nav-item text-capitalize">
                  <a class="nav-link {{ (($name == 'order-transation')?'active':'') }}" id="transation-tab" data-toggle="tab" href="#transation-content">
                     {{ translate('order_transation') }}
                  </a>
               </li>
               <li class="nav-item text-capitalize">
                  <a class="nav-link {{ (($name == 'pending-order')?'active':'') }}" id="transation-tab" data-toggle="tab" href="#pending-order-content">
                     {{ translate('pending_order') }}
                  </a>
               </li>
               <li class="nav-item text-capitalize">
                  <a class="nav-link {{ (($name == 'confirm-order-transation')?'active':'') }}" id="transation-tab" data-toggle="tab" href="#confirm-order-content">
                     {{ translate('confrim_order') }}
                  </a>
               </li>
               <li class="nav-item text-capitalize">
                  <a class="nav-link {{ (($name == 'pickup-transation')?'active':'') }}" id="transation-tab" data-toggle="tab" href="#pickup-order-content">
                     {{ translate('pickup_order') }}
                  </a>
               </li>
            </ul>
            <div class="tab-content">
               <div class="tab-pane fade {{ (($name == 'null')?'show active':'') }}" id="overview-content">
                  <div class="row">
                     @include('admin-views.tour_and_travels.travels.overview')
                  </div>
               </div>
               <div class="tab-pane fade {{ (($name == 'cab')?'show active':'') }}" id="Cab-list-content">
                  <div class="row">
                     @include('admin-views.tour_and_travels.travels.cab-list')
                  </div>
               </div>
               <div class="tab-pane fade {{ (($name == 'driver')?'show active':'') }}" id="driver-list-content">
                  <div class="row">
                     @include('admin-views.tour_and_travels.travels.driver-list')
                  </div>
               </div>
               <div class="tab-pane fade" id="transation-content-withdrawal">
                  <div class="row">
                     <div class="content container-fluid">
                        <div class="mb-3">
                           <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                              <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png')}}" alt="">
                              {{translate('withdraw_Request')}}
                           </h2>
                        </div>
                        <div class="row">
                           <div class="col-md-12">
                              <div class="card">
                                 <div class="px-3 py-4">
                                    <div class="row align-items-center">
                                       <div class="col-lg-4">
                                          <h5>
                                             {{ translate('withdraw_Request_Table')}}
                                             <span class="badge badge-soft-dark radius-50 fz-12 ml-1" id="withdraw-requests-count">{{-- $withdrawRequests->total() --}}</span>
                                          </h5>
                                       </div>
                                       <div class="col-lg-8 mt-3 mt-lg-0 d-flex gap-3 justify-content-lg-end">

                                          <select name="status" class="custom-select min-w-120 max-w-200 status-filter">
                                             <option value="all" {{ request('approved') == 'all'?'selected':''}}>{{translate('all')}}</option>
                                             <option value="approved" {{ request('approved') == 'approved' ?'selected':''}}>{{translate('approved')}}</option>
                                             <option value="denied" {{ request('approved') == 'denied'?'selected':''}}>{{translate('denied')}}</option>
                                             <option value="pending" {{ request('approved') == 'pending'?'selected':''}}>{{translate('pending')}}</option>
                                          </select>
                                       </div>
                                    </div>
                                 </div>
                                 <div id="status-wise-view">
                                    <div class="table-responsive">
                                       <table id="datatable"
                                          class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                          <thead class="thead-light thead-50 text-capitalize">
                                             <tr>
                                                <th>{{translate('SL')}}</th>
                                                <th>{{translate('amount')}}</th>
                                                <th>{{translate('name') }}</th>
                                                <th>{{translate('request_time')}}</th>
                                                <th class="text-center">{{translate('status')}}</th>
                                                <th class="text-center">{{translate('action')}}</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             @if(!empty($withdrawRequests) && count($withdrawRequests))
                                             @foreach($withdrawRequests as $key=>$withdraw)
                                             <tr>
                                                <td>{{$withdrawRequests->firstItem()+$key}}</td>
                                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $withdraw['amount']), currencyCode: getCurrencyCode())}}</td>

                                                <td>
                                                   @if ($withdraw->deliveryMan)
                                                   <span class="title-color hover-c1">{{ $withdraw->deliveryMan->f_name . ' ' . $withdraw->deliveryMan->l_name }}</span>
                                                   @else
                                                   <span>{{translate('not_found')}}</span>
                                                   @endif
                                                </td>
                                                <td>{{ date_format( $withdraw->created_at, 'd-M-Y, h:i:s A') }}</td>
                                                <td class="text-center">
                                                   @if($withdraw->approved==0)
                                                   <label class="badge badge-soft-primary">{{translate('pending')}}</label>
                                                   @elseif($withdraw->approved==1)
                                                   <label class="badge badge-soft-success">{{translate('approved')}}</label>
                                                   @else
                                                   <label class="badge badge-soft-danger">{{translate('denied')}}</label>
                                                   @endif
                                                </td>
                                                @if (Helpers::modules_permission_check('Delivery Men', 'Withdraw', 'detail'))
                                                <td>
                                                   <div class="d-flex justify-content-center">
                                                      @if (isset($withdraw->deliveryMan))
                                                      <button
                                                         class="btn btn-outline-info btn-sm square-btn withdraw-info-show"
                                                         data-action="{{route('admin.delivery-man.withdraw-view',[$withdraw['id']])}}"
                                                         title="{{translate('view')}}">
                                                         <i class="tio-invisible"></i>
                                                      </button>
                                                      @else
                                                      <a class="btn btn-outline-info btn-sm square-btn disabled" href="#">
                                                         <i class="tio-invisible"></i>
                                                      </a>
                                                      @endif
                                                   </div>
                                                </td>
                                                @endif
                                             </tr>
                                             @endforeach
                                             @endif
                                          </tbody>
                                       </table>
                                       @if(count($withdrawRequests)==0)
                                       <div class="text-center p-4">
                                          <img class="mb-3 w-160"
                                             src="{{dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg')}}"
                                             alt="{{translate('image_description')}}">
                                          <p class="mb-0">{{translate('no_data_to_show')}}</p>
                                       </div>
                                       @endif
                                    </div>
                                 </div>
                                 <div class="table-responsive mt-4">
                                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                                       {{-- $withdrawRequests->links() --}}
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="tab-pane fade" id="transation-content">
                  <div class="row">
                     <div class="content container-fluid">
                        <div class="mb-3">
                           <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                              <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png')}}" alt="">
                              {{translate('complete_order')}}
                           </h2>
                        </div>
                        <div class="row">
                           <div class="col-md-12">
                              <div class="card">
                                 <div class="px-3 py-4">
                                    <div class="row align-items-center">
                                       <div class="col-lg-4">
                                       </div>
                                       <div class="col-lg-8 mt-3 mt-lg-0 d-flex gap-3 justify-content-lg-end">
                                       </div>
                                    </div>
                                 </div>
                                 <div id="status-wise-view">
                                    <div class="table-responsive">
                                       <table id="datatable"
                                          class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                          <thead class="thead-light thead-50 text-capitalize">
                                             <tr>
                                                <th>{{translate('SL')}}</th>
                                                <th>{{translate('customer_info')}}</th>
                                                <th>{{translate('tour_info') }}</th>
                                                <th>{{translate('TXN_ID')}}</th>
                                                <th>{{translate('amount')}}</th>
                                                <th class="text-center">{{translate('final_amount')}}</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             @if(!empty($complete_order) && count($complete_order) > 0)
                                             @foreach($complete_order as $key=>$orders)
                                             <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                   <div>
                                                      <small>{{ $orders['userData']['name'] ?? '' }}</small><br>
                                                      <small>{{ $orders['userData']['phone'] ?? '' }}</small><br>
                                                      <small>{{ date('d M,Y h:i A', strtotime($orders['created_at'] ?? '')) }}</small><br>
                                                      <a class="btn btn-sm btn-outline-info" onclick="$('.modelopen_{{ $key }}').modal()">view package</a><br>
                                                      <div class="modal modelopen_{{ $key }}" tabindex="-1">
                                                         <div class="modal-dialog">
                                                            <div class="modal-content">
                                                               <div class="modal-header">
                                                                  <h5 class="modal-title">
                                                                     {{ $orders['Tour']['tour_name'] ?? '' }}
                                                                  </h5>
                                                                  <button type="button" class="close"
                                                                     data-dismiss="modal" aria-label="Close">
                                                                     <span aria-hidden="true">&times;</span>
                                                                  </button>
                                                               </div>
                                                               <div class="modal-body">
                                                                  <div class="row">
                                                                     <div class="col-12">
                                                                        <table class="table">
                                                                           <thead>
                                                                              <tr>
                                                                                 <td>Name</td>
                                                                                 <td>qty</td>
                                                                                 <td>price</td>
                                                                              </tr>
                                                                           </thead>
                                                                           <tbody>
                                                                              @if (!empty($orders['booking_package']) && json_decode($orders['booking_package'], true))
                                                                              @foreach (json_decode($orders['booking_package'], true) as $p_info)
                                                                              <tr>
                                                                                 <td>
                                                                                    @if ($p_info['type'] == 'cab')
                                                                                    @php $tourPackages = \App\Models\TourCab::where('id', $p_info['id'])->first(); @endphp
                                                                                    @elseif($p_info['type'] == 'other')
                                                                                    @php $tourPackages = \App\Models\TourPackage::where('id', $p_info['id'])->first(); @endphp
                                                                                    @elseif($p_info['type'] == 'ex_distance')
                                                                                    @php $tourPackages = ['name'=>"Ex distance"] @endphp
                                                                                    @endif
                                                                                    <div
                                                                                       class="col-3 text-left">
                                                                                       @if ($p_info['type'] == 'cab')
                                                                                       <img src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($tourPackages['image'] ?? ''), type: 'backend-product') }}"
                                                                                          class="img-fluid img-thumbnail">
                                                                                       @elseif($p_info['type'] == 'other')
                                                                                       <img src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product') }}"
                                                                                          class="img-fluid img-thumbnail">
                                                                                       @endif
                                                                                    </div>
                                                                                    <span
                                                                                       class="font-weight-bold">
                                                                                       {{ $tourPackages['name'] ?? '' }}
                                                                                    </span>
                                                                                 </td>
                                                                                 <td>{{ $p_info['qty'] }}
                                                                                 </td>
                                                                                 <td>{{ $p_info['price'] }}
                                                                                 </td>
                                                                              </tr>
                                                                              @endforeach
                                                                              @endif
                                                                           </tbody>
                                                                        </table>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                               <div class="modal-footer">
                                                                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                               </div>
                                                            </div>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </td>
                                                <td><span>{{ $orders['Tour']['tour_name']}}</span><br>
                                                   <span>{{ date('d M,Y',strtotime($orders['pickup_date']))}} {{ ($orders['pickup_time'])}}</span>
                                                </td>
                                                <td>{{ $orders['transaction_id']}}</td>
                                                <td>
                                                   <div class='row' style="width: 248px;">
                                                      <div class="col-6">{{ translate('amount') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['amount'] + $orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('coupon_amount') }}</div>
                                                      <div class="col-6"> {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('gst_amount') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['gst_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('admin_commission') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['admin_commission'])), currencyCode: getCurrencyCode()) }}</div>
                                                   </div>
                                                </td>
                                                <td class="text-center">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['final_amount'])), currencyCode: getCurrencyCode()) }}</td>
                                             </tr>
                                             @endforeach
                                             @endif
                                          </tbody>
                                          <tfoot>
                                             <tr>
                                                <td colspan='5'></td>
                                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ( \App\Models\TourOrder::where('status',1)->where('drop_status',1)->where('cab_assign',$getData['id'])->sum('final_amount')) ), currencyCode: getCurrencyCode()) }}</td>
                                             </tr>
                                          </tfoot>
                                       </table>
                                       @if(count($complete_order)==0)
                                       <div class="text-center p-4">
                                          <img class="mb-3 w-160"
                                             src="{{dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg')}}"
                                             alt="{{translate('image_description')}}">
                                          <p class="mb-0">{{translate('no_data_to_show')}}</p>
                                       </div>
                                       @endif
                                    </div>
                                 </div>
                                 <div class="table-responsive mt-4">
                                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                                       {{ $complete_order->links() }}
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="tab-pane fade" id="pending-order-content">
                  <div class="row">
                     <div class="content container-fluid">
                        <div class="mb-3">
                           <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                              <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png')}}" alt="">
                              {{translate('complete_order')}}
                           </h2>
                        </div>
                        <div class="row">
                           <div class="col-md-12">
                              <div class="card">
                                 <div class="px-3 py-4">
                                    <div class="row align-items-center">
                                       <div class="col-lg-4">
                                       </div>
                                       <div class="col-lg-8 mt-3 mt-lg-0 d-flex gap-3 justify-content-lg-end">
                                       </div>
                                    </div>
                                 </div>
                                 <div id="status-wise-view">
                                    <div class="table-responsive">
                                       <table id="datatable"
                                          class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                          <thead class="thead-light thead-50 text-capitalize">
                                             <tr>
                                                <th>{{translate('SL')}}</th>
                                                <th>{{translate('customer_info')}}</th>
                                                <th>{{translate('tour_info') }}</th>
                                                <th>{{translate('TXN_ID')}}</th>
                                                <th>{{translate('amount')}}</th>
                                                <th class="text-center">{{translate('final_amount')}}</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             @if(!empty($orderStatus['pending']) && count($orderStatus['pending']) > 0)
                                             @foreach($orderStatus['pending'] as $key=>$orders)
                                             <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                   <div>
                                                      <small>{{ $orders['userData']['name'] ?? '' }}</small><br>
                                                      <small>{{ $orders['userData']['phone'] ?? '' }}</small><br>
                                                      <small>{{ date('d M,Y h:i A', strtotime($orders['created_at'] ?? '')) }}</small><br>
                                                      <a class="btn btn-sm btn-outline-info" onclick="$('.modelopen_{{ $key }}').modal()">view package</a><br>
                                                      <div class="modal modelopen_{{ $key }}" tabindex="-1">
                                                         <div class="modal-dialog">
                                                            <div class="modal-content">
                                                               <div class="modal-header">
                                                                  <h5 class="modal-title">
                                                                     {{ $orders['Tour']['tour_name'] ?? '' }}
                                                                  </h5>
                                                                  <button type="button" class="close"
                                                                     data-dismiss="modal" aria-label="Close">
                                                                     <span aria-hidden="true">&times;</span>
                                                                  </button>
                                                               </div>
                                                               <div class="modal-body">
                                                                  <div class="row">
                                                                     <div class="col-12">
                                                                        <table class="table">
                                                                           <thead>
                                                                              <tr>
                                                                                 <td>Name</td>
                                                                                 <td>qty</td>
                                                                                 <td>price</td>
                                                                              </tr>
                                                                           </thead>
                                                                           <tbody>
                                                                              @if (!empty($orders['booking_package']) && json_decode($orders['booking_package'], true))
                                                                              @foreach (json_decode($orders['booking_package'], true) as $p_info)
                                                                              <tr>
                                                                                 <td>
                                                                                    @if ($p_info['type'] == 'cab')
                                                                                    @php $tourPackages = \App\Models\TourCab::where('id', $p_info['id'])->first(); @endphp
                                                                                    @elseif($p_info['type'] == 'other')
                                                                                    @php $tourPackages = \App\Models\TourPackage::where('id', $p_info['id'])->first(); @endphp
                                                                                    @elseif($p_info['type'] == 'ex_distance')
                                                                                    @php $tourPackages = ['name'=>"Ex distance"] @endphp
                                                                                    @endif
                                                                                    <div
                                                                                       class="col-3 text-left">
                                                                                       @if ($p_info['type'] == 'cab')
                                                                                       <img src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($tourPackages['image'] ?? ''), type: 'backend-product') }}"
                                                                                          class="img-fluid img-thumbnail">
                                                                                       @elseif($p_info['type'] == 'other')
                                                                                       <img src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product') }}"
                                                                                          class="img-fluid img-thumbnail">
                                                                                       @endif
                                                                                    </div>
                                                                                    <span
                                                                                       class="font-weight-bold">
                                                                                       {{ $tourPackages['name'] ?? '' }}
                                                                                    </span>
                                                                                 </td>
                                                                                 <td>{{ $p_info['qty'] }}
                                                                                 </td>
                                                                                 <td>{{ $p_info['price'] }}
                                                                                 </td>
                                                                              </tr>
                                                                              @endforeach
                                                                              @endif
                                                                           </tbody>
                                                                        </table>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                               <div class="modal-footer">
                                                                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                               </div>
                                                            </div>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </td>
                                                <td><span>{{ $orders['Tour']['tour_name']}}</span><br>
                                                   <span>{{ date('d M,Y',strtotime($orders['pickup_date']))}} {{ ($orders['pickup_time'])}}</span>
                                                </td>
                                                <td>{{ $orders['transaction_id']}}</td>
                                                <td>
                                                   <div class='row' style="width: 248px;">
                                                      <div class="col-6">{{ translate('amount') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['amount'] + $orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('coupon_amount') }}</div>
                                                      <div class="col-6"> {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('gst_amount') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['gst_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('admin_commission') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['admin_commission'])), currencyCode: getCurrencyCode()) }}</div>
                                                   </div>
                                                </td>
                                                <td class="text-center">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['final_amount'])), currencyCode: getCurrencyCode()) }}</td>
                                             </tr>
                                             @endforeach
                                             @endif
                                          </tbody>
                                          <tfoot>
                                             <tr>
                                                <td colspan='5'></td>
                                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ( \App\Models\TourOrder::where('status',1)->where('drop_status',1)->where('cab_assign',$getData['id'])->sum('final_amount')) ), currencyCode: getCurrencyCode()) }}</td>
                                             </tr>
                                          </tfoot>
                                       </table>
                                       @if(count($complete_order)==0)
                                       <div class="text-center p-4">
                                          <img class="mb-3 w-160"
                                             src="{{dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg')}}"
                                             alt="{{translate('image_description')}}">
                                          <p class="mb-0">{{translate('no_data_to_show')}}</p>
                                       </div>
                                       @endif
                                    </div>
                                 </div>
                                 <div class="table-responsive mt-4">
                                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                                       {{ $complete_order->links() }}
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="tab-pane fade" id="confirm-order-content">
                  <div class="row">
                     <div class="content container-fluid">
                        <div class="mb-3">
                           <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                              <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png')}}" alt="">
                              {{translate('complete_order')}}
                           </h2>
                        </div>
                        <div class="row">
                           <div class="col-md-12">
                              <div class="card">
                                 <div class="px-3 py-4">
                                    <div class="row align-items-center">
                                       <div class="col-lg-4">
                                       </div>
                                       <div class="col-lg-8 mt-3 mt-lg-0 d-flex gap-3 justify-content-lg-end">
                                       </div>
                                    </div>
                                 </div>
                                 <div id="status-wise-view">
                                    <div class="table-responsive">
                                       <table id="datatable"
                                          class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                          <thead class="thead-light thead-50 text-capitalize">
                                             <tr>
                                                <th>{{translate('SL')}}</th>
                                                <th>{{translate('customer_info')}}</th>
                                                <th>{{translate('tour_info') }}</th>
                                                <th>{{translate('TXN_ID')}}</th>
                                                <th>{{translate('amount')}}</th>
                                                <th class="text-center">{{translate('final_amount')}}</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             @if(!empty($orderStatus['confirmed']) && count($orderStatus['confirmed']) > 0)
                                             @foreach($orderStatus['confirmed'] as $key=>$orders)
                                             <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                   <div>
                                                      <small>{{ $orders['userData']['name'] ?? '' }}</small><br>
                                                      <small>{{ $orders['userData']['phone'] ?? '' }}</small><br>
                                                      <small>{{ date('d M,Y h:i A', strtotime($orders['created_at'] ?? '')) }}</small><br>
                                                      <a class="btn btn-sm btn-outline-info" onclick="$('.modelopen_{{ $key }}').modal()">view package</a><br>
                                                      <div class="modal modelopen_{{ $key }}" tabindex="-1">
                                                         <div class="modal-dialog">
                                                            <div class="modal-content">
                                                               <div class="modal-header">
                                                                  <h5 class="modal-title">
                                                                     {{ $orders['Tour']['tour_name'] ?? '' }}
                                                                  </h5>
                                                                  <button type="button" class="close"
                                                                     data-dismiss="modal" aria-label="Close">
                                                                     <span aria-hidden="true">&times;</span>
                                                                  </button>
                                                               </div>
                                                               <div class="modal-body">
                                                                  <div class="row">
                                                                     <div class="col-12">
                                                                        <table class="table">
                                                                           <thead>
                                                                              <tr>
                                                                                 <td>Name</td>
                                                                                 <td>qty</td>
                                                                                 <td>price</td>
                                                                              </tr>
                                                                           </thead>
                                                                           <tbody>
                                                                              @if (!empty($orders['booking_package']) && json_decode($orders['booking_package'], true))
                                                                              @foreach (json_decode($orders['booking_package'], true) as $p_info)
                                                                              <tr>
                                                                                 <td>
                                                                                    @if ($p_info['type'] == 'cab')
                                                                                    @php $tourPackages = \App\Models\TourCab::where('id', $p_info['id'])->first(); @endphp
                                                                                    @elseif($p_info['type'] == 'other')
                                                                                    @php $tourPackages = \App\Models\TourPackage::where('id', $p_info['id'])->first(); @endphp
                                                                                    @elseif($p_info['type'] == 'ex_distance')
                                                                                    @php $tourPackages = ['name'=>"Ex distance"] @endphp
                                                                                    @endif
                                                                                    <div
                                                                                       class="col-3 text-left">
                                                                                       @if ($p_info['type'] == 'cab')
                                                                                       <img src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($tourPackages['image'] ?? ''), type: 'backend-product') }}"
                                                                                          class="img-fluid img-thumbnail">
                                                                                       @elseif($p_info['type'] == 'other')
                                                                                       <img src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product') }}"
                                                                                          class="img-fluid img-thumbnail">
                                                                                       @endif
                                                                                    </div>
                                                                                    <span
                                                                                       class="font-weight-bold">
                                                                                       {{ $tourPackages['name'] ?? '' }}
                                                                                    </span>
                                                                                 </td>
                                                                                 <td>{{ $p_info['qty'] }}
                                                                                 </td>
                                                                                 <td>{{ $p_info['price'] }}
                                                                                 </td>
                                                                              </tr>
                                                                              @endforeach
                                                                              @endif
                                                                           </tbody>
                                                                        </table>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                               <div class="modal-footer">
                                                                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                               </div>
                                                            </div>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </td>
                                                <td><span>{{ $orders['Tour']['tour_name']}}</span><br>
                                                   <span>{{ date('d M,Y',strtotime($orders['pickup_date']))}} {{ ($orders['pickup_time'])}}</span>
                                                </td>
                                                <td>{{ $orders['transaction_id']}}</td>
                                                <td>
                                                   <div class='row' style="width: 248px;">
                                                      <div class="col-6">{{ translate('amount') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['amount'] + $orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('coupon_amount') }}</div>
                                                      <div class="col-6"> {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('gst_amount') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['gst_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('admin_commission') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['admin_commission'])), currencyCode: getCurrencyCode()) }}</div>
                                                   </div>
                                                </td>
                                                <td class="text-center">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['final_amount'])), currencyCode: getCurrencyCode()) }}</td>
                                             </tr>
                                             @endforeach
                                             @endif
                                          </tbody>
                                          <tfoot>
                                             <tr>
                                                <td colspan='5'></td>
                                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ( \App\Models\TourOrder::where('status',1)->where('drop_status',1)->where('cab_assign',$getData['id'])->sum('final_amount')) ), currencyCode: getCurrencyCode()) }}</td>
                                             </tr>
                                          </tfoot>
                                       </table>
                                       @if(count($complete_order)==0)
                                       <div class="text-center p-4">
                                          <img class="mb-3 w-160"
                                             src="{{dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg')}}"
                                             alt="{{translate('image_description')}}">
                                          <p class="mb-0">{{translate('no_data_to_show')}}</p>
                                       </div>
                                       @endif
                                    </div>
                                 </div>
                                 <div class="table-responsive mt-4">
                                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                                       {{ $complete_order->links() }}
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="tab-pane fade" id="pickup-order-content">
                  <div class="row">
                     <div class="content container-fluid">
                        <div class="mb-3">
                           <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                              <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png')}}" alt="">
                              {{translate('complete_order')}}
                           </h2>
                        </div>
                        <div class="row">
                           <div class="col-md-12">
                              <div class="card">
                                 <div class="px-3 py-4">
                                    <div class="row align-items-center">
                                       <div class="col-lg-4">
                                       </div>
                                       <div class="col-lg-8 mt-3 mt-lg-0 d-flex gap-3 justify-content-lg-end">
                                       </div>
                                    </div>
                                 </div>
                                 <div id="status-wise-view">
                                    <div class="table-responsive">
                                       <table id="datatable"
                                          class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                          <thead class="thead-light thead-50 text-capitalize">
                                             <tr>
                                                <th>{{translate('SL')}}</th>
                                                <th>{{translate('customer_info')}}</th>
                                                <th>{{translate('tour_info') }}</th>
                                                <th>{{translate('TXN_ID')}}</th>
                                                <th>{{translate('amount')}}</th>
                                                <th class="text-center">{{translate('final_amount')}}</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             @if(!empty($orderStatus['pickup']) && count($orderStatus['pickup']) > 0)
                                             @foreach($orderStatus['pickup'] as $key=>$orders)
                                             <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                   <div>
                                                      <small>{{ $orders['userData']['name'] ?? '' }}</small><br>
                                                      <small>{{ $orders['userData']['phone'] ?? '' }}</small><br>
                                                      <small>{{ date('d M,Y h:i A', strtotime($orders['created_at'] ?? '')) }}</small><br>
                                                      <a class="btn btn-sm btn-outline-info" onclick="$('.modelopen_{{ $key }}').modal()">view package</a><br>
                                                      <div class="modal modelopen_{{ $key }}" tabindex="-1">
                                                         <div class="modal-dialog">
                                                            <div class="modal-content">
                                                               <div class="modal-header">
                                                                  <h5 class="modal-title">
                                                                     {{ $orders['Tour']['tour_name'] ?? '' }}
                                                                  </h5>
                                                                  <button type="button" class="close"
                                                                     data-dismiss="modal" aria-label="Close">
                                                                     <span aria-hidden="true">&times;</span>
                                                                  </button>
                                                               </div>
                                                               <div class="modal-body">
                                                                  <div class="row">
                                                                     <div class="col-12">
                                                                        <table class="table">
                                                                           <thead>
                                                                              <tr>
                                                                                 <td>Name</td>
                                                                                 <td>qty</td>
                                                                                 <td>price</td>
                                                                              </tr>
                                                                           </thead>
                                                                           <tbody>
                                                                              @if (!empty($orders['booking_package']) && json_decode($orders['booking_package'], true))
                                                                              @foreach (json_decode($orders['booking_package'], true) as $p_info)
                                                                              <tr>
                                                                                 <td>
                                                                                    @if ($p_info['type'] == 'cab')
                                                                                    @php $tourPackages = \App\Models\TourCab::where('id', $p_info['id'])->first(); @endphp
                                                                                    @elseif($p_info['type'] == 'other')
                                                                                    @php $tourPackages = \App\Models\TourPackage::where('id', $p_info['id'])->first(); @endphp
                                                                                    @elseif($p_info['type'] == 'ex_distance')
                                                                                    @php $tourPackages = ['name'=>"Ex distance"] @endphp
                                                                                    @endif
                                                                                    <div
                                                                                       class="col-3 text-left">
                                                                                       @if ($p_info['type'] == 'cab')
                                                                                       <img src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($tourPackages['image'] ?? ''), type: 'backend-product') }}"
                                                                                          class="img-fluid img-thumbnail">
                                                                                       @elseif($p_info['type'] == 'other')
                                                                                       <img src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product') }}"
                                                                                          class="img-fluid img-thumbnail">
                                                                                       @endif
                                                                                    </div>
                                                                                    <span
                                                                                       class="font-weight-bold">
                                                                                       {{ $tourPackages['name'] ?? '' }}
                                                                                    </span>
                                                                                 </td>
                                                                                 <td>{{ $p_info['qty'] }}
                                                                                 </td>
                                                                                 <td>{{ $p_info['price'] }}
                                                                                 </td>
                                                                              </tr>
                                                                              @endforeach
                                                                              @endif
                                                                           </tbody>
                                                                        </table>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                               <div class="modal-footer">
                                                                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                               </div>
                                                            </div>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </td>
                                                <td><span>{{ $orders['Tour']['tour_name']}}</span><br>
                                                   <span>{{ date('d M,Y',strtotime($orders['pickup_date']))}} {{ ($orders['pickup_time'])}}</span>
                                                </td>
                                                <td>{{ $orders['transaction_id']}}</td>
                                                <td>
                                                   <div class='row' style="width: 248px;">
                                                      <div class="col-6">{{ translate('amount') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['amount'] + $orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('coupon_amount') }}</div>
                                                      <div class="col-6"> {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('gst_amount') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['gst_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                      <div class="col-6">{{ translate('admin_commission') }}</div>
                                                      <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['admin_commission'])), currencyCode: getCurrencyCode()) }}</div>
                                                   </div>
                                                </td>
                                                <td class="text-center">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['final_amount'])), currencyCode: getCurrencyCode()) }}</td>
                                             </tr>
                                             @endforeach
                                             @endif
                                          </tbody>
                                          <tfoot>
                                             <tr>
                                                <td colspan='5'></td>
                                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ( \App\Models\TourOrder::where('status',1)->where('drop_status',1)->where('cab_assign',$getData['id'])->sum('final_amount')) ), currencyCode: getCurrencyCode()) }}</td>
                                             </tr>
                                          </tfoot>
                                       </table>
                                       @if(count($complete_order)==0)
                                       <div class="text-center p-4">
                                          <img class="mb-3 w-160"
                                             src="{{dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg')}}"
                                             alt="{{translate('image_description')}}">
                                          <p class="mb-0">{{translate('no_data_to_show')}}</p>
                                       </div>
                                       @endif
                                    </div>
                                 </div>
                                 <div class="table-responsive mt-4">
                                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                                       {{ $complete_order->links() }}
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<!--  -->


@endsection

@push('script')
<script>
   let getYesWord = $('#message-yes-word').data('text');
   let getCancelWord = $('#message-cancel-word').data('text');
   $('.reject-artist_data').on('click', function() {
      let astrologerId = $(this).attr("data-id");
      Swal.fire({
         title: 'Are You Sure To ' + $(this).data('title'),
         type: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#3085d6',
         cancelButtonColor: '#d33',
         confirmButtonText: getYesWord,
         cancelButtonText: getCancelWord,
         reverseButtons: true
      }).then((result) => {
         if (result.value) {
            $('#' + astrologerId).submit();
         }
      });
   });
</script>
<script>
   "use strict";
   let messageAreYouSureDeleteThis = $('#message-are-you-sure-delete-this').data('text');
   let messageYouWillNotAbleRevertThis = $('#message-you-will-not-be-able-to-revert-this').data('text');

   // Handle delete button click
   $('.tour_package-delete-button').on('click', function() {
      let packageId = $(this).attr("id");
      Swal.fire({
         title: messageAreYouSureDeleteThis,
         text: messageYouWillNotAbleRevertThis,
         showCancelButton: true,
         confirmButtonColor: '#3085d6',
         cancelButtonColor: '#d33',
         confirmButtonText: getYesWord,
         cancelButtonText: getCancelWord,
         icon: 'warning',
         reverseButtons: true
      }).then((result) => {
         if (result.value) {
            // Send AJAX request to delete tour caregory
            $.ajax({
               url: $('#route-admin-tour_package-delete').data('url'),
               method: 'POST',
               data: {
                  _token: '{{ csrf_token() }}',
                  id: packageId
               },
               success: function(response) {
                  // Show success message
                  toastr.success("{{translate('Tour_traveller_cab_deleted')}}", '', {
                     positionClass: 'toast-bottom-left'
                  });
                  // Reload the page
                  location.reload();
               },
               error: function(xhr, status, error) {
                  // Show error message
                  toastr.error(xhr.responseJSON.message);
               }
            });
         }
      });
   });

   $('.tour_driver-delete-button').on('click', function() {
      let packageId = $(this).attr("id");
      Swal.fire({
         title: messageAreYouSureDeleteThis,
         text: messageYouWillNotAbleRevertThis,
         showCancelButton: true,
         confirmButtonColor: '#3085d6',
         cancelButtonColor: '#d33',
         confirmButtonText: getYesWord,
         cancelButtonText: getCancelWord,
         icon: 'warning',
         reverseButtons: true
      }).then((result) => {
         if (result.value) {
            // Send AJAX request to delete tour caregory
            $.ajax({
               url: $('#route-admin-tour_driver-delete').data('url'),
               method: 'POST',
               data: {
                  _token: '{{ csrf_token() }}',
                  id: packageId
               },
               success: function(response) {
                  // Show success message
                  toastr.success("{{translate('Tour_traveller_driver_deleted')}}", '', {
                     positionClass: 'toast-bottom-left'
                  });
                  // Reload the page
                  location.reload();
               },
               error: function(xhr, status, error) {
                  // Show error message
                  toastr.error(xhr.responseJSON.message);
               }
            });
         }
      });
   });
</script>

<script>
   function validatePAN(input) {
      const panPattern = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
      const panValue = input.value.toUpperCase();

      input.value = panValue; // Ensure uppercase
      const errorSpan = document.getElementById('pan_error');

      if (!panPattern.test(panValue)) {
         errorSpan.textContent = "Please enter a valid PAN card number (e.g., ABCDE1234F).";
      } else {
         errorSpan.textContent = "";
      }
   }

   function validateAadhar(input) {
      const aadharPattern = /^\d{12}$/;
      const errorSpan = document.getElementById('aadhar_error');

      if (!aadharPattern.test(input.value)) {
         errorSpan.textContent = "Aadhar number must be exactly 12 digits.";
      } else {
         errorSpan.textContent = "";
      }
   }

   function validatePhone(input) {
      const phoneError = document.getElementById('phone_error');
      input.value = input.value.replace(/\D/g, '');

      if (input.value.length > 10) {
         input.value = input.value.slice(0, 10);
      }

      if (input.value.length < 10) {
         phoneError.textContent = 'Phone number must be exactly 10 digits.';
      } else {
         phoneError.textContent = '';
      }
   }

   function validateDob(input) {
      const dobError = document.getElementById('date_of_brith_error');
      const dob = new Date(input.value);
      const today = new Date();
      if (isNaN(dob.getTime())) {
         dobError.textContent = 'Invalid date. Please enter a valid date.';
         return;
      }
      if (dob > today) {
         dobError.textContent = 'Date of birth cannot be in the future.';
         return;
      }
      const age = today.getFullYear() - dob.getFullYear();
      if (age < 18) {
         dobError.textContent = 'You must be at least 18 years old.';
         return;
      }
      dobError.textContent = '';
   }
</script>
@endpush