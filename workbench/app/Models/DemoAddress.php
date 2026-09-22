<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DemoAddress extends Model
{
    protected $table = 'demo_addresses';

    protected $guarded = [];

    /** @return BelongsTo<DemoUser, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(DemoUser::class, 'user_id');
    }
}
