<?php

namespace App\Livewire\Admin\Units;

use App\Models\Category;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Services\Ai\UnitDescriptionAiService;
use App\Services\AuditLogger;
use App\Services\ImageService;
use App\Support\Tenancy\TenantManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app.sidebar')]
class Form extends Component
{
    use AuthorizesRequests, WithFileUploads;

    /**
     * @var array<string, array{name:string,location:string,address_text:string,latitude:string,longitude:string}>
     */
    private const DAVAO_CONDO_LOCATION_PRESETS = [
        'avida_towers_abreeza' => [
            'name' => 'Avida Towers Abreeza',
            'location' => 'Davao City',
            'address_text' => 'Avida Towers Abreeza, Davao City',
            'latitude' => '7.0908805',
            'longitude' => '125.6097848',
        ],
        'aeon_towers' => [
            'name' => 'Aeon Towers',
            'location' => 'Davao City',
            'address_text' => 'Aeon Towers, Davao City',
            'latitude' => '7.0925497',
            'longitude' => '125.6116623',
        ],
        'eight_spatial' => [
            'name' => '8 Spatial',
            'location' => 'Davao City',
            'address_text' => '8 Spatial, Davao City',
            'latitude' => '7.067919675',
            'longitude' => '125.59128389',
        ],
        'bria_homes' => [
            'name' => 'Bria Homes',
            'location' => 'Davao City',
            'address_text' => 'Bria Homes, Davao City',
            'latitude' => '7.3273499',
            'longitude' => '125.6882758',
        ],
        'camella_north_point' => [
            'name' => 'Camella North Point',
            'location' => 'Davao City',
            'address_text' => 'Camella North Point, Davao City',
            'latitude' => '7.096197329',
            'longitude' => '125.614291984',
        ],
        'verdon_parc' => [
            'name' => 'Verdon Parc',
            'location' => 'Davao City',
            'address_text' => 'Verdon Parc, Davao City',
            'latitude' => '7.0496733',
            'longitude' => '125.5973805',
        ],
        'inspiria_condominium' => [
            'name' => 'Inspiria Condominium',
            'location' => 'Davao City',
            'address_text' => 'Inspiria Condominium, Davao City',
            'latitude' => '7.0917879',
            'longitude' => '125.6105396',
        ],
        'vivaldi_residence' => [
            'name' => 'Vivaldi Residence',
            'location' => 'Davao City',
            'address_text' => 'Vivaldi Residence, Davao City',
            'latitude' => '7.0734557',
            'longitude' => '125.6122524',
        ],
        'one_lakeshore_drive' => [
            'name' => 'One Lakeshore Drive',
            'location' => 'Davao City',
            'address_text' => 'One Lakeshore Drive, Davao City',
            'latitude' => '7.0988484',
            'longitude' => '125.6303981',
        ],
        'matina_enclaves_residences' => [
            'name' => 'Matina Enclaves Residences',
            'location' => 'Davao City',
            'address_text' => 'Matina Enclaves Residences, Davao City',
            'latitude' => '7.0534579',
            'longitude' => '125.5848117',
        ],
        'seawind_condominium' => [
            'name' => 'Seawind Condominium',
            'location' => 'Davao City',
            'address_text' => 'Seawind Condominium, Davao City',
            'latitude' => '7.1364553',
            'longitude' => '125.6605493',
        ],
    ];

    private const AI_TONE_OPTIONS = ['Luxury', 'Neutral', 'Friendly'];

    private const AI_LENGTH_OPTIONS = ['Short', 'Medium', 'Long'];

    private const AI_RATE_LIMIT_PER_MINUTE = 10;

    private const AI_RATE_LIMIT_DECAY_SECONDS = 60;

    public ?Unit $unit = null;

    public string $name = '';

    public string $category_id = '';

    public ?string $location = null;

    public ?string $latitude = null;

    public ?string $longitude = null;

    public ?string $address_text = null;

    public ?string $description = null;

    public string $status = Unit::STATUS_AVAILABLE;

    public ?int $nightly_price_php = null;

    public ?int $monthly_price_php = null;

    public string $price_display_mode = Unit::DISPLAY_NIGHT;

    public string $estimator_mode = Unit::ESTIMATOR_HYBRID;

    public bool $allow_estimator = true;

    public array $newImages = [];

    public string $selectedLocationPreset = '';

    public string $aiTone = 'Luxury';

    public string $aiLength = 'Medium';

    public ?string $ai_description_draft = null;

    public ?array $ai_description_meta = null;

    public ?string $ai_description_generated_at = null;

    /**
     * @var array<int, string>
     */
    public array $aiWarnings = [];

