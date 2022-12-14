<?php

if (class_exists('ParsedownExtra')) {
    class DynamicParent extends \ParsedownExtra
    {
        public function __construct()
        {
            parent::__construct();
        }
    }
} else {
    class DynamicParent extends \Parsedown
    {
        public function __construct()
        {
            //
        }
    }
}

class ParsedownToC extends DynamicParent
{
    /**
     * ------------------------------------------------------------------------
     *  Constants.
     * ------------------------------------------------------------------------
     */
    const VERSION = '1.4';
    const VERSION_PARSEDOWN_REQUIRED = '1.7.4';
    const TAG_TOC_DEFAULT = '[toc]';
    const ID_ATTRIBUTE_DEFAULT = 'toc';

    protected $default_params = array(
        'selectors' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        'delimiter' => '-',
        'limit' => null,
        'lowercase' => true,
        'replacements' => null,
        'transliterate' => false,
        'urlencode' => false,
        'blacklist' => [],
        'url' => '',
    );

    /**
     * Version requirement check.
     */
    public function __construct(array $params = null)
    {
        if (version_compare(\Parsedown::version, self::VERSION_PARSEDOWN_REQUIRED) < 0) {
            $msg_error  = 'Version Error.' . PHP_EOL;
            $msg_error .= '  ParsedownToc requires a later version of Parsedown.' . PHP_EOL;
            $msg_error .= '  - Current version : ' . \Parsedown::version . PHP_EOL;
            $msg_error .= '  - Required version: ' . self::VERSION_PARSEDOWN_REQUIRED .' and later'. PHP_EOL;
            throw new Exception($msg_error);
        }

        parent::__construct();

        if (!empty($params)) {
            $this->options = array_merge($this->default_params, $params);
        } else {
            $this->options = $this->default_params;
        }
    }

    /**
     * ------------------------------------------------------------------------
     * Methods (in ABC order)
     * ------------------------------------------------------------------------
     */

    /**
     * Heading process.
     * Creates heading block element and stores to the ToC list. It overrides
     * the parent method: \Parsedown::blockHeader() and returns $Block array if
     * the $Line is a heading element.
     *
     * @param  array $Line  Array that Parsedown detected as a block type element.
     * @return void|array   Array of Heading Block.
     */
    protected function blockHeader($Line)
    {
        // Use parent blockHeader method to process the $Line to $Block
        $Block = DynamicParent::blockHeader($Line);

        if (!empty($Block)) {
            // Get the text of the heading
            if (isset($Block['element']['text'])) {
                $text = $Block['element']['text'];
            }

            // Get the heading level. Levels are h1, h2, ..., h6
            $level = $Block['element']['name'];

            // Get the anchor of the heading to link from the ToC list
            $id = isset($Block['element']['attributes']['id']) ?
                $Block['element']['attributes']['id'] : $this->createAnchorID($text);

            // Set attributes to head tags
            $Block['element']['attributes']['id'] = $id;

            // Check if level are defined as a selector
            if (in_array($level, $this->options['selectors'])) {

                // Add/stores the heading element info to the ToC list
                $this->setContentsList(array(
                    'text'  => $text,
                    'id'    => $id,
                    'level' => $level
                ));
            }

            return $Block;
        }
    }

    /**
    * Heading process.
    * Creates heading block element and stores to the ToC list. It overrides
    * the parent method: \Parsedown::blockSetextHeader() and returns $Block array if
    * the $Line is a heading element.
    *
    * @param  array $Line  Array that Parsedown detected as a block type element.
    * @return void|array   Array of Heading Block.
     */
    protected function blockSetextHeader2($Line, array $Block = null)
    {
        // Use parent blockHeader method to process the $Line to $Block
        $Block = DynamicParent::blockSetextHeader($Line, $Block);

//        var_dump($Line);
        if (!empty($Block)) {
            // Get the text of the heading
            if (isset($Block['element']['text'])) {
                $text = $Block['element']['text'];
            }

            // Get the heading level. Levels are h1, h2, ..., h6
            $level = $Block['element']['name'];

            // Get the anchor of the heading to link from the ToC list
            $id = isset($Block['element']['attributes']['id']) ?
            $Block['element']['attributes']['id'] : $this->createAnchorID($text);

            // Set attributes to head tags
            $Block['element']['attributes']['id'] = $id;

            // Check if level are defined as a selector
            if (in_array($level, $this->options['selectors'])) {

                // Add/stores the heading element info to the ToC list
                $this->setContentsList(array(
                    'text'  => $text,
                    'id'    => $id,
                    'level' => $level
                ));
            }

            return $Block;
        }
    }

