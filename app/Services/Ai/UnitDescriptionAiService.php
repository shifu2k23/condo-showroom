<?php

namespace App\Services\Ai;

use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class UnitDescriptionAiService
{
    private const MAX_IMAGES = 3;

    private const MAX_IMAGE_BYTES = 2_097_152; // 2MB

    private const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    private const BASE64_DATA_URL_PATTERN = '/data:image\/[a-z0-9.+-]+;base64,/i';

    /**
     * @var array<string, string>
     */
    private const IMAGE_MIME_BY_EXTENSION = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *     draft:string,
     *     meta:array<string,mixed>,
     *     image_count:int,
     *     generated_at:string,
     *     warnings:array<int,string>
     * }
     */
    public function generate(Unit $unit, string $tone, string $length, array $context = []): array
    {
        $imagePayload = $this->buildImagePayload($unit);

        if ($imagePayload['image_count'] === 0) {
            throw new RuntimeException('Please upload at least 1 photo before generating.');
        }

        $openAiData = $this->requestOpenAi(
            imageDataUrls: $imagePayload['image_data_urls'],
            context: $this->buildContext($unit, $context),
            tone: $tone,
            length: $length
        );

        return $this->buildGenerationResult($tone, $length, $imagePayload, $openAiData);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<string, mixed>  $context
     * @return array{
     *     draft:string,
     *     meta:array<string,mixed>,
     *     image_count:int,
     *     generated_at:string,
     *     warnings:array<int,string>
     * }
     */
    public function generateFromUploadedFiles(array $files, string $tone, string $length, array $context = []): array
    {
        $imagePayload = $this->buildUploadedImagePayload($files);

        if ($imagePayload['image_count'] === 0) {
            throw new RuntimeException('Please upload at least 1 photo before generating.');
        }

        $openAiData = $this->requestOpenAi(
            imageDataUrls: $imagePayload['image_data_urls'],
            context: $this->buildContextFromArray($context),
            tone: $tone,
            length: $length
        );

        return $this->buildGenerationResult($tone, $length, $imagePayload, $openAiData);
    }

    /**
     * @param  array{image_data_urls:array<int,string>,image_count:int,warnings:array<int,string>}  $imagePayload
     * @param  array{
     *     tagline:string,
     *     highlights:array<int,string>,
     *     full_description:string,
     *     detected_visual_features:array<int,string>,
     *     warnings:array<int,string>
     * }  $openAiData
     * @return array{
     *     draft:string,
     *     meta:array<string,mixed>,
     *     image_count:int,
     *     generated_at:string,
     *     warnings:array<int,string>
     * }
     */
    private function buildGenerationResult(string $tone, string $length, array $imagePayload, array $openAiData): array
    {
        $warnings = array_values(array_unique(array_merge(
            $imagePayload['warnings'],
            $openAiData['warnings']
        )));

        $generatedAtIso = now()->toIso8601String();

        return [
            'draft' => $this->formatDraft($openAiData),
            'meta' => [
                'tone' => $tone,
                'length' => $length,
                'model' => (string) config('services.openai.model'),
                'generated_at' => $generatedAtIso,
                'image_count' => $imagePayload['image_count'],
                'detected_visual_features' => $openAiData['detected_visual_features'],
                'warnings' => $warnings,
            ],
            'image_count' => $imagePayload['image_count'],
            'generated_at' => now()->toDateTimeString(),
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *     tagline:string,
     *     highlights:array<int,string>,
     *     full_description:string,
     *     detected_visual_features:array<int,string>,
     *     warnings:array<int,string>
     * }
     */
    private function requestOpenAi(array $imageDataUrls, array $context, string $tone, string $length): array
    {
        $apiKey = (string) config('services.openai.key');
        $model = (string) config('services.openai.model');

        if ($apiKey === '' || $model === '') {
            throw new RuntimeException('AI service is not configured.');
        }

        $userContent = [
            [
                'type' => 'text',
                'text' => $this->buildUserPrompt($context, $tone, $length),
            ],
        ];

        foreach ($imageDataUrls as $imageDataUrl) {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageDataUrl,
                ],
            ];
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(45)
            ->retry(1, 250)
            ->post(self::OPENAI_ENDPOINT, [
                'model' => $model,
                'temperature' => 0.4,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'unit_description',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => [
                                'tagline',
                                'highlights',
                                'full_description',
                                'detected_visual_features',
                            ],
                            'properties' => [
                                'tagline' => ['type' => 'string'],
                                'highlights' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                                'full_description' => ['type' => 'string'],
                                'detected_visual_features' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                                'warnings' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => implode("\n", [
                            'You write accurate condo listing descriptions from provided facts and images.',
                            'Never invent amenities, features, or views that are not visible or explicitly provided.',
                            'If uncertain, use "not specified".',
                            'Never include personal data, identities, phone numbers, or emails.',
                            'Return valid JSON only.',
                        ]),
                    ],
                    [
                        'role' => 'user',
                        'content' => $userContent,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to generate AI description right now.');
        }

        $jsonContent = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($jsonContent) || trim($jsonContent) === '') {
            throw new RuntimeException('AI response was empty.');
        }

        $decoded = json_decode($jsonContent, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('AI response format is invalid.');
        }

        return $this->validateStructuredOutput($decoded);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     tagline:string,
     *     highlights:array<int,string>,
     *     full_description:string,
     *     detected_visual_features:array<int,string>,
     *     warnings:array<int,string>
     * }
     */
    private function validateStructuredOutput(array $payload): array
    {
        $validator = Validator::make($payload, [
            'tagline' => ['required', 'string', 'max:255'],
            'highlights' => ['required', 'array', 'min:1', 'max:8'],
            'highlights.*' => ['required', 'string', 'max:180'],
            'full_description' => ['required', 'string', 'max:3000'],
            'detected_visual_features' => ['required', 'array', 'max:12'],
            'detected_visual_features.*' => ['required', 'string', 'max:180'],
            'warnings' => ['nullable', 'array', 'max:8'],
            'warnings.*' => ['required', 'string', 'max:180'],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException('AI response validation failed.');
        }

        /** @var array{
         *     tagline:string,
         *     highlights:array<int,string>,
         *     full_description:string,
         *     detected_visual_features:array<int,string>,
         *     warnings:array<int,string>
         * } $validated
         */
        $validated = $validator->validated();

        $validated['highlights'] = array_values(array_map(
            static fn (string $item): string => trim($item),
            $validated['highlights']
        ));

        $validated['detected_visual_features'] = array_values(array_map(
            static fn (string $item): string => trim($item),
            $validated['detected_visual_features']
        ));

        $validated['warnings'] = array_values(array_map(
            static fn (string $item): string => trim($item),
            $validated['warnings'] ?? []
        ));

        $this->assertNoEmbeddedImageData($validated);

        return $validated;
    }

    /**
     * @param  array{
     *     tagline:string,
     *     highlights:array<int,string>,
     *     full_description:string,
     *     detected_visual_features:array<int,string>,
     *     warnings:array<int,string>
     * } $validated
     */
    private function assertNoEmbeddedImageData(array $validated): void
    {
        $stringsToCheck = array_merge(
            [
                $validated['tagline'],
                $validated['full_description'],
            ],
            $validated['highlights'],
            $validated['detected_visual_features'],
            $validated['warnings']
        );

        foreach ($stringsToCheck as $value) {
            if (preg_match(self::BASE64_DATA_URL_PATTERN, $value) === 1) {
                throw new RuntimeException('AI response validation failed.');
            }
        }
    }

    /**
     * @return array{image_data_urls:array<int,string>,image_count:int,warnings:array<int,string>}
     */
    private function buildImagePayload(Unit $unit): array
    {
        $disk = Storage::disk('public');
        $paths = $unit->images()->orderBy('sort_order')->limit(self::MAX_IMAGES)->pluck('path');

        $dataUrls = [];
        $warnings = [];

        foreach ($paths as $path) {
            $pathString = (string) $path;
            $extension = strtolower(pathinfo($pathString, PATHINFO_EXTENSION));

            $mimeType = self::IMAGE_MIME_BY_EXTENSION[$extension] ?? null;
            if ($mimeType === null) {
                $warnings[] = "Skipped {$pathString}: unsupported type.";

                continue;
            }

            if (! $disk->exists($pathString)) {
                $warnings[] = "Skipped {$pathString}: file not found.";

                continue;
            }

            $size = (int) $disk->size($pathString);
            if ($size > self::MAX_IMAGE_BYTES) {
                $warnings[] = "Skipped {$pathString}: file larger than 2MB.";

                continue;
            }

            $rawBytes = $disk->get($pathString);
            $dataUrls[] = 'data:'.$mimeType.';base64,'.base64_encode($rawBytes);
        }

        return [
            'image_data_urls' => $dataUrls,
            'image_count' => count($dataUrls),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array{image_data_urls:array<int,string>,image_count:int,warnings:array<int,string>}
     */
    private function buildUploadedImagePayload(array $files): array
    {
        $dataUrls = [];
        $warnings = [];

        foreach (array_slice($files, 0, self::MAX_IMAGES) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $filename = $file->getClientOriginalName() ?: $file->getFilename();
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $mimeType = self::IMAGE_MIME_BY_EXTENSION[$extension] ?? null;
            if ($mimeType === null) {
                $warnings[] = "Skipped {$filename}: unsupported type.";

                continue;
            }

            $size = (int) ($file->getSize() ?? 0);
            if ($size > self::MAX_IMAGE_BYTES) {
                $warnings[] = "Skipped {$filename}: file larger than 2MB.";

                continue;
            }

            $realPath = $file->getRealPath();
            if (! is_string($realPath) || $realPath === '' || ! is_file($realPath)) {
                $warnings[] = "Skipped {$filename}: file not readable.";

                continue;
            }

            $rawBytes = file_get_contents($realPath);
            if (! is_string($rawBytes) || $rawBytes === '') {
                $warnings[] = "Skipped {$filename}: file not readable.";

                continue;
            }

            $dataUrls[] = 'data:'.$mimeType.';base64,'.base64_encode($rawBytes);
        }

        return [
            'image_data_urls' => $dataUrls,
            'image_count' => count($dataUrls),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function buildContext(Unit $unit, array $context): array
    {
        return [
            'name' => (string) ($context['name'] ?? $unit->name),
            'category' => (string) ($context['category'] ?? $unit->category?->name ?? 'not specified'),
            'location' => (string) ($context['location'] ?? $unit->location ?? 'not specified'),
            'address_text' => (string) ($context['address_text'] ?? $unit->address_text ?? 'not specified'),
            'price_display_mode' => (string) ($context['price_display_mode'] ?? $unit->price_display_mode ?? 'not specified'),
            'nightly_price_php' => $context['nightly_price_php'] ?? $unit->nightly_price_php,
            'monthly_price_php' => $context['monthly_price_php'] ?? $unit->monthly_price_php,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function buildContextFromArray(array $context): array
    {
        return [
            'name' => (string) ($context['name'] ?? 'not specified'),
            'category' => (string) ($context['category'] ?? 'not specified'),
            'location' => (string) ($context['location'] ?? 'not specified'),
            'address_text' => (string) ($context['address_text'] ?? 'not specified'),
            'price_display_mode' => (string) ($context['price_display_mode'] ?? 'not specified'),
            'nightly_price_php' => $context['nightly_price_php'] ?? null,
            'monthly_price_php' => $context['monthly_price_php'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildUserPrompt(array $context, string $tone, string $length): string
    {
        $toneInstructions = match ($tone) {
            'Luxury' => 'Use polished, premium wording without exaggeration.',
            'Friendly' => 'Use warm, conversational wording that remains factual.',
            default => 'Use professional and neutral wording.',
        };

        $lengthInstructions = match ($length) {
            'Short' => 'Keep full_description around 70-110 words.',
            'Long' => 'Keep full_description around 200-280 words.',
            default => 'Keep full_description around 120-180 words.',
        };

        $nightlyPrice = is_numeric($context['nightly_price_php'] ?? null)
            ? (string) $context['nightly_price_php']
            : 'not specified';
        $monthlyPrice = is_numeric($context['monthly_price_php'] ?? null)
            ? (string) $context['monthly_price_php']
            : 'not specified';

        return implode("\n", [
            'Generate a condo unit listing description in strict JSON format.',
            'Guardrails:',
            '- Do not invent amenities not visible in images or provided context.',
            '- If a detail is unknown, use "not specified".',
            '- Do not include PII or personal references.',
            '',
            'Context:',
            '- Name: '.$context['name'],
            '- Category: '.$context['category'],
            '- Location: '.$context['location'],
            '- Address text: '.$context['address_text'],
            '- Price display mode: '.$context['price_display_mode'],
            '- Nightly price PHP: '.$nightlyPrice,
            '- Monthly price PHP: '.$monthlyPrice,
            '- Tone: '.$tone,
            '- Tone instruction: '.$toneInstructions,
            '- Length instruction: '.$lengthInstructions,
            '',
            'Output schema keys:',
            '- tagline (string)',
            '- highlights (array of concise strings)',
            '- full_description (string)',
            '- detected_visual_features (array of strings based only on visible details)',
            '- warnings (array of strings for uncertainty or missing context)',
        ]);
    }

    /**
     * @param  array{
     *     tagline:string,
     *     highlights:array<int,string>,
     *     full_description:string,
     *     detected_visual_features:array<int,string>,
     *     warnings:array<int,string>
     * } $data
     */
    private function formatDraft(array $data): string
    {
        $lines = [
            trim($data['tagline']),
            '',
            'Highlights:',
        ];

        foreach ($data['highlights'] as $highlight) {
            $lines[] = '- '.trim($highlight);
        }

        $lines[] = '';
        $lines[] = trim($data['full_description']);

        return trim(implode(PHP_EOL, $lines));
    }
}
