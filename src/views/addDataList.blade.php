@extends('layouts.app')
{{App()->setLocale(Session::get('userLanguage'))}}

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>@lang('Import VIAF -and WIKI data')
                <a title="Info" data-placement="top" data-toggle="popover"
                   data-trigger="hover"
                   {{-- // TODO Lang GER \\ --}}
                   data-content="@lang('Get VIAF -and WIKI data for all the records listed below by pressing the "Get data" button at the bottom.')"><i
                        class="fa fa-info-circle"
                        style="color: grey"></i></a>
                </a>
            </h3>
        </div>
        <div class="card-body">
            @include('adminnames::inc.addData_data')
        </div>
    </div>
    {!! Form::open(['id' => 'wikidataFill', 'action' => '\Yarm\Adminnames\Http\Controllers\AdminnamesDataCleaningController@getData', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
        {!! Form::hidden('data', null, ['id' => 'wikidata_json', 'type' => 'json']) !!}
        {!! Form::button(__('Get data'),['class'=>'btn btn-primary mt-3 float-right', 'id'=>'getData']) !!}
    {!! Form::close() !!}
@endsection
