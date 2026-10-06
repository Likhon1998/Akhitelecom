{{--
    IMEI widget for supply forms. All params are Alpine expressions evaluated in the caller's scope:
      mode      'pick' (choose phones leaving stock), 'enter' (type IMEIs of arriving phones) or '' (hidden)
      options   phones to pick from: [{imei, imei_2}]
      picked    array model of chosen IMEIs (pick mode)
      phones    array model of [{imei, imei2}] rows (enter mode)
      qty       assignable quantity model — follows the number picked, sets the number of rows to enter
      pickName  input name for picked IMEIs, e.g. "'imeis[]'"
      enterName base input name for entered phones, e.g. "'phones'"
--}}
@once
    <style>
        .imei-box { background: rgba(255, 247, 237, .6); }
        .imei-box .imei-hint { text-transform: none; }
        .imei-box .imei-picked { border-color: #fb923c; box-shadow: 0 0 0 1px #fdba74; }
        .imei-box .imei-input:focus { border-color: #fb923c; box-shadow: 0 0 0 1px #fdba74; outline: none; }
    </style>
@endonce
<div x-show="({{ $mode }}) !== ''" x-cloak class="imei-box rounded-xl border border-orange-200 p-3"
     x-effect="
        if (({{ $mode }}) === 'pick') {
            const allowed = ({{ $options }}).map(o => o.imei);
            if ({{ $picked }}.some(i => !allowed.includes(i))) {{ $picked }} = {{ $picked }}.filter(i => allowed.includes(i));
            if (Number({{ $qty }}) !== {{ $picked }}.length) {{ $qty }} = {{ $picked }}.length;
        } else if (({{ $mode }}) === 'enter') {
            const n = Math.max(0, Math.min(200, parseInt({{ $qty }}) || 0));
            while ({{ $phones }}.length < n) {{ $phones }}.push({ imei: '', imei2: '' });
            if ({{ $phones }}.length > n) {{ $phones }}.splice(n);
        }">
    <template x-if="({{ $mode }}) === 'pick'">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wide text-orange-800">
                Which phones?
                <span class="imei-hint font-semibold text-orange-700" x-text="'— tick each phone by IMEI (' + {{ $picked }}.length + ' chosen)'"></span>
            </p>
            <p x-show="!({{ $options }}).length" class="mt-1 text-xs font-semibold text-rose-600">No phones with IMEI in stock here.</p>
            <div class="mt-2 grid max-h-56 gap-1.5 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="ph in {{ $options }}" :key="ph.imei">
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border bg-white px-2.5 py-1.5 font-mono text-xs"
                           :class="{{ $picked }}.includes(ph.imei) ? 'imei-picked' : 'border-slate-200'">
                        <input type="checkbox" :name="{{ $pickName }}" :value="ph.imei" x-model="{{ $picked }}" class="rounded border-slate-300 text-orange-600">
                        <span x-text="ph.imei"></span>
                        <span x-show="ph.imei_2" class="text-slate-400" x-text="'/ ' + ph.imei_2"></span>
                    </label>
                </template>
            </div>
        </div>
    </template>
    <template x-if="({{ $mode }}) === 'enter'">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wide text-orange-800">
                IMEI of each phone
                <span class="imei-hint font-semibold text-orange-700">— one row per phone, IMEI 2 only for dual-SIM phones</span>
            </p>
            <p x-show="!{{ $phones }}.length" class="mt-1 text-xs font-semibold text-orange-700">Enter the quantity first.</p>
            <div class="mt-2 grid gap-2 lg:grid-cols-2">
                <template x-for="(ph, i) in {{ $phones }}" :key="i">
                    <div class="flex items-center gap-2">
                        <span class="w-16 shrink-0 text-[11px] font-bold text-slate-500" x-text="'Phone ' + (i + 1)"></span>
                        <input type="text" :name="{{ $enterName }} + '[' + i + '][imei]'" x-model="ph.imei" required maxlength="32"
                               inputmode="numeric" placeholder="IMEI 1"
                               class="min-w-0 flex-1 rounded-lg border-slate-200 px-2.5 py-1.5 font-mono text-xs imei-input">
                        <input type="text" :name="{{ $enterName }} + '[' + i + '][imei2]'" x-model="ph.imei2" maxlength="32"
                               inputmode="numeric" placeholder="IMEI 2 (optional)"
                               class="min-w-0 flex-1 rounded-lg border-slate-200 px-2.5 py-1.5 font-mono text-xs imei-input">
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
