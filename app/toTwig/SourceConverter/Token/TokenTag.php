<?php

/**
 * This file is part of the PHP ST utility.
 *
 * (c) Sankar suda <sankar.suda@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace toTwig\SourceConverter\Token;

use SebastianBergmann\Diff\Differ;
use toTwig\ConversionResult;
use toTwig\Converter\ConverterAbstract;
use toTwig\SourceConverter\Token;

final class TokenTag extends Token {
    public function __construct(public readonly string $content)
    {
    }
    public function replace(string $content): self {
        if ($this->content !== $content) {
            return new self($content);
        }
        return $this;
    }
    public function __toString(): string
    {
        return $this->content;
    }
}