<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    /** Las páginas del sitio: las del menú, la home, etc. */
    public const KIND_PAGE = 'page';

    /** La plantilla genérica de landing de evento (hay una sola). */
    public const KIND_EVENT_TEMPLATE = 'event_template';

    /** La landing de un evento, copiada de la plantilla. */
    public const KIND_EVENT_LANDING = 'event_landing';

    public const EVENT_TEMPLATE_SLUG = 'plantilla-evento';

    protected $fillable = [
        'slug',
        'kind',
        'event_id',
        'title',
        'menu_label',
        'menu_order',
        'visible',
        'meta_description',
    ];

    protected $attributes = [
        'kind' => self::KIND_PAGE,
    ];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'menu_order' => 'integer',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isRegular(): bool
    {
        return $this->kind === self::KIND_PAGE;
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_PAGE);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visible', true);
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->regular()->visible()->whereNotNull('menu_label')->orderBy('menu_order');
    }

    /** Dónde se editan sus secciones en el panel. */
    public function adminUrl(): string
    {
        return $this->kind === self::KIND_EVENT_TEMPLATE
            ? '/admin/landing-eventos/plantilla'
            : '/admin/pages/'.$this->id;
    }

    /**
     * Las migas de pan del panel hasta esta página: las del sitio cuelgan de
     * "Páginas"; la plantilla y las landings, de "Eventos".
     *
     * @return array<int, array{title: string, href: string}>
     */
    public function adminBreadcrumbs(): array
    {
        return match ($this->kind) {
            self::KIND_EVENT_TEMPLATE => [
                ['title' => 'Eventos', 'href' => '/admin/events'],
                ['title' => 'Plantilla de landing', 'href' => $this->adminUrl()],
            ],
            self::KIND_EVENT_LANDING => [
                ['title' => 'Eventos', 'href' => '/admin/events'],
                ['title' => 'Landing: '.$this->title, 'href' => $this->adminUrl()],
            ],
            default => [
                ['title' => 'Páginas', 'href' => '/admin/pages'],
                ['title' => $this->title, 'href' => $this->adminUrl()],
            ],
        };
    }

    /** La dirección pública: la home en la raíz, las landings bajo /eventos-especiales. */
    public function publicPath(): string
    {
        return match (true) {
            $this->kind === self::KIND_EVENT_LANDING => '/eventos-especiales/'.$this->slug,
            $this->kind === self::KIND_EVENT_TEMPLATE => '/admin/landing-eventos/plantilla/vista-previa',
            $this->slug === 'home' => '/',
            default => '/'.$this->slug,
        };
    }
}
