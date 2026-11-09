@use('App\Kraft\SiteDates')
@use('KraftInk\Plugins\Faq\Services\Documents')
@extends('default.layouts.layout')
@section('content')
    <div class="row g-4 py-3">
        <aside class="col-lg-3">
            @include('faq::default.documents.partials.navigation')
        </aside>
        <div class="col-lg-9">
            <article class="card card-body p-4 p-lg-5">
                <nav aria-label="{{ __('faq::messages.editor.breadcrumb') }}">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a
                                href="{{ route('plugins.faq.public.documents.index') }}">{{ __('faq::messages.common.faq') }}</a>
                        </li>
                        @if ($category)
                            <li class="breadcrumb-item"><a
                                    href="{{ route('plugins.faq.public.documents.index', ['category' => $category->id]) }}">{{ $category->name }}</a>
                            </li>
                        @endif
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $document->title }}</li>
                    </ol>
                </nav>
                @if ($translations->count() > 1)
                    <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="{{ __('faq::messages.editor.available_languages') }}">
                        @foreach ($translations as $translation)
                            <a @class([
                                'btn btn-sm',
                                'btn-primary' => $translation->locale === $document->locale,
                                'btn-outline-secondary' => $translation->locale !== $document->locale,
                            ])
                                href="{{ app(Documents::class)->url($translation) }}"
                                lang="{{ $translation->locale }}"
                                hreflang="{{ $translation->locale }}">{{ config('kraft.locales')[$translation->locale] }}</a>
                        @endforeach
                    </nav>
                @endif
                <h1 class="h2 mb-4">{{ $document->title }}</h1>
                <div class="prose">
                    {!! $document->body !!}
                </div>
                <p class="text-body-secondary mt-4"><small>{{ __('faq::messages.common.updated') }}
                        {{ app(SiteDates::class)->format($document->updated_at, 'd F Y') }}</small>
                </p>
                <nav class="d-flex justify-content-between flex-wrap gap-3 border-top pt-3"
                    aria-label="{{ __('faq::messages.editor.adjacent_pages') }}">
                    <div>
                        @if ($previous)
                            <a
                                href="{{ app(Documents::class)->url($previous) }}"><span
                                    class="d-block small text-body-secondary">{{ __('faq::messages.editor.previous') }}</span>{{ $previous->title }}</a>
                        @endif
                    </div>
                    <div class="text-end">
                        @if ($next)
                            <a
                                href="{{ app(Documents::class)->url($next) }}"><span
                                    class="d-block small text-body-secondary">{{ __('faq::messages.editor.next') }}</span>{{ $next->title }}</a>
                        @endif
                    </div>
                </nav>
            </article>
        </div>
    </div>
@endsection
