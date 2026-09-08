@props(['head' => []])

<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        @if (count($head))
            <thead class="bg-gray-50">
                <tr>
                    @foreach ($head as $col)
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-gray-100">
            {{ $slot }}
        </tbody>
    </table>
</div>
