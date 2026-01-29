@php
    if (!($paginator instanceof \Illuminate\Contracts\Pagination\Paginator) || !$paginator->hasPages()) {
        return;
    }
@endphp
<div class="py-8">
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation"
            class="flex justify-between items-center space-x-2 font-sans">
            <!-- Previous Button -->
            <section>
                @if ($paginator->currentPage() === 1)
                    <button class="grid place-items-center w-fit opacity-50" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                            class="size-12 md:size-6">
                            <path fill-rule="evenodd"
                                d="M9.78 4.22a.75.75 0 0 1 0 1.06L7.06 8l2.72 2.72a.75.75 0 1 1-1.06 1.06L5.47 8.53a.75.75 0 0 1 0-1.06l3.25-3.25a.75.75 0 0 1 1.06 0Z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                @else
                    <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev"
                        class=" cursor-pointer hover:bg-zinc-100 w-fit dark:hover:bg-zinc-950 grid place-items-center rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                            class="size-12 md:size-6">
                            <path fill-rule="evenodd"
                                d="M9.78 4.22a.75.75 0 0 1 0 1.06L7.06 8l2.72 2.72a.75.75 0 1 1-1.06 1.06L5.47 8.53a.75.75 0 0 1 0-1.06l3.25-3.25a.75.75 0 0 1 1.06 0Z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif
            </section>

            <!-- Page Numbers -->
            <section class="items-center gap-x-6  sm:flex">
                @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <button disabled class="bg-zinc-100 dark:bg-zinc-950 rounded  w-fit px-3 py-1">
                            {{ $page }}
                        </button>
                    @else
                        <button wire:click="gotoPage({{ $page }})"
                            class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-950 rounded w-fit px-3 py-1">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            </section>

            <!-- Next Button -->
            <section>
                @if ($paginator->currentPage() === $paginator->lastPage())
                    <button type="button" class="grid place-items-center disabled opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                            class="size-12 md:size-6">
                            <path fill-rule="evenodd"
                                d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                @else
                    <button type="button" wire:click="nextPage" wire:loading.attr="disabled" rel="next"
                        class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-950 grid place-items-center rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                            class="size-12 md:size-6">
                            <path fill-rule="evenodd"
                                d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif
            </section>
        </nav>
    @endif
</div>
