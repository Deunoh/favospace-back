<?php

namespace App\Entity;

use App\Repository\SpaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SpaceRepository::class)]
class Space
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['space_list', 'space_marks'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['space_list', 'space_marks'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'spaces')]
    private ?User $user = null;

    /**
     * @var Collection<int, Mark>
     */
    #[ORM\OneToMany(targetEntity: Mark::class, mappedBy: 'space', orphanRemoval: true)]
    #[Groups(['space_marks'])]
    private Collection $marks;

    public function __construct()
    {
        $this->marks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Mark>
     */
    public function getMarks(): Collection
    {
        return $this->marks;
    }

    public function addMark(Mark $mark): static
    {
        if (!$this->marks->contains($mark)) {
            $this->marks->add($mark);
            $mark->setSpace($this);
        }

        return $this;
    }

    public function removeMark(Mark $mark): static
    {
        if ($this->marks->removeElement($mark)) {
            // set the owning side to null (unless already changed)
            if ($mark->getSpace() === $this) {
                $mark->setSpace(null);
            }
        }

        return $this;
    }
}
