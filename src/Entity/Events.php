<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventsRepository::class)]
class Events
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $EventId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $summary;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventId(): ?string
    {
        return $this->EventId;
    }

    public function setEventId(string $EventId): self
    {
        $this->EventId = $EventId;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }
}
