@if(count($duplicateNames) > 0)
    <div class="person">
        @foreach ($duplicateNames as $index=>$name)
            @php $index == 0 ? $hidden = true : $hidden = false @endphp
            @php $index == 0 ? $bg_selected = 'bg-selected' : $bg_selected = '' @endphp
            <div class="form-row mt-1 data-clean-item rounded py-1 {{$bg_selected}}">
                @if ($loop->first)
                    <input type="radio" value="{{$index}}" checked="true" name="selected_person" class="col-1 selected_person" style="margin-top: 0.9em">
                @else
                    <input type="radio" value="{{$index}}" name="selected_person" class="col-1 selected_person" style="margin-top: 0.9em">
                @endif
                @if (Auth()->user()->autocomplete() !== 'false')
                    {!! Form::text('name[]', $name['name'], ['class' => 'form-control typeahead clean name col-5',
                                                            'placeholder' => __('... choose from list ...'),
                                                            'data-provide' => 'typeahead',
                                                            'autocomplete' => 'off',
                                                            'data-autocomplete-url'=> url('autocomplete'),
                                                            'data-autocomplete-with-firstname'=> "true"
                                                            ]) !!}
                @else
                    {!! Form::text('name[]', $name['name'], ['class' => 'form-control col-5', 'placeholder' => __('Name')]) !!}
                @endif

                @if (Auth()->user()->autocomplete() !== 'false')
                    {!! Form::text('first_name[]', $name['first_name'], ['class' => 'form-control first_name autocomplete-firstname col-5', 'placeholder' => __('First name'), 'readonly'] ) !!}
                @else
                    {!! Form::text('first_name[]', $name['first_name'], ['class' => 'form-control col-5', 'placeholder' => __('First name'), 'readonly']) !!}
                @endif

                {!! Form::hidden('id[]', $name['id'], ['class'=>'id']) !!}
                {!! Form::hidden('VIAF_id[]', null, ['class' => 'VIAF_id'] ) !!}
                {!! Form::hidden('ISNI_id[]', null, ['class' => 'ISNI_id'] ) !!}
                {!! Form::hidden('alternative_names[]', null, ['class' => 'alternative_names'] ) !!}
                {!! Form::hidden('address[]', null, ['class' => 'address'] ) !!}
                {!! Form::hidden('nationality[]', null, ['class' => 'nationality'] ) !!}
                {!! Form::hidden('information[]', null, ['class' => 'information'] ) !!}
                {!! Form::hidden('xLink[]', null, ['class' => 'xLink'] ) !!}
                {!! Form::hidden('gender[]', null, ['class' => 'gender'] ) !!}
                {!! Form::hidden('birth_year[]', null, ['class' => 'birth_year'] ) !!}
                {!! Form::hidden('death_year[]', null, ['class' => 'death_year'] ) !!}
                {!! Form::hidden('WIKIDATA_id[]', null, ['class' => 'WIKIDATA_id'] ) !!}
                {!! Form::hidden('WIKIDATA_image[]', null, ['class' => 'WIKIDATA_image'] ) !!}

                @if($hidden)
                    <div class="col-1 remove-placeholder"></div>
                @endif
                {{Form::button('<i class="fa fa-trash" style="color:red"></i>',['class'=>'btn col-1 remove float-right', 'hidden'=>$hidden])}}

                <div class="col-1 person-ids"></div>
                <span class="small col-11 mt-1 person-ids">
                    <span class="font-weight-bold">ID:&nbsp;</span><span class="person-id">
                        {{ $name['id'] }}
                    </span>
                    @if ($name['VIAF_id'])
                        <span class="font-weight-bold ml-3">VIAF&nbsp;ID:&nbsp;</span><span class="person-viaf">
                            {{ $name['VIAF_id'] }}
                        </span>
                    @else
                        <span class="font-weight-bold ml-3">VIAF&nbsp;ID:&nbsp;</span><span class="person-viaf">
                            {!! __('None') !!}
                        </span>
                    @endif
                </span>
            </div>
        @endforeach
    </div>
@else
    <div class="person">
        <div class="form-row mt-1 data-clean-item rounded py-1">
            <input type="radio" value="0" name="selected_person" class="col-1 selected_person" style="margin-top: 0.9em">
            @if (Auth()->user()->autocomplete() !== 'false')
                {!! Form::text('name[]', '', ['class' => 'form-control typeahead clean name col-5',
                                                        'placeholder' => __('... choose from list ...'),
                                                        'data-provide' => 'typeahead',
                                                        'autocomplete' => 'off',
                                                        'data-autocomplete-url'=> url('autocomplete'),
                                                        'data-autocomplete-with-firstname'=> "true"
                                                        ]) !!}
            @else
                {!! Form::text('name[]', '', ['class' => 'form-control col-5', 'placeholder' => __('Name')]) !!}
            @endif

            @if (Auth()->user()->autocomplete() !== 'false')
                {!! Form::text('first_name[]', '', ['class' => 'form-control first_name autocomplete-firstname col-5', 'placeholder' => __('First name'), 'readonly'] ) !!}
            @else
                {!! Form::text('first_name[]', '', ['class' => 'form-control col-5', 'placeholder' => __('First name'), 'readonly']) !!}
            @endif

            {!! Form::hidden('id[]', null, ['class'=>'id']) !!}
            {!! Form::hidden('VIAF_id[]', null, ['class' => 'VIAF_id'] ) !!}
            {!! Form::hidden('ISNI_id[]', null, ['class' => 'ISNI_id'] ) !!}
            {!! Form::hidden('alternative_names[]', null, ['class' => 'alternative_names'] ) !!}
            {!! Form::hidden('address[]', null, ['class' => 'address'] ) !!}
            {!! Form::hidden('nationality[]', null, ['class' => 'nationality'] ) !!}
            {!! Form::hidden('information[]', null, ['class' => 'information'] ) !!}
            {!! Form::hidden('xLink[]', null, ['class' => 'xLink'] ) !!}
            {!! Form::hidden('gender[]', null, ['class' => 'gender'] ) !!}
            {!! Form::hidden('birth_year[]', null, ['class' => 'birth_year'] ) !!}
            {!! Form::hidden('death_year[]', null, ['class' => 'death_year'] ) !!}
            {!! Form::hidden('WIKIDATA_id[]', null, ['class' => 'WIKIDATA_id'] ) !!}
            {!! Form::hidden('WIKIDATA_image[]', null, ['class' => 'WIKIDATA_image'] ) !!}

            <div class="col-1 remove-placeholder"></div>
            {{Form::button('<i class="fa fa-trash" style="color:red"></i>',['class'=>'btn col-1 remove float-right', 'hidden'=>true])}}

            <div class="col-1 person-ids" hidden="true"></div>
            <span class="small col-11 mt-1 person-ids" hidden="true">
                <span class="font-weight-bold">ID:&nbsp;</span><span class="person-id id_span">{!! __('None') !!}</span>
                <span class="font-weight-bold ml-3">VIAF&nbsp;ID:&nbsp;</span><span class="person-viaf">{!! __('None') !!}</span>
            </span>
        </div>
    </div>
@endif
