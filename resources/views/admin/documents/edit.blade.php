@extends('admin.layouts.layout')
@section('title', $document ? __('faq::messages.editor.edit_document') . ' · ' . $document->title : __('faq::messages.common.create'))
@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('plugins.faq.admin.documents.index') }}">
        {{ __('faq::messages.editor.documents') }}</a>
    @if ($document)
        <a class="btn btn-outline-secondary"
            href="{{ route('plugins.faq.admin.documents.revisions.index', $document->id) }}">{{ __('faq::messages.common.revisions') }}</a>
    @endif
@endsection
@section('content')
    @php
        $value = fn($field, $fallback = null) => old($field, $document?->{$field} ?? $fallback);
    @endphp
    <form data-ajax="true" @unless ($document) data-redirect-url="response" @endunless method="post" action="{{ $document ? route('plugins.faq.admin.documents.update', $document->id) : route('plugins.faq.admin.documents.store') }}"
        data-document-editor data-document-errors='@json($errors->keys())'>
        @csrf
        @if ($document)
            @method('PUT')
        @endif
        <section class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs card-header-tabs m-0" role="tablist">
                    @foreach (['content', 'publication', 'languages', 'seo'] as $panel)
                        <li class="nav-item" role="presentation">
                            <button @class(['nav-link', 'active' => $loop->first]) type="button" id="document-tab-{{ $panel }}"
                                data-bs-toggle="tab" data-bs-target="#document-{{ $panel }}" role="tab"
                                aria-controls="document-{{ $panel }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ __('faq::messages.editor.' . $panel) }}</button>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body tab-content">
                <div class="tab-pane fade show active" id="document-content" role="tabpanel"
                    aria-labelledby="document-tab-content">
                    <div class="mb-3">
                        <label class="form-label" for="document_title">{{ __('faq::messages.common.title') }}</label>
                        <input class="form-control" id="document_title" name="title" value="{{ $value('title') }}"
                            required maxlength="180">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="document_slug">{{ __('faq::messages.editor.address') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">/faq/</span>
                            <input class="form-control" id="document_slug" name="slug" value="{{ $value('slug') }}"
                                required pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="180" data-slug-source="[name=title]">
                        </div>
                        <div class="form-text">
                            {{ __('faq::messages.editor.address_help') }}
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="document_body">{{ __('faq::messages.common.body') }}</label>
                        <textarea class="form-control" data-editor="tinymce" data-editor-upload-url="{{ route('plugins.faq.admin.images.store') }}"
                            id="document_body" name="body" rows="18">{{ $value('body') }}</textarea>
                        <div class="form-text">
                            {{ __('faq::messages.editor.images_help') }}
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="document-publication" role="tabpanel"
                    aria-labelledby="document-tab-publication">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label" for="document_category">{{ __('faq::messages.common.category') }}</label>
                            <select class="form-select" id="document_category" name="category_id" data-option-filter="locale">
                                <option value="">{{ __('faq::messages.editor.no_category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" data-option-group="{{ $category->locale }}"
                                        @selected((int) $value('category_id') === $category->id)>{{ $category->name }} ·
                                        {{ strtoupper($category->locale) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="document_position">{{ __('faq::messages.common.position') }}</label>
                            <input class="form-control" id="document_position" name="position" type="number"
                                value="{{ $value('position', 0) }}" min="0" max="100000" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="document_permission">{{ __('faq::messages.common.permission') }}</label>
                        <input class="form-control" id="document_permission" name="permission"
                            value="{{ $value('permission') }}" maxlength="100" pattern="[A-Za-z0-9_.-]+">
                        <div class="form-text">
                            {{ __('faq::messages.editor.permission_help') }}
                        </div>
                    </div>
                    <input type="hidden" name="published" value="0">
                    <div class="form-check">
                        <input class="form-check-input" id="document_published" name="published" type="checkbox"
                            value="1" @checked($value('published', false))>
                        <label class="form-check-label"
                            for="document_published">{{ __('faq::messages.common.published') }}</label>
                    </div>
                </div>
                <div class="tab-pane fade" id="document-languages" role="tabpanel"
                    aria-labelledby="document-tab-languages">
                    <div class="mb-3">
                        <label class="form-label" for="document_locale">{{ __('faq::messages.common.language') }}</label>
                        <select class="form-select" id="document_locale" name="locale" data-document-locale>
                            @foreach (config('kraft.locales') as $code => $label)
                                <option value="{{ $code }}" @selected($value('locale', app()->getLocale()) === $code)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="document_translation">{{ __('faq::messages.editor.translation') }}</label>
                        <select class="form-select" id="document_translation" name="translation_id"
                            data-document-translation>
                            <option value="">{{ __('faq::messages.editor.no_translation') }}</option>
                            @foreach ($translations as $translation)
                                <option value="{{ $translation->id }}" data-locale="{{ $translation->locale }}"
                                    @selected((int) old('translation_id', $translationId) === $translation->id)>{{ $translation->title }} ·
                                    {{ strtoupper($translation->locale) }}{{ $translation->deleted_at ? ' · ' . __('site.content.trash') : '' }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            {{ __('faq::messages.editor.translation_help') }}
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="document-seo" role="tabpanel" aria-labelledby="document-tab-seo">
                    <div class="mb-3">
                        <label class="form-label" for="document_seo_title">{{ __('faq::messages.editor.seo_title') }}</label>
                        <input class="form-control" id="document_seo_title" name="seo_title"
                            value="{{ $value('seo_title') }}" maxlength="180">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"
                            for="document_seo_description">{{ __('faq::messages.editor.seo_description') }}</label>
                        <textarea class="form-control" id="document_seo_description" name="seo_description" rows="3" maxlength="320">{{ $value('seo_description') }}</textarea>
                        <div class="form-text">
                            {{ __('faq::messages.editor.seo_help') }}
                        </div>
                    </div>
                    <input type="hidden" name="noindex" value="0">
                    <div class="form-check">
                        <input class="form-check-input" id="document_noindex" name="noindex" type="checkbox"
                            value="1" @checked($value('noindex', false))>
                        <label class="form-check-label" for="document_noindex">{{ __('faq::messages.editor.noindex') }}</label>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary" type="submit">{{ __('faq::messages.common.save') }}</button>
            </div>
        </section>
    </form>
    @push('scripts')
        <script type="module" src="{{ route('site.extensions.assets.show', ['id' => 'faq', 'asset' => 'editor.js']) }}"></script>
    @endpush
@endsection
