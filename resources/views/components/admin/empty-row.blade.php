{{--
    The row of an admin table with nothing to show. "Nincs találat." when a
    search or filter found nothing; otherwise say what is missing and, in the
    "action" slot, how to add the first one.
--}}
@props(['colspan' => 20])

<tr>
    <td colspan="{{ $colspan }}" class="px-3 py-8">
        <div class="flex flex-col items-center gap-3 text-center text-grey-700">
            <p>{{ $slot }}</p>
            @isset($action)
                <div class="flex flex-wrap justify-center gap-2">{{ $action }}</div>
            @endisset
        </div>
    </td>
</tr>
