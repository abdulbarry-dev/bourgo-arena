@php
	$newLogo = public_path('assets/icons/brandmark-vert.webp');
	$newLogoUrl = asset('assets/icons/brandmark-vert.webp');
	$fallback = asset('assets/icons/brandmark-vert.webp');
@endphp

@if (file_exists($newLogo))
	<img src="{{ $newLogoUrl }}" alt="{{ __('Logo') }}" {{ $attributes->merge(['class' => 'object-contain']) }} />
@else
	<img src="{{ $fallback }}" alt="{{ __('Logo') }}" {{ $attributes->merge(['class' => 'object-contain']) }} />
@endif
