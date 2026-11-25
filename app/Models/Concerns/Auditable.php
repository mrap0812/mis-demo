<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/** Mencatat siapa mengubah apa dan kapan, tanpa menyimpan kata sandi atau token. */
trait Auditable
{
    protected static array $auditIgnore = ['password', 'remember_token', 'updated_at', 'created_at'];

    public static function bootAuditable(): void
    {
        static::created(fn ($m) => $m->writeAudit('created', $m->auditPayload($m->getAttributes(), null)));
        static::updated(function ($m) {
            $changes = [];
            foreach ($m->getChanges() as $k => $new) {
                if (in_array($k, static::$auditIgnore, true)) {
                    continue;
                }
                $changes[$k] = [$m->getRawOriginal($k), $new];
            }
            if ($changes) {
                $m->writeAudit('updated', $changes);
            }
        });
        static::deleted(fn ($m) => $m->writeAudit('deleted', null));
        if (method_exists(static::class, 'restored')) {
            static::restored(fn ($m) => $m->writeAudit('restored', null));
        }
    }

    protected function auditPayload(array $attrs, $unused): array
    {
        return collect($attrs)->except(static::$auditIgnore)
            ->map(fn ($v) => is_string($v) && mb_strlen($v) > 300 ? mb_substr($v, 0, 300).'…' : $v)
            ->map(fn ($v) => [null, $v])->all();
    }

    protected function writeAudit(string $action, ?array $changes): void
    {
        if ($changes) {
            $changes = collect($changes)->map(fn ($pair) => array_map(
                fn ($v) => is_string($v) && mb_strlen($v) > 300 ? mb_substr($v, 0, 300).'…' : $v, $pair))->all();
        }
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => class_basename($this),
            'auditable_id' => $this->getKey(),
            'changes' => $changes,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
