<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\BrandLogoService;
use App\Services\WebsiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::where('shop_id', Auth::user()->shop_id)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        return view('brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brands')->where(fn ($q) => $q->where('shop_id', Auth::user()->shop_id)),
            ],
            'logo' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:5120',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data = [
            'shop_id' => Auth::user()->shop_id,
            'name' => $request->name,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->storeLogo($request);
        }

        $brand = Brand::create($data);

        $this->afterSave($brand, linkProducts: true);
        $brand->refresh();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'brand' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'logo_url' => $brand->logo_url,
                ],
            ]);
        }

        return redirect()->route('brands.index')->with('success', 'Brand created successfully!');
    }

    public function edit(Brand $brand)
    {
        if ($brand->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        if ($brand->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brands')
                    ->where(fn ($q) => $q->where('shop_id', Auth::user()->shop_id))
                    ->ignore($brand->id),
            ],
            'logo' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:5120',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data = [
            'name' => $request->name,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ];

        $oldLogo = $brand->logo_path;
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->storeLogo($request);
        }

        $brand->update($data);

        if ($request->hasFile('logo') && $oldLogo !== $brand->logo_path) {
            $this->deleteLogoIfUnused($oldLogo);
        }

        $brand->products()->update(['brand_name' => $brand->name]);

        $this->afterSave($brand, linkProducts: false);

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully!');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $logo = $brand->logo_path;

        $brand->products()->withTrashed()->update(['brand_id' => null, 'brand_name' => null]);
        $brand->delete();

        $this->deleteLogoIfUnused($logo);

        return redirect()->route('brands.index')->with('success', 'Brand removed.');
    }

    private function storeLogo(Request $request): string
    {
        $path = app(BrandLogoService::class)->storeUploaded($request->file('logo'));

        if ($path === null) {
            throw ValidationException::withMessages([
                'logo' => 'The logo could not be saved. Please try a different image or contact support.',
            ]);
        }

        return $path;
    }

    /** Merged duplicates and generated name marks can share one file across brands. */
    private function deleteLogoIfUnused(?string $path): void
    {
        if (! $path || Brand::where('logo_path', $path)->exists()) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /** Default logo and product linking are cosmetic and must never block saving the brand. */
    private function afterSave(Brand $brand, bool $linkProducts): void
    {
        try {
            if (empty($brand->fresh()->logo_path)) {
                app(BrandLogoService::class)->ensureStored($brand->fresh());
            }

            if ($linkProducts) {
                app(WebsiteService::class)->linkOrphanProductsToBrands((int) $brand->shop_id);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
