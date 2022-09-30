@extends('layouts.app')
{{App()->setLocale(Session::get('userLanguage'))}}

 @section('content')
    <a href="{{url('dlbt/dataCleaningList')}}" class="btn btn-info text-light btn-sm mb-3">@lang('Go to list of possible duplicates')</a>
    <div class="card">
        <div class="card-header">
            <h3>@lang('Data Cleaning')
                <a title="Info" data-placement="top" data-toggle="popover"
                    data-trigger="hover"
                    {{-- // TODO Lang GER \\ --}}
                    data-content="@lang('Data Cleaning manual info')"><i
                        class="fa fa-info-circle"
                        style="color: grey"></i></a>
                <a href="{{url('dlbt/dataCleaningManual')}}" class="btn btn-danger rounded-circle float-right"
                    data-placement="top" data-toggle="popover"
                    data-trigger="hover" data-content="@lang('Clear and refresh data cleaning')">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </a>
            </h3>
        </div>
        <div class="card-body">
            {!! Form::open(['id' => 'dataCleaning', 'action' => '\Yarm\Adminnames\Http\Controllers\AdminnamesDataCleaningController@clean', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
            @include('adminnames::inc.duplicateNameCleaningManual_persons')
            <div class="pb-5 mt-3">
            {{Form::button("<i class='fa-solid fa-plus'></i> ",
         ["id"=>"btn-add-list","class"=>"m-1  float-right btn btn-success shadow-sm rounded-circle", "title"=>"Add a new name field",
         "data-toggle"=>"tooltip", "data-placement"=>"right"])}}
            </div>
            <div id="dataCleaningErrors" class="alert alert-danger mt-3 mb-0" role="alert" hidden>
                <ul class="mb-0 pl-3">
                    <li id="dataCleaningRadioButtonError" hidden>@lang('Please select a name').</li>
                    <li id="dataCleaningTwoNamesError" hidden>@lang('Please add at least one other name').</li>
                    <li id="dataCleaningIdError" hidden>@lang('Please make sure every item is a name from the list or a new name').</li>
                    <li id="dataCleaningNoNewNameError" hidden>@lang('Please give the new item(s) a name').</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex flex-wrap justify-content-between">
        {{Form::button("<i class='fa-solid fa-folder-plus'></i>  ".__('Add new name'),['class'=>'m-1 center-block btn btn-success', 'id'=>'btn-add-new'])}}
        {{Form::submit(__('Change names to selected'),['class'=>'m-1 float-right btn btn-primary', 'id'=>'btn-data-clean', 'disabled'=>true])}}
        {!! Form::close() !!}
    </div>

    @include('dlbt.shared.new_person_modal')
@endsection
