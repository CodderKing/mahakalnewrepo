@extends('layouts.back-end.app')

@section('title', translate('Cab Update'))
@push('css_or_js')
<style>
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/') }}" alt="">
            {{ translate('Cab Update') }}
        </h2>
    </div>
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.tour_and_travels.cab.cab-edit') }}" method="post" enctype="multipart/form-data">
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
                                            <option value="{{ $va['id']}}" {{ ((old('cab_id',$traveller_data['cab_id']) == $va['id'] )?"selected" :"" ) }}>{{ $va['name'] }}</option>
                                            @endforeach
                                            @endif
                                        </select>
                                        <input type="hidden" name="id" value="{{ $traveller_data['id'] }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="title-color" for="reg_number">{{ translate('reg_number') }}</label>
                                        <input type="text" name="reg_number" value="{{old('reg_number',$traveller_data['reg_number'])}}" class="form-control" placeholder="{{ translate('enter_register_number') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="title-color" for="model_number">{{ translate('model_number') }}</label>
                                        <input type="text" name="model_number" value="{{old('model_number',$traveller_data['model_number']) }}" class="form-control" placeholder="{{ translate('enter_model_number') }}" required>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="text-center">
                                <img class="upload-img-view" id="detail-viewer" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_cab/'.$traveller_data['image'], type: 'backend-product')  }}" alt="">
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
                                        class="custom-file-input image-preview-before-upload" data-preview="#detail-viewer" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
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
</div>

<!--  -->


@endsection

@push('script')
@endpush