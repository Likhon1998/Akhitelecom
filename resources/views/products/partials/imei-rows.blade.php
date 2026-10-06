{{--
    One card per phone: IMEI 1, plus an optional IMEI 2 for dual-SIM phones.
    $list     JS expression of the phone array (e.g. "imeis" or "row.imeis"), items are {imei, imei2}
    $name     JS expression of the input name prefix (e.g. "'imeis'")
    $errKey   JS expression of the error key prefix (e.g. "'imeis.'")
    $owner    JS expression of the object holding the phone count, or "null"
    $countKey property on $owner kept equal to the number of phones
    $editable show "+ Add phone" / remove phone buttons
--}}
@php
    $owner = $owner ?? 'null';
    $countKey = $countKey ?? '';
    $editable = $editable ?? false;
    $boxClass = "block w-full rounded-md text-sm py-2 font-mono";
@endphp
<div data-imei-group class="space-y-2">
    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px]">
        <span class="font-semibold"
              :class="{{ $list }}.length && imeiFilled({{ $list }}) === {{ $list }}.length ? 'text-emerald-700' : 'text-slate-600'"
              x-text="imeiFilled({{ $list }}) + ' of ' + {{ $list }}.length + ' phones have their IMEI'"></span>
        <span class="text-slate-400">Scan or type each IMEI — the cursor jumps to the next box. Dual-SIM phone? Click “+ IMEI 2”.</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <template x-for="(phone, i) in {{ $list }}" :key="i">
            <div data-phone class="rounded-lg border border-slate-200 bg-slate-50/50 p-2 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-slate-600" x-text="'Phone ' + (i + 1)"></span>
                    <div class="flex items-center gap-1">
                        <button type="button" x-show="phone.imei2 === null" @click="addSecondImei(phone, $event)"
                                class="rounded px-1.5 py-0.5 text-[11px] font-semibold text-orange-700 hover:bg-orange-50">+ IMEI 2</button>
                        @if($editable)
                            <button type="button" @click="{{ $list }}.splice(i, 1)" title="Remove this phone"
                                    class="rounded px-1.5 py-0.5 text-[11px] font-semibold text-slate-400 hover:bg-rose-50 hover:text-rose-600">Remove phone</button>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-12 shrink-0 text-[10px] font-semibold uppercase text-slate-400"
                              x-text="phone.imei2 === null ? 'IMEI' : 'IMEI 1'"></span>
                        <input type="text" data-imei inputmode="numeric" autocomplete="off" maxlength="32"
                               :name="{{ $name }} + '[' + i + '][imei]'"
                               x-model="phone.imei"
                               @input="delete imeiErrors[{{ $errKey }} + i]"
                               @keydown.enter.prevent="imeiNext($event)"
                               @paste="imeiPaste({{ $list }}, i, $event, {{ $owner }}, '{{ $countKey }}')"
                               class="{{ $boxClass }}"
                               :class="{
                                   'border-red-400 bg-red-50 focus:border-red-500 focus:ring-red-200': imeiErrors[{{ $errKey }} + i] || imeiIssue(phone.imei).level === 'error',
                                   'border-amber-400 bg-amber-50': !imeiErrors[{{ $errKey }} + i] && imeiIssue(phone.imei).level === 'warn',
                                   'border-slate-200 bg-white': !imeiErrors[{{ $errKey }} + i] && !imeiIssue(phone.imei).level,
                               }"
                               placeholder="15-digit IMEI">
                    </div>
                    <p class="mt-0.5 pl-14 text-[11px]"
                       x-show="imeiErrors[{{ $errKey }} + i] || imeiIssue(phone.imei).text"
                       :class="imeiErrors[{{ $errKey }} + i] || imeiIssue(phone.imei).level === 'error' ? 'text-red-600' : 'text-amber-700'"
                       x-text="imeiErrors[{{ $errKey }} + i] || imeiIssue(phone.imei).text"></p>
                </div>

                <template x-if="phone.imei2 !== null">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-12 shrink-0 text-[10px] font-semibold uppercase text-slate-400">IMEI 2</span>
                            <input type="text" data-imei data-imei2 inputmode="numeric" autocomplete="off" maxlength="32"
                                   :name="{{ $name }} + '[' + i + '][imei2]'"
                                   x-model="phone.imei2"
                                   @input="delete imeiErrors[{{ $errKey }} + i + '.imei2']"
                                   @keydown.enter.prevent="imeiNext($event)"
                                   class="{{ $boxClass }}"
                                   :class="{
                                       'border-red-400 bg-red-50 focus:border-red-500 focus:ring-red-200': imeiErrors[{{ $errKey }} + i + '.imei2'] || imeiIssue(phone.imei2).level === 'error',
                                       'border-amber-400 bg-amber-50': !imeiErrors[{{ $errKey }} + i + '.imei2'] && imeiIssue(phone.imei2).level === 'warn',
                                       'border-slate-200 bg-white': !imeiErrors[{{ $errKey }} + i + '.imei2'] && !imeiIssue(phone.imei2).level,
                                   }"
                                   placeholder="Second IMEI (dual SIM)">
                            <button type="button" @click="phone.imei2 = null; delete imeiErrors[{{ $errKey }} + i + '.imei2']" title="Remove IMEI 2"
                                    class="shrink-0 rounded-md px-2 py-1.5 text-xs font-semibold text-slate-400 hover:bg-rose-50 hover:text-rose-600">&times;</button>
                        </div>
                        <p class="mt-0.5 pl-14 text-[11px]"
                           x-show="imeiErrors[{{ $errKey }} + i + '.imei2'] || imeiIssue(phone.imei2).text"
                           :class="imeiErrors[{{ $errKey }} + i + '.imei2'] || imeiIssue(phone.imei2).level === 'error' ? 'text-red-600' : 'text-amber-700'"
                           x-text="imeiErrors[{{ $errKey }} + i + '.imei2'] || imeiIssue(phone.imei2).text"></p>
                    </div>
                </template>
            </div>
        </template>
    </div>

    @if($editable)
        <button type="button" @click="{{ $list }}.push({ imei: '', imei2: null }); $nextTick(() => [...$el.closest('[data-imei-group]').querySelectorAll('input[data-imei]:not([data-imei2])')].pop()?.focus())"
                class="text-xs font-semibold text-orange-700 hover:text-orange-800 px-2 py-1 rounded-md border border-orange-200 bg-orange-50">
            + Add phone
        </button>
    @endif
</div>
