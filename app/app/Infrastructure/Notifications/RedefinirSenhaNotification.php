<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * E-mail de recuperação de senha em pt-BR (driver "log" em dev, Amazon SES em produção).
 */
final class RedefinirSenhaNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $minutos = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Redefinição de senha — Diário Escolar Municipal')
            ->greeting('Olá!')
            ->line('Recebemos um pedido para redefinir a senha da sua conta no Diário Escolar Municipal.')
            ->action('Redefinir senha', $this->resetUrl($notifiable))
            ->line("Este link expira em {$minutos} minutos.")
            ->line('Se você não fez este pedido, ignore este e-mail: sua senha continua a mesma.')
            ->salutation('Secretaria Municipal de Educação');
    }
}
