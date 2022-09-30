@extends('layouts.app')
{{App()->setLocale(Session::get('userLanguage'))}}

 @section('content')
    <a href="{{url('dlbt/dataCleaningManual')}}" class="btn btn-info btn-sm mb-2">@lang('Go to Manual Data Cleaning')</a>
    <div class="card">
        <div class="card-header">
            <h3>@lang('Possible Duplicates')
                <a title="Info" data-placement="top" data-toggle="popover"
                    data-trigger="hover"
                    {{-- //TODO : Lang GER --}}
                    data-content="@lang('Data Cleaning list info')"><i
                        class="fa fa-info-circle"
                        style="color: grey"></i></a>
            </h3>
        </div>
        <div class="card-body pb-0">
            {!! Form::label('search', __('Show only results containing:'), ['class' => 'sr-only font-weight-bold']); !!}</h5>
            {!! Form::text('search', '', ['class' => 'form-control', 'placeholder' => __('Search by name')]) !!}
        </div>
        <div class="card-body">
            @include('adminnames::inc.duplicateNameCleaning_data')
        </div>
    </div>
    <input type="hidden" name="type" id="type" value="{{'checkDupsNames'}}"/>
    <input type="hidden" name="view" id="view" value="{{'checkDupsNames'}}"/>
@endsection
