@extends('admin.layouts.master')

@section('title', 'Sửa Hãng Điện Thoại')

@section('breadcrumb')
<ol class="breadcrumb">

    <li>
        <a href="{{ route('admin.dashboard') }}">
            <i class="fa fa-dashboard"></i> Home
        </a>
    </li>

    <li>
        <a href="{{ route('admin.producer.index') }}">
            Quản Lý Hãng Điện Thoại
        </a>
    </li>

    <li class="active">
        Sửa Hãng
    </li>

</ol>
@endsection

@section('content')

<div class="box box-primary">

    <div class="box-header with-border">
        <h3 class="box-title">
            Sửa Hãng Điện Thoại
        </h3>
    </div>

    <form action="{{ route('admin.producer.update', $producer->id) }}"
          method="POST">

        @csrf

        <div class="box-body">

            <div class="form-group">

                <label>
                    Tên Hãng Điện Thoại
                    <span class="text-red">*</span>
                </label>

                <input type="text"
                       name="name"
                       class="form-control"
                       value="{{ old('name', $producer->name) }}"
                       required>

                @error('name')
                    <span class="text-red">
                        {{ $message }}
                    </span>
                @enderror

            </div>

        </div>

        <div class="box-footer">

            <button type="submit"
                    class="btn btn-success pull-right">
                <i class="fa fa-save"></i>
                Cập Nhật
            </button>

            <a href="{{ route('admin.producer.index') }}"
               class="btn btn-danger pull-right"
               style="margin-right: 5px;">
                <i class="fa fa-ban"></i>
                Hủy
            </a>

        </div>

    </form>

</div>

@endsection