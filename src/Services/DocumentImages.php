<?php

namespace KraftInk\Plugins\Faq\Services;

use App\Kraft\LocalePolicy;
use App\Kraft\Media\EditorImageScope;
use App\Kraft\Media\PrivateEditorImages;
use App\Models\Accounts\User;
use KraftInk\Plugins\Faq\Models\Document;
use KraftInk\Plugins\Faq\Models\DocumentImage;
use KraftInk\Plugins\Faq\Models\DocumentRevision;
use KraftInk\Plugins\Faq\Models\Image;

class DocumentImages extends PrivateEditorImages
{
    public function __construct()
    {
        parent::__construct(
            new EditorImageScope(
                'faq',
                Image::class,
                DocumentImage::class,
                Document::class,
                'plugins.faq.public.images.show',
                'faq.manage',
                fn (?User $user) => app(Documents::class)
                    ->readable($user)
                    ->whereIn(
                        'locale',
                        app(LocalePolicy::class)->allowsChoice()
                            ? array_keys(config('kraft.locales'))
                            : [app(LocalePolicy::class)->defaultLocale()],
                    ),
                history: fn () => DocumentRevision::query()->select(
                    'document_id as resource_id',
                    'snapshot->body as body',
                ),
            ),
        );
    }
}
