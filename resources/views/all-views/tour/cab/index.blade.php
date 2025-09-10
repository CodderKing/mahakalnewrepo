@extends('layouts.back-end.app-tour')

@section('title', translate('cab_list'))

@section('content')
<div class="content container-fluid">
   <div class="mb-3">
      <h2 class="h1 mb-0 d-flex gap-2">
         <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/') }}" alt="">
         {{ translate('cab_list') }}
      </h2>
   </div>
   <div class="row">
      <!-- Form for adding new tour_cab -->
      <div class="col-md-12 mb-3">
         <div class="card">
            <div class="card-body">
               <form action="{{ route('tour-vendor.tour_cab_management.cab-store') }}" method="post" enctype="multipart/form-data">
                  @csrf                  
                  <div class="row">
                     <div class="col-md-8">
                        <div class="form-group form-system-language-form">
                           <div class="row">
                              <div class="col-md-6">
                                 <label class="title-color" for="name">{{ translate('select_cab') }}<span class="text-danger">*</span></label>
                                 <select name="cab_id" class="form-control">
                                    <option value="">{{ translate('select_cab') }}</option>
                                    @if($cab_list)
                                    @foreach($cab_list as $va)
                                    <option value="{{ $va['id']}}" {{ ((old('cab_id') == $va['id'] )?"selected" :"" ) }}>{{ $va['name'] }}</option>
                                    @endforeach
                                    @endif
                                 </select>
                              </div>                              
                              <div class="col-md-6">
                                 <label class="title-color" for="reg_number">{{ translate('reg_number') }}</label>
                                 <input type="text" name="reg_number" value="{{old('reg_number')}}" class="form-control" placeholder="{{ translate('enter_register_number') }}" required>
                              </div>
                              <div class="col-md-6">
                                 <label class="title-color" for="model_number">{{ translate('model_number') }}</label>
                                 <input type="text" name="model_number" value="{{old('model_number') }}" class="form-control" placeholder="{{ translate('enter_model_number') }}" required>
                              </div>
                           </div>
                        </div>
                        
                     </div>
                     <div class="col-md-4 mb-4">
                        <div class="text-center">
                           <img class="upload-img-view" id="detail-viewer"
                              src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/package/def.png', type: 'backend-product')  }}"
                              alt="">
                        </div>
                        <div class="form-group">
                           <label for="detail_image" class="title-color">
                              {{ translate('thumbnail') }}<span class="text-danger">*</span>
                           </label>
                           <span class="ml-1 text-info">
                              {{ THEME_RATIO[theme_root_path()]['Brand Image'] }}
                           </span>
                           <div class="custom-file text-left">
                              <input type="file" name="image" id="image"
                                 class="custom-file-input image-preview-before-upload" data-preview="#detail-viewer"
                                 required accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                              <label class="custom-file-label" for="detail-image">
                                 {{ translate('choose_file') }}
                              </label>
                           </div>
                        </div>
                     </div>
                  </div>
                  <!-- Buttons for form actions -->
                  <div class="d-flex flex-wrap gap-2 justify-content-end">
                     <button type="reset" class="btn btn-secondary">{{ translate('reset') }}</button>
                     <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                  </div>
               </form>
            </div>
         </div>
      </div>

      <!-- Section for displaying tour categiry list -->
      <div class="col-md-12">
         <div class="card">
            <div class="px-3 py-4">
               <!-- Search bar -->
               <div class="row align-items-center">
                  <div class="col-sm-4 col-md-6 col-lg-8 mb-2 mb-sm-0">
                     <h5 class="mb-0 d-flex align-items-center gap-2">{{ translate('Package_list') }}
                        <span class="badge badge-soft-dark radius-50 fz-12">{{ $getData ? $getData->total() ?? '' : '' }}</span>
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
            <!-- Table displaying tour package -->
            <div class="text-start">
               <div class="table-responsive">
                  <table id="datatable"
                     class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                     <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                           <th>{{ translate('SL') }}</th>
                           <th>{{ translate('cab_name') }}</th>
                           <th>{{ translate('reg_number') }}</th>
                           <th>{{ translate('model_name') }}</th>
                           <th>{{ translate('image') }}</th>
                           <th>{{ translate('status') }}</th>
                           <th>{{ translate('action') }}</th>
                        </tr>
                     </thead>
                     <tbody>
                        <!-- Loop through items -->
                        @foreach($getData as $key => $items)
                        <tr>
                           <td>{{$getData->firstItem()+$key}}</td>
                           <td>{{ ($items['Cabs']['name']??"") }}</td>
                           <td>{{ $items['reg_number'] }}</td>
                           <td>{{ $items['model_number'] }}</td>
                           <td>
                              <div class="avatar-60 d-flex align-items-center rounded">
                                 <img class="img-fluid" alt="" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_cab/' . $items['image'], type: 'backend-panchang') }}">
                              </div>
                           </td>
                           <td>
                              <!-- Form for toggling status -->
                              <form action="{{route('tour-vendor.tour_cab_management.cab_status-update') }}" method="post" id="items-status{{$items['id']}}-form">
                                 @csrf
                                 <input type="hidden" name="id" value="{{$items['id']}}">
                                 <label class="switcher mx-auto">
                                    <input type="checkbox" class="switcher_input toggle-switch-message" name="status"
                                       id="items-status{{ $items['id'] }}" value="1"
                                       {{ $items['status'] == 1 ? 'checked' : '' }}
                                       data-modal-id="toggle-status-modal"
                                       data-toggle-id="items-status{{ $items['id'] }}"
                                       data-on-image="items-status-on.png"
                                       data-off-image="items-status-off.png"
                                       data-on-title="{{ translate('Want_to_Turn_ON').' '.($items['Cabs']['name']??'').' '. translate('status') }}"
                                       data-off-title="{{ translate('Want_to_Turn_OFF').' '.($items['Cabs']['name']??'').' '.translate('status') }}"
                                       data-on-message="<p>{{ translate('if_enabled_this_tour_traveller_cab_will_be_available_on_the_website_and_customer_app') }}</p>"
                                       data-off-message="<p>{{ translate('if_disabled_this_tour_traveller_cab_will_be_hidden_from_the_website_and_customer_app') }}</p>">
                                    <span class="switcher_control"></span>
                                 </label>
                              </form>
                           </td>
                           <td>
                              <div class="d-flex justify-content-center gap-2">
                                 <a class="btn btn-outline-info btn-sm square-btn" title="{{ translate('edit') }}" href="{{route('tour-vendor.tour_cab_management.cab-update',[$items['id']])}}">
                                    <i class="tio-edit"></i>
                                 </a>
                                 <a class="tour_package-delete-button btn btn-outline-danger btn-sm square-btn" id="{{ $items['id'] }}">
                                    <i class="tio-delete"></i>
                                 </a>
                              </div>
                           </td>
                        </tr>
                        @endforeach
                     </tbody>
                  </table>
               </div>
            </div>
            <!-- Pagination for tour package list -->
            <div class="table-responsive mt-4">
               <div class="d-flex justify-content-lg-end">
                  {!! $getData->links() !!}
               </div>
            </div>
            <!-- Message for no data to show -->
            @if(count($getData) == 0)
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
<!-- Hidden HTML element for delete route -->
<span id="route-admin-tour_package-delete" data-url="{{ route('tour-vendor.tour_cab_management.traveller-cab-delete') }}"></span>
<!-- Toast message for tour package deleted -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 5">
   <div id="panchangmoonimage-deleted-message" class="toast hide" role="alert" aria-live="assertive"
      aria-atomic="true">
      <div class="toast-body">
         {{ translate('Tour_traveller_cab_deleted') }}
      </div>
   </div>
</div>
@endsection

@push('script')
<!-- Include SweetAlert2 for confirmation dialogs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="{{ dynamicAsset(path: 'public/js/ckeditor.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script>
   "use strict";
   // Retrieve localized texts
   let getYesWord = $('#message-yes-word').data('text');
   let getCancelWord = $('#message-cancel-word').data('text');
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
</script>
@endpush