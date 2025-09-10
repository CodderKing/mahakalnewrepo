@extends('layouts.back-end.app')

@section('title', 'muhurat_list_get')
@push('css_or_js')
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img width="20" src="{{ asset('public/assets/back-end/img/festival.png') }}" alt="">
                {{ translate('muhurat_list_get') }}
            </h2>
        </div>

        <div class="card p-4">
            <div class="row mb-3">
                <div class="col-md-3">
                    <select id="filterName" class="form-control">
                        <option value="">🔽 Filter by Muhurat Name</option>
                        @foreach ($getMuhurat->pluck('type')->unique() as $type)
                            <option value="{{ strtolower($type) }}">{{ ucwords($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <select id="filterMonth" class="form-control">
                        <option value="">🔽 Filter by Month</option>
                        @foreach (['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                            <option value="{{ strtolower($month) }}">{{ $month }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <select id="filterYear" class="form-control">
                        <option value="">🔽 Filter by Year</option>
                        @foreach ($getMuhurat->pluck('year')->unique() as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-md btn-success view-add-btn"
                        data-target="#muhuratAddModal" data-placement="bottom" title="Muhurat Add"> <i
                            class="tio-add"></i> Add New Muhurat</button>
                </div>
            </div>

            <table class="table table-bordered table-hover" id="muhuratTable">
                <thead>
                    <tr>
                        <th>SL</th>
                        <th>Muhurat Name</th>
                        <th>Muhurat Date</th>
                        <th>Muhurat Time</th>
                        <th>Nakshatra</th>
                        <th>Tithi</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($getMuhurat as $index => $row)
                        <tr id="row-{{ $row->id }}" data-name="{{ strtolower($row['type']) }}"
                            data-month="{{ strtolower(date('F', strtotime($row['titleLink']))) }}"
                            data-year="{{ $row['year'] }}">
                            <td>
                                {{ $index + 1 }}
                            </td>
                            <td class="type-text">{{ @ucwords($row['type']) }}
                                @if ($row['added_by'])
                                    <span class="badge badge-success ms-2">{{ $row['added_by'] }}</span>
                                @endif
                            </td>
                            <td class="muhuratdate-text">{{ $row['titleLink'] }}</td>
                            <td class="muhurattime-text">{{ $row['muhurat'] }}</td>
                            <td class="nakshatra-text">{{ $row['nakshatra'] }}</td>
                            <td class="tithi-text">{{ $row['tithi'] }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-info view-dates-btn"
                                    data-toggle="modal" data-id="{{ $row['id'] }}"
                                    data-muhuratdate="{{ $row['titleLink'] }}" data-muhurattime="{{ $row['muhurat'] }}"
                                    data-nakshatra="{{ $row['nakshatra'] }}" data-tithi="{{ $row['tithi'] }}"
                                    data-target="#muhuratEditModal" data-placement="bottom" title="Muhurat Edit"> <i
                                        class="tio-calendar-month"></i> Muhurat Edit</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Muhurat Add Modal -->
    <div class="modal fade" id="muhuratAddModal" tabindex="-1" aria-labelledby="muhuratAddModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="muhuratAddForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="muhuratAddModalLabel">Add Muhurat Details</h5>
                            <p class="text-muted mb-0" style="font-size: 14px;">Please Add the Muhurat details below.</p>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Muhurat Year</label>
                            <input type="text" class="form-control" id="addyear" name="year">
                        </div>
                        <div class="mb-3">
                            <label>Muhurat Name</label>
                            <input type="text" class="form-control" id="addtype" name="type">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Muhurat Date</label>
                                    <input type="text" class="form-control" id="addtitleLink" name="titleLink">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Muhurat Time</label>
                                    <input type="text" class="form-control" id="addmuhurat" name="muhurat">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Nakshatra</label>
                                    <input type="text" class="form-control" id="addnakshatra" name="nakshatra">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Tithi</label>
                                    <input type="text" class="form-control" id="addtithi" name="tithi">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Muhurat</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Muhurat Edit Modal -->
    <div class="modal fade" id="muhuratEditModal" tabindex="-1" aria-labelledby="muhuratEditModalLabel"
        aria-hidden="true">
        <div class="modal-dialog  modal-lg">
            <form id="muhuratEditForm">
                @csrf
                <input type="hidden" id="editRowId" name="id">
                <input type="hidden" id="editRowId" name="id">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="muhuratEditModalLabel">Edit Muhurat Details</h5>
                            <p class="text-muted mb-0" style="font-size: 14px;">Please update the Muhurat details below.
                            </p>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Muhurat Date</label>
                                    <input type="text" class="form-control" id="editMuhuratdate" name="titleLink">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Muhurat Time</label>
                                    <input type="text" class="form-control" id="editMuhurattime" name="muhurat">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Nakshatra</label>
                            <input type="text" class="form-control" id="editNakshatra" name="nakshatra">
                        </div>
                        <div class="mb-3">
                            <label>Tithi</label>
                            <input type="text" class="form-control" id="editTithi" name="tithi">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Muhurat</button>
                    </div>
                </div>
            </form>
        </div>
    </div>



@endsection

@push('script')
    <script src="//cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $('#muhuratTable').DataTable({
            pageLength: 10
        });
    </script>
    <script>
        //Add the Muhurat 
        $(document).on('click', '.view-add-btn', function() {
            $('#muhuratAddModal').modal('show');
        });
        $('#muhuratAddForm').on('submit', function(e) {
            e.preventDefault();

            let year = $('#addyear').val().trim();
            let type = $('#addtype').val().trim();
            let titleLink = $('#addtitleLink').val().trim();
            let muhurat = $('#addmuhurat').val().trim();
            let nakshatra = $('#addnakshatra').val().trim();
            let tithi = $('#addtithi').val().trim();

            if (!year || !type || !titleLink || !muhurat || !nakshatra || !tithi) {
                toastr.success('🔔All fields are required!!')
                return;
            }

            $.ajax({
                url: "{{ url('admin/muhurat/add-muhurat') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    toastr.success('🔔New Muhurat Add successfully!');
                    $('#muhuratAddModal').modal('hide');
                    $('#muhuratAddForm')[0].reset();
                    // Optionally refresh a table or data list
                },
                error: function(xhr) {
                    console.error(xhr);
                    toastr.error('❌ New Muhurat Add failed. Please try again.');
                }
            });
        });

        //Edit the Muhurat
        $(document).on('click', '.view-dates-btn', function() {
            const id = $(this).data('id');
            const muhuratdate = $(this).data('muhuratdate');
            const muhurattime = $(this).data('muhurattime');
            const nakshatra = $(this).data('nakshatra');
            const tithi = $(this).data('tithi');

            $('#editRowId').val(id);
            $('#editMuhurattime').val(muhurattime);
            $('#editMuhuratdate').val(muhuratdate);
            $('#editNakshatra').val(nakshatra);
            $('#editTithi').val(tithi);

            $('#muhuratEditModal').modal('show');
        });

        // Submit edit form with AJAX
        $('#muhuratEditForm').submit(function(e) {
            e.preventDefault();

            const id = $('#editRowId').val();
            const formData = {
                muhurattime: $('#editMuhurattime').val(),
                muhuratdate: $('#editMuhuratdate').val(),
                nakshatra: $('#editNakshatra').val(),
                tithi: $('#editTithi').val(),
                _token: $('input[name="_token"]').val(),
            };

            $.ajax({
                url: "{{ url('admin/muhurat/update-muhurat') }}" + '/' + id,
                type: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success('🔔 Muhurat updated successfully!');
                    $('#muhuratEditModal').modal('hide');
                    const row = $('#row-' + id);
                    row.find('.muhuratdate-text').text(formData.muhuratdate);
                    row.find('.muhurattime-text').text(formData.muhurattime);
                    row.find('.nakshatra-text').text(formData.nakshatra);
                    row.find('.tithi-text').text(formData.tithi);

                    // Update the data attributes in the edit button
                    const btn = row.find('.view-dates-btn');
                    btn.data('muhuratdate', formData.muhuratdate);
                    btn.data('muhurattime', formData.muhurattime);
                    btn.data('nakshatra', formData.nakshatra);
                    btn.data('tithi', formData.tithi);
                    // setTimeout(() => {
                    //     location.reload();
                    // }, 1000);
                },
                error: function(err) {
                    console.error(err);
                    toastr.error('❌ Update failed. Please try again.');
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            function filterTable() {
                const name = $('#filterName').val();
                const month = $('#filterMonth').val();
                const year = $('#filterYear').val();

                $('#muhuratTable tbody tr').each(function() {
                    const row = $(this);
                    const matchName = name === '' || row.data('name') === name;
                    const matchMonth = month === '' || row.data('month') === month;
                    const matchYear = year === '' || row.data('year') == year;

                    if (matchName && matchMonth && matchYear) {
                        row.show();
                    } else {
                        row.hide();
                    }
                });
            }

            $('#filterName, #filterMonth, #filterYear').on('change', filterTable);
        });
    </script>
@endpush
