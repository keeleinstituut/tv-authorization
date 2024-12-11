<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Exceptions\OnlyUserUnderRootRoleException;
use App\Models\InstitutionUser;
use NotificationClient\DataTransferObjects\EmailNotificationMessage;
use NotificationClient\Enums\NotificationType;
use NotificationClient\Services\NotificationPublisher;
use Throwable;

readonly class InstitutionUserObserver
{
    public function __construct(
        private InstitutionUserEventsPublisher $syncPublisher,
        private NotificationPublisher $notificationPublisher
    ) {
    }

    /**
     * Handle the InstitutionUser "updating" event.
     *
     * @throws OnlyUserUnderRootRoleException
     */
    public function updating(InstitutionUser $institutionUser): void
    {
        if ($institutionUser->isDirty(['archived_at', 'deactivation_date'])) {
            if ($institutionUser->isOnlyUserWithRootRole()) {
                throw new OnlyUserUnderRootRoleException();
            }
        }
    }

    /**
     * Handle the InstitutionUser "saved" event.
     */
    public function saved(InstitutionUser $institutionUser): void
    {
        $this->syncPublisher->publishSyncEvent($institutionUser->id);
    }

    /**
     * @throws Throwable
     */
    public function created(InstitutionUser $institutionUser): void
    {
        $this->notificationPublisher->publishEmailNotification(
            EmailNotificationMessage::make([
                'notification_type' => NotificationType::InstitutionUserCreated,
                'receiver_email' => $institutionUser->email,
                'receiver_name' => $institutionUser->user->full_name,
            ]),
            institutionId: $institutionUser->institution_id,
        );
    }

    /**
     * Handle the InstitutionUser "deleting" event.
     *
     * @throws OnlyUserUnderRootRoleException
     */
    public function deleting(InstitutionUser $institutionUser): void
    {
        if ($institutionUser->isOnlyUserWithRootRole()) {
            throw new OnlyUserUnderRootRoleException();
        }
    }

    /**
     * Handle the InstitutionUser "deleted" event.
     */
    public function deleted(InstitutionUser $institutionUser): void
    {
        $this->syncPublisher->publishSyncEvent($institutionUser->id);
    }

    /**
     * Handle the InstitutionUser "restored" event.
     */
    public function restored(InstitutionUser $institutionUser): void
    {
    }

    /**
     * Handle the InstitutionUser "force deleted" event.
     */
    public function forceDeleted(InstitutionUser $institutionUser): void
    {
        $this->syncPublisher->publishDeleteEvent($institutionUser->id);
    }
}