    /**
     * Parses the given markdown string to an HTML string but it leaves the ToC
     * tag as is. It's an alias of the parent method "\DynamicParent::text()".
     *
     * @param  string $text  Markdown string to be parsed.
     * @return string        Parsed HTML string.
     */
    public function body($text) : string
    {
        $text = $this->encodeTagToHash($text);   // Escapes ToC tag temporary
        $html = DynamicParent::text($text);      // Parses the markdown text
        $html = $this->decodeTagFromHash($html); // Unescape the ToC tag

        return $html;
    }

    /**
     * Returns the parsed ToC.
     *
     * @param  string $type_return  Type of the return format. "html", "json", or "array".
     * @return string|array         HTML/JSON string, or array of ToC.
     */
    public function contentsList($type_return = 'html')
    {
        if ('html' === strtolower($type_return)) {
            $result = '';
            if (! empty($this->contentsListString)) {
                // Parses the ToC list in markdown to HTML
                $result = $this->body($this->contentsListString);
            }
            return $result;
        }

        if ('json' === strtolower($type_return)) {
            return json_encode($this->contentsListArray);
        }

        if ('array' === strtolower($type_return)) {
            return $this->contentsListArray;
        }

        // Forces to return ToC as "html"
        error_log(
            'Unknown return type given while parsing ToC.'
            . ' At: ' . __FUNCTION__ . '() '
            . ' in Line:' . __LINE__ . ' (Using default type)'
        );
        return $this->contentsList('html');
    }

    /**
     * Generates an anchor text that are link-able even the heading is not in
     * ASCII.
     *
     * @param  string $text
     * @return string
     */
    protected function createAnchorID($str) : string
    {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());

        if($this->options['urlencode']) {
            // Check AnchorID is unique
            $str = $this->incrementAnchorId($str);

            return urlencode($str);
        }

