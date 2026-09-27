<?php

namespace App\Console\Commands;

use App\Exceptions\MailchimpException;
use App\Support\Mailchimp;
use Illuminate\Console\Command;

/**
 * El diagnóstico de la conexión con Mailchimp: ¿anda la key, qué plan es la cuenta,
 * cuánta gente hay y cuál es el id de cada audiencia?
 *
 * Sirve para dos momentos. Al configurar, porque el id de la audiencia hay que
 * elegirlo en Ajustes y conviene verlo antes. Y cuando algo falla, porque separa "la
 * key está mal" de "el newsletter está mal armado" sin tener que adivinar.
 */
class MailchimpPing extends Command
{
    protected $signature = 'mailchimp:ping';

    protected $description = 'Verifica la conexión con Mailchimp y muestra la cuenta y sus audiencias';

    public function handle(): int
    {
        if (! Mailchimp::configured()) {
            $this->components->error('Falta MAILCHIMP_API_KEY en el .env (o la key no termina con su centro de datos, algo como "-us21").');

            return self::FAILURE;
        }

        try {
            $account = Mailchimp::account();
            $lists = Mailchimp::lists();
        } catch (MailchimpException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Conectado a Mailchimp.');

        $this->components->twoColumnDetail('Cuenta', (string) ($account['account_name'] ?? '—'));
        $this->components->twoColumnDetail('Email', (string) ($account['email'] ?? '—'));
        $this->components->twoColumnDetail('Plan', (string) ($account['pricing_plan_type'] ?? '—'));
        $this->components->twoColumnDetail('Suscriptores', (string) ($account['total_subscribers'] ?? '—'));

        if ($lists === []) {
            $this->newLine();
            $this->components->warn('La cuenta no tiene ninguna audiencia todavía.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line(' <fg=gray>Audiencias (el id va en Ajustes del sitio → Newsletter):</>');

        foreach ($lists as $list) {
            $this->components->twoColumnDetail(
                (string) ($list['name'] ?? '—').' <fg=gray>'.($list['id'] ?? '').'</>',
                ($list['stats']['member_count'] ?? 0).' suscriptores',
            );
        }

        // El recordatorio que evita la confusión de siempre: automatizar el armado no
        // cambia cuántos mails entran en el plan.
        $this->newLine();
        $this->line(' <fg=gray>Recordá que la API gasta la misma cuota de envíos que el editor de Mailchimp.</>');

        return self::SUCCESS;
    }
}
