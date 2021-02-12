<?php
declare(strict_types=1);

namespace Mafia\Models;

use Mafia\Interfaces\IMafia;
use Mafia\Interfaces\IMember;

class Mafia implements IMafia
{
    /**
     * @var IMember
     */
    private IMember $godfather;

    /**
     * @var IMember[]
     */
    private array $organizationMember;

    /**
     * @var IMember[]
     */
    private array $jailList;

    /**
     * Mafia constructor.
     * @param IMember $godfather
     */
    public function __construct(IMember $godfather)
    {
        $this->godfather = $godfather;
        $this->addMember($godfather);
    }

    public function getGodfather(): IMember
    {
        return $this->godfather;
    }

    public function addMember(IMember $member): ?IMember
    {
        $this->organizationMember[$member->getId()] = $member;
        return $member;
    }

    public function getMember(int $id): ?IMember
    {
        return $this->organizationMember[$id] ?? null;
    }

    public function sendToPrison(IMember $member): bool
    {
        $this->jailList[$member->getId()] = $member;

        unset($this->organizationMember[$member->getId()]);

        /**@var IMember $oldestRemainingBoss*/
        $oldestRemainingBoss = $member->isGodfather() ?
            null : $member->getBoss()->getOldestSubordinate($this->jailList);

        if( !$oldestRemainingBoss ){
            //promote subordinate as new boss
            $oldestRemainingBoss = $member->getOldestSubordinate($this->jailList);

            $this->promoteNewBoss($oldestRemainingBoss, $member);
        }

        foreach($member->getSubordinates() as $subordinate){
            $subordinate->setBoss($oldestRemainingBoss);
            $oldestRemainingBoss->addSubordinate($subordinate);
        }

        return true;
    }

    public function releaseFromPrison(IMember $member): bool
    {
        $memberToRelease = $this->jailList[$member->getId()];
        unset($this->jailList[$member->getId()]);

        $this->recoverPromotedSubordinates($memberToRelease);

        foreach($memberToRelease->getSubordinates() as $subordinate){
            if($alternateBoss=$subordinate->getBoss()){
                $alternateBoss->removeSubordinate($subordinate);
            }
            $subordinate->setBoss($memberToRelease);
        }

        $this->addMember($memberToRelease);

        return true;
    }

    public function findBigBosses(int $minimumSubordinates): array
    {
        $bigBosses=[];
        foreach($this->organizationMember as $member){
            if($member->getId() === $this->godfather->getId()){
                continue;
            }

            if($member->countAllSubordinates() >= $minimumSubordinates){
                $bigBosses[$member->getId()]=$member;
            }
        }

        return $bigBosses;
    }

    public function compareMembers(IMember $memberA, IMember $memberB): ?IMember
    {
        if($memberA->getLevel() < $memberB->getLevel()){
            return $memberA;
        }elseif($memberB->getLevel() > $memberA->getLevel()){
            return $memberB;
        }

        if($memberA->getAge() === $memberB->getAge()){
            return $memberA->getId() > $memberB->getId() ? $memberA : $memberB;
        }

        return $memberA->getAge() > $memberB->getAge() ? $memberA : $memberB;
    }

    private function setNewGodfather(IMember $godfather): void
    {
        $this->godfather = $godfather;
    }

    private function promoteNewBoss(IMember $newBoss, IMember $member): void
    {
        if($member->isGodfather()){
            $this->setNewGodfather($newBoss);
        }

        $newBoss->setBoss($member->getBoss())
            ->setLevel($member->getLevel());

        $member->removeSubordinate($newBoss)
            ->setAlternateBoss($newBoss);
    }

    private function recoverPromotedSubordinates(IMember $toRelease): void
    {
        if (!$toRelease->getBoss()) {
            $this->godfather = $toRelease;
        }

        if(!$oldestRemainingBoss = $toRelease->getAlternateBoss()){
            return;
        }

        $toRelease->setAlternateBoss(null)
            ->addSubordinate($oldestRemainingBoss);
    }
}