        $char_map = array(
            // Latin
            '??' => 'A', '??' => 'A', '??' => 'A', '??' => 'A', '??' => 'A', '??' => 'AA', '??' => 'AE', '??' => 'C',
            '??' => 'E', '??' => 'E', '??' => 'E', '??' => 'E', '??' => 'I', '??' => 'I', '??' => 'I', '??' => 'I',
            '??' => 'D', '??' => 'N', '??' => 'O', '??' => 'O', '??' => 'O', '??' => 'O', '??' => 'O', '??' => 'O',
            '??' => 'OE', '??' => 'U', '??' => 'U', '??' => 'U', '??' => 'U', '??' => 'U', '??' => 'Y', '??' => 'TH',
            '??' => 'ss',
            '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'aa', '??' => 'ae', '??' => 'c',
            '??' => 'e', '??' => 'e', '??' => 'e', '??' => 'e', '??' => 'i', '??' => 'i', '??' => 'i', '??' => 'i',
            '??' => 'd', '??' => 'n', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o',
            '??' => 'oe', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'y', '??' => 'th',
            '??' => 'y',

            // Latin symbols
            '??' => '(c)','??' => '(r)','???' => '(tm)',

            // Greek
            '??' => 'A', '??' => 'B', '??' => 'G', '??' => 'D', '??' => 'E', '??' => 'Z', '??' => 'H', '??' => '8',
            '??' => 'I', '??' => 'K', '??' => 'L', '??' => 'M', '??' => 'N', '??' => '3', '??' => 'O', '??' => 'P',
            '??' => 'R', '??' => 'S', '??' => 'T', '??' => 'Y', '??' => 'F', '??' => 'X', '??' => 'PS', '??' => 'W',
            '??' => 'A', '??' => 'E', '??' => 'I', '??' => 'O', '??' => 'Y', '??' => 'H', '??' => 'W', '??' => 'I',
            '??' => 'Y',
            '??' => 'a', '??' => 'b', '??' => 'g', '??' => 'd', '??' => 'e', '??' => 'z', '??' => 'h', '??' => '8',
            '??' => 'i', '??' => 'k', '??' => 'l', '??' => 'm', '??' => 'n', '??' => '3', '??' => 'o', '??' => 'p',
            '??' => 'r', '??' => 's', '??' => 't', '??' => 'y', '??' => 'f', '??' => 'x', '??' => 'ps', '??' => 'w',
            '??' => 'a', '??' => 'e', '??' => 'i', '??' => 'o', '??' => 'y', '??' => 'h', '??' => 'w', '??' => 's',
            '??' => 'i', '??' => 'y', '??' => 'y', '??' => 'i',

            // Turkish
            '??' => 'S', '??' => 'I', '??' => 'C', '??' => 'U', '??' => 'O', '??' => 'G',
            '??' => 's', '??' => 'i', '??' => 'c', '??' => 'u', '??' => 'o', '??' => 'g',

            // Russian
            '??' => 'A', '??' => 'B', '??' => 'V', '??' => 'G', '??' => 'D', '??' => 'E', '??' => 'Yo', '??' => 'Zh',
            '??' => 'Z', '??' => 'I', '??' => 'J', '??' => 'K', '??' => 'L', '??' => 'M', '??' => 'N', '??' => 'O',
            '??' => 'P', '??' => 'R', '??' => 'S', '??' => 'T', '??' => 'U', '??' => 'F', '??' => 'H', '??' => 'C',
            '??' => 'Ch', '??' => 'Sh', '??' => 'Sh', '??' => '', '??' => 'Y', '??' => '', '??' => 'E', '??' => 'Yu',
            '??' => 'Ya',
            '??' => 'a', '??' => 'b', '??' => 'v', '??' => 'g', '??' => 'd', '??' => 'e', '??' => 'yo', '??' => 'zh',
            '??' => 'z', '??' => 'i', '??' => 'j', '??' => 'k', '??' => 'l', '??' => 'm', '??' => 'n', '??' => 'o',
            '??' => 'p', '??' => 'r', '??' => 's', '??' => 't', '??' => 'u', '??' => 'f', '??' => 'h', '??' => 'c',
            '??' => 'ch', '??' => 'sh', '??' => 'sh', '??' => '', '??' => 'y', '??' => '', '??' => 'e', '??' => 'yu',
            '??' => 'ya',

            // Ukrainian
            '??' => 'Ye', '??' => 'I', '??' => 'Yi', '??' => 'G',
            '??' => 'ye', '??' => 'i', '??' => 'yi', '??' => 'g',

            // Czech
            '??' => 'C', '??' => 'D', '??' => 'E', '??' => 'N', '??' => 'R', '??' => 'S', '??' => 'T', '??' => 'U',
            '??' => 'Z',
            '??' => 'c', '??' => 'd', '??' => 'e', '??' => 'n', '??' => 'r', '??' => 's', '??' => 't', '??' => 'u',
            '??' => 'z',

            // Polish
            '??' => 'A', '??' => 'C', '??' => 'e', '??' => 'L', '??' => 'N', '??' => 'o', '??' => 'S', '??' => 'Z',
            '??' => 'Z',
            '??' => 'a', '??' => 'c', '??' => 'e', '??' => 'l', '??' => 'n', '??' => 'o', '??' => 's', '??' => 'z',
            '??' => 'z',

            // Latvian
            '??' => 'A', '??' => 'C', '??' => 'E', '??' => 'G', '??' => 'i', '??' => 'k', '??' => 'L', '??' => 'N',
            '??' => 'S', '??' => 'u', '??' => 'Z',
            '??' => 'a', '??' => 'c', '??' => 'e', '??' => 'g', '??' => 'i', '??' => 'k', '??' => 'l', '??' => 'n',
            '??' => 's', '??' => 'u', '??' => 'z'
        );

