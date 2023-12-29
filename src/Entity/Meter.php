<?php

namespace App\Entity;

use App\Repository\MeterRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=MeterRepository::class)
 * @ORM\Table(name="meter")
 */
class Meter
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="number", type="string", length=64)
     */
    private $number;

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
     * One-To-Many (always inverse side (inverse side has mappedBy))
     * @ORM\OneToMany(targetEntity="MeterReading", mappedBy="meter", cascade={"persist", "remove"})
     */
    private $meterReadings;

    /**
     * Inverse Side
     * @ORM\ManyToMany(targetEntity="User", mappedBy="meters", cascade={"persist"})
     */
    private $users;

    public function __construct() {
        $this->meterReadings = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

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

    /**/
    public function getMeterReadings()
    {
        return $this->meterReadings;
    }

    public function addMeterReading(MeterReading $meterReading): self
    {
        if (!$this->meterReadings->contains($meterReading)) {
            $this->meterReadings[] = $meterReading;
            $meterReading->setMeter($this);
        }

        return $this;
    }

    public function removeMeterReading(MeterReading $meterReading): self
    {
        if ($this->meterReadings->contains($meterReading)) {
            $this->meterReadings->removeElement($meterReading);
            // set the owning side to null (unless already changed)
            if ($meterReading->getMeter() === $this) {
                $meterReading->setMeter(null);
            }
        }

        return $this;
    }
    /**/

    /**/
    public function getUsers()
    {
        return $this->users;
    }
    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }
        return $this;
    }
    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
        }
        return $this;
    }
    /**/

}
