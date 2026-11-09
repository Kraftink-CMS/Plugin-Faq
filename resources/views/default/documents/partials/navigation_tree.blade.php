@use('KraftInk\Plugins\Faq\Services\Documents')
@foreach ($nodes as $node)
    <details class="py-1" @if (in_array($node['category']->id, $navigation['expanded'], true)) open @endif>
        <summary class="fw-semibold">{{ $node['category']->name }}</summary>
        <div class="ps-3 border-start ms-1">
            <a class="small d-block py-2" href="{{ route('plugins.faq.public.documents.index', ['category' => $node['category']->id]) }}">{{ __('faq::messages.common.view_category') }}</a>
            @if (isset($navigation['documents'][$node['category']->id]))
                <ul class="list-unstyled my-2">
                    @foreach ($navigation['documents'][$node['category']->id] as $item)
                        <li class="py-1"><a @class([
                            'fw-semibold' => isset($document) && $document->id === $item->id,
                        ])
                                @if (isset($document) && $document->id === $item->id) aria-current="page" @endif
                                href="{{ app(Documents::class)->url($item) }}">{{ $item->title }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
            @include('faq::default.documents.partials.navigation_tree', ['nodes' => $node['children']])
        </div>
    </details>
@endforeach
