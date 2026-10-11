{{-- Shared product form fields — used by create & edit --}}
@php
    $product = $product ?? null;
    $isEdit = $product !== null;
    $familyProducts = collect($familyProducts ?? ($product ? [$product] : []));
    $isExistingFamily = $isEdit && filled($product?->variant_group) && $familyProducts->count() > 1;
    $toPhone = fn ($v) => is_array($v)
        ? ['imei' => (string) ($v['imei'] ?? ''), 'imei2' => array_key_exists('imei2', $v) ? (string) $v['imei2'] : null]
        : ['imei' => (string) $v, 'imei2' => null];

    $toDescriptionRows = function (?string $raw): array {
        $parsed = parse_product_description_rows($raw ?? '');
        $rows = $parsed['rows'];
        if ($parsed['intro'] !== '') {
            array_unshift($rows, ['heading' => 'Overview', 'detail' => $parsed['intro']]);
        }
        if ($rows === []) {
            $rows = [['heading' => '', 'detail' => '']];
        }

        return $rows;
    };

    $mapFamilyToVariants = function ($members) use ($toPhone, $toDescriptionRows) {
        return collect($members)->values()->map(function ($p, $i) use ($toPhone, $toDescriptionRows) {
            $imeis = $p->relationLoaded('availableImeis')
                ? $p->availableImeis->map(fn ($r) => ['imei' => $r->imei, 'imei2' => $r->imei_2])->all()
                : $p->availableImeis()->get(['imei', 'imei_2'])->map(fn ($r) => ['imei' => $r->imei, 'imei2' => $r->imei_2])->all();

            return [
                'id' => $p->id,
                'barcode' => $p->displayBarcode(),
                'color' => $p->color ?? '',
                'color_hex' => $p->color_hex ?: '#2563eb',
                'ram' => $p->ram ?? '',
                'storage' => $p->storage ?? '',
                'cost_price' => $p->cost_price,
                'selling_price' => $p->selling_price,
                'stock_quantity' => (int) $p->stock_quantity,
                'imeis' => array_map($toPhone, $imeis),
                'description_rows' => $toDescriptionRows($p->short_description),
                '_key' => 'v'.($i + 1),
            ];
        })->all();
    };

    $emptyDescriptionRows = [['heading' => '', 'detail' => '']];

    $oldVariants = old('variants');
    if (is_array($oldVariants)) {
        $defaultVariants = [];
        foreach (array_values($oldVariants) as $i => $row) {
            $row = is_array($row) ? $row : [];
            if (! isset($row['description_rows']) || ! is_array($row['description_rows'])) {
                $row['description_rows'] = $toDescriptionRows($row['short_description'] ?? '');
            }
            $defaultVariants[] = array_merge([
                'id' => null, 'barcode' => '', 'color' => '', 'color_hex' => '#2563eb', 'ram' => '', 'storage' => '',
                'cost_price' => '', 'selling_price' => '', 'stock_quantity' => 1, 'imeis' => [],
                'description_rows' => $emptyDescriptionRows,
            ], $row, ['_key' => 'v'.($i + 1)]);
            $defaultVariants[$i]['imeis'] = array_map($toPhone, array_values((array) ($defaultVariants[$i]['imeis'] ?? [])));
        }
    } elseif ($isEdit && ($isExistingFamily || old('product_mode') === 'gadget')) {
        $defaultVariants = $mapFamilyToVariants($familyProducts->isNotEmpty() ? $familyProducts : collect([$product]));
    } elseif ($isEdit) {
        // Seed for convert-to-variant (used when user switches mode client-side)
        $defaultVariants = $mapFamilyToVariants(collect([$product]));
    } else {
        $defaultVariants = [
            ['id' => null, 'barcode' => '', 'color' => 'Black', 'color_hex' => '#1e293b', 'ram' => '', 'storage' => '', 'cost_price' => '', 'selling_price' => '', 'stock_quantity' => 10, 'imeis' => [], 'description_rows' => $emptyDescriptionRows],
            ['id' => null, 'barcode' => '', 'color' => 'White', 'color_hex' => '#f8fafc', 'ram' => '', 'storage' => '', 'cost_price' => '', 'selling_price' => '', 'stock_quantity' => 10, 'imeis' => [], 'description_rows' => $emptyDescriptionRows],
            ['id' => null, 'barcode' => '', 'color' => 'Red', 'color_hex' => '#dc2626', 'ram' => '', 'storage' => '', 'cost_price' => '', 'selling_price' => '', 'stock_quantity' => 5, 'imeis' => [], 'description_rows' => $emptyDescriptionRows],
        ];
    }

    $editDefaultMode = old('product_mode', $isExistingFamily ? 'gadget' : 'simple');

    $initialImeis = old('imeis') !== null
        ? array_map($toPhone, array_values((array) old('imeis')))
        : (($isEdit && $product)
            ? $product->availableImeis()->get(['imei', 'imei_2'])->map(fn ($r) => ['imei' => $r->imei, 'imei2' => $r->imei_2])->all()
            : []);
    $imeiErrors = collect($errors->getMessages())
        ->filter(fn ($messages, $key) => preg_match('/^(imeis|variants\.\d+\.imeis)\.\d+(\.imei2)?$/', $key))
        ->map(fn ($messages) => $messages[0])
        ->all();
    $removedVariantIds = array_values(array_map('intval', (array) old('removed_variant_ids', [])));

    $rawDescription = old('short_description', $product?->short_description ?? '');
    $descriptionRows = old('description_rows');
    if (! is_array($descriptionRows)) {
        $descriptionRows = $toDescriptionRows($rawDescription);
    }
@endphp

