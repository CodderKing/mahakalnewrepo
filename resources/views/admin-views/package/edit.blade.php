@extends('layouts.back-end.app')

@section('title', translate('package_Update'))

@section('content')
    <div class="content container-fluid">

        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 align-items-center d-flex gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/package.png') }}" alt="">
                {{ translate('package_Update') }}
            </h2>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-start">
                        <form action="{{ route('admin.package.update', [$package['id']]) }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            {{-- <input type="hidden" name="service_id" value="{{ $package['service_id'] }}"> --}}
                            <ul class="nav nav-tabs w-fit-content mb-4">
                                @foreach ($languages as $lang)
                                    <li class="nav-item text-capitalize">
                                        <span
                                            class="nav-link form-system-language-tab cursor-pointer {{ $lang == $defaultLanguage ? 'active' : '' }}"
                                            id="{{ $lang }}-link">
                                            {{ getLanguageName($lang) . '(' . strtoupper($lang) . ')' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="row">
                                <div class="col-md-12">
                                    <div>
                                        @foreach ($languages as $lang)
                                            <?php
                                            if (count($package['translations'])) {
                                                $translate = [];
                                                foreach ($package['translations'] as $translations) {
                                                    if ($translations->locale == $lang && $translations->key == 'title') {
                                                        $translate[$lang]['title'] = $translations->value;
                                                    }
                                                    if ($translations->locale == $lang && $translations->key == 'description') {
                                                        $translate[$lang]['description'] = $translations->value;
                                                    }
                                                }
                                            }
                                            ?>
                                            <div class="{{ $lang != $defaultLanguage ? 'd-none' : '' }} form-system-language-form"
                                                id="{{ $lang }}-form">
                                                <div class="form-group">
                                                    <label class="title-color" for="title">{{ translate('title') }}
                                                        ({{ strtoupper($lang) }})
                                                    </label>
                                                    <input type="text" name="title[]"
                                                        value="{{ $lang == $defaultLanguage ? $package['title'] : $translate[$lang]['title'] ?? '' }}"
                                                        class="form-control" id="title"
                                                        placeholder="{{ translate('ex') }} : {{ translate('Title') }}"
                                                        {{ $lang == $defaultLanguage ? 'required' : '' }}>
                                                </div>
                                                <div class="form-group">
                                                    <label class="title-color"
                                                        for="description">{{ translate('description') }}
                                                        ({{ strtoupper($lang) }})</label>
                                                    <textarea name="description[]" class="form-control ckeditor" id="description"
                                                        {{ $lang == $defaultLanguage ? 'required' : '' }}>{!! $lang == $defaultLanguage ? $package['description'] : $translate[$lang]['description'] ?? '' !!}</textarea>
                                                </div>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                                        @endforeach
                                        <input name="position" value="0" class="d-none">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="title-color" for="person">{{ translate('person') }}</label>
                                                <input type="number" name="person" id="" class="form-control"
                                                    value="{{ isset($package['person']) ? $package['person'] : '' }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="title-color" for="price">{{ translate('color') }}</label>
                                                <input type="color" name="color" class="form-control"
                                                    placeholder="{{ translate('color') }}"
                                                    value="{{ isset($package['color']) ? $package['color'] : '' }}"
                                                    required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3">
                                <button type="reset" id="reset"
                                    class="btn btn-secondary px-4">{{ translate('reset') }}</button>
                                <button type="submit" class="btn btn--primary px-4">{{ translate('update') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/products-management.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/js/ckeditor.js') }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.ckeditor').ckeditor();
        });
    </script>
@endpush
