@use('KraftInk\Plugins\Faq\Services\Documents')
<nav aria-label="{{ __('faq::messages.editor.contents') }}" class="card site-sidebar">
    <div class="card-header fw-semibold px-4 py-3">
        <a class="d-none d-lg-inline" href="{{ route('plugins.faq.public.documents.index') }}">
            <i class="bi bi-list-ul me-2 text-primary" aria-hidden="true"></i>{{ __('faq::messages.editor.contents') }}
        </a>
        <button class="btn btn-link d-flex d-lg-none align-items-center gap-2 w-100 p-0 text-decoration-none"
            type="button" data-bs-toggle="collapse" data-bs-target="#faq-contents" aria-expanded="false"
            aria-controls="faq-contents">
            <i class="bi bi-list-ul" aria-hidden="true"></i>{{ __('faq::messages.editor.contents') }}
            <i class="bi bi-chevron-down ms-auto" aria-hidden="true"></i>
        </button>
    </div>
    <div class="collapse d-lg-block" id="faq-contents">
        <div class="card-body px-4 py-3">
            <a class="d-inline-block d-lg-none mb-2" href="{{ route('plugins.faq.public.documents.index') }}">{{ __('faq::messages.common.faq') }}</a>
            @if (isset($navigation['documents'][0]))
                <ul class="list-unstyled mb-2">
                    @foreach ($navigation['documents'][0] as $item)
                        <li class="py-1"><a @class([
                            'fw-semibold' => isset($document) && $document->id === $item->id,
                        ])
                                @if (isset($document) && $document->id === $item->id) aria-current="page" @endif
                                href="{{ app(Documents::class)->url($item) }}">{{ $item->title }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
            @include('faq::default.documents.partials.navigation_tree', ['nodes' => $navigation['tree']])
        </div>
    </div>
</nav>
