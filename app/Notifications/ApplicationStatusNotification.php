<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Support\Application\ApplicationStatusPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $applicationId,
        private readonly string $applicationCode,
        private readonly string $event,
        private readonly string $title,
        private readonly string $message,
        private readonly ?ApplicationStatus $status,
        private readonly string $url,
    ) {}

    public static function submitted(Application $application): self
    {
        return new self(
            applicationId: $application->getKey(),
            applicationCode: $application->application_code,
            event: 'application.submitted',
            title: 'Đã nộp hồ sơ',
            message: "Hồ sơ {$application->application_code} đã được ghi nhận.",
            status: ApplicationStatus::Received,
            url: self::applicationUrl($application),
        );
    }

    public static function statusChanged(Application $application, ApplicationStatus $status): self
    {
        [$event, $title, $message] = match ($status) {
            ApplicationStatus::Received => [
                'application.received',
                'Đã tiếp nhận hồ sơ',
                "Hồ sơ {$application->application_code} đã được tiếp nhận.",
            ],
            ApplicationStatus::Processing => [
                'application.processing',
                'Hồ sơ đang xử lý',
                "Hồ sơ {$application->application_code} đang được cán bộ xử lý.",
            ],
            ApplicationStatus::SupplementRequired => [
                'application.supplement_requested',
                'Cần bổ sung hồ sơ',
                "Hồ sơ {$application->application_code} cần bổ sung thông tin hoặc tài liệu.",
            ],
            ApplicationStatus::Approved => [
                'application.approved',
                'Hồ sơ đã được duyệt',
                "Hồ sơ {$application->application_code} đã được duyệt.",
            ],
            ApplicationStatus::Rejected => [
                'application.rejected',
                'Hồ sơ bị từ chối',
                "Hồ sơ {$application->application_code} đã bị từ chối.",
            ],
        };

        return new self(
            applicationId: $application->getKey(),
            applicationCode: $application->application_code,
            event: $event,
            title: $title,
            message: $message,
            status: $status,
            url: self::applicationUrl($application),
        );
    }

    public static function resultDocumentAvailable(Application $application, ApplicationDocument $document): self
    {
        return new self(
            applicationId: $application->getKey(),
            applicationCode: $application->application_code,
            event: 'application.result_document_available',
            title: 'Có tài liệu kết quả',
            message: "Hồ sơ {$application->application_code} đã có tài liệu kết quả.",
            status: $application->status,
            url: self::applicationUrl($application),
        );
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->shouldSendMail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Xin chào,')
            ->line($this->message)
            ->line("Mã hồ sơ: {$this->applicationCode}")
            ->when($this->status !== null, fn (MailMessage $mail): MailMessage => $mail
                ->line('Trạng thái: '.ApplicationStatusPresenter::label($this->status)))
            ->action('Xem hồ sơ', url($this->url))
            ->line('Cảm ơn bạn đã sử dụng cổng dịch vụ công.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->applicationId,
            'application_code' => $this->applicationCode,
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status?->value,
            'status_label' => $this->status === null ? null : ApplicationStatusPresenter::label($this->status),
            'url' => $this->url,
            'occurred_at' => now()->toISOString(),
        ];
    }

    private static function applicationUrl(Application $application): string
    {
        return "/applications/{$application->getKey()}";
    }

    private function shouldSendMail(object $notifiable): bool
    {
        if (! (bool) ($notifiable->email_notifications_enabled ?? false)) {
            return false;
        }

        return in_array($this->event, [
            'application.supplement_requested',
            'application.approved',
            'application.rejected',
        ], true);
    }
}
