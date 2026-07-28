<x-cms-layout title="Blogs" subtitle="Articles, featured post, and blog page hero / newsletter — all shown on the public /blog page." actionUrl="{{ route('cms.blogs.create') }}" actionLabel="+ New post" previewUrl="{{ route('website.blogs') }}">

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('cms.blog-categories.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Manage categories</a>
    </div>

    {{-- Blog page settings (hero + newsletter) --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900">Blog page settings</h3>
        <p class="text-xs text-slate-500 mt-0.5">Controls the hero banner and newsletter box on /blog.</p>
        <form method="POST" action="{{ route('cms.blogs.settings') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 md:grid-cols-2">
            @csrf
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Hero kicker</label>
                <input name="blog_hero_kicker" value="{{ old('blog_hero_kicker', $settings->blog_hero_kicker ?? 'OUR BLOG') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="OUR BLOG">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Hero title</label>
                <input name="blog_hero_title" value="{{ old('blog_hero_title', $settings->blog_hero_title ?? 'News & Articles') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="News & Articles">
            </div>
            <div class="md:col-span-2">
                <label class="text-[11px] font-bold uppercase text-slate-500">Hero subtitle</label>
                <textarea name="blog_hero_subtitle" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ old('blog_hero_subtitle', $settings->blog_hero_subtitle) }}</textarea>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Hero background image</label>
                <input type="file" name="blog_hero_image" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                @if(!empty($settings->blog_hero_image))
                    <img src="{{ public_storage_url($settings->blog_hero_image) }}" class="mt-2 h-16 rounded-lg object-cover" alt="">
                    <p class="mt-1 text-[11px] text-slate-400">Leave empty to keep the current image.</p>
                @endif
                @error('blog_hero_image') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Newsletter title</label>
                <input name="blog_newsletter_title" value="{{ old('blog_newsletter_title', $settings->blog_newsletter_title ?? 'Subscribe to Our Newsletter') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="text-[11px] font-bold uppercase text-slate-500">Newsletter text</label>
                <input name="blog_newsletter_text" value="{{ old('blog_newsletter_text', $settings->blog_newsletter_text) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Get the latest deals and tech news…">
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800">Save page settings</button>
            </div>
        </form>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Post</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Views</th>
                    <th class="px-5 py-3">Published</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($blogs as $blog)
                    <tr>
                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-900 flex items-center gap-2">
                                {{ $blog->title }}
                                @if($blog->is_featured)
                                    <span class="text-[10px] font-bold uppercase text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">Featured</span>
                                @endif
                            </div>
                            <a href="{{ route('website.blog', $blog->slug) }}" target="_blank" class="text-xs text-indigo-600">/blog/{{ $blog->slug }}</a>
                        </td>
                        <td class="px-5 py-4 text-center text-slate-500">{{ $blog->category?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-center text-slate-500">{{ number_format($blog->views_count ?? 0) }}</td>
                        <td class="px-5 py-4 text-center text-slate-500">{{ optional($blog->published_at)->format('M d, Y') ?? '—' }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="rounded-full px-2 py-1 text-xs font-bold {{ $blog->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $blog->is_published ? 'Live' : 'Draft' }}</span>
                        </td>
                        <td class="px-5 py-4 text-right space-x-3">
                            <a href="{{ route('cms.blogs.edit', $blog) }}" class="font-semibold text-indigo-600">Edit</a>
                            <form action="{{ route('cms.blogs.destroy', $blog) }}" method="POST" class="inline" data-confirm="Delete post?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">@csrf @method('DELETE')<button class="font-semibold text-rose-600">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No blog posts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $blogs->links() }}</div>
    </div>
</x-cms-layout>
