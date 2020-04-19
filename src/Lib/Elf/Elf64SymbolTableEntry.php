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
 * Class Elf64SymbolTableEntry
 * @package PhpProfiler\Lib\Elf
 */
final class Elf64SymbolTableEntry
{
    public const STT_NOTYPE = 0;
    public const STT_OBJECT = 1;
    public const STT_FUNC = 2;
    public const STT_SECTION = 3;
    public const STT_FILE = 4;
    public const STT_COMMON = 5;
    public const STT_TLS = 6;
    public const STT_LOOS = 10;
    public const STT_HIOS = 12;
    public const STT_LOPROC = 13;
    public const STT_HIPROC = 15;

    public int $st_name; // Elf64_Word
    public int $st_info; // unsigned char
    public int $st_other; // unsigned char
    public int $st_shndx; // Elf64_Half
    public UInt64 $st_value; // Elf64_Addr
    public UInt64 $st_size; // Elf64_Xword

    /**
     * Elf64SymbolTableEntry constructor.
     * @param int $st_name
     * @param int $st_info
     * @param int $st_other
     * @param int $st_shndx
     * @param UInt64 $st_value
     * @param UInt64 $st_size
     */
    public function __construct(
        int $st_name,
        int $st_info,
        int $st_other,
        int $st_shndx,
        UInt64 $st_value,
        UInt64 $st_size
    ) {
        $this->st_name = $st_name;
        $this->st_info = $st_info;
        $this->st_other = $st_other;
        $this->st_shndx = $st_shndx;
        $this->st_value = $st_value;
        $this->st_size = $st_size;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return (($this->st_info) & 0xf);
    }

    /**
     * @return bool
     */
    public function isTls(): bool
    {
        return $this->getType() === self::STT_TLS;
    }

    /**
     * @return bool
     */
    public function isUndefined(): bool
    {
        return $this->st_name === 0
            and $this->st_info === 0
            and $this->st_other === 0
            and $this->st_shndx === 0
            and $this->st_value->lo === 0
            and $this->st_value->hi === 0
            and $this->st_size->lo === 0
            and $this->st_size->hi === 0;
    }
}
