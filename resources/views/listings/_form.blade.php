@php $l = $listing ?? null; @endphp

@if ($errors->any())
    <div class="p-3 bg-red-100 text-red-800 rounded text-sm">
        @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

<input name="title" value="{{ old('title', $l?->title) }}" placeholder="Título (ex: Semente de soja 60kg)" required class="w-full rounded-md border-gray-300">

<select name="category_id" required class="w-full rounded-md border-gray-300">
    <option value="">Categoria</option>
    @foreach ($categories as $c)
        <option value="{{ $c->id }}" @selected(old('category_id', $l?->category_id) == $c->id)>{{ $c->name }}</option>
    @endforeach
</select>

<textarea name="description" rows="4" placeholder="Descrição" class="w-full rounded-md border-gray-300">{{ old('description', $l?->description) }}</textarea>

<div class="grid grid-cols-3 gap-2">
    <input name="price" type="number" step="0.01" value="{{ old('price', $l?->price) }}" placeholder="Preço" required class="rounded-md border-gray-300">
    <select name="unit" required class="rounded-md border-gray-300">
        @foreach (\App\Models\Listing::UNITS as $u)
            <option value="{{ $u }}" @selected(old('unit', $l?->unit ?? 'kg') === $u)>{{ $u }}</option>
        @endforeach
    </select>
    <input name="quantity" type="number" step="0.01" value="{{ old('quantity', $l?->quantity) }}" placeholder="Qtd." class="rounded-md border-gray-300">
</div>

<div class="grid grid-cols-3 gap-2">
    <input name="city" value="{{ old('city', $l?->city ?? auth()->user()->city) }}" placeholder="Cidade" required class="col-span-2 rounded-md border-gray-300">
    <input name="state" value="{{ old('state', $l?->state ?? auth()->user()->state) }}" maxlength="2" placeholder="UF" required class="rounded-md border-gray-300 uppercase">
</div>

<input type="file" name="image" accept="image/*" class="w-full text-sm">
@if ($l?->image_path)
    <img src="{{ asset('storage/'.$l->image_path) }}" class="h-24 rounded" alt="">
@endif

<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="negotiable" value="1" @checked(old('negotiable', $l?->negotiable ?? true))> Aceito negociar o preço
</label>
