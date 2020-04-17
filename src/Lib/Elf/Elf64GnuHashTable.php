<?php

/**
 * This file is part of the sj-i/php-profiler package.
 *
 * (c) sji <sji@sj-i.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace PhpProfiler\Lib\Elf;


use PhpProfiler\Lib\UInt64;

/**
 * Class Elf64GnuHashTable
 * @package PhpProfiler\Lib\Elf
 * @see https://flapenguin.me/2017/05/10/elf-lookup-dt-gnu-hash/
 */
final class Elf64GnuHashTable
{
    public const ELFCLASS_BITS = 64;

    public int $nbuckets; // uint32_t
    public int $symoffset; // uint32_t
    public int $bloom_size; // uint32_t
    public int $bloom_shift; // uint32_t
    /** @var UInt64[]  */
    public array $bloom; // uint64_t[bloom_size]
    public array $buckets = []; // uint32_t[nbuckets]
    public array $chain; // uint32_t[]


    /**
     * @param string $name
     * @param callable $symbol_table_checker
     * @return int
     */
    public function lookup(string $name, callable $symbol_table_checker): int
    {
        $hash = self::hash($name);
        if (!$this->checkBloomFilter($hash)) {
            return Elf64SymbolTable::STN_UNDEF;
        }

        $chain_offset = $this->buckets[$hash % $this->nbuckets] - $this->symoffset;

        do {
            if ((1|$this->chain[$chain_offset]) === (1|$hash)) {
                if ($symbol_table_checker($name, $chain_offset + $this->symoffset)) {
                    return $chain_offset + $this->symoffset;
                }
            }
            $chain_offset++;
        } while ($this->chain[$chain_offset] & 1 === 0);

        return Elf64SymbolTable::STN_UNDEF;
    }

    /**
     * @return int
     */
    public function getNumberOfSymbols(): int
    {
        /** @var int $last_chain_key */
        $last_chain_key = array_key_last($this->chain);
        return $last_chain_key + 1 + $this->symoffset;
    }

    /**
     * @param int $hash
     * @return bool
     */
    public function checkBloomFilter(int $hash): bool
    {
        $bloom = $this->bloom[($hash / self::ELFCLASS_BITS) % $this->bloom_size];
        $bloom_hash1 = $hash % self::ELFCLASS_BITS;
        $bloom_hash2 = ($hash >> $this->bloom_shift) % self::ELFCLASS_BITS;
        return $bloom->checkBitSet($bloom_hash1) and $bloom->checkBitSet($bloom_hash2);
    }

    /**
     * @param string $name
     * @return int
     */
    public static function hash(string $name): int
    {
        $h = 5381;

        $name_len = strlen($name);
        for ($i = 0; $i < $name_len; $i++) {
            $h = (($h << 5) + $h + ord($name[$i])) & 0xffffffff;
        }
        return $h;
    }
}