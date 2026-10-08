@php $l = $listing ?? null; @endphp

@if ($errors->any())
    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <p class="font-semibold">Não foi possível salvar o anúncio:</p>
        <ul class="mt-2 list-inside list-disc space-y-1">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div>
    <label for="title" class="mb-1 block text-sm font-semibold text-gray-800">Nome do insumo <span class="text-red-700">*</span></label>
    <input id="title" name="title" value="{{ old('title', $l?->title) }}" placeholder="Ex.: Semente de soja — lote de 60 kg" maxlength="120" required class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
    <p class="mt-1 text-xs text-gray-500">Use um nome claro e informe a variedade ou condição, se souber.</p>
</div>

<div>
    <label for="category_id" class="mb-1 block text-sm font-semibold text-gray-800">Categoria <span class="text-red-700">*</span></label>
    <select id="category_id" name="category_id" required class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
        <option value="">Selecione uma categoria</option>
        @foreach ($categories as $c)
            <option value="{{ $c->id }}" @selected(old('category_id', $l?->category_id) == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="description" class="mb-1 block text-sm font-semibold text-gray-800">Descrição</label>
    <textarea id="description" name="description" rows="4" maxlength="3000" placeholder="Conte sobre quantidade, validade, conservação e retirada." class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">{{ old('description', $l?->description) }}</textarea>
    <p class="mt-1 text-xs text-gray-500">Não inclua dados pessoais ou endereço completo na descrição pública.</p>
</div>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
    <div class="sm:col-span-1">
        <label for="price" class="mb-1 block text-sm font-semibold text-gray-800">Preço por unidade (R$) <span class="text-red-700">*</span></label>
        <input id="price" name="price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('price', $l?->price) }}" placeholder="0,00" required class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
    </div>
    <div>
        <label for="unit" class="mb-1 block text-sm font-semibold text-gray-800">Unidade <span class="text-red-700">*</span></label>
        <select id="unit" name="unit" required class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
            @foreach (\App\Models\Listing::UNITS as $u)
                <option value="{{ $u }}" @selected(old('unit', $l?->unit ?? 'kg') === $u)>{{ ucfirst($u) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="quantity" class="mb-1 block text-sm font-semibold text-gray-800">Quantidade disponível</label>
        <input id="quantity" name="quantity" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('quantity', $l?->quantity) }}" placeholder="Opcional" class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
    </div>
</div>

<div>
    <h3 class="mb-2 text-sm font-semibold text-gray-800">Onde está o produto?</h3>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="city" class="mb-1 block text-xs font-medium text-gray-600">Cidade <span class="text-red-700">*</span></label>
            <input id="city" name="city" value="{{ old('city', $l?->city ?? auth()->user()->city) }}" placeholder="Cidade" autocomplete="address-level2" maxlength="100" required class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
        </div>
        <div>
            <label for="state" class="mb-1 block text-xs font-medium text-gray-600">Estado (UF) <span class="text-red-700">*</span></label>
            <input id="state" name="state" value="{{ old('state', $l?->state ?? auth()->user()->state) }}" maxlength="2" pattern="[A-Za-z]{2}" placeholder="RS" autocomplete="address-level1" required class="w-full rounded-lg border-gray-300 uppercase focus:border-green-700 focus:ring-green-700">
        </div>
    </div>
</div>

<div>
    <label for="image" class="mb-1 block text-sm font-semibold text-gray-800">Foto do insumo</label>
    <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-600 file:me-3 file:rounded-lg file:border-0 file:bg-green-50 file:px-4 file:py-2 file:font-semibold file:text-green-900 hover:file:bg-green-100">
    <p class="mt-1 text-xs text-gray-500">JPG, PNG ou WebP; até 4 MB. Uma foto nítida ajuda quem está procurando.</p>
    @if ($l?->image_path)
        <div class="mt-3 flex items-center gap-3 rounded-lg bg-gray-50 p-3">
            <img src="{{ asset('storage/'.$l->image_path) }}" class="h-20 w-24 rounded-md object-cover" alt="Foto atual de {{ $l->title }}">
            <p class="text-sm text-gray-600">Foto atual. Envie outra imagem para substituí-la.</p>
        </div>
    @endif
</div>

<label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm text-gray-700">
    <input type="checkbox" name="negotiable" value="1" @checked(old('negotiable', $l?->negotiable ?? true)) class="mt-0.5 rounded border-gray-300 text-green-800 focus:ring-green-700">
    <span><strong class="block text-gray-900">Aceito negociar o preço</strong><span class="text-xs text-gray-500">Compradores poderão entrar em contato com uma proposta.</span></span>
</label>
