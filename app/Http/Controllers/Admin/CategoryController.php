<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Cache::forget('header_cats');
Cache::forget('mega_tree');


class CategoryController extends Controller
{
    // ===== Helpers =====
    /** Tạo slug duy nhất */
    protected function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $i = 0;
        do {
            $try = $i ? "$slug-$i" : $slug;
            $exists = Category::where('slug', $try)
                ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))
                ->exists();
            $i++;
        } while ($exists);
        return $try;
    }

    /** Lấy toàn bộ id hậu duệ (con/cháu/...) để chống vòng lặp */
    protected function descendantIds(Category $category): array
    {
        $ids = [];
        $queue = [$category->id];
        while (!empty($queue)) {
            $childIds = Category::whereIn('parent_id', $queue)->pluck('id')->all();
            $queue = $childIds;
            $ids = array_merge($ids, $childIds);
        }
        return $ids;
    }

    /** Mảng (id => tên hiển thị) có thụt lề cho select */
    protected function optionsIndented()
    {
        $all = Category::orderBy('parent_id')->orderBy('sort_order')->orderBy('name')->get()->groupBy('parent_id');
        $result = [];

        $walk = function ($parentId, $prefix) use (&$walk, &$result, $all) {
            foreach (($all[$parentId] ?? collect()) as $cat) {
                $result[$cat->id] = $prefix . $cat->name;
                $walk($cat->id, $prefix . '— ');
            }
        };
        $walk(null, '');

        return $result;
    }

    // ===== CRUD =====
    public function index(Request $r)
    {
        $view      = $r->get('view', 'all');       // all | parents | children
        $keyword   = trim((string) $r->get('keyword'));
        $parent_id = $r->filled('parent_id') ? (int) $r->get('parent_id') : null;
        $status    = $r->get('status');            // active | inactive | null

        // Đếm cho tabs
        $stats = [
            'all'      => Category::count(),
            'parents'  => Category::whereNull('parent_id')->count(),
            'children' => Category::whereNotNull('parent_id')->count(),
        ];

        // Khi xem tổng quan (không keyword, không chọn parent cụ thể, không chọn view=children)
        // thì dùng TREE để hiển thị cha xổ con (như mega menu) cho dễ soát.
        $useTree = ($view !== 'children') && !$keyword && !$parent_id;

        if ($useTree) {
            $parents = Category::query()
                ->whereNull('parent_id')
                ->when($status === 'active', fn($q) => $q->where('is_active', 1))
                ->when($status === 'inactive', fn($q) => $q->where('is_active', 0))
                ->withCount(['products', 'children'])
                ->with(['children' => function ($q) {
                    $q->withCount(['products', 'children'])
                        ->orderBy('sort_order')->orderBy('name');
                }])
                ->orderBy('sort_order')->orderBy('name')
                ->get();

            return view('admin.categories.index', [
                'mode'        => 'tree',
                'parents'     => $parents,
                'stats'       => $stats,
                'filters'     => $r->only('keyword', 'parent_id', 'status', 'view'),
                'parentsOpts' => $this->optionsIndented(),
            ]);
        }

        // LIST mode (bảng phẳng + phân trang)
        $q = Category::query()
            ->with('parent')
            ->withCount(['products', 'children'])
            ->when($keyword, fn($qq) => $qq->where(function ($w) use ($keyword) {
                $w->where('name', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%");
            }))
            ->when($parent_id !== null, fn($qq) => $qq->where('parent_id', $parent_id))
            ->when($status === 'active', fn($qq) => $qq->where('is_active', 1))
            ->when($status === 'inactive', fn($qq) => $qq->where('is_active', 0))
            ->when($view === 'parents', fn($qq) => $qq->whereNull('parent_id'))
            ->when($view === 'children', fn($qq) => $qq->whereNotNull('parent_id'))
            ->orderByRaw('parent_id IS NULL DESC')  // cha trước, con sau
            ->orderBy('sort_order')->orderBy('name');

        $categories = $q->paginate(15)->withQueryString();

        return view('admin.categories.index', [
            'mode'        => 'list',
            'categories'  => $categories,
            'stats'       => $stats,
            'filters'     => $r->only('keyword', 'parent_id', 'status', 'view'),
            'parentsOpts' => $this->optionsIndented(),
        ]);
    }


    public function create()
    {
        return view('admin.categories.create', [
            'parents' => $this->optionsIndented(),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['name']);
        } else {
            $data['slug'] = $this->uniqueSlug($data['slug']);
        }

        $data['is_active'] = (bool)($data['is_active'] ?? true);

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('ok', 'Tạo danh mục thành công!');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parents'  => $this->optionsIndented(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        // Chặn gán cha là chính nó hoặc là hậu duệ của nó
        if (!empty($data['parent_id'])) {
            if ((int)$data['parent_id'] === (int)$category->id) {
                return back()->withErrors('Không thể chọn chính danh mục làm danh mục cha.')->withInput();
            }
            $desc = $this->descendantIds($category);
            if (in_array((int)$data['parent_id'], $desc, true)) {
                return back()->withErrors('Không thể chọn danh mục con/cháu làm danh mục cha.')->withInput();
            }
        }

        if (isset($data['slug']) && $data['slug'] !== $category->slug) {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], $category->id);
        } elseif (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }

        $data['is_active'] = (bool)($data['is_active'] ?? false);

        $category->update($data);

        return redirect()->route('admin.categories.edit', $category)->with('ok', 'Cập nhật danh mục thành công!');
    }

    public function destroy(Category $category)
    {
        // Không xoá nếu còn sản phẩm hoặc có danh mục con
        $category->loadCount(['products', 'children']);
        if ($category->products_count > 0 || $category->children_count > 0) {
            return back()->withErrors('Không thể xoá danh mục đang có sản phẩm hoặc danh mục con.');
        }
        $category->delete();

        return back()->with('ok', 'Đã xoá danh mục.');
    }

    // ===== Actions nhanh =====

    public function toggle(Category $category)
    {
        $category->is_active = !$category->is_active;
        $category->save();

        return back()->with('ok', $category->is_active ? 'Đã bật hiển thị.' : 'Đã ẩn danh mục.');
    }

    public function bulk(Request $r)
    {
        $data = $r->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:categories,id'],
            'act'   => ['required', 'in:activate,deactivate,delete'],
        ], [], [
            'ids' => 'Danh sách mục đã chọn',
            'act' => 'Hành động',
        ]);

        $ids = $data['ids'];
        $act = $data['act'];

        if ($act === 'activate' || $act === 'deactivate') {
            Category::whereIn('id', $ids)->update(['is_active' => $act === 'activate' ? 1 : 0]);
            return back()->with('ok', 'Đã cập nhật trạng thái cho ' . count($ids) . ' danh mục.');
        }

        // delete: lọc những cái có thể xoá (không có con, không có sản phẩm)
        $canDelete = Category::whereIn('id', $ids)->withCount(['products', 'children'])->get()
            ->filter(fn($c) => $c->products_count == 0 && $c->children_count == 0)
            ->pluck('id')->all();

        if ($canDelete) {
            Category::whereIn('id', $canDelete)->delete();
        }

        $blocked = array_diff($ids, $canDelete);
        $msg = 'Đã xoá ' . count($canDelete) . ' danh mục.';
        if ($blocked) $msg .= ' ' . count($blocked) . ' danh mục bị chặn do đang có sản phẩm hoặc danh mục con.';
        return back()->with('ok', $msg);
    }
}
