@extends('admin.layouts.master')

@section('title', 'Quản Lý Hãng Điện Thoại')

@section('breadcrumb')
<ol class="breadcrumb">
    <li>
        <a href="{{ route('admin.dashboard') }}">
            <i class="fa fa-dashboard"></i> Home
        </a>
    </li>

    <li class="active">
        Quản Lý Hãng Điện Thoại
    </li>
</ol>
@endsection

@section('content')

<div class="box box-primary">

    <div class="box-header with-border">
        <h3 class="box-title">
            Danh Sách Hãng Điện Thoại
        </h3>

        <div class="box-tools pull-right">
            <a href="{{ route('admin.producer.new') }}"
               class="btn btn-success btn-sm">
                <i class="fa fa-plus"></i>
                Thêm Hãng
            </a>
        </div>
    </div>

    <div class="box-body">

        @if(session('success'))
            <div class="alert alert-success">
                <i class="fa fa-check"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fa fa-warning"></i>
                {{ session('error') }}
            </div>
        @endif

        <table class="table table-bordered table-hover">

            <thead>
                <tr>
                    <th style="width: 80px;">STT</th>
                    <th>Tên Hãng Điện Thoại</th>
                    <th style="width: 180px;">Số Sản Phẩm</th>
                    <th style="width: 180px;">Thao Tác</th>
                </tr>
            </thead>

            <tbody>

                @forelse($producers as $key => $producer)

                    <tr id="producer-{{ $producer->id }}">

                        <td>
                            {{ $key + 1 }}
                        </td>

                        <td>
                            <strong>
                                {{ $producer->name }}
                            </strong>
                        </td>

                        <td>
                            {{ $producer->products()->count() }}
                        </td>

                        <td>

                            <a href="{{ route('admin.producer.edit', $producer->id) }}"
                               class="btn btn-primary btn-sm">
                                <i class="fa fa-edit"></i>
                                Sửa
                            </a>

                            <button type="button"
                                    class="btn btn-danger btn-sm btn-delete-producer"
                                    data-id="{{ $producer->id }}">
                                <i class="fa fa-trash"></i>
                                Xóa
                            </button>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4" class="text-center">
                            Chưa có hãng điện thoại nào.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection

@section('embed-js')

<script>
$(document).on('click', '.btn-delete-producer', function () {

    let producer_id = $(this).data('id');

    if (!confirm('Bạn có chắc chắn muốn xóa hãng điện thoại này?')) {
        return;
    }

    $.ajax({
        url: "{{ route('admin.producer.delete') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            producer_id: producer_id
        },

        success: function (response) {

            alert(response.content);

            if (response.type === 'success') {
                $('#producer-' + producer_id).remove();
            }

        },

        error: function () {
            alert('Có lỗi xảy ra, vui lòng thử lại!');
        }
    });

});
</script>

@endsection