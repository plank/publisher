<?php

namespace Plank\Publisher\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Plank\Publisher\Contracts\Publishable;
use Symfony\Component\Finder\SplFileInfo;

class PublisherService
{
    protected bool $draftContentAllowed = false;

    public function shouldEnableDraftContent(Request $request): bool
    {
        if (Gate::denies('view-draft-content')) {
            return false;
        }

        if (($path = config()->get('publisher.admin.path')) && str($request->path())->startsWith($path)) {
            return true;
        }

        return (bool) $request->query(config('publisher.urls.previewKey'));
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

    /**
     * @return Collection<Model&Publishable>
     */
    public function publishableModels(): Collection
    {
        return Collection::wrap(File::allFiles(app_path()))
            ->map(fn (SplFileInfo $file) => $this->getClassName($file))
            ->filter(fn (string $className) => class_exists($className))
            ->filter(function (string $class) {
                return is_a($class, Model::class, true)
                    && is_a($class, Publishable::class, true)
                    && ! (new \ReflectionClass($class))->isAbstract();
            })
            ->map(fn ($class) => new $class)
            ->values();
    }

    protected function getClassName(SplFileInfo $modelFile): string
    {
        return str($modelFile->getRelativePathname())
            ->replace(DIRECTORY_SEPARATOR, '\\')
            ->replace('.php', '')
            ->prepend('App/');
    }
}
