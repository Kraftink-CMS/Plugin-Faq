@use('KraftInk\Plugins\Faq\Models\Category')

@php
    $categoryModalId = $editingCategory ? 'faq-category-' . $editingCategory->id : 'faq-category-new';
@endphp
@php
    $categoryOld = old('_modal') === $categoryModalId;
@endphp
@php
    $categoryValue = fn($field, $fallback = null) => $categoryOld
        ? old($field, $editingCategory?->{$field} ?? $fallback)
        : $editingCategory?->{$field} ?? $fallback;
    $parentOptions = $editingCategory ? $categories->whereNotIn('id', Category::descendantIds($editingCategory->id, $categories)) : $categories;
@endphp
<div>
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="form-label" for="{{ $categoryModalId }}-name">{{ __('faq::messages.editor.category_name') }}</label>
            <input class="form-control" id="{{ $categoryModalId }}-name" name="name" value="{{ $categoryValue('name') }}"
                maxlength="120" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="{{ $categoryModalId }}-locale">{{ __('faq::messages.common.language') }}</label>
            <select class="form-select" id="{{ $categoryModalId }}-locale" name="locale">
                @foreach (config('kraft.locales') as $code => $label)
                    <option value="{{ $code }}" @selected($categoryValue('locale', app()->getLocale()) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="{{ $categoryModalId }}-parent">{{ __('faq::messages.editor.parent') }}</label>
        <select class="form-select" id="{{ $categoryModalId }}-parent" name="parent_id"
            data-option-filter="locale">
            <option value="">{{ __('faq::messages.editor.no_parent') }}</option>
            @foreach ($parentOptions as $parent)
                <option value="{{ $parent->id }}"
                    data-option-group="{{ $parent->locale }}" @selected((int) $categoryValue('parent_id') === $parent->id)>{{ $parent->name }} ·
                    {{ strtoupper($parent->locale) }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label"
            for="{{ $categoryModalId }}-description">{{ __('faq::messages.editor.category_description') }}</label>
        <textarea class="form-control" id="{{ $categoryModalId }}-description" name="description" rows="3"
            maxlength="1000">{{ $categoryValue('description') }}</textarea>
    </div>
    <div>
        <label class="form-label" for="{{ $categoryModalId }}-position">{{ __('faq::messages.common.position') }}</label>
        <input class="form-control" id="{{ $categoryModalId }}-position" name="position" type="number" min="0"
            max="100000" value="{{ $categoryValue('position', 0) }}" required>
    </div>
</div>
