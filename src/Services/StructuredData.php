<?php

namespace KraftInk\Plugins\Faq\Services;

use App\Kraft\Site\Seo;
use Illuminate\Support\Collection;

class StructuredData
{
    public function forDocuments(Collection $documents): array
    {
        $items = $documents
            ->map(
                fn ($document) => [
                    '@type' => 'Question',
                    'name' => $document->title,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => Seo::text($document->body),
                    ],
                ],
            )
            ->values()
            ->all();
        if ($items === []) {
            return [];
        }

        return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items];
    }
}
