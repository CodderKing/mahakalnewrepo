<div class="col-md-12 mb-3 d-none cab-add-div">
    <div class="card">
        <div class="card-header">
            <div class="col-12">
                <a class="btn btn-primary float-end btn-sm" onclick="$('.cab-add-div').addClass('d-none');$('.cab-list-show-div').removeClass('d-none')">Cab List</a>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.tour_and_travels.cab.cab-store') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group form-system-language-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="title-color" for="name">{{ translate('select_cab') }}<span class="text-danger">*</span></label>
                                    <select name="cab_id" class="form-control">
                                        <option value="">{{ translate('select_cab') }}</option>
                                        @if($carlists)
                                        @foreach($carlists as $va)
                                        <option value="{{ $va['id']}}" {{ ((old('cab_id') == $va['id'] )?"selected" :"" ) }}>{{ $va['name'] }}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="title-color" for="reg_number">{{ translate('reg_number') }}</label>
                                    <input type="text" name="reg_number" value="{{old('reg_number')}}" class="form-control" placeholder="{{ translate('enter_register_number') }}" required>
                                    <input type="hidden" name="traveller_id" value="{{ $getData['id'] }}">
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

<div class="col-md-12 cab-list-show-div">
    <div class="card">
        <div class="card-header">
            <div class="col-12">
                <a class="btn btn-primary float-end btn-sm" onclick="$('.cab-add-div').removeClass('d-none');$('.cab-list-show-div').addClass('d-none')">Add Cab</a>
            </div>
        </div>
        <div class="px-3 py-4">
            <!-- Search bar -->
            <div class="row align-items-center">
                <div class="col-sm-4 col-md-6 col-lg-8 mb-2 mb-sm-0">
                    <h5 class="mb-0 d-flex align-items-center gap-2">{{ translate('Cab_list') }}
                        <span class="badge badge-soft-dark radius-50 fz-12">{{ $cabDetails->total() ?? '' }}</span>
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
                        @foreach($cabDetails as $key => $items)
                        <tr>
                            <td>{{$cabDetails->firstItem()+$key}}</td>
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
                                <form action="{{route('admin.tour_and_travels.cab.cab_status-update') }}" method="post" id="items-status{{$items['id']}}-form">
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
                                    <a class="btn btn-outline-info btn-sm square-btn" title="{{ translate('edit') }}" href="{{route('admin.tour_and_travels.cab.cab-update',[$items['id']])}}">
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
        <div class="table-responsive mt-4">
            <div class="d-flex justify-content-lg-end">
                {!! $cabDetails->links() !!}
            </div>
        </div>
        <!-- Message for no data to show -->
        @if(count($cabDetails) == 0)
        <div class="text-center p-4">
            <img class="mb-3 w-160"
                src="{{ dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg') }}"
                alt="{{ translate('image') }}">
            <p class="mb-0">{{ translate('no_data_to_show') }}</p>
        </div>
        @endif
    </div>
</div>


<span id="route-admin-tour_package-delete" data-url="{{ route('admin.tour_and_travels.cab.traveller-cab-delete') }}"></span>