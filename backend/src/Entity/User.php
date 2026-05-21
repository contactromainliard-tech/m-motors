<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Entité représentant un utilisateur de l'application M-Motors.
 * Un utilisateur peut être un client ou un administrateur back-office.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Identifiant unique de l'utilisateur.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Adresse email de l'utilisateur, utilisée comme identifiant de connexion.
     */
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * Rôles de l'utilisateur (ROLE_USER, ROLE_ADMIN).
     *
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Mot de passe hashé de l'utilisateur.
     *
     * @var string
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Prénom de l'utilisateur.
     */
    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    /**
     * Nom de famille de l'utilisateur.
     */
    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    /**
     * Numéro de téléphone de l'utilisateur (optionnel).
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    /**
     * Indique si l'utilisateur est un administrateur back-office.
     */
    #[ORM\Column]
    private ?bool $isAdmin = null;

    /**
     * Retourne l'identifiant unique de l'utilisateur.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne l'adresse email de l'utilisateur.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Définit l'adresse email de l'utilisateur.
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Retourne l'identifiant visuel de l'utilisateur (email).
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Retourne les rôles de l'utilisateur.
     * Chaque utilisateur a au minimum ROLE_USER.
     *
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /**
     * Définit les rôles de l'utilisateur.
     *
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Retourne le mot de passe hashé de l'utilisateur.
     *
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Définit le mot de passe hashé de l'utilisateur.
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Supprime les données sensibles de l'utilisateur.
     */
    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    /**
     * Retourne le prénom de l'utilisateur.
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Définit le prénom de l'utilisateur.
     */
    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * Retourne le nom de famille de l'utilisateur.
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Définit le nom de famille de l'utilisateur.
     */
    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * Retourne le numéro de téléphone de l'utilisateur.
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Définit le numéro de téléphone de l'utilisateur.
     */
    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Indique si l'utilisateur est administrateur.
     */
    public function isAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    /**
     * Définit si l'utilisateur est administrateur.
     */
    public function setIsAdmin(bool $isAdmin): static
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }
}