<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    /** Los estados por los que pasa, en orden. `failed` es la salida de emergencia. */
    public const DRAFT = 'draft';

    public const SCHEDULED = 'scheduled';

    public const SENDING = 'sending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    protected $fillable = [
        'type',
        'subject',
        'preview_text',
        'content',
        'html',
        'plain_text',
        'status',
        'scheduled_at',
        'sent_at',
        'mailchimp_campaign_id',
        'recipient_count',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'recipient_count' => 'integer',
        ];
    }

    /**
     * Los items elegidos, tal como los guardó el panel: [{kind, id}, ...].
     *
     * @return array<int, array{kind: string, id: int}>
     */
    public function items(): array
    {
        return $this->content['items'] ?? [];
    }

    /** Ya salió: no se toca más, ni se reenvía, ni se edita. */
    public function isSent(): bool
    {
        return $this->status === self::SENT;
    }

    /** Todavía se puede editar (el HTML se congela recién al programar). */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::DRAFT, self::SCHEDULED, self::FAILED], true);
    }

    /**
     * Los que les llegó la hora.
     *
     * `failed` queda afuera a propósito: un envío que falló lo reintenta una persona
     * desde el panel, después de mirar qué pasó. Que el cron lo repita solo cada
     * cinco minutos contra la cuenta real no le sirve a nadie.
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', self::SCHEDULED)->where('scheduled_at', '<=', now());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        // Los programados y enviados por fecha; los borradores, que no tienen ninguna,
        // arriba de todo, que es donde se los sigue trabajando.
        return $query->orderByRaw('COALESCE(sent_at, scheduled_at) IS NULL DESC')
            ->orderByRaw('COALESCE(sent_at, scheduled_at) DESC')
            ->orderByDesc('id');
    }
}
