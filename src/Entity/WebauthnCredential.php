<?php

namespace App\Entity;

use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WebauthnCredentialRepository::class)]
#[ORM\Table(name: 'webauthn_credential')]
class WebauthnCredential
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'webauthnCredentials')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'text')]
    private string $credentialData; // JSON of PublicKeyCredentialSource

    #[ORM\Column(length: 255)]
    private string $name = 'Ma clé';

    #[ORM\Column(type: 'blob')]
    private $credentialId; // binary storage

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $lastUsedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCredentialData(): string
    {
        return $this->credentialData;
    }

    public function setCredentialData(string $credentialData): static
    {
        $this->credentialData = $credentialData;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCredentialId()
    {
        return $this->credentialId;
    }

    public function setCredentialId($credentialId): static
    {
        $this->credentialId = $credentialId;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): \DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function touch(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }
}
