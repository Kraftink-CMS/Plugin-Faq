@use('KraftInk\Plugins\Faq\Services\Documents')
@extends('default.layouts.layout')
@section('content')
    <div class="row g-4 py-3">
        <aside class="col-lg-3">
            @include('faq::default.documents.partials.navigation')
        </aside>
        <section class="col-lg-9">
            <header class="page-heading mt-0">
                <h1 class="h2">{{ $category?->name ?? __('faq::messages.common.faq') }}</h1>
                @if ($category?->description)
                    <p class="text-body-secondary mb-0">{{ $category->description }}</p>
                @endif
            </header>
            <form method="get" class="card card-body site-panel mb-4" role="search">
                @if ($category)
                    <input type="hidden" name="category" value="{{ $category->id }}">
                @endif
                <label class="form-label" for="faq-search">
                    {{ __('faq::messages.common.search') }}</label>
                <div class="input-group">
                    <input class="form-control" id="faq-search" type="search" name="q" value="{{ request('q') }}" maxlength="100">
                    <button class="btn btn-primary" type="submit">{{ __('faq::messages.common.search') }}</button>
                </div>
                @if (request()->filled('q'))
                    <a class="small mt-2 align-self-start" href="{{ route('plugins.faq.public.documents.index', $category ? ['category' => $category->id] : []) }}">{{ __('faq::messages.common.reset_search') }}</a>
                @endif
            </form>
            <div id="faq-public-documents" data-ajax-pagination tabindex="-1" class="vstack gap-4">
                @forelse($documents->groupBy('category_id') as $items)
                    <section class="mb-5">
                        <h2 class="h5 section-heading mb-3">{{ $items->first()->category?->name ?? __('faq::messages.common.general') }}</h2>
                        <div class="list-group faq-questions">
                            @foreach ($items as $item)
                                <details class="list-group-item p-4" id="question-{{ $item->id }}">
                                    <summary class="fw-semibold">{{ $item->title }}</summary>
                                    <div class="prose mt-3">
                                        {!! $item->body !!}
                                    </div>
                                    <a class="small d-inline-block mt-2"
                                        href="{{ app(Documents::class)->url($item) }}">{{ __('faq::messages.editor.view_answer') }}</a>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="card card-body empty-state text-center py-5">
                        <i class="bi bi-question-circle fs-2 mb-3" aria-hidden="true"></i>
                        <p class="mb-0">{{ __(request()->filled('q') ? 'faq::messages.common.no_results' : 'faq::messages.common.empty') }}</p>
                    </div>
                @endforelse
                {{ $documents->links() }}
            </div>
        </section>
    </div>
@endsection
