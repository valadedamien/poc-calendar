<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Events;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google_Service_Calendar;

class EventsService
{
    /**
     * CouleurId -> correspond au tableau de couleur renvoyer par l'api
     * ref : https://developers.google.com/calendar/api/v3/reference/colors/get.
     */
    public const COLOR_ID = [
        'blue ' => '1',
        'green' => '2',
        'purple' => '3',
        'light_red' => '4',
        'yellow' => '5',
        'orange' => '6',
        'light_blue' => '7',
        'grey' => '8',
        'blue_purple' => '9',
        'dark_green' => '10',
        'red' => '11',
    ];

    private string $calendarId;
    private EntityManagerInterface $em;
    private Google_Service_Calendar $service;

    public function __construct(ClientService $clientService, EntityManagerInterface $em, string $calendarId)
    {
        $this->em = $em;
        $this->calendarId = $calendarId;
        $this->service = new Google_Service_Calendar($clientService->getClient());
    }

    /**
     * Insertion de l'event avec l'api google.
     */
    public function insertEvent(string $dateStart, string $dateEnd, string $summary): Event
    {
        $events = new Event();

        $events->setStart($this->getFormattedDate($dateStart));
        $events->setEnd($this->getFormattedDate($dateEnd));
        $events->setSummary($summary);
        $events->setColorId(self::COLOR_ID['orange']);

        $result = $this->service->events->insert($this->calendarId, $events);

        $this->saveDatabaseEvent($result);

        return $result;
    }

    /**
     * Suppression de l'event avec l'api de google.
     */
    public function deleteEvent(string $idEvent): void
    {
        $this->service->events->delete($this->calendarId, $idEvent);

        $this->deleteDatabaseEvent($idEvent);
    }

    /**
     * Update event avec l'api google.
     */
    public function updateEvent(string $idEvent, string $colorId): void
    {
        $event = $this->service->events->get($this->calendarId, $idEvent);

        $event->setColorId($colorId);

        $this->service->events->update($this->calendarId, $idEvent, $event);
    }

    /**
     * Transfert date -> eventdatetime.
     */
    public function getFormattedDate(string $date): EventDateTime
    {
        $eventDate = new EventDateTime();
        $eventDate->setDate($date);

        return $eventDate;
    }

    /**
     * Sauvegarde de l'event en BDD.
     */
    public function saveDatabaseEvent(Event $event): void
    {
        $saveEvent = new Events();

        $saveEvent->setSummary($event->getSummary());
        $saveEvent->setEventId($event->getId());

        $this->em->persist($saveEvent);
        $this->em->flush();
    }

    /**
     * Suppression de l'event en BDD.
     */
    public function deleteDatabaseEvent(string $eventId): void
    {
        $event = $this->em->getRepository(Events::class)->findOneBy(['EventId' => $eventId]);

        $this->em->remove($event);
        $this->em->flush();
    }
}
