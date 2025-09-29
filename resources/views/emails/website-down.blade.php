{{ $website->url }} is down!

Your website {{ $website->url }} is currently unreachable.

Details:
- Website: {{ $website->url }}
- Time checked: {{ $check->checked_at->format('Y-m-d H:i:s') }}
@if($check->status_code)
- Status Code: {{ $check->status_code }}
@endif
@if($check->error_message)
- Error: {{ $check->error_message }}
@endif
@if($check->response_time_ms)
- Response Time: {{ $check->response_time_ms }}ms
@endif

We will continue monitoring your website and notify you when it's back online.

--
Uptime Monitor