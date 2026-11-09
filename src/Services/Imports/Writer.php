<?php

namespace KraftInk\Plugins\Faq\Services\Imports;

use App\Kraft\Migration\ImportContext;
use App\Kraft\Migration\ImportedHtml;
use Illuminate\Support\Str;
use KraftInk\Plugins\Faq\Models\Document;

final class Writer
{
    public function document(ImportContext $context, int|string $identity, array $document): array
    {
        $slug = substr(Str::slug($document['slug']) ?: 'document', 0, 180);
        if (
            $context->target
                ->table('faq__documents')
                ->where('locale', config('imports.locale'))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug =
                substr($slug, 0, 150).'-import-'.substr(hash('sha256', $context->runId.':'.$identity), 0, 16);
        }

        return [
            'target_table' => 'faq__documents',
            'target_id' => $context->insert('faq__documents', [
                'locale' => config('imports.locale'),
                'slug' => $slug,
                'title' => mb_substr(strip_tags($document['title']), 0, 180),
                'body' => ImportedHtml::clean($document['body']),
                'position' => max(0, (int) $document['position']),
                'published' => false,
                'created_at' => $document['created_at'],
                'updated_at' => now(),
            ]),
        ];
    }

    public function replacements(): array
    {
        return [
            'faq__document_images' => [
                'resource_id' => Document::query()->where('locale', config('imports.locale'))->pluck('id')->all(),
            ],
            'faq__document_revisions' => [
                'document_id' => Document::query()->where('locale', config('imports.locale'))->pluck('id')->all(),
            ],
            'faq__documents' => ['locale' => [config('imports.locale')]],
        ];
    }
}
