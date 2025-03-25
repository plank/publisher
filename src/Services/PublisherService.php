<?php

namespace Plank\Publisher\Services;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Plank\LaravelModelResolver\Facades\Models;
use Plank\Publisher\Contracts\Publishable;

class PublisherService
{
    protected bool $draftContentAllowed = false;

    public function shouldEnableDraftContent(Request $request): bool
    {
        if (Gate::has('view-draft-content') && $this->shouldCheckGate() && Gate::denies('view-draft-content')) {
            return false;
        }

        if ($patterns = config()->get('publisher.draft_paths')) {
            if ($request->is($patterns)) {
                return true;
            }
        }

        return (bool) $request->query(config()->get('publisher.urls.previewKey'));
    }

    public function canPublish(Publishable&Model $model): bool
    {
        if (! Gate::has('publish')) {
            return true;
        }

        return ! $this->shouldCheckGate()
            || Gate::authorize('publish', $model);
    }

    public function canUnpublish(Publishable&Model $model): bool
    {
        if (! Gate::has('unpublish')) {
            return true;
        }

        return ! $this->shouldCheckGate()
            || Gate::authorize('unpublish', $model);
    }

    protected function shouldCheckGate(): bool
    {
        return ! App::runningInConsole() || App::runningUnitTests();
    }

    public function draftContentRestricted(): bool
    {
        return ! $this->draftContentAllowed;
    }

    public function draftContentAllowed(): bool
    {
        return $this->draftContentAllowed;
    }

    public function allowDraftContent(): void
    {
        $this->draftContentAllowed = true;
    }

    public function restrictDraftContent(): void
    {
        $this->draftContentAllowed = false;
    }

    public function withDraftContent(Closure $closure): mixed
    {
        $draftState = $this->draftContentAllowed;

        try {
            $this->draftContentAllowed = true;

            return $closure();
        } finally {
            $this->draftContentAllowed = $draftState;
        }
    }

    public function withoutDraftContent(Closure $closure): mixed
    {
        $draftState = $this->draftContentAllowed;

        try {
            $this->draftContentAllowed = false;

            return $closure();
        } finally {
            $this->draftContentAllowed = $draftState;
        }
    }

    /**
     * @return Collection<Model&Publishable>
     */
    public function publishableModels(): Collection
    {
        return Models::implements(Publishable::class);
    }
}
