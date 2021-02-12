<?php
declare(strict_types=1);

namespace Mafia\Models;

use Mafia\Interfaces\IMember;

class Member implements IMember
{
    private int $id;
    private int $age;

    private int $level=0;
    private ?IMember $boss=null;
    private ?IMember $alternateBoss=null;

    /** @var IMember[] */
    private array $subordinateTree;

    /**
     * Member constructor.
     * @param int $id
     * @param int $age
     */
    public function __construct(int $id, int $age)
    {
        $this->id = $id;
        $this->age = $age;

        $this->subordinateTree=[];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function addSubordinate(IMember $subordinate): IMember
    {
        $subordinate->setLevel($this->getLevel() + 1 );
        $this->subordinateTree[$subordinate->getId()] = $subordinate;
        return $subordinate;
    }

    public function removeSubordinate(IMember $subordinate): ?IMember
    {
        unset($this->subordinateTree[$subordinate->getId()]);
        return $this;
    }

    public function getSubordinates(): array
    {
        return $this->subordinateTree;
    }

    public function getBoss(): ?IMember
    {
        return $this->boss;
    }

    public function setBoss(?IMember $boss): IMember
    {
        if($this->boss = $boss){
            $this->boss->addSubordinate($this);
        }
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): IMember
    {
        $this->level = $level;
        return $this;
    }

    public function isGodfather(): bool
    {
        return !$this->getBoss();
    }

    /**
     * @param IMember[] $inJail
     * @return IMember|null
     */
    public function getOldestSubordinate(array $inJail): ?IMember
    {
        $oldest = null;
        foreach($this->getSubordinates() as $subordinate){
            if(in_array($subordinate->getId(), array_keys($inJail))){
                continue;
            }
            if($oldest===null){
                $oldest = $subordinate;
            }
            elseif($subordinate->getAge() > $oldest->getAge()){
                $oldest = $subordinate;
            }
        }

        return $oldest;
    }

    public function getAlternateBoss(): ?IMember
    {
        return $this->alternateBoss;
    }

    public function setAlternateBoss(?IMember $alternateBoss): Member
    {
        $this->alternateBoss = $alternateBoss;
        return $this;
    }

    public function countAllSubordinates(): int
    {
        $total=count($this->getSubordinates());

        foreach($this->getSubordinates() as $subordinate){
            $total += $subordinate->countAllSubordinates();
        }

        return $total;
    }

}
