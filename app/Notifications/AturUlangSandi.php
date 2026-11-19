<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email "lupa kata sandi" berbahasa Indonesia (bawaan Laravel berbahasa Inggris).
 * Sengaja tidak memakai antrean: QUEUE_CONNECTION=database di hosting tanpa pekerja antrean
 * akan membuat email tidak pernah terkirim.
 */
class AturUlangSandi extends Notification
{
    public function __construct(private readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $menit = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
        $tautan = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Buat kata sandi baru · Admin '.config('app.name'))
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Ada permintaan membuat kata sandi baru untuk akun admin Anda.')
            ->action('Buat kata sandi baru', $tautan)
            ->line("Tautan ini berlaku {$menit} menit dan hanya bisa dipakai sekali.")
            ->line('Bukan Anda yang meminta? Abaikan email ini. Kata sandi lama tetap berlaku.')
            ->salutation('Salam, '.config('app.name'));
    }
}
