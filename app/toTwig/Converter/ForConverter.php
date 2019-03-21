<?php

/**
 * This file is part of the PHP ST utility.
 *
 * (c) Sankar suda <sankar.suda@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace toTwig\Converter;

/**
 * @author sankar <sankar.suda@gmail.com>
 */
class ForConverter extends ConverterAbstract
{

    protected $name = 'for';
    protected $description = 'Convert foreach/foreachelse to twig';
    protected $priority = 50;

    // Lookup tables for performing some token
    // replacements not addressed in the grammar.
    private $replacements = [
        'smarty\.foreach.*\.index' => 'loop.index0',
        'smarty\.foreach.*\.iteration' => 'loop.index'
    ];

    /**
     * @param string $content
     *
     * @return string
     */
    public function convert(string $content): string
    {
        $content = $this->replaceFor($content);
        $content = $this->replaceEndForEach($content);
        $content = $this->replaceForEachElse($content);

        foreach ($this->replacements as $k => $v) {
            $content = preg_replace('/' . $k . '/', $v, $content);
        }

        return $content;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function replaceEndForEach(string $content): string
    {
        /**
         * $pattern is supposed to detect structure like this:
         * [{/endforeach}]
         **/
        $search = $this->getClosingTagPattern('foreach');
        $replace = "{% endfor %}";

        return preg_replace($search, $replace, $content);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function replaceForEachElse(string $content): string
    {
        /**
         * $pattern is supposed to detect structure like this:
         * [{foreachelse}]
         **/
        $search = $this->getOpeningTagPattern('foreachelse');
        $replace = "{% else %}";

        return preg_replace($search, $replace, $content);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function replaceFor(string $content): string
    {
        /**
         * $pattern is supposed to detect structure like this:
         * [{foreach $myColors as $color}]
         **/
        $pattern = $this->getOpeningTagPattern('foreach');
        $string = '{% for :key :item in :from %}';

        return preg_replace_callback(
            $pattern,
            function ($matches) use ($string) {
                /**
                 * $matches contains an array of strings.
                 *
                 * $matches[0] contains a string with full matched tag i.e.'[{foreach $myColors as $color}]'
                 * $matches[1] should contain a string with all attributes passed to a tag i.e.'$myColors as $color'
                 */
                $match = $matches[1];
                $search = $matches[0];

                if (preg_match("/(.*)(?:\bas\b)(.*)/i", $match, $mcs)) {
                    $replace = $this->getReplaceArgumentsForSmarty3($mcs);
                } else {
                    $replace = $this->getReplaceArgumentsForSmarty2($matches);
                }
                $replace['from'] = $this->sanitizeValue($replace['from']);
                $string = $this->replaceNamedArguments($string, $replace);

                return str_replace($search, $string, $search);
            },
            $content
        );
    }

    /**
     * Returns array of replace arguments for foreach function in smarty 3
     * For example:
     * {foreach $arrayVar as $itemVar}
     * or
     * {foreach $arrayVar as $keyVar=>$itemVar}
     *
     * @param array $mcs
     *
     * @return array
     */
    private function getReplaceArgumentsForSmarty3(array $mcs): array
    {
        $replace = [];
        /**
         * $pattern is supposed to detect structure like this:
         * [{foreach $arrayVar as $keyVar=>$itemVar}]
         **/
        if (preg_match("/(.*)\=\>(.*)/", $mcs[2], $match)) {
            if (!isset($replace['key'])) {
                $replace['key'] = '';
            }
            $replace['key'] .= $this->sanitizeVariableName($match[1]) . ',';
            $mcs[2] = $match[2];
        }
        $replace['item'] = $this->sanitizeVariableName($mcs[2]);
        $replace['from'] = $mcs[1];

        return $replace;
    }

    /**
     * Returns array of replace arguments for foreach function in smarty 2
     * For example:
     * {foreach from=$myArray key="myKey" item="myItem"}
     *
     * @param array $matches
     *
     * @return array
     */
    private function getReplaceArgumentsForSmarty2(array $matches): array
    {
        $attr = $this->getAttributes($matches);

        if (isset($attr['key'])) {
            $replace['key'] = $this->sanitizeVariableName($attr['key']) . ',';
        }

        $replace['item'] = $this->sanitizeVariableName($attr['item']);
        $replace['from'] = $attr['from'];

        return $replace;
    }
}
