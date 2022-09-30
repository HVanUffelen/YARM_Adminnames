@php
    $cols = ['id', 'name', 'first_name', 'VIAF_id', 'WIKIDATA_id', 'ISNI_id', 'WIKIDATA_image', 'xLink', 'alternative_names',
        'gender', 'nationality', 'birth_year', 'death_year'];
@endphp
<div id="accordion">
    @foreach ($names as $index=>$name)
        @php
            if (!$name->VIAF_id && !$name->WIKIDATA_id && !$name->VIAF_multiple) {
                $color_class = 'alert-danger border-danger';
                $auto_success = 'false';
            } elseif (!$name->VIAF_id) {
                $color_class = 'alert-warning border-warning';
                $auto_success = 'pending';
            } else {
                $color_class = 'alert-success border-success';
                $auto_success = 'true';
            }
        @endphp
        <div class="card my-1" id="card{{$name->id}}">
            <div class="card-header">
                <h5 class="mb-0 d-flex flex-row">
                    <button type="button" class="btn w-100 text-dark text-left {{$color_class}}" data-toggle="collapse" data-target="#collapse{{$index}}" aria-expanded="true" aria-controls="{{$index}}">
                        <span class="font-weight-bold">{{$name->first_name}}&nbsp;{{$name->name}}</span> (ID: {{$name->id}})
                    </button>
                    <button type="button" data-name="{{json_encode($name)}}" class="btn btn-primary ml-2 editName"
                        data-toggle="popover" data-trigger="hover" data-content="@lang('Edit this name')">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button type="button" data-name="{{json_encode($name)}}" class="btn btn-success ml-1 confirmOne"
                        data-toggle="popover" data-trigger="hover" data-content="@lang('Confirm this name')">
                        <i class="fa fa-check-square-o"></i>
                    </button>
                </h5>
            </div>

            <div id="collapse{{$index}}" class="collapse" aria-labelledby="{{$index}}" data-parent="#accordion">
                <div class="card-body bg-custom-light-gray">
                    <div class="alert {{$color_class}}" role="alert">
                        @if ($name->VIAF_multiple)
                            <p class="mb-0">
                                @lang('There are multiple VIAF search results for')
                                <span class="font-weight-bold">{{$name->first_name}} {{$name->name}}</span>.
                                @lang('Please get the correct VIAF ID from the VIAF results.')
                            </p>
                        @endif
                        @if (!$name->VIAF_id && !$name->WIKIDATA_id && !$name->VIAF_multiple)
                            <p class="mb-0">
                                @lang('There were no VIAF or WIKIDATA search results for')
                                <span class="font-weight-bold">{{$name->first_name}} {{$name->name}}</span>.
                            </p>
                        @else
                            @if (!$name->VIAF_id && !$name->VIAF_multiple)
                                <p class="mb-0">
                                    @lang('There was no VIAF ID found for')
                                    <span class="font-weight-bold">{{$name->first_name}} {{$name->name}}</span>.
                                </p>
                            @endif
                            @if (!$name->WIKIDATA_id && !$name->VIAF_multiple)
                            <p class="mb-0">
                                @lang('There is no WIKIDATA for')
                                <span class="font-weight-bold">{{$name->first_name}} {{$name->name}}</span>.
                            </p>
                            @endif
                        @endif
                        @if ($name->VIAF_id)
                            <p class="mb-0">
                                @lang('All data fetched successfully for')
                                <span class="font-weight-bold">{{$name->first_name}} {{$name->name}}</span>.
                            </p>
                        @endif
                        <ul class="mb-0">
                            @if ($name->VIAF_search_link)
                                <li>
                                    <a href="{{$name->VIAF_search_link}}" target="_blank">@lang('VIAF search results')</a>
                                </li>
                            @endif
                            @if ($name->WIKIDATA_search_link)
                                <li>
                                    <a href="{{$name->WIKIDATA_search_link}}" target="_blank">@lang('WIKIDATA search results')</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <ul>
                        {!! Form::hidden('checked' . '[]', $name->checked, ['class' => 'checked']) !!}
                        {!! Form::hidden('auto_success' . '[]', $auto_success, ['class' => 'auto_success']) !!}
                        @foreach ($cols as $col)
                        {!! Form::hidden($col . '[]', $name->$col, ['class' => $col]) !!}
                        <li class="my-1">
                            <span class="font-weight-bold">{{ucfirst($col)}}: </span>
                            @if ($name->$col)
                                @if ($col == 'gender')
                                    @switch($name->$col)
                                        @case('a')
                                        <span class="{{$col}}">@lang('Female')</span>
                                            @break
                                        @case('b')
                                        <span class="{{$col}}">@lang('Male')</span>
                                            @break
                                        @case('c')
                                        <span class="{{$col}}">@lang('Other')</span>
                                            @break
                                        @case('u')
                                        <span class="{{$col}}">@lang('Unknown')</span>
                                            @break
                                        @default
                                    @endswitch
                                @elseif($col == 'WIKIDATA_image')
                                    <a href="{{$name->$col}}" target="_blank" class="ml-2 {{$col}}">
                                        <img src="{{$name->$col}}" alt="{{$name->first_name}} {{$name->name}}" height="100px">
                                    </a>
                                @elseif($col == 'xLink')
                                    <ul id="xLinks">
                                        @foreach (explode(';', $name->$col) as $xLink)
                                            <li class="{{$col}}">
                                                <a href="{{$xLink}}" target="_blank">{{$xLink}}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="{{$col}}">{{$name->$col}}</span>
                                @endif
                            @else
                                @if ($col == 'xLink')
                                    <ul id="xLinks" hidden></ul>
                                @endif
                                <span class="font-italic small text-secondary {{$col}}">@lang('No data found')</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endforeach
</div>
