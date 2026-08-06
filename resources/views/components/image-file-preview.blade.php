@props([
    'name',
    'id' => null,
    'existing' => null,
    'accept' => 'image/jpeg,image/png,image/jpg,image/webp,image/gif,image/svg+xml,image/x-icon,.ico,.svg',
    'required' => false,
    'previewClass' => 'h-28 max-w-full rounded-xl object-contain border border-slate-200 bg-white p-1',
    'inputClass' => 'mt-1 w-full rounded-xl border-slate-200 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700',
])

@php
    $inputId = $id ?: preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
@endphp

<div
    x-data="{
        preview: @js($existing),
        blob: null,
        onPick(event) {
            const file = event.target.files && event.target.files[0];
            if (this.blob) {
                URL.revokeObjectURL(this.blob);
                this.blob = null;
            }
            if (file) {
                this.blob = URL.createObjectURL(file);
                this.preview = this.blob;
            } else {
                this.preview = @js($existing);
            }
        }
    }"
>
    <input
        type="file"
        id="{{ $inputId }}"
        name="{{ $name }}"
        accept="{{ $accept }}"
        class="{{ $inputClass }}"
        @change="onPick($event)"
        @if($required) required @endif
    >

    <div x-show="preview" x-cloak class="mt-2 space-y-1">
        <img :src="preview" alt="Selected image preview" class="{{ $previewClass }}">
        <p class="text-[11px] font-medium text-emerald-700">Preview — click Save to keep this picture.</p>
    </div>
</div>
