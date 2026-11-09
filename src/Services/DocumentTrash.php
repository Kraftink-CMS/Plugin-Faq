<?php

namespace KraftInk\Plugins\Faq\Services;

use App\Kraft\Audit;
use App\Kraft\Trash\TrashRegistry;
use App\Kraft\Trash\TrashSource;
use App\Models\Accounts\User;
use Illuminate\Support\Facades\DB;
use KraftInk\Plugins\Faq\Models\Document;
use Illuminate\Validation\ValidationException;

class DocumentTrash
{
    public function restore(User $actor, int $id): void
    {
        DB::transaction(function () use ($id) {
            $document = Document::query()->where('id', $id)->whereNotNull('deleted_at')->lockForUpdate()->first();
            if ($document === null) {
                abort(404);
            }
            if (
                Document::query()
                    ->where('locale', $document->locale)
                    ->where('slug', $document->slug)
                    ->where('id', '!=', $id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'slug' => __('validation.unique', ['attribute' => __('faq::messages.editor.address')]),
                ]);
            }
            Document::query()
                ->where('id', $id)
                ->update(['deleted_at' => null, 'updated_at' => now()]);
        }, 3);
    }

    public function purge(User $actor, int $id): void
    {
        DB::transaction(function () use ($actor, $id) {
            $document = Document::query()->where('id', $id)->whereNotNull('deleted_at')->lockForUpdate()->first();
            if ($document === null) {
                abort(404);
            }
            app(DocumentImages::class)->purge($id);
            Document::query()->where('id', $id)->delete();
            Audit::record('faq.document_purged', ['id' => $id], $actor);
        }, 3);
    }

    public function register(TrashRegistry $registry): void
    {
        $registry->register(
            new TrashSource(
                'faq.documents',
                'faq::messages.common.faq',
                ['faq.manage'],
                ['faq.manage'],
                fn (User $user) => Document::query()
                    ->whereNotNull('deleted_at')
                    ->select(['id', 'title', 'deleted_at'])
                    ->selectRaw('NULL AS restore_reason'),
                fn (User $actor, int $id) => app(DocumentTrash::class)->restore($actor, $id),
                purgePermissions: ['faq.purge'],
                purge: fn (User $actor, int $id) => app(DocumentTrash::class)->purge($actor, $id),
            ),
        );
    }
}
