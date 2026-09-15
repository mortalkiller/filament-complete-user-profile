<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

class Sessions extends AbstractFeature
{
    protected int $sort = 40;

    public function getId(): string
    {
        return 'sessions';
    }
}
