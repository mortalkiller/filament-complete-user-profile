<div>
    @if ($this->isSupported())
        {{ $this->table }}
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $this->getUnsupportedReason() }}
        </p>
    @endif
</div>
