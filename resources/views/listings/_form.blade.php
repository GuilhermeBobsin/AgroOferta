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

<div>
    <h3 class="mb-2 text-sm font-semibold text-gray-800">Onde está o produto?</h3>

    <x-brazil-location-fields
        :city="$l?->city ?? auth()->user()->city"
        :state="$l?->state ?? auth()->user()->state"
        :required="true"
        prefix="listing-location"
    />

    <input type="hidden" name="latitude" id="listing-latitude"
           value="{{ old('latitude', $l?->latitude) }}">
    <input type="hidden" name="longitude" id="listing-longitude"
           value="{{ old('longitude', $l?->longitude) }}">

    <div class="mt-3 rounded-xl bg-green-50 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p id="listing-geo-message" aria-live="polite" class="text-sm text-gray-700">
                @if (old('latitude') !== null && old('longitude') !== null)
                    Localização do produto selecionada. Salve o anúncio para aplicar.
                @elseif ($l?->latitude !== null && $l?->longitude !== null)
                    Este anúncio já tem coordenadas salvas.
                @else
                    Marque a localização do produto para calcular distâncias com mais precisão.
                @endif
            </p>

            <button type="button" id="listing-geolocate"
                class="shrink-0 rounded-lg border border-green-800 px-4 py-2 text-sm font-semibold text-green-900">
                Marcar posição do produto
            </button>
        </div>

        <p class="mt-2 text-xs leading-5 text-gray-600">
            Sem coordenadas próprias, será usada a localização aproximada da cidade.
            Os outros usuários verão apenas a distância aproximada.
        </p>
    </div>
</div>

<div>
    <label for="image" class="mb-1 block text-sm font-semibold text-gray-800">
        Foto do insumo
    </label>

    <input id="image" type="file" name="image"
        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
        class="block w-full text-sm text-gray-600">

    <p class="mt-1 text-xs text-gray-500">JPG, PNG ou WebP; até 4 MB.</p>

    <div id="image-preview" class="mt-3 hidden items-center gap-3 rounded-lg bg-green-50 p-3" aria-live="polite">
        <img id="image-preview-photo" class="rounded-md object-cover"
             style="width: 12rem; height: 10rem;" alt="Prévia da nova foto">
        <p class="text-sm text-gray-700">Nova foto selecionada. Ela será enviada ao salvar.</p>
    </div>

    @if ($l?->image_path)
        <div class="mt-3 flex items-center gap-3 rounded-lg bg-gray-50 p-3">
            <img src="{{ asset('storage/'.$l->image_path) }}"
                 class="h-20 w-24 rounded-md object-cover"
                 alt="Foto atual de {{ $l->title }}">
            <p class="text-sm text-gray-600">Foto atual. Envie outra para substituí-la.</p>
        </div>
    @endif
</div>


<label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm text-gray-700">
    <input type="checkbox" name="negotiable" value="1" @checked(old('negotiable', $l?->negotiable ?? true)) class="mt-0.5 rounded border-gray-300 text-green-800 focus:ring-green-700">
    <span><strong class="block text-gray-900">Aceito negociar o preço</strong><span class="text-xs text-gray-500">Compradores poderão entrar em contato com uma proposta.</span></span>
</label>

<script>
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    const imagePreviewPhoto = document.getElementById('image-preview-photo');
    let imagePreviewUrl;

    imageInput?.addEventListener('change', () => {
        if (imagePreviewUrl) {
            URL.revokeObjectURL(imagePreviewUrl);
            imagePreviewUrl = undefined;
        }

        const [image] = imageInput.files;
        if (!image || !image.type.startsWith('image/')) {
            imagePreview.classList.add('hidden');
            imagePreview.classList.remove('flex');
            imagePreviewPhoto.removeAttribute('src');
            return;
        }

        imagePreviewUrl = URL.createObjectURL(image);
        imagePreviewPhoto.src = imagePreviewUrl;
        imagePreview.classList.remove('hidden');
        imagePreview.classList.add('flex');
    });

    document.getElementById('listing-geolocate')?.addEventListener('click', () => {
        const message = document.getElementById('listing-geo-message');
        if (!navigator.geolocation) {
            message.textContent = 'Seu navegador não oferece acesso à localização.';
            return;
        }

        message.textContent = 'Obtendo a localização do produto...';
        navigator.geolocation.getCurrentPosition(
            (position) => {
                document.getElementById('listing-latitude').value = position.coords.latitude.toFixed(7);
                document.getElementById('listing-longitude').value = position.coords.longitude.toFixed(7);
                message.textContent = 'Localização obtida. Revise cidade e estado e salve o anúncio.';
            },
            (error) => {
                message.textContent = error.code === 1
                    ? 'A permissão de localização foi negada. Você pode continuar sem coordenadas.'
                    : 'Não foi possível obter a localização. Tente novamente ou continue sem coordenadas.';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
    });
</script>