    public function mount(Unit|int|string|null $unit = null): void
    {
        if ($unit !== null && ! $unit instanceof Unit) {
            $unit = (new Unit)->resolveRouteBinding($unit);
        }

        $this->unit = $unit instanceof Unit && $unit->exists
            ? $unit->load('images')
            : null;

        if ($this->unit) {
            $this->authorize('update', $this->unit);
        } else {
            $this->authorize('create', Unit::class);
        }

        if (! $this->unit) {
            return;
        }

        $this->name = $this->unit->name;
        $this->category_id = (string) $this->unit->category_id;
        $this->location = $this->unit->location;
        $this->latitude = $this->unit->latitude !== null ? number_format((float) $this->unit->latitude, 7, '.', '') : null;
        $this->longitude = $this->unit->longitude !== null ? number_format((float) $this->unit->longitude, 7, '.', '') : null;
        $this->address_text = $this->unit->address_text;
        $this->description = $this->unit->description;
        $this->status = $this->unit->status;
        $this->nightly_price_php = $this->unit->nightly_price_php;
        $this->monthly_price_php = $this->unit->monthly_price_php;
        $this->price_display_mode = $this->unit->price_display_mode;
        $this->estimator_mode = $this->unit->estimator_mode ?? Unit::ESTIMATOR_HYBRID;
        $this->allow_estimator = (bool) $this->unit->allow_estimator;
        $this->ai_description_draft = $this->unit->ai_description_draft;
        $this->ai_description_meta = $this->unit->ai_description_meta;
        $this->ai_description_generated_at = $this->unit->ai_description_generated_at?->toDateTimeString();
    }

    public function updatedSelectedLocationPreset(string $presetKey): void
    {
        if ($this->unit !== null || $presetKey === '') {
            return;
        }

        $this->applyCondoLocationPreset($presetKey);
    }

    public function applyCondoLocationPreset(string $presetKey): void
    {
        if ($this->unit !== null) {
            return;
        }

        $preset = self::DAVAO_CONDO_LOCATION_PRESETS[$presetKey] ?? null;
        if ($preset === null) {
            return;
        }

        $latitude = (float) $preset['latitude'];
        $longitude = (float) $preset['longitude'];

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return;
        }

        $this->name = $preset['name'];
        $this->location = $preset['location'];
        $this->address_text = $preset['address_text'];
        $this->latitude = number_format($latitude, 7, '.', '');
        $this->longitude = number_format($longitude, 7, '.', '');