<div class="space-y-5"
     x-data="{
        name: @js(old('name', $product?->name ?? '')),
        color: @js(old('color', $product?->color ?? '')),
        colorHex: @js(old('color_hex', $product?->color_hex ?: '#2563eb')),
        storage: @js(old('storage', $product?->storage ?? '')),
        ram: @js(old('ram', $product?->ram ?? '')),
        variantGroup: @js(old('variant_group', $product?->variant_group ?? '')),
        selling: @js(old('selling_price', $product?->selling_price ?? '')),
        barcodeValue: @js(old('barcode', $product?->barcode ?? '')),
        barcodeConflict: '',
        barcodeLookupUrl: @js(route('products.barcode-lookup')),
        excludeProductId: @js($product?->id ?? 0),
        _barcodeCheckTimer: null,
        descriptionRows: @js(collect($descriptionRows)->values()->all()),
        autoGroup: @js(! ($isEdit && filled($product?->variant_group))),
        productMode: @js($isEdit ? $editDefaultMode : old('product_mode', '')),
        removedVariantIds: @js($removedVariantIds),
        requiresImei: @js((bool) old('requires_imei', $product?->requires_imei ?? false)),
        openingQty: @js((int) old('stock_quantity', 0)),
        currentStock: @js((int) ($product?->stock_quantity ?? 0)),
        imeis: @js($initialImeis),
        imeiErrors: @js((object) $imeiErrors),
        variantUid: {{ max(count($defaultVariants), 1) }},
        variants: @js(collect($defaultVariants)->values()->map(function ($row, $i) use ($toPhone, $emptyDescriptionRows) {
            $descRows = $row['description_rows'] ?? $emptyDescriptionRows;
            if (! is_array($descRows) || $descRows === []) {
                $descRows = $emptyDescriptionRows;
            }

            return array_merge($row, [
                'id' => $row['id'] ?? null,
                '_key' => $row['_key'] ?? ('v'.($i + 1)),
                'imeis' => array_map($toPhone, array_values((array) ($row['imeis'] ?? []))),
                'description_rows' => collect($descRows)->values()->map(fn ($r) => [
                    'heading' => (string) ($r['heading'] ?? ''),
                    'detail' => (string) ($r['detail'] ?? ''),
                ])->all(),
            ]);
        })->all()),
        normImei(v) { return String(v ?? '').replace(/\s+/g, ''); },
        resizeImeis(list, n) {
            n = Math.max(0, Math.min(500, parseInt(n) || 0));
            while (list.length < n) list.push({ imei: '', imei2: null });
            if (list.length > n) list.splice(n);
        },
        syncImeiBoxes() {
            if (!this.requiresImei || {{ $isEdit ? 'true' : 'false' }}) return;
            this.resizeImeis(this.imeis, this.openingQty);
            this.variants.forEach((row) => this.resizeImeis(row.imeis, row.stock_quantity));
        },
        imeiFilled(list) {
            return list.filter((p) => this.normImei(p.imei) !== '' && (p.imei2 === null || this.normImei(p.imei2) !== '')).length;
        },
        imeiAll() {
            return (this.isMulti ? this.variants.flatMap((row) => row.imeis) : this.imeis)
                .flatMap((p) => p.imei2 === null ? [p.imei] : [p.imei, p.imei2])
                .map((v) => this.normImei(v));
        },
        addSecondImei(phone, event) {
            phone.imei2 = '';
            const box = event.target.closest('[data-phone]');
            this.$nextTick(() => box.querySelector('input[data-imei2]')?.focus());
        },
        luhnOk(digits) {
            let sum = 0;
            for (let i = 0; i < digits.length; i++) {
                let d = +digits[digits.length - 1 - i];
                if (i % 2 === 1) { d *= 2; if (d > 9) d -= 9; }
                sum += d;
            }
            return sum % 10 === 0;
        },
        imeiIssue(value) {
            const v = this.normImei(value);
            if (v === '') return { level: '', text: '' };
            if (/^\d+$/.test(v) && v.length !== 15) return { level: 'error', text: 'An IMEI has 15 digits (' + v.length + ' entered).' };
            if (!/^\d+$/.test(v) && !/^[A-Za-z0-9-]{6,32}$/.test(v)) return { level: 'error', text: 'Only digits (IMEI) or letters/digits (serial) allowed.' };
            if (this.imeiAll().filter((x) => x === v).length > 1) return { level: 'error', text: 'This IMEI is entered for more than one phone.' };
            if (/^\d{15}$/.test(v) && !this.luhnOk(v)) return { level: 'warn', text: 'Check digit looks wrong — please re-check this IMEI.' };
            return { level: '', text: '' };
        },
        imeiNext(event) {
            const inputs = [...event.target.closest('[data-imei-group]').querySelectorAll('input[data-imei]')];
            const next = inputs[inputs.indexOf(event.target) + 1];
            next ? next.focus() : event.target.blur();
        },
        imeiPaste(list, index, event, owner, countKey) {
            const text = event.clipboardData?.getData('text') || '';
            const lines = text.split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
            const phones = lines.length > 1
                ? lines.map((l) => l.split(/[\s,;]+/).filter(Boolean))
                : text.split(/[\s,;]+/).filter(Boolean).map((t) => [t]);
            if (phones.length < 2) return;
            event.preventDefault();
            if (list.length < index + phones.length) {
                this.resizeImeis(list, index + phones.length);
                if (owner && countKey) owner[countKey] = list.length;
            }
            phones.forEach(([first, second], k) => {
                list[index + k].imei = first;
                if (second) list[index + k].imei2 = second;
            });
        },
        categoryModal: false,
        brandModal: false,
        quickName: '',
        quickLoading: false,
        quickError: '',
        categoryUrl: @js(route('categories.store')),
        brandUrl: @js(route('brands.store')),
        csrf: @js(csrf_token()),
        get hasMode() { return this.productMode === 'simple' || this.productMode === 'gadget'; },
        get isMulti() { return this.productMode === 'gadget'; },
        get isSimple() { return this.productMode === 'simple'; },
        serializeDescriptionRows(rows) {
            return (rows || [])
                .map(r => {
                    const h = String(r.heading || '').trim();
                    const d = String(r.detail || '').trim();
                    if (!h && !d) return '';
                    if (!h) return d;
                    return h + ' | ' + d;
                })
                .filter(Boolean)
                .join('\n');
        },
        get descriptionSerialized() {
            return this.serializeDescriptionRows(this.descriptionRows);
        },
        cloneDescriptionRows(rows) {
            const list = (rows && rows.length) ? rows : [{ heading: '', detail: '' }];
            return list.map((r) => ({ heading: String(r.heading || ''), detail: String(r.detail || '') }));
        },
        chooseMode(mode) {
            this.productMode = mode;
            this.syncGroup();
            this.$nextTick(() => document.getElementById('product_name_input')?.focus());
        },
        convertToVariantProduct() {
            this.productMode = 'gadget';
            if (!this.variantGroup) {
                this.autoGroup = true;
                this.syncGroup();
            }
            if (!this.variants || this.variants.length === 0) {
                this.variants = [{
                    _key: 'v1',
                    id: this.excludeProductId || null,
                    barcode: String(this.barcodeValue || ''),
                    color: this.color || '',
                    color_hex: this.colorHex || '#2563eb',
                    ram: this.ram || '',
                    storage: this.storage || '',
                    cost_price: '',
                    selling_price: '',
                    stock_quantity: this.currentStock || 0,
                    imeis: (this.imeis || []).map((p) => ({ imei: p.imei || '', imei2: p.imei2 ?? null })),
                    description_rows: this.cloneDescriptionRows(this.descriptionRows),
                    _conflict: '',
                }];
                this.variantUid = 1;
            } else if (this.variants.length === 1 && !this.variants[0].id && this.excludeProductId) {
                this.variants[0].id = this.excludeProductId;
                if (!this.variants[0].barcode) this.variants[0].barcode = String(this.barcodeValue || '');
                if (!this.variants[0].description_rows?.length) {
                    this.variants[0].description_rows = this.cloneDescriptionRows(this.descriptionRows);
                }
            }
            this.$nextTick(() => document.querySelector('[data-variant-row]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        },
        addDescriptionRow() {
            this.descriptionRows.push({ heading: '', detail: '' });
        },
        removeDescriptionRow(i) {
            if ((this.descriptionRows || []).length <= 1) {
                this.descriptionRows = [{ heading: '', detail: '' }];
                return;
            }
            this.descriptionRows.splice(i, 1);
        },
        addVariantDescriptionRow(index) {
            const row = this.variants[index];
            if (!row) return;
            if (!row.description_rows) row.description_rows = [];
            row.description_rows.push({ heading: '', detail: '' });
        },
        removeVariantDescriptionRow(index, rowIndex) {
            const row = this.variants[index];
            if (!row) return;
            if (!row.description_rows || row.description_rows.length <= 1) {
                row.description_rows = [{ heading: '', detail: '' }];
                return;
            }
            row.description_rows.splice(rowIndex, 1);
        },
        copyDescriptionFromFirstVariant(index) {
            if (index <= 0 || !this.variants[0]) return;
            this.variants[index].description_rows = this.cloneDescriptionRows(this.variants[0].description_rows);
        },
        onBarcodeScanned(event) {
            const raw = (event?.target?.value ?? this.barcodeValue ?? '').trim();
            this.barcodeValue = raw;
            if (event?.target) event.target.value = raw;
            this.checkBarcodeConflict(raw);
        },
        onBarcodeInput(event) {
            this.barcodeValue = event?.target?.value ?? this.barcodeValue;
            clearTimeout(this._barcodeCheckTimer);
            this._barcodeCheckTimer = setTimeout(() => this.checkBarcodeConflict(this.barcodeValue), 250);
        },
        async checkBarcodeConflict(code) {
            const barcode = String(code || '').trim();
            if (!barcode) {
                this.barcodeConflict = '';
                return;
            }
            try {
                const url = new URL(this.barcodeLookupUrl, window.location.origin);
                url.searchParams.set('barcode', barcode);
                if (this.excludeProductId) url.searchParams.set('exclude_id', this.excludeProductId);
                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.found && data.product) {
                    this.barcodeConflict = 'Already used by “' + data.product.name + '” (#' + data.product.id + '). Scan writes the code here — pick a unique barcode to save.';
                } else {
                    this.barcodeConflict = '';
                }
            } catch (e) {}
        },
        async onVariantBarcodeInput(index) {
            const row = this.variants[index];
            if (!row) return;
            const barcode = String(row.barcode || '').trim();
            row._conflict = '';
            if (!barcode) return;
            const dup = this.variants.some((other, j) => j !== index && String(other.barcode || '').trim() === barcode);
            if (dup) {
                row._conflict = 'Same barcode as another option in this form.';
                return;
            }
            try {
                const url = new URL(this.barcodeLookupUrl, window.location.origin);
                url.searchParams.set('barcode', barcode);
                if (row.id) url.searchParams.set('exclude_id', row.id);
                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.found && data.product) {
                    row._conflict = 'Already used by “' + data.product.name + '”.';
                }
            } catch (e) {}
        },
        slugify(s) {
            return String(s || '').toLowerCase().trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 80);
        },
        syncGroup() {
            if (this.autoGroup) {
                this.variantGroup = this.slugify(this.name);
            }
        },
        pickSwatch(hex, label) {
            this.colorHex = hex;
            if (!this.color) this.color = label;
        },
        addVariantRow() {
            this.variantUid++;
            const row = {
                _key: 'v' + this.variantUid,
                id: null,
                barcode: '', color: '', color_hex: '#2563eb', ram: '', storage: '',
                cost_price: '', selling_price: '', stock_quantity: 1, imeis: [],
                description_rows: this.cloneDescriptionRows(this.variants[0]?.description_rows),
                _files: [], _conflict: '',
            };
            this.variants.push(row);
            if (this.requiresImei) this.resizeImeis(row.imeis, row.stock_quantity);
        },
        removeVariantRow(i) {
            if (this.variants.length <= 1) return;
            const row = this.variants[i];
            if (row?.id) {
                const id = parseInt(row.id, 10);
                if (id && !this.removedVariantIds.includes(id)) this.removedVariantIds.push(id);
            }
            (row._files || []).forEach((f) => { if (f?.url) URL.revokeObjectURL(f.url); });
            this.variants.splice(i, 1);
        },
        syncVariantFiles(index) {
            const row = this.variants[index];
            if (!row) return;
            const input = document.getElementById('variant-file-input-' + row._key);
            if (!input) return;
            const dt = new DataTransfer();
            (row._files || []).forEach((item) => dt.items.add(item.file));
            input.files = dt.files;
        },
        pickVariantImages(index, event) {
            const row = this.variants[index];
            if (!row) return;
            if (!row._files) row._files = [];
            const picked = Array.from(event.target.files || []);
            const room = 20 - row._files.length;
            picked.slice(0, room).forEach((file) => {
                row._files.push({ name: file.name, url: URL.createObjectURL(file), file });
            });
            event.target.value = '';
            this.$nextTick(() => this.syncVariantFiles(index));
        },
        removeVariantImage(index, fileIndex) {
            const row = this.variants[index];
            if (!row || !row._files) return;
            const removed = row._files.splice(fileIndex, 1)[0];
            if (removed?.url) URL.revokeObjectURL(removed.url);
            this.$nextTick(() => this.syncVariantFiles(index));
        },
        openCategoryModal() {
            this.quickName = '';
            this.quickError = '';
            this.categoryModal = true;
            this.$nextTick(() => this.$refs.quickCategoryInput?.focus());
        },
        openBrandModal() {
            this.quickName = '';
            this.quickError = '';
            this.brandModal = true;
            this.$nextTick(() => this.$refs.quickBrandInput?.focus());
        },
        async saveQuickCategory() {
            const name = (this.quickName || '').trim();
            if (!name) { this.quickError = 'Enter a category name.'; return; }
            this.quickLoading = true;
            this.quickError = '';
            try {
                const res = await fetch(this.categoryUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.quickError = data.errors?.name?.[0] || data.message || 'Could not create category.';
                    return;
                }
                const select = document.getElementById('category_id');
                const opt = document.createElement('option');
                opt.value = data.category.id;
                opt.textContent = data.category.name;
                opt.selected = true;
                select.appendChild(opt);
                this.categoryModal = false;
            } catch (e) {
                this.quickError = 'Network error. Please try again.';
            } finally {
                this.quickLoading = false;
            }
        },
        async saveQuickBrand() {
            const name = (this.quickName || '').trim();
            if (!name) { this.quickError = 'Enter a brand name.'; return; }
            this.quickLoading = true;
            this.quickError = '';
            try {
                const res = await fetch(this.brandUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name, is_active: true }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.quickError = data.errors?.name?.[0] || data.message || 'Could not create brand.';
                    return;
                }
                const select = document.getElementById('brand_id');
                const opt = document.createElement('option');
                opt.value = data.brand.id;
                opt.textContent = data.brand.name;
                opt.selected = true;
                select.appendChild(opt);
                this.brandModal = false;
            } catch (e) {
                this.quickError = 'Network error. Please try again.';
            } finally {
                this.quickLoading = false;
            }
        },
     }"
     x-init="syncGroup(); syncImeiBoxes();
             $watch('requiresImei', (on) => {
                 if (!on) return;
                 if ({{ $isEdit ? 'true' : 'false' }} && imeis.length === 0) resizeImeis(imeis, currentStock);
                 syncImeiBoxes();
             })">

    @if(!$isEdit)
    {{-- Step 0: choose type first --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">1. What are you adding?</h3>
            <p class="text-xs text-slate-500 mt-0.5">Choose first — the form below changes to match.</p>
        </div>
        <div class="p-4">
            <input type="hidden" name="product_mode" :value="productMode">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button type="button" @click="chooseMode('simple')"
                        class="text-left flex items-start gap-3 rounded-xl border p-4 transition"
                        :class="productMode === 'simple' ? 'border-blue-500 bg-blue-50/60 ring-2 ring-blue-200' : 'border-slate-200 hover:border-slate-300'">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                          :class="productMode === 'simple' ? 'border-blue-600 bg-blue-600' : 'border-slate-300'">
                        <span x-show="productMode === 'simple'" class="h-2 w-2 rounded-full bg-white"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Single item</span>
                        <span class="block text-[12px] text-slate-500 mt-1">One product, one barcode, one stock — e.g. one charger with no color options.</span>
                    </span>
                </button>
                <button type="button" @click="chooseMode('gadget')"
                        class="text-left flex items-start gap-3 rounded-xl border p-4 transition"
                        :class="productMode === 'gadget' ? 'border-orange-500 bg-orange-50/50 ring-2 ring-orange-200' : 'border-slate-200 hover:border-slate-300'">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                          :class="productMode === 'gadget' ? 'border-orange-500 bg-orange-500' : 'border-slate-300'">
                        <span x-show="productMode === 'gadget'" class="h-2 w-2 rounded-full bg-white"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Multi-variant</span>
                        <span class="block text-[12px] text-slate-500 mt-1">Same name, several colors/sizes (cable colors, phone memory…). Each has own barcode, stock &amp; photos.</span>
                    </span>
                </button>
            </div>
            <p x-show="!hasMode" class="mt-3 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2" x-cloak>
                Select Single item or Multi-variant to continue.
            </p>
        </div>
    </section>
    @else
        <input type="hidden" name="product_mode" :value="productMode">
        <template x-for="rid in removedVariantIds" :key="'rm-'+rid">
            <input type="hidden" name="removed_variant_ids[]" :value="rid">
        </template>
    @endif

    <div x-show="hasMode" x-cloak class="space-y-5">

    {{-- IMEI --}}
    <section class="rounded-xl border-2 overflow-hidden transition"
             :class="requiresImei ? 'border-orange-300 bg-white' : 'border-orange-100 bg-white'">
        <div class="px-4 py-3 border-b border-orange-100 bg-orange-50/70">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '1' : '2' }}. Is this a phone? — IMEI tracking</h3>
            <p class="text-xs text-slate-500 mt-0.5">Turn on for mobile phones so every unit is sold by its IMEI. Leave off for cables &amp; accessories.</p>
        </div>
        <div class="p-4 space-y-3">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800 cursor-pointer">
                <input type="hidden" name="requires_imei" value="0">
                <input type="checkbox" name="requires_imei" value="1" x-model="requiresImei"
                       class="h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                Yes — this product needs an IMEI for every phone
            </label>
            <div x-show="requiresImei && isSimple" x-cloak class="space-y-3">
                <p class="rounded-lg border border-orange-100 bg-orange-50/60 px-3 py-2 text-[12px] text-slate-700">
                    Every phone has its <strong>own</strong> IMEI — even when model, color and storage are the same.
                    Dual-SIM phones have two: click <strong>“+ IMEI 2”</strong> on that phone.
                    @if($isEdit)
                        Each card below is one phone in stock, and stock always equals the number of phones. Add a phone when a new one arrives; sold phones are kept in sales history.
                    @else
                        Enter how many phones you have, then type or scan the IMEI of each one. Stock is set to this number.
                    @endif
                </p>

                @unless($isEdit)
                    <div class="flex items-center gap-3">
                        <label for="imei_phone_count" class="text-xs font-semibold text-slate-600">Number of phones</label>
                        <input id="imei_phone_count" type="number" min="0" max="500" step="1"
                               x-model.number="openingQty" @input="resizeImeis(imeis, openingQty)"
                               class="w-28 rounded-lg border-slate-200 text-sm py-2">
                    </div>
                @endunless

                <p class="text-[11px] text-slate-400" x-show="imeis.length === 0">
                    {{ $isEdit ? 'No phones in stock yet — click “+ Add phone” for each phone you receive.' : 'Enter the number of phones to add their IMEIs.' }}
                </p>

                @include('products.partials.imei-rows', [
                    'list' => 'imeis',
                    'name' => "'imeis'",
                    'errKey' => "'imeis.'",
                    'owner' => $isEdit ? 'null' : '$data',
                    'countKey' => $isEdit ? '' : 'openingQty',
                    'editable' => $isEdit,
                ])
                @error('imei_list') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <p class="rounded-lg border border-orange-100 bg-orange-50/60 px-3 py-2 text-[12px] text-slate-700" x-show="requiresImei && isMulti" x-cloak>
                You'll enter each phone's IMEI inside every color / option in <strong>section 6</strong> below — one card per phone, with “+ IMEI 2” for dual-SIM phones.
            </p>
        </div>
    </section>

    {{-- Gallery: single item (or edit) only — multi uses photos per variant --}}
    <template x-if="isSimple">
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '2' : '3' }}. Product gallery</h3>
            <p class="text-xs text-slate-500 mt-0.5">Add as many photos as you want (up to 20). First photo is the main thumbnail.</p>
        </div>
        <div class="p-4">
            @include('products.partials.image-uploads', ['product' => $product ?? null])
        </div>
    </section>
    </template>

    {{-- Basic info --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '3' : '4' }}. Basic information</h3>
            <p class="text-xs text-slate-500 mt-0.5">Title, brand, and category customers see on the store.</p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Product name <span class="text-red-500">*</span></label>
                <input type="text" id="product_name_input" name="name" x-model="name" @input="syncGroup()" value="{{ old('name', $product?->name ?? '') }}"
                       :required="hasMode"
                       class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5"
                       placeholder="e.g. USB-C Cable or Pixel 7">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="category_id" class="block text-xs font-semibold text-slate-600">Category</label>
                        <button type="button" @click="openCategoryModal()"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
                                title="Add category">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    <select id="category_id" name="category_id" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product?->category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="brand_id" class="block text-xs font-semibold text-slate-600">Brand</label>
                        <button type="button" @click="openBrandModal()"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
                                title="Add brand">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    <select id="brand_id" name="brand_id" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        <option value="">No brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ old('brand_id', $product?->brand_id ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="isSimple">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Barcode <span class="text-red-500">*</span></label>
                    <input type="text" name="barcode" x-model="barcodeValue" x-ref="barcodeInput"
                           :required="isSimple"
                           :disabled="isMulti"
                           autocomplete="off"
                           autocorrect="off"
                           spellcheck="false"
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-mono"
                           :placeholder="barcodeValue ? barcodeValue : 'Scan or type unique barcode…'"
                           @input="onBarcodeInput($event)"
                           @keydown.enter.prevent="onBarcodeScanned($event)">
                    <p class="text-[11px] text-slate-400 mt-1">Scan with a barcode gun — the code appears in this field. Must be unique.</p>
                    <p class="text-amber-600 text-xs mt-1" x-show="barcodeConflict" x-text="barcodeConflict" x-cloak></p>
                    @error('barcode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">SKU (optional)</label>
                    <input type="text" name="sku" value="{{ old('sku', $product?->sku ?? '') }}"
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-mono"
                           placeholder="e.g. IPH15-256-NAT">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Availability (shop filter)</label>
                    <select name="availability" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        @php $avail = old('availability', $product?->availability ?? 'in_stock'); @endphp
                        <option value="in_stock" @selected($avail === 'in_stock')>In Stock</option>
                        <option value="pre_order" @selected($avail === 'pre_order')>Pre Order</option>
                        <option value="up_coming" @selected($avail === 'up_coming')>Up Coming</option>
                        <option value="out_of_stock" @selected($avail === 'out_of_stock')>Out of Stock</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Used by category sidebar filters on the website.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '4' : '5' }}. Pricing</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                <span x-show="isSimple">Cost &amp; selling price for this item.</span>
                <span x-show="isMulti" x-cloak>Default price for all variants. You can override per color/option below.</span>
            </p>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cost price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product?->cost_price ?? '') }}" required
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                @error('cost_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Selling price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="selling_price" x-model="selling" value="{{ old('selling_price', $product?->selling_price ?? '') }}" required
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-medium text-blue-600">
                @error('selling_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="px-4 pb-4" x-data="{
            dtype: @js(old('pos_discount_type', $product?->pos_discount_type ?? '')),
            dval: @js(old('pos_discount_value', $product?->pos_discount_value ?? '')),
        }">
            <div class="rounded-xl border border-rose-100 bg-rose-50/40 p-4 space-y-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-rose-700">Product discount (always on)</p>
                    <p class="mt-0.5 text-[11px] text-slate-500">Applies on POS and storefront until you clear it. Timed Sale campaigns on the product list can still stack — customer pays the lower price.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Type</label>
                        <select name="pos_discount_type" x-model="dtype"
                                class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                            <option value="">No discount</option>
                            <option value="percent">Percent (%)</option>
                            <option value="fixed">Fixed (Tk)</option>
                        </select>
                        @error('pos_discount_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                            <span x-text="dtype === 'percent' ? 'Percent off' : 'Amount off (Tk)'"></span>
                        </label>
                        <input type="number" step="0.01" min="0" name="pos_discount_value" x-model="dval"
                               :disabled="!dtype"
                               :required="!!dtype"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5 disabled:bg-slate-100 disabled:text-slate-400">
                        @error('pos_discount_value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <p class="text-xs text-slate-600 pb-2.5" x-show="dtype && selling && dval" x-cloak>
                            Offer ≈
                            <span class="font-bold text-rose-700"
                                  x-text="'Tk ' + (dtype === 'percent'
                                    ? Math.max(0, Number(selling) * (1 - Number(dval)/100)).toFixed(2)
                                    : Math.max(0, Number(selling) - Number(dval)).toFixed(2))"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Variants --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '5' : '6' }}. Color / size options</h3>
            <p class="text-xs text-slate-500 mt-0.5" x-show="isSimple">Optional color or size for this single item.</p>
            <p class="text-xs text-slate-500 mt-0.5" x-show="isMulti" x-cloak>Add one row per color/option. Each needs a unique barcode and can have many pictures.</p>
        </div>
        <div class="p-4 space-y-4">
            <div x-show="isMulti || {{ $isEdit ? 'true' : 'false' }}">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <label class="text-xs font-semibold text-slate-600">Variant group key</label>
                    <label class="text-[11px] text-slate-500 inline-flex items-center gap-1.5 cursor-pointer" x-show="!{{ $isEdit ? 'true' : 'false' }}">
                        <input type="checkbox" x-model="autoGroup" @change="syncGroup()" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Auto from product name
                    </label>
                </div>
                <input type="text" name="variant_group" x-model="variantGroup" @input="autoGroup = false"
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-mono"
                       placeholder="e.g. usb-c-cable or pixel-7">
                <p class="text-[11px] text-slate-400 mt-1" x-show="isMulti" x-cloak>
                    Links all colors under one store page. Keep the same for every row below.
                </p>
            </div>
            <div x-show="isSimple && !{{ $isEdit ? 'true' : 'false' }}" x-cloak>
                <input type="hidden" name="variant_group" :value="variantGroup">
            </div>

            <div x-show="isSimple" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Color name</label>
                        <input type="text" name="color" x-model="color"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="e.g. Black">
                        <div class="flex flex-wrap gap-2 mt-2.5">
                            @foreach([
                                ['#1e293b', 'Black'],
                                ['#f8fafc', 'White'],
                                ['#dc2626', 'Red'],
                                ['#2563eb', 'Blue'],
                                ['#c5c9a0', 'Lemongrass'],
                                ['#16a34a', 'Green'],
                                ['#ca8a04', 'Gold'],
                            ] as [$hex, $label])
                                <button type="button" @click="pickSwatch('{{ $hex }}', '{{ $label }}')"
                                        title="{{ $label }}"
                                        class="w-7 h-7 rounded-full border-2 border-white shadow ring-1 ring-slate-200 hover:ring-blue-400 transition"
                                        style="background: {{ $hex }}"></button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Swatch color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" x-model="colorHex"
                                   class="h-10 w-14 rounded-lg border border-slate-200 cursor-pointer shrink-0">
                            <input type="text" name="color_hex" x-model="colorHex"
                                   class="flex-1 rounded-lg border-slate-200 text-sm py-2.5 font-mono"
                                   placeholder="#2563eb">
                            <div class="w-10 h-10 rounded-full border-2 border-blue-600 ring-2 ring-blue-100 shrink-0"
                                 :style="'background:' + colorHex" title="Preview"></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Storage / size (optional)</label>
                        <input type="text" name="storage" x-model="storage" list="storage-presets"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="e.g. 128GB or 2m">
                        <datalist id="storage-presets">
                            <option value="64GB"><option value="128GB"><option value="256GB"><option value="512GB"><option value="1TB">
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">RAM (optional)</label>
                        <input type="text" name="ram" x-model="ram" list="ram-presets"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="e.g. 8GB">
                        <datalist id="ram-presets">
                            <option value="4GB"><option value="6GB"><option value="8GB"><option value="12GB"><option value="16GB">
                        </datalist>
                    </div>
                </div>
            </div>

            @if($isEdit)
            <div x-show="isSimple" class="rounded-xl border border-orange-200 bg-orange-50/40 p-4 space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800">Need colors / options later?</p>
                        <p class="text-xs text-slate-600 mt-0.5">
                            Convert this single product into a multi-variant product. This item becomes the first option;
                            then add Blue, 256GB, etc. — all share one store page. You can edit every option together.
                        </p>
                    </div>
                    <button type="button" @click="convertToVariantProduct()"
                            class="shrink-0 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 px-3 py-2 rounded-lg">
                        Convert to variant product
                    </button>
                </div>
            </div>
            @endif

            <template x-if="isMulti">
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-slate-700">
                        Variants
                        <span class="font-normal text-slate-500">(barcode + stock + photos each)</span>
                    </p>
                    <button type="button" @click="addVariantRow()"
                            class="text-xs font-semibold text-orange-700 hover:text-orange-800 px-2 py-1 rounded-md border border-orange-200 bg-orange-50">
                        + Add color / option
                    </button>
                </div>
                @if($isEdit)
                <div class="rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-2 text-[12px] text-amber-900">
                    Edit every option here. Shared name, brand and visibility apply to the whole family.
                    Stock and description are separate per option (shoppers see that option’s qty &amp; text when they switch).
                    Removing an option archives it (barcode freed for reuse).
                </div>
                @endif
                <template x-for="(row, index) in variants" :key="row._key">
                    <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/60 space-y-3" data-variant-row>
                        <input type="hidden" :name="'variants['+index+'][id]'" :value="row.id || ''">
                        <input type="hidden" :name="'variants['+index+'][short_description]'" :value="serializeDescriptionRows(row.description_rows)">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide"
                                  x-text="(row.id ? 'Option ' : 'New option ') + (index + 1)"></span>
                            <button type="button" @click="removeVariantRow(index)" x-show="variants.length > 1"
                                    class="text-[11px] text-red-600 hover:text-red-700">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2">
                            <div class="lg:col-span-2">
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Barcode *</label>
                                <input type="text" :name="'variants['+index+'][barcode]'" x-model="row.barcode"
                                       autocomplete="off" spellcheck="false"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2 font-mono"
                                       :placeholder="row.barcode ? row.barcode : 'Scan barcode…'"
                                       @input.debounce.250ms="onVariantBarcodeInput(index)"
                                       @keydown.enter.prevent="row.barcode = String(row.barcode || '').trim(); onVariantBarcodeInput(index)">
                                <p class="text-[10px] text-amber-600 mt-0.5" x-show="row._conflict" x-text="row._conflict" x-cloak></p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Color</label>
                                <input type="text" :name="'variants['+index+'][color]'" x-model="row.color"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2"
                                       placeholder="Black">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Swatch</label>
                                <input type="color" :name="'variants['+index+'][color_hex]'" x-model="row.color_hex"
                                       class="h-9 w-full rounded-md border border-slate-200 cursor-pointer">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">RAM</label>
                                <input type="text" :name="'variants['+index+'][ram]'" x-model="row.ram"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2" placeholder="optional">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Storage</label>
                                <input type="text" :name="'variants['+index+'][storage]'" x-model="row.storage"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2" placeholder="optional">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Cost (Tk)</label>
                                <input type="number" step="0.01" min="0" :name="'variants['+index+'][cost_price]'" x-model="row.cost_price"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2"
                                       placeholder="Same as default">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Selling (Tk)</label>
                                <input type="number" step="0.01" min="0" :name="'variants['+index+'][selling_price]'" x-model="row.selling_price"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2 font-medium text-blue-700"
                                       :placeholder="selling ? ('Default Tk ' + selling) : 'Same as default'">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1"
                                       x-text="requiresImei ? (row.id ? 'Stock (= phones below)' : 'Opening phones') : (row.id ? 'Stock (this option only)' : 'Opening stock')"></label>
                                <template x-if="!requiresImei || !row.id">
                                    <input type="number" min="0" :name="'variants['+index+'][stock_quantity]'" x-model="row.stock_quantity"
                                           @input="requiresImei && resizeImeis(row.imeis, row.stock_quantity)"
                                           class="block w-full rounded-md border-slate-200 text-sm py-2"
                                           :title="requiresImei ? 'Number of phones for this option' : 'Stock for this color/option only — not shared with other variants'">
                                </template>
                                <template x-if="requiresImei && row.id">
                                    <div>
                                        <input type="hidden" :name="'variants['+index+'][stock_quantity]'" :value="row.stock_quantity ?? 0">
                                        <div class="block w-full rounded-md border border-slate-200 bg-white text-sm py-2 px-3 text-slate-700"
                                             x-text="(row.stock_quantity ?? 0) + ' units (from IMEI list)'"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1.5">
                                Pictures for this color
                                @if($isEdit)<span class="font-normal text-slate-400">(optional — leave empty to keep current)</span>@endif
                            </label>
                            @include('products.partials.variant-image-uploads')
                        </div>
                        <div x-show="requiresImei" class="rounded-lg border border-orange-100 bg-white p-3">
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1.5">
                                IMEI of each phone
                                <span class="font-normal" x-show="!row.id">(opening qty sets how many phones)</span>
                            </label>
                            <p class="text-[11px] text-slate-400" x-show="row.imeis.length === 0 && !row.id">Set the opening qty above to add the phones.</p>
                            @include('products.partials.imei-rows', [
                                'list' => 'row.imeis',
                                'name' => "'variants['+index+'][imeis]'",
                                'errKey' => "'variants.'+index+'.imeis.'",
                                'owner' => 'row',
                                'countKey' => 'stock_quantity',
                                'editable' => $isEdit,
                            ])
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="block text-[10px] font-semibold text-slate-500">
                                    Description for this option
                                    <span class="font-normal text-slate-400">(changes on the store when shoppers pick this variation)</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <button type="button" x-show="index > 0" @click="copyDescriptionFromFirstVariant(index)"
                                            class="text-[10px] font-semibold text-slate-600 hover:text-slate-800 px-2 py-1 rounded-md border border-slate-200 bg-slate-50">
                                        Copy from option 1
                                    </button>
                                    <button type="button" @click="addVariantDescriptionRow(index)"
                                            class="text-[10px] font-semibold text-blue-700 hover:text-blue-800 px-2 py-1 rounded-md border border-blue-200 bg-blue-50">
                                        + Add row
                                    </button>
                                </div>
                            </div>
                            <div class="product-desc-rows">
                                <div class="product-desc-rows__head">
                                    <div>Heading</div>
                                    <div>Description</div>
                                    <div></div>
                                </div>
                                <template x-for="(dRow, dIndex) in (row.description_rows || [])" :key="row._key + '-desc-' + dIndex">
                                    <div class="product-desc-rows__row">
                                        <div>
                                            <input type="text" x-model="dRow.heading"
                                                   :name="'variants['+index+'][description_rows]['+dIndex+'][heading]'"
                                                   class="block w-full rounded-md border-slate-200 text-sm py-2"
                                                   placeholder="e.g. Display">
                                        </div>
                                        <div>
                                            <input type="text" x-model="dRow.detail"
                                                   :name="'variants['+index+'][description_rows]['+dIndex+'][detail]'"
                                                   class="block w-full rounded-md border-slate-200 text-sm py-2"
                                                   placeholder="e.g. 6.1&quot; OLED">
                                        </div>
                                        <div>
                                            <button type="button" @click="removeVariantDescriptionRow(index, dIndex)"
                                                    class="text-slate-400 hover:text-red-600 text-lg leading-none" title="Remove row">&times;</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
                @error('variants') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                @error('variants.*.barcode') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
            </template>
        </div>
    </section>

    {{-- Store description (simple) + visibility --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '6' : '7' }}. <span x-text="isMulti ? 'Visibility' : 'Store description & visibility'"></span></h3>
            <p class="text-xs text-slate-500 mt-0.5" x-show="!isMulti">Add rows as a form — storefront shows them as a table (heading | detail).</p>
            <p class="text-xs text-slate-500 mt-0.5" x-show="isMulti" x-cloak>Descriptions are set on each variant above. These flags apply to the whole family.</p>
        </div>
        <div class="p-4 space-y-4">
            <div x-show="!isMulti">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <label class="block text-xs font-semibold text-slate-600">Description rows</label>
                    <button type="button" @click="addDescriptionRow()"
                            class="text-xs font-semibold text-blue-700 hover:text-blue-800 px-2 py-1 rounded-md border border-blue-200 bg-blue-50">
                        + Add row
                    </button>
                </div>
                <input type="hidden" name="short_description" :value="isMulti ? '' : descriptionSerialized" :disabled="isMulti">
                <div class="product-desc-rows">
                    <div class="product-desc-rows__head">
                        <div>Heading</div>
                        <div>Description</div>
                        <div></div>
                    </div>
                    <template x-for="(row, index) in descriptionRows" :key="'desc-'+index">
                        <div class="product-desc-rows__row">
                            <div>
                                <input type="text" x-model="row.heading"
                                       :name="isMulti ? null : ('description_rows['+index+'][heading]')"
                                       :disabled="isMulti"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2"
                                       placeholder="e.g. Display">
                            </div>
                            <div>
                                <input type="text" x-model="row.detail"
                                       :name="isMulti ? null : ('description_rows['+index+'][detail]')"
                                       :disabled="isMulti"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2"
                                       placeholder="e.g. 6.1&quot; OLED">
                            </div>
                            <div>
                                <button type="button" @click="removeDescriptionRow(index)"
                                        class="text-slate-400 hover:text-red-600 text-lg leading-none" title="Remove row">&times;</button>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="text-[11px] text-slate-400 mt-1.5">Left column = heading, right column = detail. Shown as a table on the product page.</p>
                @error('short_description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_published', $isEdit ? ($product?->is_published ?? true) : true) ? 'checked' : '' }}>
                    Publish on website
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_new_arrival" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_new_arrival', $product?->is_new_arrival ?? false) ? 'checked' : '' }}>
                    New Arrival
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_best_seller" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_best_seller', $product?->is_best_seller ?? false) ? 'checked' : '' }}>
                    Trending
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_featured', $product?->is_featured ?? false) ? 'checked' : '' }}>
                    Featured
                </label>
            </div>
            <p class="text-[11px] text-slate-400">New Arrival and Trending control which products appear in those homepage sections.</p>

            @if(!$isEdit)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="isSimple">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Opening quantity</label>
                        <input type="number" name="stock_quantity" min="0" step="1"
                               x-model.number="openingQty"
                               @input="requiresImei && resizeImeis(imeis, openingQty)"
                               :disabled="isMulti"
                               :readonly="requiresImei"
                               :class="requiresImei && 'bg-slate-50 text-slate-500'"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="e.g. 10">
                        <p class="text-[11px] text-slate-400 mt-1" x-show="requiresImei" x-cloak>Set by the number of phones in IMEI tracking.</p>
                        @error('stock_quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" min="0"
                               :disabled="isMulti"
                               :required="isSimple"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
                <div x-show="isMulti" x-cloak class="rounded-lg border border-orange-100 bg-orange-50/50 px-3 py-2.5 text-xs text-slate-600">
                    Opening stock is set <strong>per color/option</strong> above. Shared low-stock alert:
                    <input type="number" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" min="0"
                           :disabled="!isMulti"
                           class="inline-block w-20 ml-1 rounded border-slate-200 text-sm py-1">
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Current stock</label>
                        <div class="block w-full rounded-lg border border-slate-200 bg-slate-50 text-sm py-2.5 px-3 font-medium text-slate-900">
                            {{ $product?->stock_quantity ?? 0 }} units
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Change stock via
                            @if(($product?->stock_quantity ?? 0) === 0)
                                <a href="{{ route('supply.opening-inventory.index') }}" class="text-blue-600 underline">Opening Inventory</a>
                                or
                            @endif
                            <a href="{{ route('supply.adjustments.index') }}" class="text-blue-600 underline">Stock Adjustment</a>.
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', $product?->alert_quantity ?? 5) }}" required min="0"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
            @endif
        </div>
    </section>

    </div>{{-- /hasMode --}}

    {{-- Quick add category modal --}}
    <div x-show="categoryModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="categoryModal = false">
        <div class="absolute inset-0 bg-slate-900/40" @click="categoryModal = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl" @click.stop>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900">Add category</h3>
                    <p class="mt-0.5 text-[12px] text-slate-500">Create a category without leaving this page.</p>
                </div>
                <button type="button" @click="categoryModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Category name</label>
            <input type="text" x-ref="quickCategoryInput" x-model="quickName" @keydown.enter.prevent="saveQuickCategory()"
                   placeholder="e.g. Phones"
                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-blue-100">
            <p x-show="quickError" x-text="quickError" class="mt-2 text-[12px] font-medium text-rose-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="categoryModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="saveQuickCategory()" :disabled="quickLoading"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                    <span x-text="quickLoading ? 'Saving…' : 'Add category'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Quick add brand modal --}}
    <div x-show="brandModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="brandModal = false">
        <div class="absolute inset-0 bg-slate-900/40" @click="brandModal = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl" @click.stop>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900">Add brand</h3>
                    <p class="mt-0.5 text-[12px] text-slate-500">Create a brand without leaving this page.</p>
                </div>
                <button type="button" @click="brandModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Brand name</label>
            <input type="text" x-ref="quickBrandInput" x-model="quickName" @keydown.enter.prevent="saveQuickBrand()"
                   placeholder="e.g. Apple"
                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-blue-100">
            <p x-show="quickError" x-text="quickError" class="mt-2 text-[12px] font-medium text-rose-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="brandModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="saveQuickBrand()" :disabled="quickLoading"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                    <span x-text="quickLoading ? 'Saving…' : 'Add brand'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