        // Make custom replacements
        if(!empty($this->options['replacements'])) {
            $str = preg_replace(array_keys($this->options['replacements']), $this->options['replacements'], $str);
        }

        // Transliterate characters to ASCII
        if ($this->options['transliterate']) {
            $str = str_replace(array_keys($char_map), $char_map, $str);
        }

        // Replace non-alphanumeric characters with our delimiter
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $this->options['delimiter'], $str);

        // Remove duplicate delimiters
        $str = preg_replace('/(' . preg_quote($this->options['delimiter'], '/') . '){2,}/', '$1', $str);

        // Truncate slug to max. characters
        $str = mb_substr($str, 0, ($this->options['limit'] ? $this->options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');

        // Remove delimiter from ends
        $str = trim($str, $this->options['delimiter']);

        $str = $this->options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;

        $str = $this->incrementAnchorId($str);

        return $str;
    }

    /**
     * Decodes the hashed ToC tag to an original tag and replaces.
     *
     * This is used to avoid parsing user defined ToC tag which includes "_" in
     * their tag such as "[[_toc_]]". Unless it will be parsed as:
     *   "<p>[[<em>TOC</em>]]</p>"
     *
     * @param  string $text
     * @return string
     */
    protected function decodeTagFromHash($text)
    {
        $salt = $this->getSalt();
        $tag_origin = $this->getTagToC();
        $tag_hashed = hash('sha256', $salt . $tag_origin);

        if (strpos($text, $tag_hashed) === false) {
            return $text;
        }

        return str_replace($tag_hashed, $tag_origin, $text);
    }

    /**
     * Encodes the ToC tag to a hashed tag and replace.
     *
     * This is used to avoid parsing user defined ToC tag which includes "_" in
     * their tag such as "[[_toc_]]". Unless it will be parsed as:
     *   "<p>[[<em>TOC</em>]]</p>"
     *
     * @param  string $text
     * @return string
     */
    protected function encodeTagToHash($text)
    {
        $salt = $this->getSalt();
        $tag_origin = $this->getTagToC();

        if (strpos($text, $tag_origin) === false) {
            return $text;
        }

        $tag_hashed = hash('sha256', $salt . $tag_origin);

        return str_replace($tag_origin, $tag_hashed, $text);
    }

    /**
     * Get only the text from a markdown string.
     * It parses to HTML once then trims the tags to get the text.
     *
     * @param  string $text  Markdown text.
     * @return string
     */
    protected function fetchText($text)
    {
        return trim(strip_tags($this->line($text)));
    }

    /**
     * Gets the ID attribute of the ToC for HTML tags.
     *
     * @return string
     */
    protected function getIdAttributeToC()
    {
        if (isset($this->id_toc) && ! empty($this->id_toc)) {
            return $this->id_toc;
        }

        return self::ID_ATTRIBUTE_DEFAULT;
    }

    /**
     * Unique string to use as a salt value.
     *
     * @return string
     */
    protected function getSalt()
    {
        static $salt;
        if (isset($salt)) {
            return $salt;
        }

        $salt = hash('md5', time());
        return $salt;
    }

    /**
     * Gets the markdown tag for ToC.
     *
     * @return string
     */
    protected function getTagToC()
    {
        if (isset($this->tag_toc) && ! empty($this->tag_toc)) {
            return $this->tag_toc;
        }

        return self::TAG_TOC_DEFAULT;
    }

    /**
     * Set/stores the heading block to ToC list in a string and array format.
     *
     * @param  array $Content   Heading info such as "level","id" and "text".
     * @return void
     */
    protected function setContentsList(array $Content)
    {
        // Stores as an array
        $this->setContentsListAsArray($Content);
        // Stores as string in markdown list format.
        $this->setContentsListAsString($Content);
    }

    /**
     * Sets/stores the heading block info as an array.
     *
     * @param  array $Content
     * @return void
     */
    protected function setContentsListAsArray(array $Content)
    {
        $this->contentsListArray[] = $Content;
    }

    protected $contentsListArray = array();

    /**
     * Sets/stores the heading block info as a list in markdown format.
     *
     * @param  array $Content  Heading info such as "level","id" and "text".
     * @return void
     */
    protected function setContentsListAsString(array $Content)
    {
        $text  = $this->fetchText($Content['text']);
        $id    = $Content['id'];
        $level = (integer) trim($Content['level'], 'h');
        $link  = "[${text}]({$this->options['url']}#${id})";

        if ($this->firstHeadLevel === 0) {
            $this->firstHeadLevel = $level;
        }
        $cutIndent = $this->firstHeadLevel - 1;
        if ($cutIndent > $level) {
            $level = 1;
        } else {
            $level = $level - $cutIndent;
        }

        $indent = str_repeat('  ', $level);

        // Stores in markdown list format as below:
        // - [Header1](#Header1)
        //   - [Header2-1](#Header2-1)
        //     - [Header3](#Header3)
        //   - [Header2-2](#Header2-2)
        // ...
        $this->contentsListString .= "${indent}- ${link}" . PHP_EOL;
    }
    protected $contentsListString = '';
    protected $firstHeadLevel = 0;

    /**
     * Sets the user defined ToC markdown tag.
     *
     * @param  string $tag
     * @return void
     */
    public function setTagToc($tag)
    {
        $tag = trim($tag);
        if (self::escape($tag) === $tag) {
            // Set ToC tag if it's safe
            $this->tag_toc = $tag;
        } else {
            // Do nothing but log
            error_log(
                'Malformed ToC user tag given.'
                . ' At: ' . __FUNCTION__ . '() '
                . ' in Line:' . __LINE__ . ' (Using default ToC tag)'
            );
        }
    }
    protected $tag_toc = '';

    /**
     * Parses markdown string to HTML and also the "[toc]" tag as well.
     * It overrides the parent method: \Parsedown::text().
     *
     * @param  string $text
     * @return string
     */
    public function text($text)
    {
        // Parses the markdown text except the ToC tag. This also searches
        // the list of contents and available to get from "contentsList()"
        // method.
        $html = $this->body($text);

        $tag_origin  = $this->getTagToC();

        if (strpos($text, $tag_origin) === false) {
            return $html;
        }

        $toc_data = $this->contentsList();
        $toc_id   = $this->getIdAttributeToC();
        $needle  = '<p>' . $tag_origin . '</p>';
        $replace = "<div id=\"${toc_id}\">${toc_data}</div>";

        return str_replace($needle, $replace, $html);
    }


    protected $isBlacklistInitialized = false;
    protected $anchorDuplicates = [];

    /**
     * Add blacklisted ids to anchor list
     */
    protected function initBlacklist() {

        if ($this->isBlacklistInitialized) return;

        if (!empty($this->options['blacklist']) && is_array($this->options['blacklist'])) {

            foreach ($this->options['blacklist'] as $v) {
                if (is_string($v)) $this->anchorDuplicates[$v] = 0;
            }
        }

        $this->isBlacklistInitialized = true;
    }

    /**
     * Collect and count anchors in use to prevent duplicated ids. Return string
     * with incremental, numeric suffix. Also init optional blacklist of ids.
     *
     * @param  string $str
     * @return string
     */
    protected function incrementAnchorId($str) {

        // add blacklist to list of used anchors
        if (!$this->isBlacklistInitialized) $this->initBlacklist();

        $this->anchorDuplicates[$str] = !isset($this->anchorDuplicates[$str]) ? 0 : ++$this->anchorDuplicates[$str];

        $newStr = $str;

        if ($count = $this->anchorDuplicates[$str]) {

            $newStr .= "-{$count}";

            // increment until conversion doesn't produce new duplicates anymore
            if (isset($this->anchorDuplicates[$newStr])) {
                $newStr = $this->incrementAnchorId($str);
            }
            else {
                $this->anchorDuplicates[$newStr] = 0;
            }

        }

        return $newStr;
    }

}
