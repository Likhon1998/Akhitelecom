{{--
    One IMEI box per phone.
    $list     JS expression of the IMEI array (e.g. "imeis" or "row.imeis")
    $name     JS expression of the input name prefix (e.g. "'imeis'")
    $errKey   JS expression of the error key prefix (e.g. "'imeis.'")
    $owner    JS expression of the object holding the phone count, or "null"
    $countKey property on $owner kept equal to the number of boxes
    $editable show "+ Add phone" / remove buttons
--}}
@php
    $owner = $owner ?? 'null';
    $countKey = $countKey ?? '';
    $editable = $editable ?? false;
@endphp
<div data-imei-group class="space-y-2">
    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px]">
        <span class="font-semibold"
              :class="{{ $list }}.length && imeiFilled({{ $list }}) === {{ $list }}.length ? 'text-emerald-700' : 'text-slate-600'"
              x-text="imeiFilled({{ $list }}) + ' of ' + {{ $list }}.length + ' phones have an IMEI'"></span>
        <span class="text-slate-400">Scan or type each phone's IMEI — the cursor jumps to the next phone. You can also paste a list.</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-2">
        <template x-for="(imei, i) in {{ $list }}" :key="i">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-16 shrink-0 text-[11px] font-semibold text-slate-500" x-text="'Phone ' + (i + 1)"></span>
                    <input type="text" data-imei inputmode="numeric" autocomplete="off" maxlength="32"
                           :name="{{ $name }} + '[' + i + ']'"
                           x-model="{{ $list }}[i]"
                           @input="delete imeiErrors[{{ $errKey }} + i]"
                           @keydown.enter.prevent="imeiNext($event)"
                           @paste="imeiPaste({{ $list }}, i, $event, {{ $owner }}, '{{ $countKey }}')"
                           class="block w-full rounded-md text-sm py-2 font-mono"
                           :class="{
                               'border-red-400 bg-red-50 focus:border-red-500 focus:ring-red-200': imeiErrors[{{ $errKey }} + i] || imeiIssue({{ $list }}[i]).level === 'error',
                               'border-amber-400 bg-amber-50': !imeiErrors[{{ $errKey }} + i] && imeiIssue({{ $list }}[i]).level === 'warn',
                               'border-slate-200': !imeiErrors[{{ $errKey }} + i] && !imeiIssue({{ $list }}[i]).level,
                           }"
                           placeholder="15-digit IMEI">
                    @if($editable)
                        <button type="button" @click="{{ $list }}.splice(i, 1)" title="Remove this phone"
                                class="shrink-0 rounded-md px-2 py-1.5 text-xs font-semibold text-slate-400 hover:bg-rose-50 hover:text-rose-600">&times;</button>
                    @endif
                </div>
                <p class="mt-0.5 pl-[4.5rem] text-[11px]"
                   x-show="imeiErrors[{{ $errKey }} + i] || imeiIssue({{ $list }}[i]).text"
                   :class="imeiErrors[{{ $errKey }} + i] || imeiIssue({{ $list }}[i]).level === 'error' ? 'text-red-600' : 'text-amber-700'"
                   x-text="imeiErrors[{{ $errKey }} + i] || imeiIssue({{ $list }}[i]).text"></p>
            </div>
        </template>
    </div>

    @if($editable)
        <button type="button" @click="{{ $list }}.push(''); $nextTick(() => [...$el.closest('[data-imei-group]').querySelectorAll('input[data-imei]')].pop()?.focus())"
                class="text-xs font-semibold text-orange-700 hover:text-orange-800 px-2 py-1 rounded-md border border-orange-200 bg-orange-50">
            + Add phone
        </button>
    @endif
</div>
