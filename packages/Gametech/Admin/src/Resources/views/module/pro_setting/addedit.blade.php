@php
    $color = [
        0 => 'card text-black bg-success mb-3' ,
        1 => 'card text-black bg-info mb-3' ,
        2 => 'card text-black bg-primary mb-3' ,
        3 => 'card text-black bg-danger mb-3' ,
        4 => 'card text-primary bg-olive mb-3',
        5 => 'card text-black bg-warning mb-3',
        6 => 'card text-black bg-success mb-3',
        7 => 'card text-black bg-info mb-3',
        8 => 'card text-black bg-info mb-3',
        10 => 'card text-black bg-primary mb-3',
        ]
@endphp

<b-container class="row  row-cols-3" v-show="show=true">
    @foreach ($pros as $i => $pro)


        <b-form @submit.stop.prevent="addEditSubmitNew" id="frmaddedit" class="col form-group">
            <b-form-row>
                <b-col>
                    <b-card class="{{ $color[$i] }}" header-tag="header" border-variant="secondary">
                        <template #header>
                            <h6 class="mb-0">{{ $pro->name_th }}</h6>
                        </template>
                        <b-card-text>
                            <b-form-group
                                id="input-group-{{ $pro->id }}-min"
                                label="ยอดขั้นต่ำ (โปร):"
                                label-for="{{ $pro->id }}-min"
                                description="">
                                <b-form-input
                                    id="{{ $pro->id }}[bonus_min]"
                                    name="{{ $pro->id }}[bonus_min]"

                                    type="text"
                                    size="sm"
                                    placeholder=""
                                    autocomplete="off"
                                    required

                                ></b-form-input>
                            </b-form-group>
                            <b-form-group
                                id="input-group-{{ $pro->id }}-max"
                                label="ยอดสููงสุด (โปร):"
                                label-for="{{ $pro->id }}-max"
                                description="">
                                <b-form-input
                                    id="{{ $pro->id }}[bonus_max]"
                                    name="{{ $pro->id }}[bonus_max]"

                                    type="text"
                                    size="sm"
                                    placeholder=""
                                    autocomplete="off"
                                    required

                                ></b-form-input>
                            </b-form-group>
                            <b-form-group
                                id="input-group-{{ $pro->id }}-turnpro"
                                label="ยอดเทิร์น (โปร):"
                                label-for="{{ $pro->id }}-turnpro"
                                description="">
                                <b-form-input
                                    id="{{ $pro->id }}[turnpro]"
                                    name="{{ $pro->id }}[turnpro]"

                                    type="text"
                                    size="sm"
                                    placeholder=""
                                    autocomplete="off"
                                    required

                                ></b-form-input>
                            </b-form-group>
                            @if($pro->length_type == 'PERCENT')
                                <b-form-group
                                    id="input-group-{{ $pro->id }}-bonus_percent"
                                    label="ยอดจ่าย (%):"
                                    label-for="{{ $pro->id }}-bonus_percent"
                                    description="">
                                    <b-form-input
                                        id="{{ $pro->id }}[bonus_percent]"
                                        name="{{ $pro->id }}[bonus_percent]"

                                        type="text"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                        required

                                    ></b-form-input>
                                </b-form-group>
                            @endif
                        </b-card-text>
                        <b-button type="submit" variant="primary">บันทึก</b-button>
                    </b-card>
                </b-col>


            </b-form-row>


        </b-form>

    @endforeach
</b-container>

