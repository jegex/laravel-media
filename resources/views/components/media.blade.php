@props([
    'media',
    'conversion' => null,
    'loading' => null,
    'class' => '',
    'alt' => null,
    'attributes',
])

@php
    $loadingValue = $loading ?? config('media.default_loading_attribute_value');
    $loadingAttribute = $loadingValue ? " loading=\"{$loadingValue}\"" : '';
    $altText = $alt ?? ($media->getAltTxt() ?: $media->getName());

    if ($conversion) {
        $url = $media->getUrl($conversion);
    } else {
        $url = $media->getUrl();
    }
@endphp

<img src="{{ $url }}" alt="{{ $altText }}" class="{{ $class }}" {!! $loadingAttribute !!} {{ $attributes }}>
