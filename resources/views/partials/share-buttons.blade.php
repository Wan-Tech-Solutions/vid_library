@php
    $shareUrl = route('landing') . '?v=' . $video->id;
    $videoTitle = urlencode($video->title);
@endphp

<div x-data="{ copied: false }" class="mt-4 flex flex-wrap gap-5 items-center text-gray-600 dark:text-gray-300">

    {{-- WhatsApp --}}
    <a href="https://wa.me/?text={{ urlencode($video->title . ' - ' . $shareUrl) }}" target="_blank"
        data-tippy-content="Share on WhatsApp" class="hover:text-green-500 transition hover:scale-110">
        <svg class="w-6 h-6 fill-current" viewBox="0 0 32 32">
            <path
                d="M16.003 2.002c-7.732 0-14 6.268-14 14 0 2.483.656 4.838 1.894 6.932l-1.992 7.066 7.256-1.903c2.03 1.067 4.315 1.631 6.842 1.631h.001c7.732 0 14-6.268 14-14s-6.268-14-14-14zM16.003 25.93c-2.1 0-4.093-.56-5.858-1.621l-.42-.249-4.308 1.13 1.147-4.06-.274-.44c-1.17-1.879-1.789-4.033-1.789-6.228 0-6.505 5.288-11.793 11.792-11.793s11.793 5.288 11.793 11.793-5.288 11.793-11.793 11.793zM22.406 19.406l-2.413-1.13c-.326-.153-.707-.061-.945.209l-.735.868c-.52.613-1.376.767-2.063.373-1.133-.652-2.23-1.698-3.136-2.906-.717-.948-.89-1.82-.479-2.362l.684-.906c.236-.313.267-.734.076-1.084l-1.13-2.13c-.211-.397-.672-.604-1.108-.5-.963.232-2.164.931-2.426 2.429-.33 1.921.715 4.383 3.109 6.92 2.268 2.399 4.658 3.426 6.698 3.426.488 0 .93-.061 1.33-.176 1.206-.365 2.02-1.419 2.316-2.272.13-.377-.067-.793-.478-.978z" />
        </svg>
    </a>

    {{-- Facebook --}}
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank"
        data-tippy-content="Share on Facebook" class="hover:text-blue-600 transition hover:scale-110">
        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
            <path
                d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.86 8 9.8v-6.93h-2.4v-2.87H10V9.97c0-2.38 1.42-3.7 3.6-3.7 1.04 0 2.13.18 2.13.18v2.36h-1.2c-1.18 0-1.55.73-1.55 1.48v1.77h2.64l-.42 2.87h-2.22v6.93c4.56-.94 8-4.96 8-9.8z" />
        </svg>
    </a>

    {{-- Email --}}
    <a href="mailto:?subject={{ $videoTitle }}&body=Check this out: {{ $shareUrl }}"
        data-tippy-content="Share via Email" class="hover:text-gray-800 transition hover:scale-110">
        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
            <path
                d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM4 8l8 5 8-5v2l-8 5-8-5V8z" />
        </svg>
    </a>

    {{-- Instagram --}}
    <a href="https://www.instagram.com/stories/create/?url={{ urlencode($shareUrl) }}" target="_blank"
        data-tippy-content="Share on Instagram" class="hover:text-pink-500 transition hover:scale-110">
        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
            <path
                d="M7 2C4.8 2 3 3.8 3 6v12c0 2.2 1.8 4 4 4h10c2.2 0 4-1.8 4-4V6c0-2.2-1.8-4-4-4H7zm10 2c1.1 0 2 .9 2 2v2h-2V4h-2V2h2zM7 4h10c1.1 0 2 .9 2 2v2h-2c-1.1 0-2 .9-2 2v2H9V8c0-1.1-.9-2-2-2H5V6c0-1.1.9-2 2-2zm0 4h2v2H7V8zm5 2c1.7 0 3 1.3 3 3s-1.3 3-3 3-3-1.3-3-3 1.3-3 3-3zm0 5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" />
        </svg>
    </a>

    {{-- Copy to clipboard --}}
    <button
        @click="navigator.clipboard.writeText('{{ $shareUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
        data-tippy-content="Copy Link" class="hover:text-emerald-600 transition hover:scale-110 focus:outline-none">
        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
            <path d="M3 3v18h18V3H3zm2 2h14v14H5V5zm6 4h2v6h-2V9z" />
        </svg>
    </button>

    <span x-show="copied" x-transition class="text-sm text-emerald-600 ml-2">Copied!</span>

</div>
