<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents\Repository;

use Doctrine\Persistence\ObjectRepository;

/**
 * @template T of object
 * @template-implements ObjectRepository<T>
 */
interface DocumentRepositoryInterface extends ObjectRepository
{
    /**
     * @return iterable<T> & \Countable
     */
    public function findAllUnused(): iterable;

    /**
     * @return iterable<T> & \Countable
     */
    public function findDuplicates(): iterable;

    /**
     * @return iterable<T> & \Countable
     */
    public function findAllWithoutFileHash(): iterable;
}
