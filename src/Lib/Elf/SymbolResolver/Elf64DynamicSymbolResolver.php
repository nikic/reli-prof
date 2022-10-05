<?php

/**
 * This file is part of the reliforp/reli-prof package.
 *
 * (c) sji <sji@sj-i.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpProfiler\Lib\Elf\SymbolResolver;

use PhpProfiler\Lib\ByteStream\ByteReaderInterface;
use PhpProfiler\Lib\Elf\Parser\Elf64Parser;
use PhpProfiler\Lib\Elf\Parser\ElfParserException;
use PhpProfiler\Lib\Elf\Structure\Elf64\Elf64GnuHashTable;
use PhpProfiler\Lib\Elf\Structure\Elf64\Elf64StringTable;
use PhpProfiler\Lib\Elf\Structure\Elf64\Elf64SymbolTable;
use PhpProfiler\Lib\Elf\Structure\Elf64\Elf64SymbolTableEntry;

final class Elf64DynamicSymbolResolver implements Elf64SymbolResolver
{
    /**
     * @throws ElfParserException
     */
    public static function load(Elf64Parser $parser, ByteReaderInterface $php_binary): self
    {
        $elf_header = $parser->parseElfHeader($php_binary);
        $elf_program_header = $parser->parseProgramHeader($php_binary, $elf_header);
        $elf_dynamic_array = $parser->parseDynamicStructureArray($php_binary, $elf_program_header->findDynamic()[0]);
        $elf_string_table = $parser->parseStringTable($php_binary, $elf_dynamic_array);
        $elf_gnu_hash_table = $parser->parseGnuHashTable($php_binary, $elf_dynamic_array);
        if (is_null($elf_gnu_hash_table)) {
            throw new ElfParserException('cannot find gnu hash table');
        }
        $elf_symbol_table = $parser->parseSymbolTableFromDynamic(
            $php_binary,
            $elf_dynamic_array,
            $elf_gnu_hash_table->getNumberOfSymbols()
        );
        return new self(
            $elf_symbol_table,
            $elf_gnu_hash_table,
            $elf_string_table
        );
    }

    public function __construct(
        private Elf64SymbolTable $symbol_table,
        private Elf64GnuHashTable $hash_table,
        private Elf64StringTable $string_table,
    ) {
    }

    public function resolve(string $symbol_name): Elf64SymbolTableEntry
    {
        $index = $this->hash_table->lookup($symbol_name, function (string $name, int $index) {
            $symbol = $this->symbol_table->lookup($index);
            return $name === $this->string_table->lookup($symbol->st_name);
        });
        return $this->symbol_table->lookup($index);
    }
}
