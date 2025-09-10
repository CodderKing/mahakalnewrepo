@php \Carbon\Carbon::setLocale('hi'); @endphp

@foreach ($pujaschedule as $index => $pujaList)
    @php
        $first = $pujaList->first();
        $service = $first->service ?? null;
        $serviceName = $service->name ?? 'N/A';
        $serviceVenue = $service->pooja_venue ?? 'N/A';
        $categoryName = $service->category->name ?? 'Unknown';
        $bookingDates = $pujaList->pluck('booking_date')->unique();
    @endphp

    @foreach ($bookingDates as $date)
        @php
            $carbonDate = \Carbon\Carbon::parse($date);
            $weekdayHindi = $carbonDate->translatedFormat('l');
            $weekdayEnglish = $carbonDate->locale('en')->isoFormat('dddd');
        @endphp

        <tr>
            @if ($loop->first)
                <td rowspan="{{ $bookingDates->count() }}">{{ $loop->parent->iteration }}</td>
                <td rowspan="{{ $bookingDates->count() }}">{{ $serviceName }}<br>{{ $serviceVenue }}</td>
                <td rowspan="{{ $bookingDates->count() }}">{{ ucfirst($categoryName) }}</td>
            @endif

            <td>{{ $weekdayHindi }} ({{ $weekdayEnglish }})</td>
            <td>{{ $carbonDate->format('d M, Y') }}</td>
            <td>–</td>
        </tr>
    @endforeach
@endforeach
