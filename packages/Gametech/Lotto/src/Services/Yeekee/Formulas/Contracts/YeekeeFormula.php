<?php

namespace Gametech\Lotto\Services\Yeekee\Formulas\Contracts;

interface YeekeeFormula
{
    public function key(): string;

    /**
     * @param  array<int,array{position:int,number_text:string,number_value:int}>  $shoots
     * @param  array<string,mixed>  $config
     * @return array{raw_result:string,top_3:string,bottom_2:string}
     */
    public function compute(array $shoots, array $config): array;
}
