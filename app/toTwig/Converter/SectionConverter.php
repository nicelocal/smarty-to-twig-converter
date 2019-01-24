<?php
/**
 * Created by PhpStorm.
 * User: jskoczek
 * Date: 28/08/18
 * Time: 12:34
 */

namespace toTwig\Converter;

use toTwig\ConverterAbstract;

/**
 * Class SectionConverter
 */
class SectionConverter extends ConverterAbstract
{

    protected $name = 'section';
    protected $description = 'Convert smarty {section} to twig {for}';

    protected $priority = 20;

    /**
     * Function converts smarty {section} tags to twig {for}
     *
     * @param \SplFileInfo $file
     * @param string       $content
     *
     * @return null|string|string[]
     */
    public function convert(\SplFileInfo $file, string $content): string
    {
        $contentReplacedOpeningTag = $this->replaceSectionOpeningTag($content);
        $content = $this->replaceSectionClosingTag($contentReplacedOpeningTag);

        return $content;
    }

    /**
     * Function converts opening tag of smarty {section} to twig {for}
     *
     * @param string $content
     *
     * @return null|string|string[]
     */
    private function replaceSectionOpeningTag(string $content): string
    {
        $pattern = $this->getOpeningTagPattern('section');
        $string = '{% for :name in :start..:loop %}';

        return preg_replace_callback(
            $pattern,
            function ($matches) use ($string) {
                $match = $matches[1];
                $search = $matches[0];

                $attr = $this->attributes($match);
                if (!isset($attr['start'])) {
                    $attr['start'] = 0;
                }

                $attr['name'] = $this->variable($attr['name']);

                $replace = $attr;
                $string = $this->vsprintf($string, $replace);

                // Replace more than one space to single space
                $string = preg_replace('!\s+!', ' ', $string);

                return str_replace($search, $string, $search);
            },
            $content
        );
    }

    /**
     * Function converts closing tag of smarty {section} to twig {for}
     *
     * @param string $content
     *
     * @return null|string|string[]
     */
    private function replaceSectionClosingTag(string $content): string
    {
        $search = $this->getClosingTagPattern('section');
        $search = '#\[\{\s*/section\s*\}\]#';
        $replace = '{% endfor %}';

        return preg_replace($search, $replace, $content);
    }
}
