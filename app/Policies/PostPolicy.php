<?php

namespace App\Policies;

use App\Enums\PostStatus;
use App\Enums\Role;
use App\Models\Post;
use App\Models\User;

/**
 * Pemisahan fungsi konten (spesifikasi §8 dan §21):
 * - Editor menulis dan mengajukan, tidak bisa menyetujui atau menerbitkan.
 * - Peninjau menyetujui, meminta revisi, menerbitkan; tidak boleh menyetujui tulisannya sendiri.
 * - Admin mengelola semuanya, tetapi tetap tidak menyetujui tulisannya sendiri.
 */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Post $post): bool
    {
        if ($user->role === Role::Admin->value) {
            return true;
        }
        if ($user->role === Role::Peninjau->value) {
            return $post->status !== PostStatus::Terbit || $post->author_id === $user->id;
        }

        // Editor: hanya tulisannya sendiri yang masih draf atau perlu revisi.
        return $post->author_id === $user->id
            && in_array($post->status, [PostStatus::Draf, PostStatus::PerluRevisi], true);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->role === Role::Admin->value
            || ($post->author_id === $user->id && $post->status === PostStatus::Draf);
    }

    public function submit(User $user, Post $post): bool
    {
        return $post->author_id === $user->id
            && in_array($post->status, [PostStatus::Draf, PostStatus::PerluRevisi], true);
    }

    public function review(User $user, Post $post): bool
    {
        return in_array($user->role, [Role::Peninjau->value, Role::Admin->value], true)
            && $post->author_id !== $user->id
            && $post->status === PostStatus::Ditinjau;
    }

    public function publish(User $user, Post $post): bool
    {
        return in_array($user->role, [Role::Peninjau->value, Role::Admin->value], true)
            && $post->author_id !== $user->id
            && in_array($post->status, [PostStatus::Ditinjau, PostStatus::Disetujui], true);
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->role === Role::Admin->value;
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return false;
    }
}
