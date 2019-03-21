<?php

namespace toTwig\Converter;

/**
 * Class OxcontentConverter
 *
 * @author Tomasz Kowalewski (t.kowalewski@createit.pl)
 */
class OxcontentConverter extends ConverterAbstract
{

    protected $name = 'oxcontent';
    protected $description = "Convert smarty {oxcontent} to twig function {% include 'content::...' %}";
    protected $priority = 100;

    /**
     * @param string $content
     *
     * @return null|string|string[]
     */
    public function convert(string $content): string
    {
        /**
         * $pattern is supposed to detect structure like this:
         * [{oxcontent ident='foo' field='bar'}]
         **/
        $pattern = $this->getOpeningTagPattern('oxcontent');

        return preg_replace_callback(
            $pattern,
            function ($matches) {
                /**
                 * $matches contains an array of strings.
                 *
                 * $matches[0] contains a string with full matched tag i.e.'[{oxcontent ident='foo' field='bar'}]'
                 * $matches[1] should contain a string with all attributes passed to a tag i.e.'ident="foo" field="bar"'
                 */
                $attributes = $this->getAttributes($matches);

                $key = null;
                if (isset($attributes['ident'])) {
                    $key = 'ident';
                } elseif (isset($attributes['oxid'])) {
                    $key = 'oxid';
                }

                $value = $this->rawString($this->sanitizeValue($attributes[$key]));

                $templateName = "content::$key::$value";

                $parameters = array_map(
                    function ($attribute) {
                        return $this->rawString($this->sanitizeValue($attribute));
                    },
                    array_filter(
                        $attributes,
                        function ($attribute) {
                            return !in_array($attribute, ['ident', 'oxid', 'assign']);
                        },
                        ARRAY_FILTER_USE_KEY
                    )
                );

                if (!empty($parameters)) {
                    $templateName .= '?' . http_build_query($parameters);
                }

                // Different approaches for syntax with and without assignment
                $assignVar = $this->extractAssignVariableName($attributes);
                if ($assignVar) {
                    $twigTag = "{% set $assignVar = include('$templateName') %}";
                } else {
                    $twigTag = "{% include '$templateName' %}";
                }

                return $twigTag;
            },
            $content
        );
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    private function extractAssignVariableName(array $attributes): ?string
    {
        $assignVar = null;
        if (isset($attributes['assign'])) {
            $assignVar = $this->sanitizeVariableName($attributes['assign']);
        }

        return $assignVar;
    }
}
