@extends('admin.layouts.layout')
@section('title', __('faq::messages.common.faq'))
@section('content')
    <div data-hash-tabs data-hash-tabs-errors="{{ json_encode($errors->keys()) }}">
        <ul class="nav nav-tabs mb-3" role="tablist">
            @foreach (['documents', 'categories'] as $panel)
                <li class="nav-item" role="presentation">
                    <button @class(['nav-link', 'active' => $tab === $panel]) id="faq-tab-{{ $panel }}" type="button" data-bs-toggle="tab"
                        data-bs-target="#faq-library-{{ $panel }}" role="tab"
                        aria-controls="faq-library-{{ $panel }}"
                        aria-selected="{{ $tab === $panel ? 'true' : 'false' }}">{{ __('faq::messages.editor.' . $panel) }}</button>
                </li>
            @endforeach
        </ul>
        <div class="tab-content">
            <div @class(['tab-pane fade', 'show active' => $tab === 'documents']) id="faq-library-documents" role="tabpanel"
                aria-labelledby="faq-tab-documents">
                <div id="faq-admin-documents" data-ajax-pagination data-table tabindex="-1" class="vstack gap-4">
                    <div class="mb-3"><a class="btn btn-primary"
                            href="{{ route('plugins.faq.admin.documents.create') }}">{{ __('faq::messages.common.create') }}</a></div>
                    <section class="card">
                        <div class="card-body">
                            <x-tables.controls :paginator="$documents" class="mb-3" />
                            <form class="row g-2" method="get" action="{{ route('plugins.faq.admin.documents.index') }}">
                                <input type="hidden" name="tab" value="documents">
                                <div class="col-md-5">
                                    <input class="form-control" name="q" value="{{ request('q') }}"
                                        placeholder="{{ __('faq::messages.common.search') }}"
                                        aria-label="{{ __('faq::messages.common.search') }}" maxlength="100">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="locale" aria-label="{{ __('faq::messages.common.language') }}">
                                        <option value="">{{ __('faq::messages.editor.all_languages') }}</option>
                                        @foreach (config('kraft.locales') as $code => $label)
                                            <option value="{{ $code }}" @selected(request('locale') === $code)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="state" aria-label="{{ __('faq::messages.editor.state') }}">
                                        <option value="">{{ __('faq::messages.editor.all_states') }}</option>
                                        @foreach (['published', 'draft'] as $state)
                                            <option value="{{ $state }}" @selected(request('state') === $state)>
                                                {{ __('faq::messages.editor.' . $state) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <noscript>
                                    <div class="col-md-1">
                                        <button class="btn btn-outline-secondary w-100" type="submit"
                                            aria-label="{{ __('faq::messages.common.search') }}"><i class="bi bi-search"
                                                aria-hidden="true"></i></button>
                                    </div>
                                </noscript>
                            </form>
                        </div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th scope="col" data-table-sort="title">{{ __('faq::messages.common.title') }}</th>
                                        <th>{{ __('faq::messages.common.category') }}</th>
                                        <th scope="col" data-table-sort="locale">{{ __('faq::messages.common.language') }}</th>
                                        <th scope="col" data-table-sort="published">{{ __('faq::messages.editor.state') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($documents as $item)
                                        <tr>
                                            <td><strong>{{ $item->title }}</strong>
                                                @if ($item->permission)
                                                    <span
                                                        class="badge text-bg-secondary ms-1">{{ __('faq::messages.editor.restricted') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $categories->firstWhere('id', $item->category_id)?->name ?? '—' }}
                                            </td>
                                            <td>{{ strtoupper($item->locale) }}</td>
                                            <td><span
                                                    @class([
                                                        'badge',
                                                        'text-bg-success' => $item->published,
                                                        'text-bg-secondary' => !$item->published,
                                                    ])>{{ __('faq::messages.editor.' . ($item->published ? 'published' : 'draft')) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                        type="button"
                                                        data-bs-toggle="dropdown">{{ __('common.labels.actions') }}</button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item"
                                                                href="{{ route('plugins.faq.admin.documents.edit', $item->id) }}">{{ __('common.actions.edit') }}</a>
                                                        </li>
                                                        <li><a class="dropdown-item"
                                                                href="{{ route('plugins.faq.admin.documents.revisions.index', $item->id) }}">{{ __('faq::messages.common.revisions') }}</a>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item text-danger" type="button"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#faq-document-delete-{{ $item->id }}">{{ __('common.actions.delete') }}</button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-tables.empty :colspan="5" :message="__('faq::messages.editor.no_documents')" :action="__('faq::messages.common.create')"
                                            :href="route('plugins.faq.admin.documents.create')" />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $documents->links() }}
                        </div>
                    </section>
                    @foreach ($documents as $item)
                        <x-forms.modal :id="'faq-document-delete-' . $item->id" :title="__('common.actions.delete')" :action="route('plugins.faq.admin.documents.destroy', $item->id)" method="DELETE" :submit="__('common.actions.delete')"
                            size="sm">
                            <p>{{ __('faq::messages.editor.delete_document') }}</p>
                            <strong>{{ $item->title }}</strong>
                        </x-forms.modal>
                    @endforeach
                </div>
            </div>
            <div @class(['tab-pane fade', 'show active' => $tab === 'categories']) id="faq-library-categories" role="tabpanel"
                aria-labelledby="faq-tab-categories">
                <div id="faq-admin-categories" data-ajax-pagination data-table tabindex="-1" class="vstack gap-4">
                    <div class="mb-3"><button class="btn btn-primary" type="button" data-bs-toggle="modal"
                            data-bs-target="#faq-category-new">{{ __('faq::messages.editor.add_category') }}</button></div>
                    <section class="card">
                        <x-tables.controls :paginator="$categoryPage" container-class="card-body" />
                        <div class="card-body p-0 table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th scope="col" data-table-sort="name">{{ __('faq::messages.editor.category_name') }}</th>
                                        <th>{{ __('faq::messages.editor.parent') }}</th>
                                        <th scope="col" data-table-sort="locale">{{ __('faq::messages.common.language') }}</th>
                                        <th scope="col" data-table-sort="position">{{ __('faq::messages.common.position') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categoryPage as $item)
                                        <tr>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $categories->firstWhere('id', $item->parent_id)?->name ?? '—' }}</td>
                                            <td>{{ strtoupper($item->locale) }}</td>
                                            <td>{{ $item->position }}</td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                        type="button"
                                                        data-bs-toggle="dropdown">{{ __('common.labels.actions') }}</button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <button class="dropdown-item" type="button" data-bs-toggle="modal"
                                                                data-bs-target="#faq-category-{{ $item->id }}">{{ __('common.actions.edit') }}</button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item text-danger" type="button"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#faq-category-delete-{{ $item->id }}">{{ __('common.actions.delete') }}</button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-tables.empty :colspan="5" :message="__('faq::messages.editor.no_categories')" :action="__('faq::messages.editor.add_category')"
                                            modal="faq-category-new" />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $categoryPage->links() }}
                        </div>
                    </section>
                    <x-forms.modal id="faq-category-new" :title="__('faq::messages.editor.add_category')" :action="route('plugins.faq.admin.categories.store')">
                        @include('faq::admin.categories.partials.fields', ['editingCategory' => null])
                    </x-forms.modal>
                    @foreach ($categoryPage as $item)
                        <x-forms.modal :id="'faq-category-' . $item->id" :title="__('faq::messages.editor.edit_category')" :action="route('plugins.faq.admin.categories.update', $item->id)" method="PUT">
                            @include('faq::admin.categories.partials.fields', ['editingCategory' => $item])
                        </x-forms.modal>
                        <x-forms.modal :id="'faq-category-delete-' . $item->id" :title="__('common.actions.delete')" :action="route('plugins.faq.admin.categories.destroy', $item->id)" method="DELETE"
                            :submit="__('common.actions.delete')" size="sm">
                            <p>{{ __('faq::messages.editor.delete_category') }}</p>
                            <strong>{{ $item->name }}</strong>
                        </x-forms.modal>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
