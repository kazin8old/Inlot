@extends('admin.layouts.app')

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-4">
                <h3 class="title">Редактирование администратора</h3>
            </div>
            <div class="col-md-8">
                <p></p>
                <a href="{{ route('admin.administrators.index') }}">
                    <button class="btn bg-gray btn-flat margin">
                        <i class="fa fa-undo margin-r-5"></i>
                        Назад
                    </button>
                </a>
            </div>
        </div>
        @if (Session::has('success_message'))
            <div class="alert alert-info">{{ Session::get('success_message') }} </div>
            @endif
        <!-- Default box -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-success">
                    <div class="box-body">
                        {!!
                        Form::model(
                            $administrator,
                            ['route' => ['admin.administrators.update', $administrator->id],
                            'method' => 'patch']
                        )
                        !!}
                            @include('admin.administrators.form')
                        {{ Form::close() }}
                    </div><!-- /.box-body -->
                </div><!-- /.box -->
            </div>
        </div>

    </section><!-- /.content -->
@endsection