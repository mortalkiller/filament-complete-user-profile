<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

class Overview extends AbstractFeature
{
    protected int $sort = 10;

    public function getId(): string
    {
        return 'overview';
    }
}
