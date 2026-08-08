@php
    $poweredBy = config('app.powered_by', 'Bynnas');
    $variant = $variant ?? 'default';
@endphp
@if($variant === 'print')
    <p class="powered-by-print" style="margin:10px 0 0;text-align:center;font-size:9px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#94a3b8;">
        Powered by <span style="color:#0f172a;font-weight:800;letter-spacing:.02em;text-transform:none;">{{ $poweredBy }}</span>
    </p>
@elseif($variant === 'fixed')
    <div class="powered-by-fixed" aria-hidden="false">
        Powered by <strong>{{ $poweredBy }}</strong>
    </div>
@elseif($variant === 'admin')
    <div class="powered-by-admin">
        Powered by <strong>{{ $poweredBy }}</strong>
    </div>
@else
    <p class="powered-by {{ $variant === 'footer' ? 'powered-by--footer' : '' }}"
       style="{{ $variant === 'footer' ? '' : 'margin:0;text-align:center;font-size:12px;font-weight:500;letter-spacing:.02em;color:#94a3b8;' }}">
        Powered by <strong style="{{ $variant === 'footer' ? '' : 'color:#0f172a;font-weight:800;' }}">{{ $poweredBy }}</strong>
    </p>
@endif
