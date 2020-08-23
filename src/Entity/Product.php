<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 */
class Product
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\Length(
     *      min = 3,
     *      max = 200,
     *      minMessage = "Name must be at least {{ limit }} characters",
     *      maxMessage = "Name must be no more than {{ limit }} characters"
     * )
     * @ORM\Column(type="string", length=200)
     */
    private $name;

    /**
     * @Assert\Length(
     *      max = 1000,
     *      maxMessage = "Description must be no more than {{ limit }} characters"
     * )
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateCreate;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="integer")
     */
    private $stock;

    /**
     * @ORM\ManyToMany(targetEntity=Category::class)
     */
    private $categories;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $externalId;

    /**
     * Product constructor.
     */
    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    /**
     * @return $this
     */
    public function setDateCreate(): self
    {
        $this->dateCreate = new \DateTime();

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return $this
     */
    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getStock(): ?int
    {
        return $this->stock;
    }

    /**
     * Пока что храним значение остатка в виде integer
     * @param int $stock
     * @return $this
     */
    public function setStock(int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function removeCategory(Category $category): self
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * Используем тип строки, поскольку внешний id может быть не только числовым
     *
     * @param string|null $externalId
     * @return $this
     */
    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * На вход принимаем массив id категорий, проверяем, существует ли указанная категория
     * @param integer[] $categoriesIds
     */
    public function setCategories(array $categoriesIds)
    {
//        throw new EntityNotFoundException('Category not found');
    }

    /**
     * Логику установки полей продукта хотелось собарть в одном месте и не размазывать по разным участкам кода.
     * Намеренно не использую тут сеттеры, а устанавливаю каждое свойства руками, в данном случае нахожу это более
     * удобным, а сеттеры иметь надо, поскольку всегда могут пригодиться в другом месте
     *
     * @param array $fields
     */
    public function setProductData(array $fields): void
    {
        $this->name = $fields['name'] ?? '';
        $this->description = $fields['description'] ?? '';

        $this->externalId = $fields['externalId'] ?? '';

        /**
         * Для цены используется тип float, для большего удобства пользования предположим, что
         * пользователь может передать цену как строку, поэтому сначала попытаемся привести значение
         * к float, в случае неудачи - проставим цену как 0.
         */
        if (!empty($fields['price']) && settype($fields['price'], 'float')) {
            $this->price = round($fields['price'], 2);
        } else {
            $this->price = 0.00;
        }

        $this->stock = (!empty($fields['stock']) && settype($fields['stock'], 'integer'))
            ? $fields['stock']
            : 0;

        if (!empty($fields['categories']) && is_array($fields['categories'])) {
            $this->setCategories($fields['categories']);
        }

        $this->setDateCreate();
    }
}
