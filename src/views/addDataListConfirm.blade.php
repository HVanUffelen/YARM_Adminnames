@extends('layouts.app')
{{App()->setLocale(Session::get('userLanguage'))}}

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>@lang('Confirm import')
                <a title="Info" data-placement="top" data-toggle="popover"
                    data-trigger="hover"
                    {{-- // TODO Lang GER \\ --}}
                    data-content="@lang('Look at possible errors that may have occured during the data fetching and confirm that all data is correct. You can also add/remove data here.')"><i
                        class="fa fa-info-circle"
                        style="color: grey"></i></a>
                </a>
            </h3>
        </div>
        <div class="card-body">
            {!! Form::open(['id' => 'wikidataConfirm', 'action' => '\Yarm\Adminnames\Http\Controllers\AdminnamesDataCleaningController@confirmData', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
            @include('adminnames::inc.addDataConfirm_accordion')
        </div>
    </div>
        {!! Form::submit(__('Confirm import'), ['class'=>'btn btn-primary mt-3 float-right', 'id'=>'confirmDataImport']) !!}
    {!! Form::close() !!}
    @include('dlbt.shared.new_person_modal')
@endsection

