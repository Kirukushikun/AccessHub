@props(['title' => 'Nothing here yet', 'message' => null])

<div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
    <p class="text-sm font-medium text-gray-900">{{ $title }}</p>
    @if ($message)
        <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">{{ $message }}</p>
    @endif
    @isset($action)
        <div class="mt-4 flex justify-center">{{ $action }}</div>
    @endisset
</div>
