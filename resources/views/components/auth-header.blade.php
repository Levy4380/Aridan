@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1">
    <h1 class="text-[20px] font-bold text-ink">{{ $title }}</h1>
    <p class="text-sm text-subtle">{{ $description }}</p>
</div>
