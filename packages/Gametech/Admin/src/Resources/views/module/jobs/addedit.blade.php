<div class="col-12 col-sm-6 col-md-3">

    <div class="small-box bg-danger">

        <div class="inner"
             title="Last {{ config('queue-monitor.ui.metrics_time_frame') ?? 14 }} days">
            <h3>{{ $metric->format($metric->value) }}</h3>
            <p>{{ $metric->title }}</p>
        </div>


        <div class=icon">

        </div>

        <div class="small-box-footer">
            @if($metric->previousValue !== null)

                <div
                    class=" {{ $metric->hasChanged() ? ($metric->hasIncreased() ? 'text-green-700' : 'text-red-800') : 'text-gray-800' }}">

                    @if($metric->hasChanged())
                        @if($metric->hasIncreased())
                            Up from
                        @else
                            Down from
                        @endif
                    @else
                        No change from
                    @endif

                    {{ $metric->format($metric->previousValue) }}
                </div>
            @else
                information
            @endif
        </div>


    </div>

</div>
