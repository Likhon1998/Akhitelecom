<x-cms-layout
    title="Landing Page"
    subtitle="Store branding, trust features, and homepage promo banners. Changes sync to the public website."
    previewUrl="{{ route('home') }}"
>
    <form method="POST" action="{{ route('cms.landing.update') }}" enctype="multipart/form-data" class="space-y-6"
          x-data="{
            features: @js($features->map(fn($f)=>['id'=>$f->id,'icon'=>$f->icon,'title'=>$f->title,'subtitle'=>$f->subtitle,'sort_order'=>$f->sort_order,'is_active'=>$f->is_active])->values()),
            banners: @js($banners->map(fn($b)=>[
                'id'=>$b->id,
                'title'=>$b->title,
                'subtitle'=>$b->subtitle,
                'badge_text'=>$b->badge_text,
                'highlight_text'=>$b->highlight_text,
                'discount_badge'=>$b->discount_badge,
                'price_from'=>$b->price_from,
                'button_text'=>$b->button_text,
                'button_url'=>$b->button_url,
                'theme'=>$b->theme,
                'sort_order'=>$b->sort_order,
                'is_active'=>$b->is_active,
                'image_path'=>$b->image_path,
            ])->values()),
            addFeature(){ this.features.push({id:null,icon:'truck',title:'',subtitle:'',sort_order:this.features.length,is_active:true}) },
            addBanner(){ this.banners.push({id:null,title:'',subtitle:'',badge_text:'',highlight_text:'',discount_badge:'',price_from:'',button_text:'Shop Now',button_url:'/shop',theme:'dark',sort_order:this.banners.length,is_active:true,image_path:null}) }
          }">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900">Store identity</h3>
            <p class="text-sm text-slate-500 mt-1">Shown in the website header, footer, and browser title.</p>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Store name</label>
                    <input name="store_name" value="{{ old('store_name', $settings->store_name) }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Logo</label>
                    <input type="file" name="logo" accept="image/*" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    @if($settings->logo_path)
                        <img src="{{ public_storage_url($settings->logo_path) }}" alt="" class="mt-2 h-10 object-contain">
                    @endif
                    <p class="mt-1 text-[11px] text-slate-400">Header / footer logo.</p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Browser tab icon (favicon)</label>
                    <input type="file" name="favicon" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml,image/x-icon,.ico" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    @php
                        $faviconPreview = $settings->favicon_path
                            ? public_storage_url($settings->favicon_path)
                            : ($settings->logo_path ? public_storage_url($settings->logo_path) : asset('favicon.svg'));
                    @endphp
                    <div class="mt-2 flex items-center gap-2">
                        <img src="{{ $faviconPreview }}" alt="" class="h-8 w-8 rounded object-contain border border-slate-200 bg-white p-0.5">
                        <p class="text-[11px] text-slate-400">Shown in the browser tab. Square PNG/SVG works best. Falls back to logo if empty.</p>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Currency</label>
                    @php
                        $code = old('currency_code', $settings->currency_code ?: 'BDT');
                        $symbol = old('currency_symbol', $settings->currency_symbol ?: '৳');
                        // Heal phone-code mistakes (880 / +880 / 088) for the form display
                        if (preg_match('/^\+?\d{2,4}$/', trim((string) $symbol))) {
                            $symbol = $code === 'USD' ? '$' : '৳';
                        }
                        $presets = [
                            ['code' => 'BDT', 'symbol' => '৳', 'label' => 'Bangladesh Taka (৳)'],
                            ['code' => 'BDT', 'symbol' => 'Tk', 'label' => 'Bangladesh Taka (Tk)'],
                            ['code' => 'USD', 'symbol' => '$', 'label' => 'US Dollar ($)'],
                            ['code' => 'EUR', 'symbol' => '€', 'label' => 'Euro (€)'],
                            ['code' => 'GBP', 'symbol' => '£', 'label' => 'British Pound (£)'],
                            ['code' => 'INR', 'symbol' => '₹', 'label' => 'Indian Rupee (₹)'],
                        ];
                    @endphp
                    <div class="mt-1 grid gap-2 sm:grid-cols-2" x-data="{
                        code: @js($code),
                        symbol: @js($symbol),
                        apply(code, symbol) { this.code = code; this.symbol = symbol; }
                    }">
                        <div>
                            <label class="text-[10px] font-semibold text-slate-400">Code</label>
                            <input name="currency_code" x-model="code" class="mt-0.5 w-full rounded-xl border-slate-200" required maxlength="10">
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold text-slate-400">Symbol (shown before prices)</label>
                            <input name="currency_symbol" x-model="symbol" class="mt-0.5 w-full rounded-xl border-slate-200" required maxlength="10"
                                   placeholder="৳ — not 880">
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-[11px] text-slate-500 mb-2">Quick pick — do <span class="font-bold text-rose-600">not</span> use phone codes like 880 / +880.</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($presets as $preset)
                                    <button type="button"
                                            @click="apply(@js($preset['code']), @js($preset['symbol']))"
                                            class="rounded-lg border px-2.5 py-1.5 text-[11px] font-bold transition"
                                            :class="code === @js($preset['code']) && symbol === @js($preset['symbol']) ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                                        {{ $preset['label'] }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500">Preview: <span class="font-bold text-slate-800" x-text="symbol + '1,250.00'"></span></p>
                        </div>
                    </div>
                    @error('currency_symbol')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="hidden">
                    {{-- keep grid layout: removed duplicate currency fields --}}
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Special offer text</label>
                    <input name="special_offer_text" value="{{ old('special_offer_text', $settings->special_offer_text) }}" class="mt-1 w-full rounded-xl border-slate-200">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Trusted-by text</label>
                    <input name="trusted_by_text" value="{{ old('trusted_by_text', $settings->trusted_by_text) }}" class="mt-1 w-full rounded-xl border-slate-200">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Deals kicker</label>
                    <input name="deals_kicker" value="{{ old('deals_kicker', $settings->deals_kicker) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="SPECIAL OFFERS">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Deals title</label>
                    <input name="deals_title" value="{{ old('deals_title', $settings->deals_title) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Deals You'll">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Deals accent word</label>
                    <input name="deals_title_accent" value="{{ old('deals_title_accent', $settings->deals_title_accent) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Love">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold uppercase text-slate-500">Deals subtitle</label>
                    <input name="deals_subtitle" value="{{ old('deals_subtitle', $settings->deals_subtitle) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Grab the best deals on top-quality gadgets and accessories.">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Contact email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}" class="mt-1 w-full rounded-xl border-slate-200">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Contact phone</label>
                    <input name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" class="mt-1 w-full rounded-xl border-slate-200">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold uppercase text-slate-500">Address</label>
                    <textarea name="contact_address" rows="2" class="mt-1 w-full rounded-xl border-slate-200">{{ old('contact_address', $settings->contact_address) }}</textarea>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Homepage features</h3>
                    <p class="text-sm text-slate-500">Trust bar under the hero. Recommended: <strong>4 items</strong>. Fully CMS-managed — edits show on the storefront.</p>
                </div>
                <button type="button" @click="addFeature()" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Feature</button>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="(f, i) in features" :key="i">
                    <div class="grid gap-2 rounded-xl border border-slate-100 bg-slate-50/70 p-3 md:grid-cols-12">
                        <input type="hidden" :name="'features['+i+'][id]'" :value="f.id || ''">
                        <select :name="'features['+i+'][icon]'" x-model="f.icon" class="md:col-span-2 rounded-lg border-slate-200 text-sm">
                            <option value="truck">Truck</option>
                            <option value="return">Return</option>
                            <option value="lock">Lock</option>
                            <option value="shield">Shield</option>
                            <option value="support">Support</option>
                            <option value="shipping">Shipping</option>
                            <option value="payment">Payment</option>
                            <option value="warranty">Warranty</option>
                            <option value="chat">Chat</option>
                        </select>
                        <input :name="'features['+i+'][title]'" x-model="f.title" placeholder="Title" class="md:col-span-3 rounded-lg border-slate-200 text-sm">
                        <input :name="'features['+i+'][subtitle]'" x-model="f.subtitle" placeholder="Subtitle" class="md:col-span-4 rounded-lg border-slate-200 text-sm">
                        <input type="number" :name="'features['+i+'][sort_order]'" x-model="f.sort_order" class="md:col-span-1 rounded-lg border-slate-200 text-sm">
                        <label class="md:col-span-1 flex items-center gap-1 text-xs font-semibold text-slate-600">
                            <input type="checkbox" :name="'features['+i+'][is_active]'" value="1" x-model="f.is_active" class="rounded border-slate-300"> On
                        </label>
                        <button type="button" @click="features.splice(i,1)" class="md:col-span-1 text-xs font-bold text-rose-600">Remove</button>
                    </div>
                </template>
                <p x-show="features.length === 0" class="text-sm text-slate-400 py-2" x-cloak>No features yet. Click “+ Feature” to add items for the homepage trust bar.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Promo banners (Deals You’ll Love)</h3>
                    <p class="text-sm text-slate-500">Homepage deal cards. Use Dark / Light theme. Optional badge, highlight text (e.g. 40%), and discount circle (−40%).</p>
                </div>
                <button type="button" @click="addBanner()" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Banner</button>
            </div>
            <div class="mt-4 space-y-4">
                <template x-for="(b, i) in banners" :key="i">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 space-y-3">
                        <input type="hidden" :name="'banners['+i+'][id]'" :value="b.id || ''">
                        <div class="grid gap-2 md:grid-cols-2">
                            <input :name="'banners['+i+'][title]'" x-model="b.title" placeholder="Title (e.g. SOMOSTEL B2)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][subtitle]'" x-model="b.subtitle" placeholder="Subtitle (e.g. Up to 40% Off)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][badge_text]'" x-model="b.badge_text" placeholder="Badge (e.g. BEST SELLER)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][highlight_text]'" x-model="b.highlight_text" placeholder="Highlight in subtitle (e.g. 40%)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][discount_badge]'" x-model="b.discount_badge" placeholder="Corner badge (e.g. -40%)" class="rounded-lg border-slate-200 text-sm">
                            <input type="number" step="0.01" :name="'banners['+i+'][price_from]'" x-model="b.price_from" placeholder="Price from" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][button_url]'" x-model="b.button_url" placeholder="Button URL" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][button_text]'" x-model="b.button_text" placeholder="Button text" class="rounded-lg border-slate-200 text-sm">
                            <select :name="'banners['+i+'][theme]'" x-model="b.theme" class="rounded-lg border-slate-200 text-sm">
                                <option value="dark">Dark (pink CTA)</option>
                                <option value="light">Light (blue CTA)</option>
                            </select>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <template x-if="b.image_path">
                                    <img :src="'{{ asset('storage') }}/' + b.image_path" alt="" class="h-14 w-14 rounded-lg object-cover border border-slate-200 bg-white">
                                </template>
                                <div>
                                    <label class="text-[11px] font-bold uppercase text-slate-500">Banner image</label>
                                    <input type="file" :name="'banners['+i+'][image]'" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block text-sm">
                                </div>
                            </div>
                            <input type="number" :name="'banners['+i+'][sort_order]'" x-model="b.sort_order" class="w-20 rounded-lg border-slate-200 text-sm" title="Sort order">
                            <label class="flex items-center gap-1 text-xs font-semibold text-slate-600">
                                <input type="checkbox" :name="'banners['+i+'][is_active]'" value="1" x-model="b.is_active" class="rounded border-slate-300"> Active
                            </label>
                            <button type="button" @click="banners.splice(i,1)" class="text-xs font-bold text-rose-600">Remove</button>
                        </div>
                    </div>
                </template>
                <p x-show="banners.length === 0" class="text-sm text-slate-400 py-2" x-cloak>No promo banners yet. Click “+ Banner” and upload an image for each card.</p>
            </div>
        </div>

        <div class="flex justify-end">
            <button class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">Save landing page</button>
        </div>
    </form>
</x-cms-layout>
