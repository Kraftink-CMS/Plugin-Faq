<?php

namespace KraftInk\Plugins\Faq\Providers;

use App\Events\CollectingSeoPages;
use App\Events\CollectingSitemapEntries;
use App\Kraft\Features;
use App\Kraft\LocalePolicy;
use App\Kraft\Migration\MappingRegistry;
use App\Kraft\Widgets\WidgetRegistry;
use App\Models\Accounts\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use KraftInk\Plugins\Faq\Listeners\DocumentRetention;
use KraftInk\Plugins\Faq\Services\DocumentTrash;
use KraftInk\Plugins\Faq\Services\Documents;
use KraftInk\Plugins\Faq\Services\Imports\ImportMappings;
use App\Events\PruningData;
use App\Kraft\Trash\TrashRegistry;
use KraftInk\Plugins\Faq\Models\Document;

class PluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(PruningData::class, [DocumentRetention::class, 'prune']);
        $this->registerWidgets(app(WidgetRegistry::class));
        app(Features::class)->register('faq', fn (?User $user) => app(Documents::class)->visible($user)->exists());
        Event::listen(CollectingSitemapEntries::class, function (CollectingSitemapEntries $event) {
            $event->source(function () use ($event) {
                $documents = app(Documents::class);
                $locales = app(LocalePolicy::class)->allowsChoice()
                    ? array_keys(config('kraft.locales'))
                    : [$event->locale];
                foreach (
                    Document::query()
                        ->indexable()
                        ->whereIn('locale', $locales)
                        ->select(['slug', 'locale', 'updated_at'])
                        ->orderBy('locale')
                        ->orderBy('id')
                        ->cursor() as $document
                ) {
                    yield [
                        'url' => $documents->url($document),
                        'modified' => $document->updated_at,
                    ];
                }
            });
        });
        app(ImportMappings::class)->register(app(MappingRegistry::class));
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'faq');
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'faq');
        app(DocumentTrash::class)->register(app(TrashRegistry::class));
        Event::listen(CollectingSeoPages::class, function (CollectingSeoPages $event): void {
            $event->add(
                'plugins.faq.public.documents.index',
                __('faq::seo.pages.index'),
                [
                    'category' => __('faq::seo.variables.category'),
                    'category_description' => __('faq::seo.variables.category_description'),
                    'documents_count' => __('faq::seo.variables.documents_count'),
                ],
                defaults: ['title' => '{category}', 'description' => '{description}'],
                excerptVariables: ['category_description'],
            );
            $event->add(
                'plugins.faq.public.documents.show',
                __('faq::seo.pages.question'),
                [
                    'question' => __('faq::seo.variables.question'),
                    'answer' => __('faq::seo.variables.answer'),
                    'category' => __('faq::seo.variables.category'),
                ],
                ['plugins.faq.public.documents.localized'],
                ['title' => '{question}', 'description' => '{description}'],
                excerptVariables: ['answer'],
            );
        });
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
    }

    private function registerWidgets(WidgetRegistry $widgets): void
    {
        $widgets->register(
            owner: 'faq',
            id: 'questions',
            label: 'faq::widgets.questions',
            render: fn (array $context, array $config) => view('faq::widgets.questions', [
                'documents' => app(Documents::class)
                    ->visible($context['user'] ?? null)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->limit($config['limit'])
                    ->get(['id', 'title', 'slug', 'locale']),
            ]),
            fields: ['limit' => ['type' => 'integer', 'label' => 'faq::widgets.count', 'min' => 1, 'max' => 12]],
            permission: 'faq.view',
            zones: ['home'],
            defaults: ['limit' => 5],
        );
    }
}
