
@if (session()->has($attributes->get('type')))
<div class="alert alert-important alert-{{ $attributes->get('type') }} alert-dismissible" role="alert">
    <div class="d-flex">
      <div>
        @if ($attributes->get('type') == 'success')
        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
            <path d="M5 12l5 5l10 -10"></path>
         </svg>
        @elseif ($attributes->get('type') == 'warning')
        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
            <path d="M10.24 3.957l-8.422 14.06a1.989 1.989 0 0 0 1.7 2.983h16.845a1.989 1.989 0 0 0 1.7 -2.983l-8.423 -14.06a1.989 1.989 0 0 0 -3.4 0z"></path>
            <path d="M12 9v4"></path>
            <path d="M12 17h.01"></path>
         </svg>
        @endif

      </div>
      <div>
        {{ session($attributes->get('type')) }}
      </div>
    </div>
    <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
  </div>
  @endif
