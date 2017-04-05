<?php

namespace DevIT\MelodiMedia\Parsers;

Interface MelodiApiParser
{
    /**
     * Parse a certain response from the melodi api parser.
     *
     * @param string $content
     *
     * @return array|null
     * @throws \DevIT\MelodiMedia\Exceptions\ContentCouldNotBeParsed
     */
    public function parse(string $content): ?array;
}
