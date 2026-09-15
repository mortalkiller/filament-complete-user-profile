<div class="space-y-4">
    @if ($issue = $this->getRequirementIssue())
        <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm text-warning-700 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-300">
            {{ $issue }}
        </div>
    @endif

    {{ $this->table }}
</div>
