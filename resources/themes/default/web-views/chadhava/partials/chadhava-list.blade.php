<div class="row">
@forelse($poojas as $chadhava)
    <div class="col-md-4 pb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="pooja-name">{{ $chadhava->name }}</h5>
                <div class="pooja-heading">{{ $chadhava->pooja_heading }}</div>
                <div class="pooja-venue">{{ $chadhava->chadhava_venue }}</div>
                <p class="card-text">{{ $chadhava->short_details }}</p>
                <p class="pooja-calendar">{{ \Carbon\Carbon::parse($chadhava->upcoming_date)->format('d M Y') }}</p>
                <p>Rating: {{ round($chadhava->review_avg_rating ?? 0, 1) }}/5</p>
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <p>No Chadhava found.</p>
    </div>
@endforelse
</div>

@if(method_exists($poojas, 'links'))
    <div class="mt-4 d-flex justify-content-center">
        {{ $poojas->links() }}
    </div>
@endif
