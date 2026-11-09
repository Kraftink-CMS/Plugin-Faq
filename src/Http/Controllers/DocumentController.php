<?php

namespace KraftInk\Plugins\Faq\Http\Controllers;

use App\Kraft\System\TableQuery;
use App\Kraft\Html;
use App\Kraft\LocalePolicy;
use App\Kraft\Site\Seo;
use App\Models\Accounts\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use KraftInk\Plugins\Faq\Models\Category;
use KraftInk\Plugins\Faq\Models\Document;
use KraftInk\Plugins\Faq\Models\DocumentRevision;
use KraftInk\Plugins\Faq\Services\DocumentImages;
use KraftInk\Plugins\Faq\Services\Documents;
use KraftInk\Plugins\Faq\Services\StructuredData;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DocumentController
{
    public const string PLUGIN = 'faq';

    public function index(Request $request, Documents $library): Response
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => [
                'nullable',
                'integer',
                Rule::exists('faq__categories', 'id')->where('locale', app()->getLocale()),
            ],
        ]);
        $query = $library->visible($request->user());
        if ($request->filled('q')) {
            $query->where(
                fn ($query) => $query
                    ->whereLike('title', '%'.$data['q'].'%')
                    ->orWhereLike('body', '%'.$data['q'].'%'),
            );
        }
        $category = !empty($data['category']) ? Category::query()->find($data['category']) : null;
        if ($category) {
            $query->whereIn('category_id', $library->descendants($category->id));
        }
        $documents = $query->with('category')->orderBy('position')->orderBy('title')->paginate(30)->withQueryString();
        $pageDocuments = $documents->getCollection();
        $indexableIds = Document::query()->indexable()->whereKey($pageDocuments->modelKeys())->pluck('id');

        return response()
            ->view('faq::default.documents.index', [
                'documents' => $documents,
                'category' => $category,
                'navigation' => $library->navigation($request->user(), $category?->id),
                'plugin' => static::PLUGIN,
                'seo' => [
                    'excerpt' => $category?->description,
                    'canonical' => $category
                        ? route('plugins.faq.public.documents.index', ['category' => $category->id])
                        : '',
                    'structured_data' => app(StructuredData::class)->forDocuments($pageDocuments->whereIn('id', $indexableIds)),
                ],
                'seoVariables' => [
                    'category' => $category?->name ?? __('faq::messages.common.faq'),
                    'category_description' => $category?->description,
                    'documents_count' => $documents->total(),
                ],
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, string $slug, Documents $library): Response
    {
        $document = $library->visible($request->user())->where('slug', $slug)->first();
        if ($document === null) {
            abort(404);
        }

        return $this->render($request, $document, $library);
    }

    private function render(Request $request, Document $document, Documents $library): Response
    {
        $siblings = $library
            ->visible($request->user())
            ->where('category_id', $document->category_id)
            ->orderBy('position')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'locale']);
        $position = $siblings->search(fn ($item) => $item->id === $document->id);
        $alternates = $library->indexableAlternates($document);
        $canIndex = $alternates === null
            ? Document::query()->indexable()->whereKey($document->id)->exists()
            : isset($alternates[$document->locale]);
        $category = $document->category;

        return response()
            ->view('faq::default.documents.show', [
                'document' => $document,
                'category' => $category,
                'navigation' => $library->navigation($request->user(), $document->category_id),
                'translations' => $library->translations($document, $request->user()),
                'previous' => $position > 0 ? $siblings[$position - 1] : null,
                'next' => $siblings[$position + 1] ?? null,
                'plugin' => static::PLUGIN,
                'seo' => [
                    'excerpt' => $document->body,
                    'seo_title' => $document->seo_title,
                    'seo_description' => $document->seo_description,
                    'canonical' => $library->url($document),
                    'robots' => $canIndex ? '' : Seo::NO_INDEX,
                    'alternates' => $alternates ?? [],
                    'structured_data' => $canIndex ? app(StructuredData::class)->forDocuments(collect([$document])) : [],
                ],
                'seoVariables' => [
                    'question' => $document->title,
                    'answer' => $canIndex ? $document->body : '',
                    'category' => $category?->name,
                ],
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function localized(Request $request, string $locale, string $slug, Documents $library): Response
    {
        $policy = app(LocalePolicy::class);
        $allowsChoice = $policy->allowsChoice();
        if (!$policy->valid($locale) || (!$allowsChoice && $locale !== $policy->defaultLocale())) {
            abort(404);
        }
        $document = $library->visible($request->user(), $locale)->where('slug', $slug)->first();
        if ($document === null) {
            abort(404);
        }
        $cookie = $allowsChoice ? $policy->choose($request, $locale) : null;
        app()->setLocale($locale);
        $response = $this->render($request, $document, $library);

        return $cookie ? $response->withCookie($cookie) : $response;
    }

    public function admin(Request $request, Documents $library): View
    {
        $data = $request->validate([
            'tab' => ['nullable', Rule::in(['documents', 'categories'])],
            'q' => 'nullable|string|max:100',
            'locale' => ['nullable', Rule::in(array_keys(config('kraft.locales')))],
            'state' => ['nullable', Rule::in(['published', 'draft'])],
        ]);
        $tab = $data['tab'] ?? ($request->route('section') ?? 'documents');
        if (str_starts_with((string) $request->old('_modal'), 'faq-category-')) {
            $tab = 'categories';
        }
        $query = Document::query()->whereNull('deleted_at');
        if ($request->filled('q')) {
            $query->whereLike('title', '%'.$data['q'].'%');
        }
        if (!empty($data['locale'])) {
            $query->where('locale', $data['locale']);
        }
        if (!empty($data['state'])) {
            $query->where('published', $data['state'] === 'published');
        }

        return view('faq::admin.documents.index', [
            'documents' => TableQuery::paginate(
                $query->orderBy('position')->orderBy('title')->orderBy('id'),
                ['title' => 'title', 'locale' => 'locale', 'published' => 'published'],
                pageName: 'documents_page',
                search: [],
            )
                ->withQueryString()
                ->appends(['tab' => 'documents']),
            'categoryPage' => TableQuery::paginate(
                Category::query()->orderBy('position')->orderBy('name')->orderBy('id'),
                ['name' => 'name', 'locale' => 'locale', 'position' => 'position'],
                pageName: 'categories_page',
                search: ['name'],
            )
                ->withQueryString()
                ->appends(['tab' => 'categories']),
            'categories' => Category::query()
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id', 'locale']),
            'tab' => $tab,
            'plugin' => static::PLUGIN,
        ]);
    }

    public function edit(?int $id = null): View
    {
        $document = $id ? Document::query()->whereNull('deleted_at')->find($id) : null;
        if ($id && !$document) {
            abort(404);
        }
        $translations = $document?->translation_group
            ? Document::query()
                ->where('translation_group', $document->translation_group)
                ->where('id', '!=', $id)
                ->pluck('id')
                ->all()
            : [];

        return view('faq::admin.documents.edit', [
            'document' => $document,
            'categories' => app(Documents::class)->categories(),
            'translations' => Document::query()
                ->where(fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', $translations))
                ->when($id, fn ($query) => $query->where('id', '!=', $id))
                ->orderBy('title')
                ->get(['id', 'title', 'locale', 'deleted_at']),
            'translationId' => $translations[0] ?? null,
            'plugin' => static::PLUGIN,
        ]);
    }

    public function save(Request $request, ?int $id = null): RedirectResponse
    {
        $document = $id ? Document::query()->whereNull('deleted_at')->find($id) : null;
        if ($id && !$document) {
            abort(404);
        }
        $data = $request->validate([
            'title' => 'required|string|max:180',
            'slug' => [
                'required',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',
                'max:180',
                Rule::unique('faq__documents')->where('locale', $request->input('locale'))->ignore($id),
            ],
            'body' => 'required|string|max:100000',
            'locale' => ['required', Rule::in(array_keys(config('kraft.locales')))],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('faq__categories', 'id')->where('locale', $request->input('locale')),
            ],
            'position' => 'required|integer|min:0|max:100000',
            'permission' => 'nullable|regex:/^[a-zA-Z0-9_.-]+$/D|max:100',
            'published' => 'sometimes|boolean',
            'translation_id' => 'nullable|integer|exists:faq__documents,id',
            'seo_title' => 'nullable|string|max:180',
            'seo_description' => 'nullable|string|max:320',
            'noindex' => 'sometimes|boolean',
        ]);
        $data['body'] = Html::clean($data['body']);
        $data['published'] = $request->boolean('published');
        $data['noindex'] = $request->has('noindex') ? $request->boolean('noindex') : $document?->noindex ?? false;
        $data['updated_at'] = now();
        DB::transaction(function () use ($data, $document, $id, $request) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($document) {
                $document = Document::query()->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            }
            if ($id && !$document) {
                abort(404);
            }
            if ($request->has('translation_id')) {
                $data['translation_group'] = app(Documents::class)->linkTranslation(
                    $id,
                    $data['translation_id'] ?? null,
                    $data['locale'],
                );
            } elseif (
                $document?->translation_group &&
                $document->locale !== $data['locale'] &&
                Document::query()
                    ->where('translation_group', $document->translation_group)
                    ->where('locale', $data['locale'])
                    ->where('id', '!=', $id)
                    ->exists()
            ) {
                throw ValidationException::withMessages(['locale' => __('faq::messages.editor.translation_exists')]);
            }
            unset($data['translation_id']);
            if (
                !$request->has('category_id') &&
                $document?->category_id &&
                Category::query()->where('id', $document->category_id)->where('locale', '!=', $data['locale'])->exists()
            ) {
                $data['category_id'] = null;
            }
            if ($document) {
                $document->recordRevision($request->user()->id);
                Document::query()->where('id', $id)->update($data);
            } else {
                $id = Document::query()->insertGetId($data + ['created_at' => now()]);
            }
            app(DocumentImages::class)->attach($id, $data['body'], $request->user());
        }, 3);

        return redirect()->route('plugins.faq.admin.documents.index')->with('status', __('faq::messages.common.saved'));
    }

    public function saveCategory(Request $request, ?int $id = null): RedirectResponse
    {
        if ($id && !Category::query()->where('id', $id)->exists()) {
            abort(404);
        }
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('faq__categories')
                    ->where('locale', $request->input('locale'))
                    ->where('parent_id', $request->filled('parent_id') ? $request->integer('parent_id') : null)
                    ->ignore($id),
            ],
            'locale' => ['required', Rule::in(array_keys(config('kraft.locales')))],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('faq__categories', 'id')->where('locale', $request->input('locale')),
            ],
            'description' => 'nullable|string|max:1000',
            'position' => 'required|integer|min:0|max:100000',
        ]);
        DB::transaction(function () use ($data, $id) {
            $categories = Category::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'parent_id'])
                ->keyBy('id');
            $parent = $data['parent_id'] ?? null;
            $seen = $id ? [$id] : [];
            for ($depth = 0; $parent; $depth++) {
                if ($depth >= 19 || in_array((int) $parent, $seen, true)) {
                    throw ValidationException::withMessages(['parent_id' => __('faq::messages.editor.invalid_parent')]);
                }
                $seen[] = (int) $parent;
                $parent = $categories[$parent]->parent_id ?? null;
            }
            if ($id) {
                $categories[$id]->parent_id = $data['parent_id'] ?? null;
                $descendants = [$id];
                for ($depth = count($seen); $descendants; $depth++) {
                    if ($depth > 20) {
                        throw ValidationException::withMessages([
                            'parent_id' => __('faq::messages.editor.invalid_parent'),
                        ]);
                    }
                    $descendants = $categories->whereIn('parent_id', $descendants)->pluck('id')->all();
                }
            }
            if (
                $id &&
                (Document::query()->where('category_id', $id)->where('locale', '!=', $data['locale'])->exists() ||
                    Category::query()->where('parent_id', $id)->where('locale', '!=', $data['locale'])->exists())
            ) {
                throw ValidationException::withMessages([
                    'locale' => __('faq::messages.editor.category_language_in_use'),
                ]);
            }
            $data['updated_at'] = now();
            if ($id) {
                Category::query()->where('id', $id)->update($data);
            } else {
                Category::query()->insert($data + ['created_at' => now()]);
            }
        }, 3);

        return redirect()
            ->route('plugins.faq.admin.categories.index')
            ->with('status', __('faq::messages.common.saved'));
    }

    public function destroyCategory(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            if (Category::query()->where('id', $id)->lockForUpdate()->first() === null) {
                abort(404);
            }
            Document::query()
                ->where('category_id', $id)
                ->update(['category_id' => null]);
            Category::query()
                ->where('parent_id', $id)
                ->update(['parent_id' => null]);
            Category::query()->where('id', $id)->delete();
        }, 3);

        return redirect()
            ->route('plugins.faq.admin.categories.index')
            ->with('status', __('faq::messages.common.deleted'));
    }

    public function revisions(int $id): View
    {
        $document = Document::query()->whereNull('deleted_at')->find($id);
        if ($document === null) {
            abort(404);
        }

        return view('faq::admin.documents.revisions', [
            'document' => $document,
            'revisions' => TableQuery::paginate(
                DocumentRevision::query()->where('document_id', $id)->orderByDesc('id'),
                ['created_at' => 'created_at'],
                pageName: 'page',
                search: [],
            ),
            'plugin' => static::PLUGIN,
        ]);
    }

    public function restore(Request $request, int $id, int $revision): RedirectResponse
    {
        DB::transaction(function () use ($request, $id, $revision) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $document = Document::query()->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            $record = $document?->revisions()->find($revision);
            if (!($document && $record)) {
                abort(404);
            }
            $snapshot = $record->snapshot;
            if (!empty($snapshot['anonymized'])) {
                throw new HttpException(410, __('faq::messages.privacy.revision_removed'));
            }
            $data = array_intersect_key(
                $snapshot,
                array_flip([
                    'title',
                    'body',
                    'category_id',
                    'position',
                    'permission',
                    'published',
                    'seo_title',
                    'seo_description',
                    'noindex',
                ]),
            );
            if (
                !empty($data['category_id']) &&
                !Category::query()->where('id', $data['category_id'])->where('locale', $document->locale)->exists()
            ) {
                $data['category_id'] = null;
            }
            $data['body'] = Html::clean($data['body'] ?? '');
            $data['updated_at'] = now();
            $document->recordRevision($request->user()->id);
            Document::query()->where('id', $id)->update($data);
            app(DocumentImages::class)->attach($id, $data['body'], $request->user());
        }, 3);

        return redirect()
            ->route('plugins.faq.admin.documents.edit', $id)
            ->with('status', __('faq::messages.common.restored'));
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $document = Document::query()->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            if ($document === null) {
                abort(404);
            }
            Document::query()
                ->where('id', $id)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        }, 3);

        return redirect()
            ->route('plugins.faq.admin.documents.index')
            ->with('status', __('site.content.moved_to_trash'));
    }
}
