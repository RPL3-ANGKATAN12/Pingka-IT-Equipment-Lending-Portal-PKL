@auth
    @if(auth()->user()->role === 'admin')
    @else
        <button
            {{ $attributes->merge(['class' => 'btn-primary']) }}
            onclick="window.location.href='{{ route('loans.create') }}'"
        >
            <i class="fas fa-pen-fancy"></i>
            {{ $slot }}
        </button>
    @endif
@endauth

@guest
    <button
        {{ $attributes->merge(['class' => 'btn-primary']) }}
        onclick="window.location.href='{{ route('loans.create') }}'"
    >
        <i class="fas fa-pen-fancy"></i>
        {{ $slot }}
    </button>
@endguest