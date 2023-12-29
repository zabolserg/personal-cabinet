<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="user")
 */
class User implements UserInterface
{
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_APPROVAL = 2;
    public const STATUS_MSG = [
        self::STATUS_INACTIVE => 'Не активний',
        self::STATUS_ACTIVE => 'Активний',
        self::STATUS_APPROVAL => 'Новий',
    ];

    public const ROLE_SYSTEM = 'ROLE_SYSTEM';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_USER = 'ROLE_USER';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="email", type="string", length=128, unique=true)
     */
    private $email;

    /**
     * @var string The hashed password
     * @ORM\Column(name="password", type="string", length=128)
     */
    private $password;

    /**
     * User password from registration form
     */
    private $userPassword;

    /**
     * @ORM\Column(name="roles", type="json")
     */
    private $roles = [];
    //private $roles = ['ROLE_USER'];

    /**
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @ORM\Column(name="first_name", type="string", length=64, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(name="last_name", type="string", length=64, nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(name="patronymic", type="string", length=64, nullable=true)
     */
    private $patronymic;

    /**
     * @ORM\Column(name="eic_code", type="string", length=32, unique=true)
     */
    private $eicCode;

    /**
     * @ORM\Column(name="postcode", type="integer", nullable=true)
     */
    private $postcode;

    /**
     * @ORM\Column(name="city", type="string", length=64, nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(name="street", type="string", length=64, nullable=true)
     */
    private $street;

    /**
     * @ORM\Column(name="building", type="string", length=16, nullable=true)
     */
    private $building;

    /**
     * @ORM\Column(name="apartment", type="string", length=16, nullable=true)
     */
    private $apartment;

    /**
     * @ORM\Column(name="personal_account", type="string", length=32, nullable=true)
     */
    private $personalAccount;

    /**
     * @ORM\Column(name="home_phone", type="string", length=32, nullable=true)
     */
    private $homePhone;

    /**
     * @ORM\Column(name="mobile_phone", type="string", length=32, nullable=true)
     */
    private $mobilePhone;

    /**
     * @ORM\Column(name="time_create", type="datetime")
     */
    private $timeCreate;

    /**
     * @ORM\Column(name="time_update", type="datetime")
     */
    private $timeUpdate;

    /**
     * One user has many documents. This is the inverse side.
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Document", mappedBy="user", cascade={"persist", "remove"})
     */
    private $documents;

    /**
     * Owning Side
     * @ORM\ManyToMany(targetEntity="Meter", inversedBy="users", cascade={"persist"})
     * @ORM\JoinTable(name="user_to_meter")
     */
    private $meters;

    public function __construct() {
        $this->documents = new ArrayCollection();
        $this->meters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getUserPassword(): ?string
    {
        return $this->userPassword;
    }

    public function setUserPassword(string $userPassword): self
    {
        $this->userPassword = $userPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function setPatronymic(?string $patronymic): self
    {
        $this->patronymic = $patronymic;

        return $this;
    }

    public function getEicCode(): ?string
    {
        return $this->eicCode;
    }

    public function setEicCode(string $eicCode): self
    {
        $this->eicCode = $eicCode;

        return $this;
    }

    public function getPostcode(): ?int
    {
        return $this->postcode;
    }

    public function setPostcode(?int $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getBuilding(): ?string
    {
        return $this->building;
    }

    public function setBuilding(?string $building): self
    {
        $this->building = $building;

        return $this;
    }

    public function getApartment(): ?string
    {
        return $this->apartment;
    }

    public function setApartment(?string $apartment): self
    {
        $this->apartment = $apartment;

        return $this;
    }

    public function getPersonalAccount(): ?string
    {
        return $this->personalAccount;
    }

    public function setPersonalAccount(?string $personalAccount): self
    {
        $this->personalAccount = $personalAccount;

        return $this;
    }

    public function getHomePhone(): ?string
    {
        return $this->homePhone;
    }

    public function setHomePhone(?string $homePhone): self
    {
        $this->homePhone = $homePhone;

        return $this;
    }

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function setMobilePhone(?string $mobilePhone): self
    {
        $this->mobilePhone = $mobilePhone;

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

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**/
    public function getDocuments()
    {
        return $this->documents;
    }

    public function addDocument(Document $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
            $document->setUser($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        if ($this->documents->contains($document)) {
            $this->documents->removeElement($document);
            // set the owning side to null (unless already changed)
            if ($document->getUser() === $this) {
                $document->setUser(null);
            }
        }

        return $this;
    }
    /**/

    /**/
    public function getMeters()
    {
        return $this->meters;
    }

    public function addMeter(Meter $meter): self
    {
        if (!$this->meters->contains($meter)) {
            $this->meters[] = $meter;
            $meter->addUser($this);
        }
        return $this;
    }

    public function removeMeter(Meter $meter): self
    {
        if ($this->meters->contains($meter)) {
            $this->meters->removeElement($meter);
            $meter->removeUser($this);
        }
        return $this;
    }
    /**/

}
