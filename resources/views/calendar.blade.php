<x-layouts::app :title="__('calendar.title')">
    <x-page-header :title="__('calendar.title')" :description="__('calendar.description')" />

    <div class="rounded-ui border border-border bg-surface p-10 text-center">
        <p class="text-[17px] font-bold text-ink">{{ __('calendar.placeholder_title') }}</p>
        <p class="mt-1 text-sm text-subtle">{{ __('calendar.placeholder_body') }}</p>
    </div>
</x-layouts::app>
