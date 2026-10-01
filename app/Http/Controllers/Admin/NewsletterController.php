<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MailchimpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsletterRequest;
use App\Models\Newsletter;
use App\Models\Setting;
use App\Support\EventCalendar;
use App\Support\NewsletterItems;
use App\Support\NewsletterRenderer;
use App\Support\NewsletterSender;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El newsletter desde el panel: armarlo, verlo, probarlo, programarlo y mandarlo.
 *
 * Las fechas son el punto delicado. La app corre en UTC (config/app.php) y el
 * <input type="datetime-local"> del navegador escribe hora argentina sin decir de qué
 * zona es. Sin convertir, un envío pedido para las 9:00 saldría a las 6:00. Acá entra
 * y sale todo por EventCalendar::TIMEZONE, y en la base se guarda UTC como siempre.
 */
class NewsletterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Newsletters/Index', [
            'newsletters' => Newsletter::ordered()->get()->map(fn (Newsletter $n) => $this->row($n))->all(),
            // Cuánto se lleva gastado del mes, para no chocarse con el tope de la
            // cuenta sin enterarse. El mes es el de acá, no el de UTC.
            'sent_this_month' => (int) Newsletter::query()
                ->where('status', Newsletter::SENT)
                ->where('sent_at', '>=', CarbonImmutable::now(EventCalendar::TIMEZONE)->startOfMonth()->utc())
                ->sum('recipient_count'),
            'missing' => NewsletterSender::missing(),
            'blocked' => NewsletterSender::blockedReason(),
            'mode' => NewsletterSender::mode(),
        ]);
    }

    public function store(NewsletterRequest $request): RedirectResponse
    {
        $newsletter = Newsletter::create([
            'type' => $request->validated('type'),
            'subject' => $request->validated('subject'),
            'preview_text' => $request->validated('preview_text'),
            'content' => ['items' => $request->items()],
            'status' => Newsletter::DRAFT,
        ]);

        return redirect()
            ->route('admin.newsletters.edit', $newsletter)
            ->with('success', 'Newsletter creado.');
    }

    public function edit(Newsletter $newsletter): Response
    {
        return Inertia::render('Admin/Newsletters/Edit', [
            'newsletter' => [
                ...$this->row($newsletter),
                'preview_text' => $newsletter->preview_text,
                'items' => NewsletterItems::describe($newsletter->items()),
            ],
            'pool' => NewsletterItems::pool(),
            'missing' => NewsletterSender::missing(),
            'blocked' => NewsletterSender::blockedReason(),
            'mode' => NewsletterSender::mode(),
            'test_email' => Setting::get('newsletter_test_email'),
        ]);
    }

    public function update(NewsletterRequest $request, Newsletter $newsletter): RedirectResponse
    {
        $this->abortIfLocked($newsletter);

        $newsletter->update([
            'type' => $request->validated('type'),
            'subject' => $request->validated('subject'),
            'preview_text' => $request->validated('preview_text'),
            'content' => ['items' => $request->items()],
        ]);

        // Si ya estaba programado, lo congelado quedó viejo: se vuelve a congelar con
        // lo que se acaba de guardar. Editar y que salga la versión anterior sería
        // lo peor de los dos mundos.
        if ($newsletter->status === Newsletter::SCHEDULED) {
            NewsletterSender::freeze($newsletter);

            return back()->with('success', 'Guardado. Sigue programado para el '.$this->localLabel($newsletter->scheduled_at).'.');
        }

        return back()->with('success', 'Newsletter guardado.');
    }

    public function destroy(Newsletter $newsletter): RedirectResponse
    {
        $this->abortIfLocked($newsletter);

        $newsletter->delete();

        return redirect()
            ->route('admin.newsletters.index')
            ->with('success', 'Newsletter eliminado.');
    }

    /**
     * El mail tal cual va a salir, para abrir en una pestaña.
     *
     * Uno ya enviado muestra lo que REALMENTE se mandó (el HTML congelado), no lo que
     * se vería hoy: es el archivo de lo que la gente recibió.
     */
    public function preview(Newsletter $newsletter): HttpResponse
    {
        $html = $newsletter->isSent() && filled($newsletter->html)
            ? $newsletter->html
            : NewsletterRenderer::html($newsletter);

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function duplicate(Newsletter $newsletter): RedirectResponse
    {
        $copy = Newsletter::create([
            'type' => $newsletter->type,
            'subject' => $newsletter->subject,
            'preview_text' => $newsletter->preview_text,
            'content' => $newsletter->content,
            'status' => Newsletter::DRAFT,
        ]);

        return redirect()
            ->route('admin.newsletters.edit', $copy)
            ->with('success', 'Copia creada como borrador.');
    }

    public function sendTest(Request $request, Newsletter $newsletter): RedirectResponse
    {
        $email = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ])['email'];

        if ($missing = NewsletterSender::missing()) {
            return back()->with('error', $missing[0]);
        }

        // Se recuerda para la próxima: es siempre la misma casilla.
        Setting::set('newsletter_test_email', $email);

        try {
            NewsletterSender::sendTest($newsletter, [$email]);
        } catch (MailchimpException|ConnectionException $e) {
            return back()->with('error', 'Mailchimp no pudo mandar la prueba: '.$e->getMessage());
        }

        return back()->with('success', "Prueba enviada a {$email}.");
    }

    public function schedule(Request $request, Newsletter $newsletter): RedirectResponse
    {
        $this->abortIfLocked($newsletter);

        $when = $this->parseLocal($request->validate([
            'scheduled_at' => ['required', 'date'],
        ])['scheduled_at']);

        if ($when->isPast()) {
            return back()->withErrors(['scheduled_at' => 'Esa fecha ya pasó. Elegí un momento futuro.']);
        }

        if ($newsletter->items() === []) {
            return back()->withErrors(['items' => 'Elegí al menos una clase o un evento antes de programar.']);
        }

        if ($missing = NewsletterSender::missing()) {
            return back()->with('error', $missing[0]);
        }

        // Programar en la máquina de desarrollo dejaría un envío real esperando en una
        // base que es una copia: mejor no dejar ni armarlo.
        if ($blocked = NewsletterSender::blockedReason()) {
            return back()->with('error', $blocked);
        }

        // Congelar acá y no en el cron: url() necesita el host real del pedido, y el
        // contenido tiene que quedar fijo desde el momento en que se decide mandarlo.
        NewsletterSender::freeze($newsletter);

        $newsletter->update([
            'status' => Newsletter::SCHEDULED,
            'scheduled_at' => $when->utc(),
            'error' => null,
        ]);

        return back()->with('success', 'Programado para el '.$this->localLabel($newsletter->scheduled_at).'.');
    }

    public function unschedule(Newsletter $newsletter): RedirectResponse
    {
        $this->abortIfLocked($newsletter);

        $newsletter->update([
            'status' => Newsletter::DRAFT,
            'scheduled_at' => null,
        ]);

        return back()->with('success', 'Se canceló la programación. Vuelve a ser un borrador.');
    }

    public function sendNow(Newsletter $newsletter): RedirectResponse
    {
        $this->abortIfLocked($newsletter);

        if ($newsletter->items() === []) {
            return back()->withErrors(['items' => 'Elegí al menos una clase o un evento antes de enviar.']);
        }

        if ($missing = NewsletterSender::missing()) {
            return back()->with('error', $missing[0]);
        }

        if ($blocked = NewsletterSender::blockedReason()) {
            return back()->with('error', $blocked);
        }

        NewsletterSender::freeze($newsletter);

        // Pasa por el mismo camino que el cron —programado para ahora— en vez de tener
        // su propia rutina de envío: la protección contra mandar dos veces vive ahí.
        $newsletter->update([
            'status' => Newsletter::SCHEDULED,
            'scheduled_at' => now(),
            'error' => null,
        ]);

        try {
            NewsletterSender::send($newsletter);
        } catch (MailchimpException|ConnectionException $e) {
            // Quedó marcado como fallido con el motivo; que no se pueda hablar con
            // Mailchimp no tiene que terminar en una pantalla de error del servidor.
            return back()->with('error', 'No se pudo enviar: '.$e->getMessage());
        }

        $newsletter->refresh();

        return back()->with('success', "Enviado a {$newsletter->recipient_count} suscriptores.");
    }

    /**
     * Un newsletter que ya salió no se toca más, y uno que está saliendo justo ahora
     * tampoco: cambiarle algo a mitad de envío no arregla nada y puede romper el
     * rastro que permite saber si salió.
     */
    private function abortIfLocked(Newsletter $newsletter): void
    {
        abort_if($newsletter->status === Newsletter::SENDING, 409, 'Este newsletter se está enviando en este momento.');
        abort_if($newsletter->isSent(), 403, 'Este newsletter ya se envió.');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Newsletter $newsletter): array
    {
        return [
            'id' => $newsletter->id,
            'type' => $newsletter->type,
            'subject' => $newsletter->subject,
            'status' => $newsletter->status,
            'items_count' => count($newsletter->items()),
            'recipient_count' => $newsletter->recipient_count,
            'error' => $newsletter->error,
            'editable' => $newsletter->isEditable(),
            // Le pasó la hora y sigue ahí: o el modo es manual y está esperando que lo
            // manden, o el cron no está corriendo. En los dos casos hay que verlo.
            'waiting' => $newsletter->status === Newsletter::SCHEDULED
                && $newsletter->scheduled_at?->isPast(),
            // Para mostrar, ya en hora argentina.
            'scheduled_label' => $this->localLabel($newsletter->scheduled_at),
            'sent_label' => $this->localLabel($newsletter->sent_at),
            // Para el <input type="datetime-local">, que quiere este formato exacto.
            'scheduled_input' => $newsletter->scheduled_at
                ?->setTimezone(EventCalendar::TIMEZONE)
                ->format('Y-m-d\TH:i'),
            'preview_url' => route('admin.newsletters.preview', $newsletter),
        ];
    }

    /** El texto que manda el navegador es hora de acá; en la base va UTC. */
    private function parseLocal(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, EventCalendar::TIMEZONE);
    }

    private function localLabel(mixed $date): ?string
    {
        return $date?->setTimezone(EventCalendar::TIMEZONE)->format('d/m/Y H:i');
    }
}
