<?php
/**
 * Analytics / Search Console connector contract.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Connectors;

interface Contract
{
    /**
     * @return array{ok:bool,message:string}
     */
    public function test(): array;

    /**
     * @return array{rows:list<array<string, mixed>>,error?:string}
     */
    public function fetch(int $lookbackDays): array;
}
