<?php

namespace KraftInk\Plugins\Faq\Services\Imports\Azuriom;

use App\Kraft\Migration\ImportContext;
use App\Kraft\Migration\MappingRegistry;
use KraftInk\Plugins\Faq\Services\Imports\Writer;

final class Mappings
{
    public function __construct(private readonly Writer $writer)
    {
    }

    public function register(MappingRegistry $registry): void
    {
        $registry->register(
            'azuriom',
            'faq_questions',
            $this->document(...),
            50,
            'faq',
            $this->writer->replacements(...),
        );
    }

    private function document(array $row, ImportContext $context): array
    {
        return $this->writer->document($context, $row['id'], [
            'slug' => $row['slug'] ?? $row['name'],
            'title' => $row['name'],
            'body' => $row['answer'],
            'position' => $row['position'] ?? ($row['order'] ?? 0),
            'created_at' => $row['created_at'] ?? now(),
            'updated_at' => $row['updated_at'] ?? now(),
        ]);
    }
}
