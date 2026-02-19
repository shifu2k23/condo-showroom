<?php

namespace App\Livewire\Admin\AuditLogs;

use App\Models\AuditLog;
use App\Models\Unit;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app.sidebar')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'action', history: true)]
    public string $actionFilter = '';

    #[Url(as: 'unit', history: true)]
    public string $unitFilter = '';

    #[Url(as: 'user', history: true)]
    public string $userFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->currentId();

        $query = AuditLog::query()
            ->with(['unit', 'user'])
            ->when($this->search !== '', function ($builder): void {
                $builder->where(function ($inner): void {
                    $inner->where('action', 'like', '%'.$this->search.'%')
                        ->orWhere('ip_address', 'like', '%'.$this->search.'%')
                        ->orWhere('user_agent', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->actionFilter !== '', fn ($builder) => $builder->where('action', $this->actionFilter))
            ->when($this->unitFilter !== '', fn ($builder) => $builder->where('unit_id', $this->unitFilter))
            ->when($this->userFilter !== '', fn ($builder) => $builder->where('user_id', $this->userFilter))
            ->latest();

        return view('livewire.admin.audit-logs.index', [
            'logs' => $query->paginate(20),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()
                ->where('tenant_id', $tenantId)
                ->where('is_super_admin', false)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
