<form method="get" class="flex items-center gap-2">
    @foreach(request()->except('sort') as $k=>$v)
    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    <select name="sort" class="form-control" onchange="this.form.submit()">
        <option value="">Mới nhất</option>
        <option value="price_asc" @selected(request('sort')==='price_asc' )>Giá ↑</option>
        <option value="price_desc" @selected(request('sort')==='price_desc' )>Giá ↓</option>
    </select>
</form>