        $this->dispatch(
            'leaflet-picker-set-coordinates',
            componentId: $this->getId(),
            latitude: $this->latitude,
            longitude: $this->longitude,
        );
    }

    public function save(ImageService $imageService, AuditLogger $auditLogger): void
    {
        if ($this->unit) {
            $this->authorize('update', $this->unit);
        } else {
            $this->authorize('create', Unit::class);
        }

        $validated = $this->validate($this->rules());

        if ($this->nightly_price_php === null && $this->monthly_price_php === null) {
            $this->addError('nightly_price_php', 'At least one price (nightly or monthly) is required.');

            return;
        }

        if ($this->price_display_mode === Unit::DISPLAY_NIGHT && $this->nightly_price_php === null) {
            $this->addError('nightly_price_php', 'Nightly price is required for NIGHT display mode.');

            return;
        }

        if ($this->price_display_mode === Unit::DISPLAY_MONTH && $this->monthly_price_php === null) {
            $this->addError('monthly_price_php', 'Monthly price is required for MONTH display mode.');

            return;
        }

        $wasNew = $this->unit === null;
        $existingUnit = $this->unit;

        $unit = DB::transaction(function () use ($validated): Unit {
            $payload = [
                'name' => $validated['name'],
                'slug' => $this->generateUniqueSlug($validated['name']),
                'category_id' => (int) $validated['category_id'],
                'location' => $validated['location'] ?: null,
                'latitude' => isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                'longitude' => isset($validated['longitude']) ? (float) $validated['longitude'] : null,
                'address_text' => $validated['address_text'] ?: null,
                'description' => $validated['description'] ?: null,
                'ai_description_draft' => $this->ai_description_draft ?: null,
                'ai_description_meta' => $this->ai_description_meta,
                'ai_description_generated_at' => $this->ai_description_generated_at,
                'status' => $validated['status'],
                'nightly_price_php' => $validated['nightly_price_php'],
                'monthly_price_php' => $validated['monthly_price_php'],
                'price_display_mode' => $validated['price_display_mode'],
                'estimator_mode' => $validated['estimator_mode'],
                'allow_estimator' => (bool) $validated['allow_estimator'],
                'updated_by' => auth()->id(),
            ];

            if ($this->unit === null) {
                $payload['created_by'] = auth()->id();

                return Unit::create($payload);
            }

            $this->unit->update($payload);

            return $this->unit->fresh();
        });

        $this->unit = $unit->load('images');

        if ($this->newImages !== []) {
            $sortOrder = (int) ($this->unit->images()->max('sort_order') ?? -1);
            foreach ($this->newImages as $uploadedImage) {
                $sortOrder++;
                $path = $imageService->storeUnitImage($uploadedImage, $this->unit->id);

                UnitImage::create([
                    'unit_id' => $this->unit->id,
                    'path' => $path,
                    'sort_order' => $sortOrder,
                ]);

                $auditLogger->log(
                    action: 'IMAGE_ADD',
                    unit: $this->unit,
                    changes: ['path' => $path, 'sort_order' => $sortOrder]
                );
            }
        }

        $this->newImages = [];
        $this->unit->refresh()->load('images');

        $auditLogger->log(
            action: $wasNew ? 'CREATE' : 'UPDATE',
            unit: $this->unit,
            changes: [
                'before' => $existingUnit?->only([
                    'name',
                    'category_id',
                    'location',
                    'latitude',
                    'longitude',
                    'address_text',
                    'status',
                    'nightly_price_php',
                    'monthly_price_php',
                    'price_display_mode',
                    'estimator_mode',
                    'allow_estimator',
                ]),
                'after' => $this->unit->only([
                    'name',
                    'category_id',
                    'location',
                    'latitude',
                    'longitude',
                    'address_text',
                    'status',
                    'nightly_price_php',
                    'monthly_price_php',
                    'price_display_mode',
                    'estimator_mode',
                    'allow_estimator',
                ]),
            ]
        );

        session()->flash('status', $wasNew ? 'Unit created successfully.' : 'Unit updated successfully.');
        $this->redirectRoute('admin.units.index', navigate: true);
    }

    public function removeImage(int $imageId, ImageService $imageService, AuditLogger $auditLogger): void
    {
        if (! $this->unit) {
            return;
        }

        $this->authorize('manageImages', $this->unit);

        $image = $this->unit->images()->whereKey($imageId)->firstOrFail();
        $path = $image->path;

        $imageService->delete($path);
        $image->delete();

        $auditLogger->log(
            action: 'IMAGE_REMOVE',
            unit: $this->unit,
            changes: ['path' => $path]
        );

        $this->unit->refresh()->load('images');
    }

    public function generateAiDescription(UnitDescriptionAiService $aiService, AuditLogger $auditLogger): void
    {
        $this->authorize('access-admin');
        $this->resetErrorBag('ai_generation');
        $this->aiWarnings = [];

        if (! in_array($this->aiTone, self::AI_TONE_OPTIONS, true)) {
            $this->addError('ai_generation', 'Invalid AI tone selected.');

            return;
        }

        if (! in_array($this->aiLength, self::AI_LENGTH_OPTIONS, true)) {
            $this->addError('ai_generation', 'Invalid AI length selected.');

            return;
        }

        $adminId = auth()->id();
        if (! is_int($adminId)) {
            abort(403);
        }

        $rateKey = 'unit-ai-description:'.$adminId;
        $callbackExecuted = false;

        $attemptResult = RateLimiter::attempt(
            $rateKey,
            self::AI_RATE_LIMIT_PER_MINUTE,
            function () use (&$callbackExecuted, $aiService, $auditLogger): bool {
                $callbackExecuted = true;
                $unit = null;
                if ($this->unit?->exists) {
                    $unit = $this->unit->fresh([
                        'category',
                        'images' => fn ($query) => $query->orderBy('sort_order'),
                    ]);
                }

                if (($unit === null || $unit->images->isEmpty()) && $this->newImages === []) {
                    $this->addError('ai_generation', 'Please upload at least 1 photo before generating.');

                    return false;
                }

                $contextWarnings = [];
                if ($this->location === null || trim($this->location) === '') {
                    $contextWarnings[] = 'Location is missing. Add location for more accurate output.';
                }

                try {
                    $generated = ($unit !== null && $unit->images->isNotEmpty())
                        ? $aiService->generate(
                            unit: $unit,
                            tone: $this->aiTone,
                            length: $this->aiLength,
                            context: $this->aiContextForGeneration($unit)
                        )
                        : $aiService->generateFromUploadedFiles(
                            files: $this->newImages,
                            tone: $this->aiTone,
                            length: $this->aiLength,
                            context: $this->aiContextForGeneration($unit)
                        );
                } catch (\Throwable $exception) {
                    report($exception);
                    $safeMessages = [
                        'Please upload at least 1 photo before generating.',
                        'AI service is not configured.',
                    ];

                    $this->addError(
                        'ai_generation',
                        in_array($exception->getMessage(), $safeMessages, true)
                            ? $exception->getMessage()
                            : 'Unable to generate AI description right now. Please try again.'
                    );

                    return false;
                }

                $this->ai_description_draft = $generated['draft'];
                $this->ai_description_meta = $generated['meta'];
                $this->ai_description_generated_at = $generated['generated_at'];
                $this->aiWarnings = array_values(array_unique(array_merge(
                    $contextWarnings,
                    $generated['warnings']
                )));

                $auditLogger->log(
                    action: 'AI_DESCRIPTION_GENERATED',
                    unit: $unit,
                    changes: [
                        'tone' => $this->aiTone,
                        'length' => $this->aiLength,
                        'image_count' => $generated['image_count'],
                    ]
                );

                return true;
            },
            self::AI_RATE_LIMIT_DECAY_SECONDS
        );

        if ($attemptResult === false && ! $callbackExecuted) {
            $this->addError('ai_generation', 'Too many attempts. Please try again in a minute.');
        }
    }

    public function applyAiDescriptionDraft(): void
    {
        $this->authorize('access-admin');

        if ($this->ai_description_draft === null || trim($this->ai_description_draft) === '') {
            return;
        }

        $this->description = $this->ai_description_draft;
    }

    public function clearAiDescriptionDraft(): void
    {
        $this->authorize('access-admin');
        $this->ai_description_draft = null;
        $this->ai_description_meta = null;
        $this->ai_description_generated_at = null;
        $this->aiWarnings = [];
        $this->resetErrorBag('ai_generation');
    }

    public function moveImageUp(int $imageId, ImageService $imageService): void
    {
        $this->moveImage($imageId, $imageService, -1);
    }

    public function moveImageDown(int $imageId, ImageService $imageService): void
    {
        $this->moveImage($imageId, $imageService, 1);
    }

    public function render()
    {
        $sortedDavaoCondoLocationPresets = collect(self::DAVAO_CONDO_LOCATION_PRESETS)
            ->sortBy(
                fn (array $preset): string => $preset['name'],
                SORT_NATURAL | SORT_FLAG_CASE
            )
            ->all();

        return view('livewire.admin.units.form', [
            'categories' => Category::query()->orderBy('name')->get(),
            'existingImages' => $this->unit?->images()->orderBy('sort_order')->get() ?? collect(),
            'isEditMode' => $this->unit !== null,
            'davaoCondoLocationPresets' => $sortedDavaoCondoLocationPresets,
            'aiToneOptions' => self::AI_TONE_OPTIONS,
            'aiLengthOptions' => self::AI_LENGTH_OPTIONS,
        ]);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(
                    'tenant_id',
                    app(TenantManager::class)->currentId()
                ),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.Unit::STATUS_AVAILABLE.','.Unit::STATUS_UNAVAILABLE],
            'nightly_price_php' => ['nullable', 'integer', 'min:0'],
            'monthly_price_php' => ['nullable', 'integer', 'min:0'],
            'price_display_mode' => ['required', 'in:'.Unit::DISPLAY_NIGHT.','.Unit::DISPLAY_MONTH],
            'estimator_mode' => ['required', 'in:'.Unit::ESTIMATOR_HYBRID.','.Unit::ESTIMATOR_NIGHTLY_ONLY.','.Unit::ESTIMATOR_MONTHLY_ONLY],
            'allow_estimator' => ['required', 'boolean'],
            'newImages.*' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'unit';
        $slug = $base;
        $counter = 1;

        while (
            Unit::query()
                ->where('slug', $slug)
                ->when($this->unit, fn ($query) => $query->whereKeyNot($this->unit->id))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function moveImage(int $imageId, ImageService $imageService, int $direction): void
    {
        if (! $this->unit) {
            return;
        }

        $this->authorize('manageImages', $this->unit);

        $images = $this->unit->images()->orderBy('sort_order')->get()->values();
        $currentIndex = $images->search(fn (UnitImage $image) => $image->id === $imageId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $currentIndex + $direction;
        if (! isset($images[$targetIndex])) {
            return;
        }

        $orderedIds = $images->pluck('id')->all();
        [$orderedIds[$currentIndex], $orderedIds[$targetIndex]] = [$orderedIds[$targetIndex], $orderedIds[$currentIndex]];
        $imageService->reorderUnitImages($this->unit->id, $orderedIds);

        $this->unit->refresh()->load('images');
    }

    /**
     * @return array<string, mixed>
     */
    private function aiContextForGeneration(?Unit $unit): array
    {
        return [
            'name' => $this->name !== '' ? $this->name : $unit?->name,
            'category' => $unit?->category?->name ?? $this->selectedCategoryName(),
            'location' => $this->location,
            'address_text' => $this->address_text,
            'price_display_mode' => $this->price_display_mode,
            'nightly_price_php' => $this->nightly_price_php,
            'monthly_price_php' => $this->monthly_price_php,
        ];
    }

    private function selectedCategoryName(): ?string
    {
        $categoryId = filter_var($this->category_id, FILTER_VALIDATE_INT);
        if (! is_int($categoryId) || $categoryId <= 0) {
            return null;
        }

        return Category::query()->whereKey($categoryId)->value('name');
    }
}
