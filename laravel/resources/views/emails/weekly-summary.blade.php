<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Summary - HowTo-Genie</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <h1>HowTo-Genie Weekly Summary</h1>

    <p><strong>Posts published this week:</strong> {{ $summary['posts_published'] ?? 0 }}</p>

    @if(!empty($summary['top_performer_title']))
    <p><strong>Top performer:</strong>
        @if(!empty($summary['top_performer_url']))
            <a href="{{ $summary['top_performer_url'] }}">{{ $summary['top_performer_title'] }}</a>
        @else
            {{ $summary['top_performer_title'] }}
        @endif
    </p>
    @endif

    <p><strong>Revenue estimate:</strong> ${{ number_format($summary['revenue_estimate'] ?? 0, 2) }}</p>

    @if(!empty($summary['streak']))
    <p><strong>Streak / health:</strong> {{ $summary['streak'] }}</p>
    @endif
</body>
</html>
