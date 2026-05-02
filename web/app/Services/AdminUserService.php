<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function create(array $payload): User
    {
        $user = User::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'password' => $payload['password'],
            'is_active' => $payload['is_active'] ?? true,
        ]);

        if (! empty($payload['role'])) {
            $user->syncRoles([$payload['role']]);
        }

        $this->activityLogger->log(
            'users',
            'created',
            null,
            User::class,
            (int) $user->id,
            [
                'message' => sprintf('User %s dibuat.', (string) $user->name),
                'label' => (string) $user->email,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => (string) ($payload['role'] ?? ''),
                'is_active' => (bool) $user->is_active,
            ],
        );

        return $user->refresh();
    }

    public function update(User $user, array $payload, ?User $actor = null): User
    {
        $currentRole = strtolower((string) ($user->roles->first()?->name ?? ''));
        $nextRole = array_key_exists('role', $payload)
            ? strtolower(trim((string) ($payload['role'] ?? '')))
            : $currentRole;
        $nextIsActive = array_key_exists('is_active', $payload)
            ? (bool) $payload['is_active']
            : (bool) $user->is_active;

        $this->ensureOwnerSafety($user, $currentRole, $nextRole, $nextIsActive);
        $this->ensureActorSafety($user, $actor, $nextIsActive);

        $user->update([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'is_active' => $nextIsActive,
            ...(! empty($payload['password']) ? ['password' => $payload['password']] : []),
        ]);

        if (array_key_exists('role', $payload)) {
            if ($nextRole === '') {
                $user->syncRoles([]);
            } else {
                $user->syncRoles([$payload['role']]);
            }
        }

        $this->activityLogger->log(
            'users',
            'updated',
            $actor?->id,
            User::class,
            (int) $user->id,
            [
                'message' => sprintf('User %s diperbarui.', (string) $user->name),
                'label' => (string) $user->email,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => $nextRole,
                'is_active' => (bool) $user->is_active,
                'updated_fields' => array_keys($payload),
            ],
        );

        return $user->refresh();
    }

    public function delete(User $user, ?User $actor = null): void
    {
        if ($actor && (int) $actor->id === (int) $user->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if ($user->hasRole('owner') && User::role('owner')->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'At least one owner account must remain.',
            ]);
        }

        $this->activityLogger->log(
            'users',
            'deleted',
            $actor?->id,
            User::class,
            (int) $user->id,
            [
                'message' => sprintf('User %s dihapus.', (string) $user->name),
                'label' => (string) $user->email,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
            ],
        );

        $user->delete();
    }

    private function ensureOwnerSafety(User $user, string $currentRole, string $nextRole, bool $nextIsActive): void
    {
        if ($currentRole !== 'owner') {
            return;
        }

        $ownersCount = User::role('owner')->count();
        $ownerWillBeRemoved = $nextRole !== 'owner' || ! $nextIsActive;

        if ($ownerWillBeRemoved && $ownersCount <= 1) {
            throw ValidationException::withMessages([
                'role' => 'At least one active owner account must remain.',
            ]);
        }
    }

    private function ensureActorSafety(User $user, ?User $actor, bool $nextIsActive): void
    {
        if (! $actor) {
            return;
        }

        if ((int) $actor->id === (int) $user->id && ! $nextIsActive) {
            throw ValidationException::withMessages([
                'is_active' => 'You cannot deactivate your own account.',
            ]);
        }
    }
}
