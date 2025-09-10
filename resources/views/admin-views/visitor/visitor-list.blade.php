@extends('layouts.back-end.app')

@section('title', translate('visitor'))

@section('content')
<div class="content container-fluid">
   <div class="mb-3">
      <h2 class="h1 mb-0 d-flex gap-2">
         <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/app.png') }}" alt="">
         {{ translate('visitor') }}
      </h2>
   </div>
   <div class="row">
      <!-- Section for displaying  visitor -->
      <div class="col-md-12">
         <div class="card">
            <div class="px-3 py-4">
               <!-- Search bar -->
               <div class="row align-items-center">
                  <div class="col-sm-4 col-md-6 col-lg-8 mb-2 mb-sm-0">
                     <h5 class="mb-0 d-flex align-items-center gap-2">{{ translate('list') }}
                      <span class="badge badge-soft-dark radius-50 fz-12">{{ $visitor->total() }}</span>
                     </h5>
                  </div>
                  <div class="col-sm-8 col-md-6 col-lg-4">
                     <form action="{{ url()->current() }}" method="GET">
                        <div class="input-group input-group-custom input-group-merge">
                           <div class="input-group-prepend">
                              <div class="input-group-text">
                                 <i class="tio-search"></i>
                              </div>
                           </div>
                           <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                             placeholder="{{ translate('search_by_name') }}"
                              aria-label="{{ translate('search_by_name') }}" required>
                           <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                        </div>
                     </form>
                  </div>
               </div>
            </div>
            <!-- Table displaying user-->
            <div class="text-start">
               <div class="table-responsive">
                  <table id="datatable"
                     class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                     <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                           <th>{{ translate('SL') }}</th>
                           <th>{{ translate('Date & Time') }}</th>
                            <th>{{ translate('IP Address') }}</th>
                            <th>{{ translate('Country') }}</th>
                            <th>{{ translate('City') }}</th>
                            <th>{{ translate('Url') }}</th>
                            <th>{{ translate('Referer') }}</th>
                        </tr>
                     </thead>
                     <tbody>
                        @foreach($visitor as $key => $data)
                            <tr>
                               <td>{{ $visitor->firstItem() + $key }}</td> 
                               <td>{{ $data->created_at->format('d-m-Y h:i A') }}</td>
                                <td>{{ $data->ip_address }}</td>
                                <td>{{ $data->country }}</td>
                                <td>{{ $data->city }}</td>
                                <td>{{ $data->url }}</td>
                                <td>{{ $data->referer }}</td>
                            </tr>
                        @endforeach
                     </tbody>
                  </table>
               </div>
            </div>
            <!-- Pagination for list -->
            <div class="table-responsive mt-4">
               <div class="d-flex justify-content-lg-end">
                  {{ $visitor->links() }}
               </div>
            </div>
            <!-- Message for no data to show -->
            @if(count($visitor) == 0)
            <div class="text-center p-4">
               <img class="mb-3 w-160"
                  src="{{ dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg') }}"
                  alt="{{ translate('image') }}">
               <p class="mb-0">{{ translate('no_data_to_show') }}</p>
            </div>
            @endif
         </div>
      </div>
   </div>
</div>

@endsection