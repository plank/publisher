<?php

namespace Plank\Publisher\Repositories;

class PublisherRepository
{
    protected bool $draftContentAllowed = false;

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
}
