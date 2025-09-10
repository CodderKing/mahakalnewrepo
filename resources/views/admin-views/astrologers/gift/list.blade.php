@php use App\Utils\Helpers; @endphp
@extends('layouts.back-end.app')

@section('title', translate('gifts'))

@section('content')

<style>
    .imagePreview {
        max-width: 100%;
        max-height: 100px;
    }
</style>

    {{-- add modal --}}
    <div class="modal fade" id="add-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Add Gift</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <form action="" method="post">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="" class="form-label">Name</label>
                        <input type="text" name="name" id="" class="form-control"
                            placeholder="Enter Name" />
                    </div>
                    <div class="mb-3">
                        <img id="imagePreview" class="imagePreview" src="#" alt="Image Preview" style="display: none;">
                        <label for="" class="form-label">Image</label>
                        <input type="file" name="image" id="imageUpload" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label for="" class="form-label">Amount</label>
                        <input type="number" name="amount" id="" class="form-control"
                            placeholder="Enter Amount" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" class="btn btn-primary">Add Gift</button>
                </div>
            </form>
          </div>
        </div>
      </div>

    {{-- edit modal --}}
    <div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Update Gift</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <form action="" method="post">
                @csrf
                <input type="text" name="id" id="edit-id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="" class="form-label">Name</label>
                        <input type="text" name="name" id="edit-name" class="form-control"
                            placeholder="Enter Name" />
                    </div>
                    <div class="mb-3">
                        <img id="editImagePreview" class="imagePreview" src="#" alt="Image Preview" style="display: none;">
                        <label for="" class="form-label">Image</label>
                        <input type="file" name="image" id="editImageUpload" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label for="" class="form-label">Amount</label>
                        <input type="number" name="amount" id="amount" class="form-control"
                            placeholder="Enter Amount" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" class="btn btn-primary">Update Gift</button>
                </div>
            </form>
          </div>
        </div>
      </div>

    {{-- main page --}}
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/festival.png') }}"
                    alt="">
                {{ translate('gifts') }}
                {{-- <span class="badge badge-soft-dark radius-50 fz-14">{{ $festivals->total() }}</span> --}}
            </h2>
        </div>
        <div class="row mt-20">
            <div class="col-md-12">
                <div class="card">
                    <div class="px-3 py-4">
                        <div class="row g-2 flex-grow-1">
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
                                            aria-label="{{ translate('search_by_name') }}"
                                            value="{{ request('searchValue') }}" required>
                                        <button type="submit"
                                            class="btn btn--primary input-group-text">{{ translate('search') }}</button>
                                    </div>
                                </form>
                            </div>
                            @if (Helpers::modules_permission_check('Astrologer & Pandit', 'Gift', 'add'))
                            <div class="col-sm-4 col-md-6 col-lg-8 d-flex justify-content-end">
                                <button type="button" class="btn btn-outline--primary" onclick="addModal()">
                                    {{ translate('add_Gift') }}
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table
                                class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 text-start">
                                <thead class="thead-light thead-50 text-capitalize">
                                    <tr>
                                        <th>{{ translate('SL') }}</th>
                                        <th>{{ translate('Image') }}</th>
                                        <th>{{ translate('Name') }}</th>
                                        <th>{{ translate('Amount') }}</th>
                                        @if (Helpers::modules_permission_check('Astrologer & Pandit', 'Gift', 'status'))
                                        <th>{{ translate('Status') }}</th>
                                        @endif
                                        @if (Helpers::modules_permission_check('Astrologer & Pandit', 'Gift', 'edit') || Helpers::modules_permission_check('Astrologer & Pandit', 'Gift', 'delete'))
                                        <th>{{ translate('Action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td><img src="{{ url('public/assets/back-end/img/admin.jpg') }}" alt=""
                                                width="50px"></td>
                                        <td>Safal</td>
                                        <td>1000</td>
                                        @if (Helpers::modules_permission_check('Astrologer & Pandit', 'Gift', 'status'))
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="customSwitch1">
                                                <label class="custom-control-label" for="customSwitch1"></label>
                                              </div>
                                        </td>
                                        @endif
                                        @if (Helpers::modules_permission_check('Astrologer & Pandit', 'Gift', 'edit') || Helpers::modules_permission_check('Astrologer & Pandit', 'Gift', 'delete'))
                                        <td>
                                            <div class="d-flex justify-content-start gap-2">
                                                @if (Helpers::modules_permission_check('Astrologer & Pandit', 'Gift', 'edit'))
                                                <a class="btn btn-outline-info btn-sm square-btn"
                                                    title="{{ translate('edit') }}" href="javascript:0" data-id="1" data-image="{{ url('public/assets/back-end/img/admin.jpg') }}" data-name="safal" data-amount="1000" onclick="editModal(this)">
                                                    <i class="tio-edit"></i>
                                                </a>
                                                @endif

                                                @if (Helpers::modules_permission_check('Astrologer & Pandit', 'Gift', 'delete'))
                                                <a class="btn btn-outline-danger btn-sm delete delete-data"
                                                    href="javascript:" title="{{ translate('delete') }}">
                                                    <i class="tio-delete"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </td>
                                        @endif
                                    </tr>
                                    {{-- @foreach ($festivals as $key => $festival)
                                    <tr>
                                        <td>{{ $festivals->firstItem()+$key }}</td>
                                        <td>{{isset($festival->month) ? $festival->month->month : translate('month_not_found') }}

                                        </td>
                                        <td>{{ $festival->festival_date }}</td>

                                        <td>
                                            <div class="avatar-60 d-flex align-items-center rounded">
                                                <img class="img-fluid" alt=""
                                                     src="{{ getValidImage(path: 'storage/app/public/festival-img/'.$festival['festival_image'], type: 'backend-festival') }}">
                                            </div>
                                        </td>
                                        <td>{{ $festival->title }}</td>
                                        <td>{{ $festival->tithi }}</td>
                                        <td class="overflow-hidden max-width-100px">
                                            <span data-toggle="tooltip" data-placement="right" title="{{$festival['detail']}}">
                                                 {!! Str::limit($festival['detail'],20) !!}
                                            </span>
                                        </td>
                                        <td>
                                            <form action="{{route('admin.festival.status-update') }}" method="post" id="festival-status{{$festival['id']}}-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{$festival['id']}}">
                                                <label class="switcher mx-auto">
                                                    <input type="checkbox" class="switcher_input toggle-switch-message" name="status"
                                                           id="festival-status{{ $festival['id'] }}" value="1" {{ $festival['status'] == 1 ? 'checked' : '' }}
                                                           data-modal-id = "toggle-status-modal"
                                                           data-toggle-id = "festival-status{{ $festival['id'] }}"
                                                           data-on-image = "festival-status-on.png"
                                                           data-off-image = "festival-status-off.png"
                                                           data-on-title = "{{ translate('Want_to_Turn_ON').' '.$festival['defaultname'].' '. translate('status') }}"
                                                           data-off-title = "{{ translate('Want_to_Turn_OFF').' '.$festival['defaultname'].' '.translate('status') }}"
                                                           data-on-message = "<p>{{ translate('if_enabled_this_festival_will_be_available_on_the_website_and_customer_app') }}</p>"
                                                           data-off-message = "<p>{{ translate('if_disabled_this_festival_will_be_hidden_from_the_website_and_customer_app') }}</p>">
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a class="btn btn-outline-info btn-sm square-btn" title="{{ translate('edit') }}"
                                                    href="{{ route('admin.festival.update', [$festival['id']]) }}">
                                                    <i class="tio-edit"></i>
                                                </a>
                                                <a class="btn btn-outline-danger btn-sm delete delete-data" href="javascript:"
                                                data-id="festival-{{$festival['id']}}"
                                                title="{{ translate('delete')}}">
                                                <i class="tio-delete"></i>
                                            </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="table-responsive mt-4">
                        <div class="d-flex justify-content-lg-end">
                            {{-- {{ $festivals->links() }} --}}
                        </div>
                    </div>
                    {{-- @if (count($festivals) == 0)
                        <div class="text-center p-4">
                            <img class="mb-3 w-160" src="{{ dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg') }}" alt="">
                            <p class="mb-0">{{ translate('no_data_to_show') }}</p>
                        </div>
                    @endif --}}
                </div>
            </div>
        </div>
    </div>
    <span id="route-admin-festival-delete" data-url="{{ route('admin.festival.delete') }}"></span>
    <span id="route-admin-festival-status-update" data-url="{{ route('admin.festival.status-update') }}"></span>
    {{-- <span id="get-festivals" data-festivals="{{ json_encode($festivals) }}"></span> --}}
    <div class="modal fade" id="select-festival-modal" tabindex="-1" aria-labelledby="toggle-modal"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i
                            class="tio-clear"></i></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0 pb-sm-5">
                    <div class="d-flex flex-column align-items-center text-center gap-2 mb-2">
                        <div
                            class="toggle-modal-img-box d-flex flex-column justify-content-center align-items-center mb-3 position-relative">
                            <img src="{{ dynamicAsset('public/assets/back-end/img/icons/info.svg') }}" alt=""
                                width="90" />
                        </div>
                        <h5 class="modal-title mb-2 festival-title-message"></h5>
                    </div>
                    <form action="{{ route('admin.festival.delete') }}" method="post"
                        class="product-festival-update-form-submit">
                        @csrf
                        <input name="id" hidden="">
                        <div class="gap-2 mb-3">
                            <label class="title-color" for="exampleFormControlSelect1">{{ translate('select_Category') }}
                                <span class="text-danger">*</span>
                            </label>
                            <select name="festival_id" class="form-control js-select2-custom festival-option" required>

                            </select>
                        </div>
                        <div class="d-flex justify-content-center gap-3">
                            <button type="submit" class="btn btn--primary min-w-120">{{ translate('update') }}</button>
                            <button type="button" class="btn btn-danger-light min-w-120"
                                data-dismiss="modal">{{ translate('cancel') }}</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/products-management.js') }}"></script>

    {{-- add image preveiw --}}
    <script>
        const input = document.getElementById('imageUpload');
        const preview = document.getElementById('imagePreview');
    
        input.addEventListener('change', function(event) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function() {
                    preview.src = reader.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        });
    </script>

    {{-- edit image preveiw --}}
    <script>
        const editInput = document.getElementById('editImageUpload');
        const editPreview = document.getElementById('editImagePreview');
    
        editInput.addEventListener('change', function(event) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function() {
                    editPreview.src = reader.result;
                    editPreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                editPreview.src = '#';
                editPreview.style.display = 'none';
            }
        });
    </script>

    {{-- add modal --}}
    <script>
        function addModal() {
            $('#add-modal').modal('show');
        }
    </script>

    {{-- edit modal --}}
    <script>
        function editModal(that) {
            var id = $(that).attr('data-id');
            var name = $(that).attr('data-name');
            var image = $(that).attr('data-image');
            var amount = $(that).attr('data-amount');
            $('#edit-id').val(id);
            $('#edit-name').val(name);
            $('#edit-amount').val(amount);
            if(image){
                $('#editImagePreview').css('display', 'block');
                $('#editImagePreview').attr('src',image);
            }
            $('#edit-modal').modal('show');
        }
    </script>
@endpush
