<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Mortalkiller\FilamentCompleteUserProfile\Contracts\SessionStore;

class Sessions extends AbstractFeature
{
    protected int $sort = 40;

    public function getId(): string
    {
        return 'sessions';
    }

    public function getRequirementIssue(SessionStore $store): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return $store->getUnsupportedReason();
    }
}
