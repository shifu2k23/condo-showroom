<?php

test('admin livewire blade files are saved without utf8 bom', function () {
    $files = [
        resource_path('views/livewire/admin/dashboard.blade.php'),
        resource_path('views/livewire/admin/units/index.blade.php'),
        resource_path('views/livewire/admin/categories/index.blade.php'),
        resource_path('views/livewire/admin/viewing-requests/index.blade.php'),
        resource_path('views/livewire/admin/rentals/index.blade.php'),
        resource_path('views/livewire/admin/audit-logs/index.blade.php'),
        resource_path('views/livewire/admin/units/form.blade.php'),
        resource_path('views/livewire/admin/rentals/form.blade.php'),
    ];

    foreach ($files as $file) {
        $content = file_get_contents($file);
        expect($content)->not->toStartWith("\xEF\xBB\xBF");
    }
});

