<?php

namespace App\Entity;

use App\Repository\MeterReadingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MeterReadingRepository::class)
 * @ORM\Table(name="meter_reading")
 */
class MeterReading
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="meter_id", type="integer")
     */
    private $meterId;

    /**
     * @ORM\Column(name="value", type="float")
     */
    private $value;

    /**
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @ORM\Column(name="time_create", type="datetime")
     */
    private $timeCreate;

    /**
     * @ORM\Column(name="time_update", type="datetime")
     */
    private $timeUpdate;

    /**
     * Many-To-One (always owning side (owning side has inversedBy))
     * @ORM\ManyToOne(targetEntity="Meter", inversedBy="meterReadings", cascade={"persist"})
     * @ORM\JoinColumn(name="meter_id", referencedColumnName="id")
     */
    private $meter;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMeterId(): ?int
    {
        return $this->meterId;
    }

    public function setMeterId(int $meterId): self
    {
        $this->meterId = $meterId;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTimeCreate(): ?\DateTimeInterface
    {
        return $this->timeCreate;
    }

    public function setTimeCreate(\DateTimeInterface $timeCreate): self
    {
        $this->timeCreate = $timeCreate;

        return $this;
    }

    public function getTimeUpdate(): ?\DateTimeInterface
    {
        return $this->timeUpdate;
    }

    public function setTimeUpdate(\DateTimeInterface $timeUpdate): self
    {
        $this->timeUpdate = $timeUpdate;

        return $this;
    }

    public function getMeter(): ?Meter
    {
        return $this->meter;
    }

    public function setMeter(?Meter $meter): self
    {
        $this->meter = $meter;

        return $this;
    }
}
