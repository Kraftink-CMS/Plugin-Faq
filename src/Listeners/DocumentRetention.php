<?php

namespace KraftInk\Plugins\Faq\Listeners;

use App\Events\PruningData;
use App\Kraft\System\RevisionRetention;
use KraftInk\Plugins\Faq\Models\Document;
use KraftInk\Plugins\Faq\Models\DocumentRevision;
use KraftInk\Plugins\Faq\Services\DocumentImages;

class DocumentRetention
{
    public function prune(PruningData $event): void
    {
        $event->add(
            'faq.history',
            app(RevisionRetention::class)->prune(DocumentRevision::query(), 'document_id', $event->dryRun),
        );

        if (!$event->dryRun) {
            $references = 0;
            Document::query()
                ->orderBy('id')
                ->chunkById(100, function ($documents) use (&$references) {
                    foreach ($documents as $document) {
                        $references += app(DocumentImages::class)->retain($document->id);
                    }
                });
            $event->add('faq.image_references', $references);
        }
        $event->add('faq.images', app(DocumentImages::class)->cleanup(dryRun: $event->dryRun));
    }
}
