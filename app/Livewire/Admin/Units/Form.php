<?php

namespace App\Livewire\Admin\Units;

use App\Models\Category;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Services\AuditLogger;
use App\Services\ImageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app.sidebar')]
class Form extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public ?Unit $unit = null;

    public string $name = '';

    public string $category_id = '';

    public ?string $location = null;

    public ?string $description = null;

    public string $status = Unit::STATUS_AVAILABLE;

    public ?int $nightly_price_php = null;

    public ?int $monthly_price_php = null;

    public string $price_display_mode = Unit::DISPLAY_NIGHT;

    public string $estimator_mode = Unit::ESTIMATOR_HYBRID;

    public bool $allow_estimator = true;

    public array $newImages = [];

    public function mount($unit = null): void
    {
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
        $this->description = $this->unit->description;
        $this->status = $this->unit->status;
        $this->nightly_price_php = $this->unit->nightly_price_php;
        $this->monthly_price_php = $this->unit->monthly_price_php;
        $this->price_display_mode = $this->unit->price_display_mode;
        $this->estimator_mode = $this->unit->estimator_mode ?? Unit::ESTIMATOR_HYBRID;
        $this->allow_estimator = (bool) $this->unit->allow_estimator;
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
                'description' => $validated['description'] ?: null,
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
        return view('livewire.admin.units.form', [
            'categories' => Category::query()->orderBy('name')->get(),
            'existingImages' => $this->unit?->images()->orderBy('sort_order')->get() ?? collect(),
            'isEditMode' => $this->unit !== null,
        ]);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'location' => ['nullable', 'string', 'max:255'],
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
}
